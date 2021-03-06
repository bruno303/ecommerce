<?php 
session_start();
require_once("vendor/autoload.php");

use \Slim\Slim;
use \Hcode\Page;
use \Hcode\PageAdmin;
use \Hcode\Model\User;

$app = new Slim([
	'settings' => [
		'displayErrorDetails' => true
	]
]);

$app->config('debug', true);

$app->get('/', function()
{
	$page = new Page();
	$page->setTpl("index");
});

$app->get('/admin/', function()
{
	User::verifyLogin();
	$page = new PageAdmin();
	$page->setTpl("index");
});

$app->get('/admin/login/', function()
{
	$page = new PageAdmin(
		["header"=>false,
		 "footer"=>false]
	);
	$page->setTpl("login");
});

$app->post('/admin/login/', function()
{
	User::login($_POST['login'], $_POST['password']);
	header('location: /admin/');
	exit;
});

$app->get('/admin/logout/', function()
{
	User::logout();
	header("location: /admin/login/");
	exit;
});

$app->get('/admin/users/', function()
{
	$page = new PageAdmin();
	$users = User::listAll();
	$page->setTpl('users', array(
		"users"=>$users
	));
});

$app->get('/admin/users/create/', function()
{
	User::verifyLogin();
	$page = new PageAdmin();
	$page->setTpl('users-create');
});

$app->get('/admin/users/:iduser/delete', function($iduser)
{
	User::verifyLogin();
	$user = new User();
	$user->get((int)$iduser);
	$user->delete($iduser);
	header("location: /admin/users/");
	exit;
});

$app->get('/admin/users/:iduser/', function($iduser)
{
	User::verifyLogin();
	$user = new User();
	$user->get((int)$iduser);
	$page = new PageAdmin();
	$page->setTpl('users-update', array("user"=>$user->getValues()));
});

$app->post('/admin/users/create', function()
{
	User::verifyLogin();
	$user = new User();
	$_POST["inadmin"] = isset($_POST["inadmin"]) ? 1 : 0;
	$user->setData($_POST);
	$user->save();
	header("location: /admin/users/");
	exit;
});

$app->post('/admin/users/:iduser', function($iduser)
{
	User::verifyLogin();
	$user = new User();
	$user->get((int)$iduser);
	$_POST["inadmin"] = isset($_POST["inadmin"]) ? 1 : 0;
	$user->setData($_POST);
	$user->update();
	header("location: /admin/users/");
	exit;
});

$app->get('/admin/forgot/', function()
{
	$page = new PageAdmin(
		["header"=>false,
		 "footer"=>false]
	);
	$page->setTpl("forgot");
});

$app->get('/admin/forgot/', function()
{
	$page = new PageAdmin(
		["header"=>false,
		 "footer"=>false]
	);
	$page->setTpl("forgot");
});

$app->post('/admin/forgot/', function()
{
	$user = User::getForgot($_POST["email"]);
	header("location: /admin/forgot/sent/");
	exit;
});

$app->get('/admin/forgot/sent/', function()
{
	$page = new PageAdmin(
		["header"=>false,
		 "footer"=>false]
	);
	$page->setTpl("forgot-sent");
});

$app->get('/admin/forgot/reset', function()
{
	$user = User::validForgotDecrypt($_GET['code']);
	$page = new PageAdmin(
		["header"=>false,
		 "footer"=>false]
	);
	$page->setTpl("forgot-reset", array(
		"name"=>$user["desperson"],
		"code"=>$_GET["code"]
	));
});

$app->post('/admin/forgot/reset', function()
{
	$forgot = User::validForgotDecrypt($_POST['code']);
	User::setForgotUsed($forgot["idrecovery"]);
	$user = new User();
	$user->get((int)$forgot["iduser"]);
	$user->setPassword($_POST["password"]);

	$page = new PageAdmin(
		["header"=>false,
		 "footer"=>false]
	);
	$page->setTpl("forgot-reset-success");
});

$app->run();

 ?>