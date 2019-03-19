<?php
use Hcode\Page;

/* home do site */
$app->get("/", function() {
    
	$page = new Page();

	$page->setTpl("index");

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
