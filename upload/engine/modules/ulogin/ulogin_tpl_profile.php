<?php
if (!defined('DATALIFEENGINE'))
	die("Hacking attempt!");

$my_profile = isset($my_profile) ? $my_profile : true;
if ($is_logged && isset($member_id['user_id']) && $my_profile) {

	$ulogin_lang = '';
	require_once ENGINE_DIR . '/modules/ulogin/ulogin.lng';

	if ($config['charset'] == "windows-1251") {
		foreach ($ulogin_lang as $key => $val) {
			$count = 0;
			foreach ($ulogin_lang[$key] as $key1 => $val1) {
				$count ++;
				$ulogin_lang[$key][$key1] = iconv('UTF-8', 'windows-1251//IGNORE', $val1);
			}
			if ($count == 0) {
				$ulogin_lang[$key] = iconv('UTF-8', 'windows-1251//IGNORE', $val);
			}
		}
	}

	$networks = array();


	require_once ENGINE_DIR . '/modules/ulogin/ulogin_model.class.php';
	$ulogin_model = new UloginModel();

	$ulogin_config = $ulogin_model->getUloginConfig();

	$networks = $ulogin_model->getUloginUserNetworks($member_id['user_id']);

	$tpl_config = array(
		'uloginid' => !empty($uloginid) ? $uloginid : $ulogin_config['uloginid_profile'],
		'ulogin_profile_title' => $ulogin_lang['ulogin_profile_title'],
		'add_account' => $ulogin_lang['add_account'],
		'delete_account' => $ulogin_lang['delete_account'],
		'add_account_explain' => $ulogin_lang['add_account_explain'],
		'delete_account_explain' => $ulogin_lang['delete_account_explain'],
		'networks' => $networks,
		'template' => !empty($template) ? $template : 'ulogin/ulogin_profile.tpl',
		'cachePrefix' => !empty($cachePrefix) ? $cachePrefix : 'news',
		'cacheSuffix' => !empty($cacheSuffix) ? true : false
	);

	$cacheName = md5(implode('_', $tpl_config));

	$ulogin_profile = false;
	$ulogin_profile = dle_cache($tpl_config['cachePrefix'], $cacheName . $config['skin'], $tpl_config['cacheSuffix']);

	if (!$ulogin_profile) {
		if (file_exists(TEMPLATE_DIR . '/' . $tpl_config['template'])) {

			$tpl_2 = new dle_template();
			$tpl_2->dir = TEMPLATE_DIR;

			$tpl_2->load_template($tpl_config['template']);

			$tpl_2->set('{uloginid}', $tpl_config['uloginid']);
			$tpl_2->set('{ulogin_profile_title}', $tpl_config['ulogin_profile_title']);
			$tpl_2->set('{add_account}', $tpl_config['add_account']);
			$tpl_2->set('{delete_account}', $tpl_config['delete_account']);
			$tpl_2->set('{add_account_explain}', $tpl_config['add_account_explain']);
			$tpl_2->set('{delete_account_explain}', $tpl_config['delete_account_explain']);

			if (!empty($tpl_config['networks']) && is_array($tpl_config['networks'])) {
				$networks_str = '';
				foreach ($tpl_config['networks'] as $network) {
					$networks_str .= "<div data-ulogin-network='$network' class=\"ulogin_provider big_provider {$network}_big\" onclick=\"uloginDeleteAccount('$network')\"></div>";
				}
				$tpl_2->set('{networks}', $networks_str);
				$tpl_2->set('{display}', 'block');
			} else {
				$tpl_2->set('{display}', 'none');
			}

			$tpl_2->compile('ulogin_profile');
			$ulogin_profile = $tpl_2->result['ulogin_profile'];
			create_cache($tpl_config['cachePrefix'], $ulogin_profile, $cacheName . $config['skin'], $tpl_config['cacheSuffix']);
			$tpl_2->clear();

		} else {
			$ulogin_profile = '<b style="color:red">Отсутствует файл шаблона: ' . $config['skin'] . '/' . $tpl_config['template'] . '</b>';
		}
	}

	echo $ulogin_profile;
}