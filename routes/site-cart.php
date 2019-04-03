<?php
use Hcode\Model\Cart;
use Hcode\Model\Address;
use Hcode\Model\Product;
use Hcode\Page;
use Hcode\Model\User;
use Hcode\Model\Order;
use Hcode\Model\OrderStatus;

/** Página do carrinho */
$app->get("/cart", function() {

	$cart = Cart::getFromSession();
	
	$page = new Page();

	$page->setTpl("cart", [
		"cart" => $cart->getValues(),
		"products" => $cart->getProducts(),
		"error" => Cart::getMsgError()
	]);
});

$app->get("/cart/:idproduct/add", function($idproduct) {
	
	$product = new Product();

	$product->get((int) $idproduct);

	// recupera o carrinho da sessão
	$cart = Cart::getFromSession();

	// resolve a questão do select de quantidade
	$qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1;

	// adicionando produto no carrinho
	for ($i = 0; $i < $qtd; $i++)
		$cart->addProduct($product);

	header("Location: /cart");
	exit;
});

$app->get("/cart/:idproduct/minus", function($idproduct) {
	
	$product = new Product();

	$product->get((int) $idproduct);

	// recupera o carrinho da sessão
	$cart = Cart::getFromSession();

	// removendo UM produto no carrinho
	$cart->removeProduct($product);

	header("Location: /cart");
	exit;
});

$app->get("/cart/:idproduct/remove", function($idproduct) {
	
	$product = new Product();

	$product->get((int) $idproduct);

	// recupera o carrinho da sessão
	$cart = Cart::getFromSession();

	// adicionando TODOS os produtos do mesmo tipo no carrinho
	$cart->removeProduct($product, true);

	header("Location: /cart");
	exit;
});

/** Rotas para o frete */
$app->post("/cart/freight", function() {

	$cart =  Cart::getFromSession();

	$cart->setFreight($_POST["zipcode"]);

	header("Location: /cart");
	exit;
});

/** Checkout */
$app->get("/checkout", function() {

	// false = não é admin
	User::verifyLogin(false);

	$address = new Address();
	$cart = Cart::getFromSession();

	if (!isset($_GET['zipcode'])) {
		if ($cart->getdeszipcode() && $cart->getdeszipcode() != '')
			$_GET['zipcode'] = $cart->getdeszipcode();
	}
	
	// caso não tenha cep no carrinho é preciso zerar os campos
	if (!$cart->getdeszipcode() || $cart->getdeszipcode() == '') {
		$address->setdesaddress('');
		$address->setdescomplement('');
		$address->setdesdistrict('');
		$address->setdescity('');
		$address->setdesstate('');
		$address->setdescountry('');
		$address->setdeszipcode('');

		Address::setMsgError("É necessário ter um CEP válido.");
	}

	if (isset($_GET['zipcode'])) {
		// carrega o endereço utilizando a consulta ao cep
		$address->loadFromCEP($_GET['zipcode']);
		
		// coloca o endereço novo no carrinho
		$cart->setdeszipcode($_GET['zipcode']);
		$cart->save();

		// força a calcular o total porque pode ter mudado o frete
		$cart->getCalculateTotals();
	}

	$page = new Page();

	$page->setTpl("checkout", [
		"cart" => $cart->getValues(),
		"address" => $address->getValues(),
		"products" => $cart->getProducts(),
		"error" => Address::getMsgError()
	]);
});

$app->post("/checkout", function() {
	User::verifyLogin(false);

	// verificações do formulário
	if (!isset($_POST['zipcode']) || $_POST['zipcode'] === '') {
		Address::setMsgError("Informe o CEP.");

		header('Location: /checkout');
		exit;
	}

	if (!isset($_POST['desaddress']) || $_POST['desaddress'] === '') {
		Address::setMsgError("Informe o endereço.");

		header('Location: /checkout');
		exit;
	}

	if (!isset($_POST['desdistrict']) || $_POST['desdistrict'] === '') {
		Address::setMsgError("Informe o bairro.");

		header('Location: /checkout');
		exit;
	}

	if (!isset($_POST['descity']) || $_POST['descity'] === '') {
		Address::setMsgError("Informe a cidade.");

		header('Location: /checkout');
		exit;
	}

	if (!isset($_POST['desstate']) || $_POST['desstate'] === '') {
		Address::setMsgError("Informe o estado.");

		header('Location: /checkout');
		exit;
	}

	if (!isset($_POST['descountry']) || $_POST['descountry'] === '') {
		Address::setMsgError("Informe o país.");

		header('Location: /checkout');
		exit;
	}
	// fim verificação

	$user = User::getFromSession();

	$address = new Address();

	$_POST['deszipcode'] = $_POST['zipcode'];
	$_POST['idperson'] = $user->getidperson();

	$address->setData($_POST);

	$address->save();

	$cart = Cart::getFromSession();

	// valor total de produtos do carrinho
	// $totals = $cart->getCalculateTotals();
	$totals = $cart->getValues();

	$order = new Order();

	$order->setData([
		'idcart' => $cart->getidcart(),
		'idaddress' => $address->getidaddress(),
		'iduser' => $user->getiduser(),
		'idstatus' => OrderStatus::EM_ABERTO,
		'vltotal' => $totals['vltotal']
		// 'vltotal' => $totals['vlprice'] + $cart->getvlfreight()
		]);

	$order->save();

	header("Location: /order/".$order->getidorder());
	exit;
});


// pagamento
$app->get("/order/:idorder", function($idorder) {
	
	User::verifyLogin(false);

	$order = new Order();

	$order->get((int) $idorder);

	$page = new Page();

	$page->setTpl("payment", [
		'order' => $order->getValues()
	]);
});

// boleto
$app->get("/boleto/:idorder", function($idorder) {

	User::verifyLogin(false);

	$order = new Order();

	$order->get((int) $idorder);

	// variáveis de configuração do boleto Itau
	// DADOS DO BOLETO PARA O SEU CLIENTE
	$dias_de_prazo_para_pagamento = 10;
	$taxa_boleto = 5.00;
	$data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/04/2006"; 
	$valor_cobrado = $order->getvltotal(); // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
	$valor_cobrado = str_replace(",", ".",$valor_cobrado);
	$valor_boleto=number_format($valor_cobrado+$taxa_boleto, 2, ',', '');

	$dadosboleto["nosso_numero"] = $order->getidorder();  // Nosso numero - REGRA: Máximo de 8 caracteres!
	$dadosboleto["numero_documento"] = $order->getidorder();	// Num do pedido ou nosso numero
	$dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
	$dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
	$dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
	$dadosboleto["valor_boleto"] = $valor_boleto; 	// Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula

	// DADOS DO SEU CLIENTE
	$dadosboleto["sacado"] = $order->getdesperson();
	$dadosboleto["endereco1"] = $order->desaddress() . " " . $order->getdesdistrict();
	$dadosboleto["endereco2"] = $order->getdescity() . " - "  . $order->getdesstate() . " / " . $order->getdescountry() . "  - CEP: " . $order->getdeszipcode();

	// INFORMACOES PARA O CLIENTE
	$dadosboleto["demonstrativo1"] = "Pagamento de Compra na Loja Hcode E-commerce";
	$dadosboleto["demonstrativo2"] = "Taxa bancária - R$ 0,00";
	$dadosboleto["demonstrativo3"] = "";
	$dadosboleto["instrucoes1"] = "- Sr. Caixa, cobrar multa de 2% após o vencimento";
	$dadosboleto["instrucoes2"] = "- Receber até 10 dias após o vencimento";
	$dadosboleto["instrucoes3"] = "- Em caso de dúvidas entre em contato conosco: suporte@hcode.com.br";
	$dadosboleto["instrucoes4"] = "&nbsp; Emitido pelo sistema Projeto Loja Hcode E-commerce - www.hcode.com.br";

	// DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
	$dadosboleto["quantidade"] = "";
	$dadosboleto["valor_unitario"] = "";
	$dadosboleto["aceite"] = "";		
	$dadosboleto["especie"] = "R$";
	$dadosboleto["especie_doc"] = "";


	// ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO SEU BOLETO --------------- //


	// DADOS DA SUA CONTA - ITAÚ
	$dadosboleto["agencia"] = "1690"; // Num da agencia, sem digito
	$dadosboleto["conta"] = "48781";	// Num da conta, sem digito
	$dadosboleto["conta_dv"] = "2"; 	// Digito do Num da conta

	// DADOS PERSONALIZADOS - ITAÚ
	$dadosboleto["carteira"] = "175";  // Código da Carteira: pode ser 175, 174, 104, 109, 178, ou 157

	// SEUS DADOS
	$dadosboleto["identificacao"] = "Hcode Treinamentos";
	$dadosboleto["cpf_cnpj"] = "24.700.731/0001-08";
	$dadosboleto["endereco"] = "Rua Ademar Saraiva Leão, 234 - Alvarenga, 09853-120";
	$dadosboleto["cidade_uf"] = "São Bernardo do Campo - SP";
	$dadosboleto["cedente"] = "HCODE TREINAMENTOS LTDA - ME";

	// NÃO ALTERAR!
	$path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "res" . DIRECTORY_SEPARATOR . "boletophp" . DIRECTORY_SEPARATOR . "include" . DIRECTORY_SEPARATOR;
	require_once($path."funcoes_itau.php"); 
	require_once($path."layout_itau.php");

});