<?php
use Hcode\Model\User;
use Hcode\PageAdmin;

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