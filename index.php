<?php 

session_start();

require_once("vendor/autoload.php");

use Slim\Slim;
use Hcode\Page;
use Hcode\Model\User;
use Hcode\PageAdmin;

$app = new Slim();

$app->config('debug', true);

// home do site
$app->get('/', function() {
    
	$page = new Page();

	$page->setTpl("index");

});

// home do admin
$app->get('/admin', function() {
	// validando a sessão do usuário
	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("index");

});

// página de Login
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

// logout
$app->get('/admin/logout', function() {
	User::logout();

	header("Location: /admin/login");
	exit;
});

$app->run();
