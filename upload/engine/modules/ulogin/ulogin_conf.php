<?php
if (!defined('ULOGIN_DIR')) {
	define ('ULOGIN_DIR', ENGINE_DIR . '/modules/ulogin');
}

global $config;
$ulogin_lang = array();

require_once (ULOGIN_DIR . '/ulogin_adm.lng');

if ( $config['charset'] == "windows-1251" ) {
	foreach($ulogin_lang as $key => $val){
		$count=0;
		foreach($ulogin_lang[$key] as $key1 => $val1){
			$count++;
			$ulogin_lang[$key][$key1] = iconv('UTF-8','windows-1251//IGNORE',$val1);
		}
		if ($count==0)$ulogin_lang[$key] = iconv('UTF-8','windows-1251//IGNORE',$val);
	}
}

$ulogin_opt_array = array (
	'name' => $ulogin_lang['opt_ulogin'],
	'url' => "$PHP_SELF?mod=ulogin",
	'descr' => $ulogin_lang['opt_uloginc'],
	'image' => "logo_ulogin.png",
	'access' => "admin",
	'mod' => 'ulogin',
);

$ulogin_admin_sections_array = array(
	'name' => 'ulogin',
	'title' => $ulogin_lang['opt_ulogin'],
	'descr' => $ulogin_lang['opt_uloginc'],
	'icon' => "logo_ulogin.png",
	'allow_groups' => "1",
);

require_once ULOGIN_DIR . '/ulogin_model.class.php';
$ulogin_model = new UloginModel();

$ulogin_model->checkUloginTable();
$ulogin_model->checkUpassColumn();
$ulogin_model->setUloginAdminSection($ulogin_admin_sections_array);



