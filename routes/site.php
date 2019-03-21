<?php
use Hcode\Page;
use Hcode\Model\Category;
use Hcode\Model\Product;

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
	$category = new Category();

	$category->get((int) $idcategory);

	$page = new Page();

	$page->setTpl("category", [
		"category" => $category->getValues(),
		"products" => []
	]);
});
