<?php
use Hcode\Page;
use Hcode\Model\Category;
use Hcode\Model\Product;
use Hcode\Model\Cart;
use Hcode\Model\Address;
use Hcode\Model\User;

/* home do site */
$app->get("/", function() {
	$products = Product::listAll();
	
	$page = new Page();

	$page->setTpl("index", [
		"products" => Product::checkList($products)
	]);

});

/** Categories - Views FrontEnd */
$app->get("/category/:idcategory", function($idcategory) {
	// verificando se foi passada alguma página
	$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
	
	$category = new Category();

	$category->get((int) $idcategory);

	$pagination = $category->getProductsPage($page);

	// percorre o total de páginas para enviar para a view
	// cria um array com o link url e nr da pagina
	$pages = [];
	for ($i = 1; $i <= $pagination["pages"]; $i++) {
		array_push($pages, [
			"link" => "/category/".$category->getidcategory()."?page=".$i,
			"page" => $i
		]);
	}

	$page = new Page();

	$page->setTpl("category", [
		"category" => $category->getValues(),
		"products" => $pagination["data"],
		"pages" => $pages
	]);
});

/** Página de descrição do produto */
$app->get("/product/:desurl", function($desurl) {
	$product = new Product();

	$product->getFromURL($desurl);

	$page = new Page();

	$page->setTpl("product-detail", array(
		"product" => $product->getValues(),
		"categories" => $product->getCategories()
	));
});

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

	header("Location: /order");
	exit;
});

/** Login Usuário Comum */
$app->get("/login", function() {
	
	$page = new Page();

	// pega os valores de preenchimento do formulário caso existam
	$registerValues = (isset($_SESSION["registerValues"])) ? 
		$_SESSION["registerValues"] : [ "name" => "", "email" => "", "phone" => "" ];

	$page->setTpl("login", [
		"error" => User::getMsgError(),
		"errorRegister" => User::getErrorRegister(),
		"registerValues" => $registerValues
	]);
});

$app->post("/login", function() {

	try {
		User::login($_POST["login"], $_POST["password"]);
	} catch (Exception $e) {
		User::setMsgError($e->getMessage());
	}

	header("Location: /checkout");
	exit;
});

$app->get("/logout", function() {
	
	User::logout();

	header("Location: /login");
	exit;
});

$app->post("/register", function() {

	// registra os valores dos inputs para que não sejam perdidos ao chamar Location na página
	$_SESSION["registerValues"] = $_POST;

	// validações
	if (!isset($_POST["name"]) || $_POST["name"] == "") {
		User::setErrorRegister("Preencha o seu nome.");

		header("Location: /login");
		exit;
	}

	if (!isset($_POST["email"]) || $_POST["email"] == "") {
		User::setErrorRegister("Preencha o seu email.");

		header("Location: /login");
		exit;
	}

	if (!isset($_POST["password"]) || $_POST["password"] == "") {
		User::setErrorRegister("Preencha a sua senha.");

		header("Location: /login");
		exit;
	}

	// tratando o caso de dois usuários com o mesmo login
	if (User::checkLoginExist($_POST["email"]) === true) {
		User::setErrorRegister("Este email já está sendo utilizado por outro usuário.");

		header("Location: /login");
		exit;
	}

	$user = new User();

	$user->setData([
		"inadmin" => 0,
		"deslogin" => $_POST["email"],
		"desperson" => $_POST["name"],
		"desemail" => $_POST["email"],
		"despassword" => $_POST["password"],
		"nrphone" => $_POST["phone"]
	]);

	$user->save();

	User::login($_POST["email"], $_POST["password"]);

	header("Location: /checkout");
	exit;
});

/** ESQUECEU A SENHA */
/* Site - forgot */
$app->get("/forgot", function() {

	$page = new Page();

	$page->setTpl("forgot");
});

$app->post("/forgot", function() {
	User::getForgot($_POST["email"], false);

	header("Location: /forgot/sent");
	exit;
});

$app->get("/forgot/sent", function() {
	$page = new Page();

	$page->setTpl("forgot-sent");
});

$app->get("/forgot/reset", function() {

	$user = User::validForgotDecrypt($_GET["code"]);

	$page = new Page();

	$page->setTpl("forgot-reset", array(
		"name" => $user["desperson"],
		"code" => $_GET["code"]
	));
});

$app->post("/forgot/reset", function() {

	// dados do forgot
	$forgot = User::validForgotDecrypt($_POST["code"]);
	User::setForgotUsed($forgot["idrecovery"]);

	// pegando os dados do usuário
	$user = new User();
	$user->get((int) $forgot["iduser"]);

	// salvando uma nova hash
	// http://php.net/manual/pt_BR/function.password-hash.php
	$password = password_hash($_POST["password"], PASSWORD_DEFAULT, [ "cost" => 12 ]);
	$user->setPassword($password);

	$page = new Page();

	$page->setTpl("forgot-reset-sucess");
});

/** User Profile */
$app->get("/profile", function() {

	User::verifyLogin(false);

	$user = User::getFromSession();

	$page = new Page();

	$page->setTpl("profile", [
		"user" => $user->getValues(),
		"profileMsg" => User::getMsgSuccess(),
		"profileError" => User::getMsgError()
	]);
});

$app->post("/profile", function() {
	User::verifyLogin(false);

	// tratando os campos dos formulários
	if (!isset($_POST["desperson"]) || $_POST["desperson"] === "") {
		User::setError("Preencha o seu nome.");

		header("Location: /profile");
		exit;
	}

	if (!isset($_POST["desemail"]) || $_POST["desemail"] === "") {
		User::setError("Preencha o seu email.");

		header("Location: /profile");
		exit;
	}

	$user = User::getFromSession();

	// tratando o caso de dois usuários com o mesmo login caso o usuário tenha modificado o email
	if ($_POST["desemail"] !== $user->getdesemail()) {
		if (User::checkLoginExist($_POST["desemail"]) === true) {
			User::setError("Este email já está sendo utilizado por outro usuário.");
	
			header("Location: /profile");
			exit;
		}
	}

	// garante que o usuário não mude o inadmin e a senha indevidamente
	$_POST['inadmin'] = $user->getinadmin();
	$_POST['password'] = $user->getdespassword();

	// o login de usuário é o email dele, logo...
	$_POST['deslogin'] = $_POST['desemail'];

	$user->setData($_POST);

	$user->update();

	User::setMsgSuccess("Dados alterados com sucesso!");

	header("Location: /profile");
	exit;
});