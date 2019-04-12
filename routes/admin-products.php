<?php

use Hcode\Model\User;
use Hcode\PageAdmin;
use Hcode\Model\Product;

/** Products - Admin */
$app->get("/admin/products", function() {
	User::verifyLogin();

	// busca de produtos
	$search = (isset($_GET['search'])) ? $_GET['search'] : "";
	
	// página atual
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	// filtro da query
	// Lista os produtos do banco com paginação.
	// Leva em consideração se o usuário digitou algo na caixa de busca.
	if ($search != '') {
		$pagination = Product::getPageSearch($search, $page, 10);
	} else {
		$pagination =  Product::getPage($page, 10);
	}

	// todas as páginas
	$pages = [];
	for ($i = 0; $i < $pagination['pages']; $i++) {
		array_push($pages, [
			'href' => '/admin/products?'.http_build_query([
				'page' => $i + 1,
				'search' => $search
			]),
			'text' => $i + 1
		]);
	}

	$page = new PageAdmin();

	$page->setTpl("products", [
		"products" => $pagination['data'],
		"search" => $search,
		"pages" => $pages
	]);
});


$app->get("/admin/products/create", function() {
    User::verifyLogin();

    $page = new PageAdmin();

    $page->setTpl("products-create");
});

$app->post("/admin/products/create", function() {
    User::verifyLogin();

    $product = new Product();

    $product->setData($_POST);

    $product->save();

    header("Location: /admin/products");
    exit;
});

$app->get("/admin/products/:idproduct", function($idproduct) {
    User::verifyLogin();

    $product = new Product();

    $product->get((int) $idproduct);

    $page = new PageAdmin();

    $page->setTpl("products-update", [
        "product" => $product->getValues()
    ]);
});

$app->post("/admin/products/:idproduct", function($idproduct) {
    User::verifyLogin();

    $product = new Product();

    $product->get((int) $idproduct);

    $product->setData($_POST);

    $product->save();

    // upload do arquivo
    $product->setPhoto($_FILES["file"]);

    header("Location: /admin/products");
    exit;
});

$app->get("/admin/products/:idproduct/delete", function($idproduct) {
    User::verifyLogin();

    $product = new Product();

    $product->get((int) $idproduct);

    $product->delete();
    
    header("Location: /admin/products");
    exit;
});
