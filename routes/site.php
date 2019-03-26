<?php
use Hcode\Page;
use Hcode\Model\Category;
use Hcode\Model\Product;
use Hcode\Model\Cart;

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

	$page->setTpl("cart");
});