# uLogin

Donate link: http://ulogin.ru  
Tags: ulogin, login, social, authorization  
Tested up to: 10.4 
Stable tag: 2.0.0  
License: GNU General Public License, version 2  

**uLogin** — это инструмент, который позволяет пользователям получить единый доступ к различным Интернет-сервисам без необходимости повторной регистрации,
а владельцам сайтов — получить дополнительный приток пользователей из социальных сетей и популярных порталов (Google, Яндекс, Mail.ru, ВКонтакте, Facebook и др.)


## Установка

Зайдите в папку **upload->templates** и *измените название папки "Default"* на название вашего текущего шаблона сайта.  
После этого скопируйте к себе на сервер все файлы из папки **upload**.  

#### 1. В файле **engine/engine.php:35**

- после  

` 
    switch ( $do ) { 
`
 
+ вставить  
   
` 	
    case "ulogin" :  
        include ENGINE_DIR . '/modules/ulogin/ulogin.php';  
        break;  
`


#### 2. В файле **admin.php:44**

- после  

` 
    require_once (ENGINE_DIR . '/inc/include/init.php');
`
 
+ вставить  
   
` 	
    require_once (ENGINE_DIR . '/modules/ulogin/ulogin_conf.php');
`


#### 3. В файле **engine/modules/profile.php:419** 

- после  

` 
    $tpl->set( '{usertitle}', stripslashes( $row['name'] ) );  	
`
 
+ вставить  
   
` 
    $tpl->set( '{my_profile}', ( $row['user_id'] == $member_id['user_id'] ? true : false ) );
`


#### 4. В файле **engine/skins/default.skin.php:290** 

- после  

` 
    $options['admin_sections'][] = array (
                'name' => $row['title'], 
                'url' => "$PHP_SELF?mod={$row['name']}", 
                'mod' => "{$row['name']}",
                'access' => 1 
            );
`
 
+ вставить  
   
` 
    if ($row['name'] == 'ulogin') {$options['user'][] = $options['admin_sections'][count($options['admin_sections'])-1];}
`


#### 5. В файле **engine/inc/options.php:117** 

- после  

` 
    array (
        'name' => $lang['opt_group'], 
        'url' => "$PHP_SELF?mod=usergroup", 
        'descr' => $lang['opt_groupc'], 
        'image' => "usersgroup.png", 
        'access' => "admin" 
    ),
`
 
+ вставить  
   
` 
    $ulogin_opt_array,
`


#### 6. В файле **engine/modules/main.php:373**  (в версиях DLE < 10.4 ищем в файле **index.php:339**)

- перед(!) 

` 
    $tpl->set ( '{headers}', $metatags."\n".$js_array );
`
 
+ вставить  
   
` 
    include_once ENGINE_DIR . '/modules/ulogin/ulogin_tpl_headers.php';
`


### Файлы шаблона

Далее производится вставка кода в файлы шаблона. Строки для поиска указаны на примере шаблона **Default**.


#### 7. В файле **templates/Default/main.tpl:61**

- после  

` 
    {info}
`
 
+ вставить  
   
` 
    {ulogin_message}
`


#### 8. В файле **templates/Default/userinfo.tpl:41**

- после  

` 
    [not-logged]
    <div id="options" style="display:none;">
`
 
+ вставить  
   
` 
    {include file="engine/modules/ulogin/ulogin_tpl_profile.php?my_profile={my_profile}"}
`


#### 9. В файле **templates/Default/login.tpl:33**

- после  

` 
    <form method="post" action="">
        <div id="logform" class="radial">
            <ul class="reset">
                <li class="lfield">{include file="engine/modules/ulogin/ulogin_tpl_form.php"}</li>
`
 
+ вставить  
   
` 
    <li class="lfield">{include file="engine/modules/ulogin/ulogin_tpl_form.php"}</li>
`


#### 10. В файле **templates/Default/login.tpl:52**

- после  

` 
    <li class="lvsep"><a href="{registration-link}">Регистрация</a></li>
`
 
+ вставить  
   
` 
    <li class="lvsep">Войти с помощью:</li><li class="lvsep" style="background: none">{include file="engine/modules/ulogin/ulogin_tpl_form.php"}</li>
`



## Модуль "uLogin - авторизация"

Данный модуль находится на панели администрации в разделах *"Пользователи"* и *"Сторонние модули"*.

Здесь задаются: 
 
- **Значение поля uLogin ID** - общее поле для всех виджетов uLogin, необязательный параметр (см. *"Настройки виджета uLogin"*).    
- **Значение поля uLogin ID профиля пользователя** - идентификатор виджета в профиле пользователя.  
- **Группа пользователей** - Группа, присваиваемая пользователям, зарегистрированных с помощью uLogin. По умолчанию - группа *uLogin* - создаётся после установки модуля.



## Настройки виджета uLogin

При установке расширения uLogin авторизация пользователей будет осуществляться с настройками по умолчанию.  
Для более детальной настройки виджетов uLogin Вы можете воспользоваться сервисом uLogin.  

Вы можете создать свой виджет uLogin и редактировать его самостоятельно:

для создания виджета необходимо зайти в Личный Кабинет (ЛК) на сайте http://ulogin.ru/lk.php,
добавить свой сайт к списку Мои сайты и на вкладке Виджеты добавить новый виджет. После этого вы можете отредактировать свой виджет.

**Важно!** Для успешной работы плагина необходимо включить в обязательных полях профиля поле **Еmail** в Личном кабинете uLogin.  
Заполнять поля в графе «Тип авторизации» не нужно, т.к. расширение uLogin настроено на автоматическое заполнение данных параметров.

Созданный в Личном Кабинете виджет имеет параметр **uLogin ID**.  
Скопируйте значение **uLogin ID** вашего виджета в соответствующее поле в настройках плагина на вашем сайте и сохраните настройки.   

Если всё было сделано правильно, виджет изменится согласно вашим настройкам.


## Особенности

Вы можете добавить форму виджета uLogin в любом месте шаблона, вставив следующий код (как в пунктах 9, 10 установки)

`
    {include file="engine/modules/ulogin/ulogin_tpl_form.php"}
`

Вы можете добавить форму синхронизации аккаунтов пользователя в любом месте шаблона, вставив следующий код (как в пункте 8 установки)

`
    {include file="engine/modules/ulogin/ulogin_tpl_profile.php?my_profile={my_profile}"}
`

Чтобы для блока указать значение **uLogin ID** отличное от заданного в настройках модуля, вы можете дописать в адресную строку приведённого выше кода параметр *uloginid* (синтаксис GET-переменных):

`
    {include file="engine/modules/ulogin/ulogin_tpl_form.php?uloginid=11111111"}
    {include file="engine/modules/ulogin/ulogin_tpl_profile.php?my_profile={my_profile}&uloginid=11111111"}
`


