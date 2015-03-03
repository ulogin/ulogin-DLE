<?php
if (!defined('DATALIFEENGINE'))
	die("Hacking attempt!");

if (!defined('ULOGIN_DIR')) {
	define ('ULOGIN_DIR', ENGINE_DIR . '/modules/ulogin');
}
require_once ULOGIN_DIR . '/ulogin.class.php';


$uLogin = new uLogin();

if ($_GET['mode'] == "delete") {
	$uLogin->delete();
} else {
	$uLogin->login();
}