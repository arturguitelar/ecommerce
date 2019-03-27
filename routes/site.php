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

	$cart = Cart::getFromSession();

	$address = new Address();

	$page = new Page();

	$page->setTpl("checkout", [
		"cart" => $cart->getValues(),
		"address" => $address->getValues()
	]);
});

/** Login Usuário Comum */
$app->get("/login", function() {
	
	$page = new Page();

	$page->setTpl("login", [
		"error" => User::getMsgError()
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