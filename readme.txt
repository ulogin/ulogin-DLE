=== uLogin - виджет авторизации через социальные сети ===
Donate link: http://ulogin.ru/
Tags: ulogin, login, social, authorization
Requires at least: 8.5
Tested up to: 9.6
Stable tag: 1.7
License: GPL3
Форма авторизации uLogin через социальные сети. Улучшенный аналог loginza.

== Description ==

uLogin — это инструмент, который позволяет пользователям получить единый доступ к различным Интернет-сервисам без необходимости повторной регистрации,
а владельцам сайтов — получить дополнительный приток клиентов из социальных сетей и популярных порталов (Google, Яндекс, Mail.ru, ВКонтакте, Facebook и др.)

== Installation ==

1. Скопируйте файл ulogin.php в /engine/modules/

2. В файл login.tpl вашего шаблона выше формы авторизации вставляем (или вместо) :
  <script src="http://ulogin.ru/js/ulogin.js"></script>
  <div id="uLogin" x-ulogin-params="display=small&fields=first_name,last_name,photo,email,bdate,nickname&providers=vkontakte,odnoklassniki,mailru,facebook&hidden=twitter,google,yandex,livejournal,openid&redirect_uri={ulogin}"></div>
  <br>

3. В файл userinfo.tpl вашего шаблона ниже <tr><td class="label">Подпись:</td><td><textarea name="signature" style="width:98%;" rows="5" class="f_textarea">{editsignature}</textarea></td></tr> вставляем:

  [group=6]
	      <tr>
		  <td class="label">Синхронизация профилей uLogin:</td>
		      <td>
			  <script src="http://ulogin.ru/js/ulogin.js"></script>
			  <div id="uLogin" x-ulogin-params="display=small&fields=first_name,last_name,photo,photo_big,email,bdate,nickname&providers=vkontakte,odnoklassniki,mailru,facebook&hidden=other&redirect_uri={ulogin_sync}"></div>
		      </td>
	      </tr>
  [/group]

здесь group = 6 - номер группы пользователей Ulogin(об этом см. пункт 9);

4. В файл index.php ниже $tpl->set ( '{speedbar}', $tpl->result['speedbar'] ); вставляем:

  $tpl->set( '{ulogin}', urlencode('http://' . $_SERVER['HTTP_HOST'] .$_SERVER['REQUEST_URI'] ));

5. В файл /engine/modules/profile.php после $tpl->load_template( 'userinfo.tpl' ); вставляем:

  $tpl->set( '{ulogin_sync}', urlencode('http://' . $_SERVER['HTTP_HOST'] .$_SERVER['REQUEST_URI'] ));

6. В файл init.php в папке engine ниже require_once ENGINE_DIR . '/modules/gzip.php'; вставляем:

  require_once ENGINE_DIR . '/modules/ulogin.php';

7. В файл engine/inc/options.php ниже showRow( $lang['opt_sys_reggroup'], $lang['opt_sys_reggroupd'], makeDropDown( $sys_group_arr, "save_con[reg_group]", $config['reg_group'] ) ); вставляем:

  showRow( $lang['opt_sys_regulogin'], $lang['opt_sys_regulogind'], makeDropDown( $sys_group_arr, "save_con[reg_group_ulogin]", $config['reg_group_ulogin'] ) );

8. В файл language/Russian/adminpanel.lng ниже 'wysiwyg_language' => "ru", вставляем:

  'opt_sys_regulogin' => "Помещать пользователей авторизующихся через ULogin в группе:",
  'opt_sys_regulogind' => "Выберите группу в которую будут помещены пользователи авторизирующиеся через ULogin",

9. Зайти в Админцентр - Настройки системы - Настройки пользователей
    Выстаить параметр "Помещать пользователей авторизующихся через ULogin в группе:"
    Предварительно можно создать спец. группу, например "ULogin"
    После создания группы ULogin, заменить номер группы в пункте 3 на ID группы Ulogin.

== Changelog ==
- Добавлена синхронизация профилей Ulogin (Мой профиль -> редактировать профиль->Синхронизация профилей uLogin ). Данная функция доступна только для пользователей Ulogin;
- Удалена возможность регистрации пользователей через Ulogin с одинаковым email, вместо этого добавлена возможность синхронизации.
- Изменен способ загрузки аватара. При отсутствии аватара подгружает его при последующем входе в систему.