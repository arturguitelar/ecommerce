<?php 

session_start();

require_once("vendor/autoload.php");

use Slim\Slim;
use Hcode\Page;
use Hcode\Model\User;
use Hcode\PageAdmin;

$app = new Slim();

$app->config('debug', true);

/* home do site */
$app->get('/', function() {
    
	$page = new Page();

	$page->setTpl("index");

});

/* home do admin */
$app->get('/admin', function() {
	// validando a sessão do usuário
	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("index");

});

/* Login */
$app->get('/admin/login', function() {
	// O header e o footer da página de login são diferentes.
	// O padrão precisa ser desabilitado através de parâmetros num array.
	$page = new PageAdmin([
		"header" => false,
		"footer" => false
	]);

	$page->setTpl("login");

});

$app->post('/admin/login', function() {
	User::login($_POST["login"], $_POST["password"]);

	header("Location: /admin");
	exit;
});

/* Logout */
$app->get('/admin/logout', function() {
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
$app->get('/admin/users/:iduser/delete', function($iduser) {
	User::verifyLogin();
});

/* Users - listAll */
$app->get('/admin/users', function() {
	User::verifyLogin();

	// lista os usuários do banco...
	$users =  User::listAll();
	
	$page = new PageAdmin();
	
	// ...então o array de usuários é passado para o template
	$page->setTpl("users", array( "users" => $users ));
});

/* Users - create */
$app->get('/admin/users/create', function() {
	User::verifyLogin();
	
	$page = new PageAdmin();
	
	$page->setTpl("users-create");
});

/* Users - update */
$app->get('/admin/users/:iduser', function($iduser) {
	User::verifyLogin();
	
	$page = new PageAdmin();
	
	$page->setTpl("users-update");
});

/* Users - post create */
$app->post('/admin/users/create', function() {
	User::verifyLogin();

	$user = new User();

	// verificando se é criado como um admin
	$_POST["inadmin"] = isset($_POST["inadmin"]) ? 1 : 0;

	$user->setData($_POST);

	$user->save();

	header("Location: /admin/users");
	exit;
});

/* Users - post update */
$app->post('/admin/users/:iduser', function($iduser) {
	User::verifyLogin();
});


$app->run();
