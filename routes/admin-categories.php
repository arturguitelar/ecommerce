<?php

use Hcode\Model\User;
use Hcode\PageAdmin;
use Hcode\Model\Category;
use Hcode\Model\Product;

/** Categories - Admin */
$app->get("/admin/categories", function() {
	User::verifyLogin();

	// busca de categorias
	$search = (isset($_GET['search'])) ? $_GET['search'] : "";
	
	// página atual
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	// filtro da query
	// Lista os categorias do banco com paginação.
	// Leva em consideração se o usuário digitou algo na caixa de busca.
	if ($search != '') {
		$pagination = Category::getPageSearch($search, $page, 10);
	} else {
		$pagination =  Category::getPage($page, 10);
	}

	// todas as páginas
	$pages = [];
	for ($i = 0; $i < $pagination['pages']; $i++) {
		array_push($pages, [
			'href' => '/admin/categories?'.http_build_query([
				'page' => $i + 1,
				'search' => $search
			]),
			'text' => $i + 1
		]);
	}
	
	$page = new PageAdmin();

	$page->setTpl("categories", [
		"categories" => $pagination['data'],
		"search" => $search,
		"pages" => $pages
	]);
});

$app->get("/admin/categories/create", function() {
	User::verifyLogin();

	$page = new PageAdmin();
	
	$page->setTpl("categories-create");
});

$app->post("/admin/categories/create", function() {
	User::verifyLogin();

	$category = new Category();
	
	$category->setData($_POST);
	
	$category->save();
	
	header("Location: /admin/categories");
	exit;
});

$app->get("/admin/categories/:idcategory/delete", function($idcategory) {
	User::verifyLogin();

	$category = new Category();

	$category->get((int) $idcategory);

	$category->delete();

	header("Location: /admin/categories");
	exit;
});

$app->get("/admin/categories/:idcategory", function($idcategory) {
	User::verifyLogin();

	$category = new Category();

	$category->get((int) $idcategory);
	
	$page = new PageAdmin();

	$page->setTpl("categories-update", [
		"category" => $category->getValues()
	]);
});

$app->post("/admin/categories/:idcategory", function($idcategory) {
	User::verifyLogin();

	$category = new Category();

	$category->get((int) $idcategory);

	$category->setData($_POST);

	$category->save();

	header("Location: /admin/categories");
	exit;
});

$app->get("/admin/categories/:idcategory/products", function($idcategory) {
	User::verifyLogin();

	$category = new Category();

	$category->get((int) $idcategory);

	$page = new PageAdmin();

	$page->setTpl("categories-products", [
		"category" => $category->getValues(),
		"productsRelated" => $category->getProducts(),
		"productsNotRelated" => $category->getProducts(false)
	]);
});

// rotas para adicionar e remover os produtos nas categorias
$app->get("/admin/categories/:idcategory/products/:idproduct/add", function($idcategory, $idproduct) {
	User::verifyLogin();

	$category = new Category();

	$category->get((int) $idcategory);

	$product = new Product();

	$product->get((int) $idproduct);

	$category->addProduct($product);

	header("Location: /admin/categories/$idcategory/products");
	exit;
});

$app->get("/admin/categories/:idcategory/products/:idproduct/remove", function($idcategory, $idproduct) {
	User::verifyLogin();

	$category = new Category();

	$category->get((int) $idcategory);

	$product = new Product();

	$product->get((int) $idproduct);

	$category->removeProduct($product);

	header("Location: /admin/categories/$idcategory/products");
	exit;
});
// fim rotas para adicionar e remover os produtos nas categorias
