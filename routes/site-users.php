<?php
use Hcode\Page;
use Hcode\Model\User;

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

