<?php
if (!defined('DATALIFEENGINE'))
	die("Hacking attempt!");

$js_array .= "\n<script type=\"text/javascript\" src=\"//ulogin.ru/js/ulogin.js\"></script>";
$js_array .= "\n<script type=\"text/javascript\" src=\"{$config['http_home_url']}templates/{$config['skin']}/ulogin/js/ulogin.js\"></script>";
$js_array .= "\n<link media=\"screen\" href=\"//ulogin.ru/css/providers.css\" type=\"text/css\" rel=\"stylesheet\" />";
$js_array .= "\n<link media=\"screen\" href=\"{$config['http_home_url']}templates/{$config['skin']}/ulogin/style/ulogin.css\" type=\"text/css\" rel=\"stylesheet\" />";


$ulogin_message = array();

if (isset($_SESSION['ulogin_title'])) {
	$ulogin_message['title'] = $_SESSION['ulogin_title'];
	unset($_SESSION['ulogin_title']);
}
if (isset($_SESSION['ulogin_message'])) {
	$ulogin_message['message'] = $_SESSION['ulogin_message'];
	unset($_SESSION['ulogin_message']);
}


function get_ulogin_message($title='', $message='') {
	global $config;

	$tpl_config  = array(
		'title' => empty($title) ? '' : $title,
		'message' => empty($message) ? '' : $message,
		'message_type' => '',

		'template' => !empty($template) ? $template : 'ulogin/ulogin_message.tpl',
		'cachePrefix' => !empty($cachePrefix) ? $cachePrefix : 'news',
		'cacheSuffix' => !empty($cacheSuffix) ? true : false
	);

	$cacheName = md5(implode('_', $tpl_config));

	$ulogin_message  = false;
	$ulogin_message  = dle_cache($tpl_config['cachePrefix'], $cacheName . $config['skin'], $tpl_config['cacheSuffix']);

	if (!$ulogin_message) {
		if (file_exists(TEMPLATE_DIR . '/' . $tpl_config['template'])) {

			$tpl_2      = new dle_template();
			$tpl_2->dir = TEMPLATE_DIR;

			$tpl_2->load_template($tpl_config['template']);

			$tpl_2->set('{title}', $tpl_config['title']);
			$tpl_2->set('{message}', $tpl_config['message']);
			$tpl_2->set('{message_type}', $tpl_config['message_type']);
			$tpl_2->set('{display}', ($tpl_config['title'] || $tpl_config['message']) ? 'block' : 'none');

			$tpl_2->compile('ulogin_message');
			$ulogin_message = $tpl_2->result['ulogin_message'];
			create_cache($tpl_config['cachePrefix'], $ulogin_message, $cacheName . $config['skin'], $tpl_config['cacheSuffix']);
			$tpl_2->clear();

		} else {
			$ulogin_message = '<b style="color:red">Отсутствует файл шаблона: ' . $config['skin'] . '/' . $tpl_config['template'] . '</b>';
		}
	}
	return $ulogin_message;
}

$tpl->set('{ulogin_message}', get_ulogin_message($ulogin_message['title'], $ulogin_message['message']));
