<?php
if (!defined('DATALIFEENGINE'))
	die("Hacking attempt!");

$backurl = preg_replace('/\.php([$|\?])/i', '._php$1', $_SERVER['REQUEST_URI']);
$backurl = urlencode(urlencode($backurl));

$http_str = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") ? 'https' : 'http';
$redirect_uri = urlencode($http_str . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] . '?do=ulogin&backurl=' . $backurl);

require_once ENGINE_DIR . '/modules/ulogin/ulogin_model.class.php';
$ulogin_model = new UloginModel();

$ulogin_config = $ulogin_model->getUloginConfig();

$tpl_config  = array(
	'uloginid' => !empty($uloginid) ? $uloginid : $ulogin_config['uloginid'],
	'redirect_uri' => $redirect_uri,
	'callback' => 'uloginCallback',
	'ulogin_online' => !empty($ulogin_online) ? $ulogin_online : false,

	'template' => !empty($template) ? $template : 'ulogin/ulogin_form.tpl',
	'cachePrefix' => !empty($cachePrefix) ? $cachePrefix : 'news',
	'cacheSuffix' => !empty($cacheSuffix) ? true : false
);

$cacheName = md5(implode('_', $tpl_config));

$ulogin_form  = false;
$ulogin_form  = dle_cache($tpl_config['cachePrefix'], $cacheName . $config['skin'], $tpl_config['cacheSuffix']);

if (!$ulogin_form) {
	if (!$tpl_config['ulogin_online'] && $is_logged) {
		$ulogin_form = '';

	} else if (file_exists(TEMPLATE_DIR . '/' . $tpl_config['template'])) {

		$tpl_2      = new dle_template();
		$tpl_2->dir = TEMPLATE_DIR;

		$tpl_2->load_template($tpl_config['template']);

		if(!empty($tpl_config['uloginid'])) {
			$tpl_2->set('[uloginid]', "");
			$tpl_2->set('[/uloginid]', "");
			$tpl_2->set_block ("'\\[not-uloginid\\](.*?)\\[/not-uloginid\\]'si", "");
			$tpl_2->set('{uloginid}', $tpl_config['uloginid']);
		} else {
			$tpl_2->set_block ("'\\[uloginid\\](.*?)\\[/uloginid\\]'si", "");
			$tpl_2->set('[not-uloginid]', "");
			$tpl_2->set('[/not-uloginid]', "");
		}

		$tpl_2->set('{redirect_uri}', $tpl_config['redirect_uri']);
		$tpl_2->set('{callback}', $tpl_config['callback']);

		$tpl_2->compile('ulogin_form');
		$ulogin_form = $tpl_2->result['ulogin_form'];
		create_cache($tpl_config['cachePrefix'], $ulogin_form, $cacheName . $config['skin'], $tpl_config['cacheSuffix']);
		$tpl_2->clear();

	} else {
		$ulogin_form = '<b style="color:red">Отсутствует файл шаблона: ' . $config['skin'] . '/' . $tpl_config['template'] . '</b>';
	}
}

echo $ulogin_form;