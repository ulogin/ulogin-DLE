=== uLogin - виджет авторизации через социальные сети ===
Donate link: http://ulogin.ru/
Tags: ulogin, login, social, authorization
Requires at least: 8.5
Tested up to: 9.4
Stable tag: 1.7
License: GPL3
Форма авторизации uLogin через социальные сети. Улучшенный аналог loginza.

== Description ==

uLogin — это инструмент, который позволяет пользователям получить единый доступ к различным Интернет-сервисам без необходимости повторной регистрации,
а владельцам сайтов — получить дополнительный приток клиентов из социальных сетей и популярных порталов (Google, Яндекс, Mail.ru, ВКонтакте, Facebook и др.)

== Installation ==


Создайте таблицу в базе данных. Вместо prefix_ укажите ваш префикс к таблицам DLE. Ниже приведен код SQL-скрипта:

CREATE TABLE IF NOT EXISTS `prefix_ulogin` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `ident` char(255) NOT NULL,
  `email` char(255) DEFAULT NULL,
  `seed` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;


Скопируйте файл ulogin.php в /engine/modules/
В файл login.tpl вашего шаблона выше формы авторизации вставляем (или вместо)

<script src="http://ulogin.ru/js/ulogin.js"></script>
<div id="uLogin" x-ulogin-params="display=small&fields=first_name,last_name,photo,email,bdate,nickname&providers=vkontakte,odnoklassniki,mailru,facebook&hidden=twitter,google,yandex,livejournal,openid&redirect_uri={ulogin}"></div>
<br>

В файл index.php ниже 
$tpl->set ( '{speedbar}', $tpl->result['speedbar'] );
вставляем
$tpl->set( '{ulogin}', urlencode('http://' . $_SERVER['HTTP_HOST'] .$_SERVER['REQUEST_URI'] ));

В файл init.php в папке engine
ниже
require_once ENGINE_DIR . '/modules/gzip.php';
вставляем
require_once ENGINE_DIR . '/modules/ulogin.php';

В файл engine/inc/options.php
ниже
showRow( $lang['opt_sys_reggroup'], $lang['opt_sys_reggroupd'], makeDropDown( $sys_group_arr, "save_con[reg_group]", $config['reg_group'] ) );
вставляем
showRow( $lang['opt_sys_regulogin'], $lang['opt_sys_regulogind'], makeDropDown( $sys_group_arr, "save_con[reg_group_ulogin]", $config['reg_group_ulogin'] ) );

В файл language/Russian/adminpanel.lng
ниже
'wysiwyg_language' => "ru",
вставляем
'opt_sys_regulogin' => "Помещать пользователей авторизующихся через ULogin в группе:",
'opt_sys_regulogind' => "Выберите группу в которую будут помещены пользователи авторизирующиеся через ULogin",

Зайти в Админцентр - Настройки системы - Настройки пользователей
Выстаить параметр "Помещать пользователей авторизующихся через ULogin в группе:"
Предварительно можно создать спец. группу, например "ULogin"


Если вы используете DLE с кодировкой UTF, то в файле ulogin.php раскомментируйте следующие строки:
	//	$fullname = $user['first_name'].' '.$user['last_name'];
	//	$login=$user['first_name'].'_'.$user['last_name'];

