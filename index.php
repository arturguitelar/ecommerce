<?php 

session_start();

require_once("vendor/autoload.php");

use Slim\Slim;
use Hcode\Page;
use Hcode\PageAdmin;
use Hcode\Model\User;
use Hcode\Model\Category;

$app = new Slim();

$app->config("debug", true);

/* home do site */
$app->get("/", function() {
    
	$page = new Page();

	$page->setTpl("index");

});

/* home do admin */
$app->get("/admin", function() {
	// validando a sessão do usuário
	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("index");

});

/* Login */
$app->get("/admin/login", function() {
	// O header e o footer da página de login são diferentes.
	// O padrão precisa ser desabilitado através de parâmetros num array.
	$page = new PageAdmin([
		"header" => false,
		"footer" => false
	]);

	$page->setTpl("login");

});

$app->post("/admin/login", function() {
	User::login($_POST["login"], $_POST["password"]);

	header("Location: /admin");
	exit;
});

/* Logout */
$app->get("/admin/logout", function() {
	User::logout();

	header("Location: /admin/login");
	exit;
});

/* Users - delete */
/**  
 * Nota 1: O Slim não "entende" o método delete de forma tradicional,
 * é preciso enviar via post com um campo adicional no formulário
 * chamado _method escrito "delete".
 * 
 * Nota 2: É preciso colocar essa rota de delete antes da rota que
 * chama a url /admin/users/:iduser/ para que o Slim entenda
 * que não é a mesma rota. Pra deixar mais organizado eu coloquei aqui em cima.
*/
$app->get("/admin/users/:iduser/delete", function($iduser) {
	User::verifyLogin();

	$user = new User();

	// garantindo que o id passado será um inteiro
	$user->get((int) $iduser);

	$user->delete();

	header("Location: /admin/users");
	exit;
});

/* Users - listAll */
$app->get("/admin/users", function() {
	User::verifyLogin();

	// lista os usuários do banco...
	$users =  User::listAll();
	
	$page = new PageAdmin();
	
	// ...então o array de usuários é passado para o template
	$page->setTpl("users", array( "users" => $users ));
});

/* Users - create */
$app->get("/admin/users/create", function() {
	User::verifyLogin();
	
	$page = new PageAdmin();
	
	$page->setTpl("users-create");
});

/* Users - update */
$app->get("/admin/users/:iduser", function($iduser) {
	User::verifyLogin();

	$user = new User();

	// certificando-se que o id do usuário será um número inteiro
	$user->get((int) $iduser);
	
	$page = new PageAdmin();
	
	$page->setTpl("users-update", array(
		"user" => $user->getValues()
	));
});

/* Users - post create - salvando de fato */
$app->post("/admin/users/create", function() {
	User::verifyLogin();

	$user = new User();

	// verificando se é criado como um admin
	$_POST["inadmin"] = isset($_POST["inadmin"]) ? 1 : 0;

	$user->setData($_POST);

	$user->save();

	header("Location: /admin/users");
	exit;
});

/* Users - post update - salvando a edição */
$app->post("/admin/users/:iduser", function($iduser) {
	User::verifyLogin();

	$user = new User();

	$user->get((int) $iduser);

	$_POST["inadmin"] = isset($_POST["inadmin"]) ? 1 : 0;

	$user->setData($_POST);

	$user->update();

	header("Location: /admin/users");
	exit;
});

/* Admin - forgot */
$app->get("/admin/forgot", function() {
	// tirando o header e footer padrão
	$page = new PageAdmin([
		"header" => false,
		"footer" => false
	]);

	$page->setTpl("forgot");
});

$app->post("/admin/forgot", function() {
	$user = User::getForgot($_POST["email"]);

	header("Location: /admin/forgot/sent");
	exit;
});

$app->get("/admin/forgot/sent", function() {
	$page = new PageAdmin([
		"header" => false,
		"footer" => false
	]);

	$page->setTpl("forgot-sent");
});

$app->get("/admin/forgot/reset", function() {

	$user = User::validForgotDecrypt($_GET["code"]);

	$page = new PageAdmin([
		"header" => false,
		"footer" => false
	]);

	$page->setTpl("forgot-reset", array(
		"name" => $user["desperson"],
		"code" => $_GET["code"]
	));
});

$app->post("/admin/forgot/reset", function() {

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

	$page = new PageAdmin([
		"header" => false,
		"footer" => false
	]);

	$page->setTpl("forgot-reset-sucess");
});

/** Categories */
$app->get("/admin/categories", function() {
	User::verifyLogin();

	$categories = Category::listAll();
	
	$page = new PageAdmin();

	$page->setTpl("categories", [
		"categories" => $categories
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

$app->run();
