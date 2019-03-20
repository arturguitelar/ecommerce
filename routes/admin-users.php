<?php
use Hcode\Model\User;
use Hcode\PageAdmin;

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