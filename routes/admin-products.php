<?php

use Hcode\Model\User;
use Hcode\PageAdmin;
use Hcode\Model\Product;

/** Products - Admin */
$app->get("/admin/products", function() {
	User::verifyLogin();

	$products = Product::listAll();

	$page = new PageAdmin();

	$page->setTpl("products", [
		"products" => $products
	]);
});
