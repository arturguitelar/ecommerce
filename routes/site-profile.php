<?php
use Hcode\Model\User;
use Hcode\Page;

/** User Profile */
$app->get("/profile", function() {

	User::verifyLogin(false);

	$user = User::getFromSession();

	$page = new Page();

	$page->setTpl("profile", [
		"user" => $user->getValues(),
		"profileMsg" => User::getMsgSuccess(),
		"profileError" => User::getMsgError()
	]);
});

$app->post("/profile", function() {
	User::verifyLogin(false);

	// tratando os campos dos formulários
	if (!isset($_POST["desperson"]) || $_POST["desperson"] === "") {
		User::setError("Preencha o seu nome.");

		header("Location: /profile");
		exit;
	}

	if (!isset($_POST["desemail"]) || $_POST["desemail"] === "") {
		User::setError("Preencha o seu email.");

		header("Location: /profile");
		exit;
	}

	$user = User::getFromSession();

	// tratando o caso de dois usuários com o mesmo login caso o usuário tenha modificado o email
	if ($_POST["desemail"] !== $user->getdesemail()) {
		if (User::checkLoginExist($_POST["desemail"]) === true) {
			User::setError("Este email já está sendo utilizado por outro usuário.");
	
			header("Location: /profile");
			exit;
		}
	}

	// garante que o usuário não mude o inadmin e a senha indevidamente
	$_POST['inadmin'] = $user->getinadmin();
	$_POST['password'] = $user->getdespassword();

	// o login de usuário é o email dele, logo...
	$_POST['deslogin'] = $_POST['desemail'];

	$user->setData($_POST);

	$user->update();

	User::setMsgSuccess("Dados alterados com sucesso!");

	header("Location: /profile");
	exit;
});