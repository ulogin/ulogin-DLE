<?php

//=====================================================
// ULogin DataLife Engine 9.4
//-----------------------------------------------------
//  Module for user authorization service uLogin
//-----------------------------------------------------
// http://ulogin.ru/
// team@ulogin.ru
// License GPL3
//-----------------------------------------------------
// Copyright (c) 2011-2012 uLogin
//=====================================================


if(!defined('DATALIFEENGINE'))
{
	die("Hacking attempt!");
}

require_once ENGINE_DIR . '/classes/parse.class.php';
include ('engine/api/api.class.php');

//===================================================
// login_ulogin_user
//---------------------------------------------------
// login user
//---------------------------------------------------
// $id - registred user id
//---------------------------------------------------
// $pass - user password
//---------------------------------------------------
//
//===================================================
function login_ulogin_user($id, $pass) {
    global $db;
    global $config;
    global $is_logged;
    global $_IP;
    global $_TIME;
    
    $add_time = time() + ($config['date_adjust'] * 60);
    $_IP = $db->safesql( $_SERVER['REMOTE_ADDR'] );
	$member_id = $db->super_query( "SELECT user_id FROM " . USERPREFIX . "_users where user_id=".$id );
	set_cookie( "dle_user_id", $member_id['user_id'], 365 );
	set_cookie( "dle_password", $_POST['login_password'], 365 );

	@session_register( 'dle_user_id' );
	@session_register( 'dle_password' );
	@session_register( 'member_lasttime' );

	$_SESSION['dle_user_id'] = $member_id['user_id'];
	$_SESSION['dle_password'] = $pass;
	$_SESSION['member_lasttime'] = $member_id['lastdate'];
	$_SESSION['dle_log'] = 0;

	$dle_login_hash = md5( strtolower( $_SERVER['HTTP_HOST'] . $member_id['name'] . sha1($pass) . $config['key'] . date( "Ymd" ) ) );

	if( $config['log_hash'] ) {
		$salt = "abchefghjkmnpqrstuvwxyz0123456789";
		$hash = '';
		srand( ( double ) microtime() * 1000000 );

		for($i = 0; $i < 9; $i ++) {
			$hash .= $salt{rand( 0, 33 )};
		}

		$hash = md5( $hash );

		$db->query( "UPDATE " . USERPREFIX . "_users set hash='" . $hash . "', lastdate='{$_TIME}', logged_ip='" . $_IP . "' WHERE user_id='$member_id[user_id]'" );

		set_cookie( "dle_hash", $hash, 365 );

		$_COOKIE['dle_hash'] = $hash;
		$mem_id['hash'] = $hash;

	}
	else
            $db->query( "UPDATE LOW_PRIORITY " . USERPREFIX . "_users set lastdate='{$_TIME}', logged_ip='" . $_IP . "' WHERE user_id='$member_id[user_id]'" );
                
	$is_logged = TRUE;
}

//===================================================
// check_ulogin_register
//---------------------------------------------------
// check login name and generate new
//---------------------------------------------------
// $name - login name
//---------------------------------------------------
//
//===================================================

function check_ulogin_register($name) {
	global $lang, $db, $banned_info, $relates_word;
	$stop = false;
	
	if( strlen( $name ) > 20 ) $name = substr($name, 0, 20);
	if( preg_match( "/[\||\'|\<|\>|\[|\]|\"|\!|\?|\$|\@|\/|\\\|\&\~\*\{\+]/", $name ) ) $name = preg_replace("/[\||\'|\<|\>|\[|\]|\"|\!|\?|\$|\@|\/|\\\|\&\~\*\{\+]/",'',$name);
	if (strpos( strtolower ($name) , '.php' ) !== false) {
            @$name = str_replace('.php', '', $name);
            @$name = str_replace('.PHP', '', $name);
        }
	
	$name = strtolower( $name );
        $search_name = strtr( $name, $relates_word );
		
	$row = $db->super_query("SELECT COUNT(*) as count FROM ".USERPREFIX."_users WHERE LOWER(name) REGEXP '[[:<:]]{$search_name}[[:>:]]' OR name = '$name'");
	if( $row['count'] ) $stop = true;

	return $stop;

}

//===================================================
// get_ulogin_user_from_token
//---------------------------------------------------
// get user data from uLogin service
//---------------------------------------------------
// $token - unique token
//---------------------------------------------------
// return array filled with user data
//===================================================

function get_ulogin_user_from_token($token){

    $s = array("error" => "file_get_contents or curl required");
    if (function_exists('file_get_contents') && ini_get('allow_url_open')){

        $result = file_get_contents('http://ulogin.ru/token.php?token=' . $token . '&host=' . $_SERVER['HTTP_HOST']);
        $s = json_decode($result, true);

    }elseif(function_exists('curl_init')){

        $request = curl_init('http://ulogin.ru/token.php?token=' . $token . '&host=' . $_SERVER['HTTP_HOST']);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($request);

        if ($result)
            $s = json_decode($result, true);

    }
    return $s;
}

function get_ulogin_member($identity){
    global $db, $dle_api;
    $ulogin_id = $db->super_query( "SELECT user_id,seed,email FROM " . USERPREFIX . "_ulogin where ident='".$db->safesql($identity)."'" );
    $member = FALSE;
    if($ulogin_id) {
        $member = $db->super_query( "SELECT user_id, name FROM " . USERPREFIX . "_users where user_id=".$ulogin_id['user_id'] );
        if (isset($member['user_id'])) {
            $password = $identity.$ulogin_id['seed'];
            if (!$dle_api->external_auth($member['name'], $password)){

                $passwords = $db->super_query("SELECT concat(ident,seed) as password FROM ".USERPREFIX."_ulogin WHERE user_id = ".$member['user_id'] . " AND seed = ".$ulogin_id['seed'], true);
                $password = reset($passwords);

                while($password){

                    if ($dle_api->external_auth($member['name'], $password['password'])) {
                        break;
                    }

                    $password = next($passwords);

                }

                $password = $password['password'];
            }
            $member['password'] = md5($password);
        }

        $member['ulogin_id'] = $ulogin_id['user_id'];
        $member['email'] = $ulogin_id['email'];
    }
    
    return $member;
}

//===================================================
// email_exist
//---------------------------------------------------
// check if exist user with same email
//---------------------------------------------------
// $email - email to check
//---------------------------------------------------
//
//===================================================

function email_exist($email){
    global $db;
    $row = $db->super_query("SELECT COUNT(*) as count FROM ".USERPREFIX."_users WHERE email = '$email'");
    return $row['count'];
}

//===================================================
// register_user
//---------------------------------------------------
// register user
//---------------------------------------------------
// $user_data - array with user data required to
// register
//---------------------------------------------------
//
//===================================================

function register_user($user_data = array()){
    global  $config, $db;
    $parse = new ParseFilter( );
    $parse->safe_mode = true;
    $parse->allow_url = false;
    $parse->allow_image = false;
    
    $fullname = $config['charset'] != 'utf-8' ? convert_unicode($user_data['first_name'].' '.$user_data['last_name'], $config['charset']) : $user_data['first_name'].' '.$user_data['last_name'];
    $fullname = $db->safesql( $parse->process( $fullname) );
    $login = isset ($user_data['nickname']) ? $user_data['nickname'] : $user_data['first_name'];
    $login = $config['charset'] != 'utf-8' ? convert_unicode($login) : $login;
    $login = $db->safesql( $parse->process( htmlspecialchars( trim( $login ) ) ) );
    $login = preg_replace('#\s+#i', ' ', $login);

    $not_allow_symbol = array ("\x22", "\x60", "\t", '\n', '\r', "\n", "\r", '\\', ",", "/", "�", "#", ";", ":", "~", "[", "]", "{", "}", ")", "(", "*", "^", "%", "$", "<", ">", "?", "!", '"', "'", " " );
    $email = $db->safesql(trim( str_replace( $not_allow_symbol, '', strip_tags( stripslashes( $user_data['email'])))));
        
    
    
    $idx = 0;
    $test_login = $login;
   
    while ($reg_error = check_ulogin_register($test_login)){
        $idx ++;
        $test_login = $login.'_'.$idx;
    }
        
    $login = $test_login;
                
    //$stopregistration = false;

    $config['reg_group_ulogin'] = intval( $config['reg_group_ulogin'] ) ? intval( $config['reg_group_ulogin'] ) : 4;
    $seed=mt_rand();
    $password = md5(md5($user_data['identity'].$seed));

    $add_time = time() + ($config['date_adjust'] * 60);
    $_IP = $db->safesql( $_SERVER['REMOTE_ADDR'] );
			
    if( intval( $config['reg_group'] ) < 3 ) $config['reg_group'] = 4;
			
    $db->query( "INSERT INTO " . USERPREFIX . "_users (name, fullname, password, email, reg_date, lastdate, user_group, info, signature, favorites, xfields, logged_ip) VALUES ('$login', '$fullname', '$password', '$email', '$add_time', '$add_time', '" . $config['reg_group_ulogin'] . "', '', '', '', '', '" . $_IP . "')" );
    $user_id = $db->insert_id();
    
    load_photo($user_data, $user_id);
    
    return array('user_id' => $user_id, 'seed' => $seed);
}

//===================================================
// register_ulogin_user
//---------------------------------------------------
// add ulogin user identity
//---------------------------------------------------
// $ulogin_user - array with user identity, original email,
// random value
//---------------------------------------------------
//
//===================================================

function register_ulogin_user($ulogin_user = array()){
    
    global $db;
    
    if (is_array($ulogin_user)){
        
        if ($ulogin_user['ulogin_id'] > 0)
            $db->query("UPDATE " . USERPREFIX . "_ulogin SET user_id =".$ulogin_user['user_id'].", seed=".$ulogin_user['seed']." where ident ='".$db->safesql($ulogin_user['identity'])."'");
        else
            $db->query("INSERT INTO " . USERPREFIX . "_ulogin (user_id, ident, email, seed) values (".$ulogin_user['user_id'].", '".$ulogin_user['identity']."','".$ulogin_user['email']."',".$ulogin_user['seed'].")");
          
    }
    
}

//===================================================
// uploadPhoto
//---------------------------------------------------
// upload image from $url to $filename
// detect MIME
//---------------------------------------------------
// $ulogin_user - array with user identity, email,
// random value
//---------------------------------------------------
// return array filled with MIME (type), filename('tmp_name),
// file size (size), extension(ext)
//===================================================

function uploadPhoto($url, $filename){
    $file = array();
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, 1);
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    $result = curl_exec($ch);
    if (!$result)
        return false;

    $savepath = ROOT_DIR . "/uploads/fotos/";

    $value = array();
    preg_match('/Content-Type: (?<value>\w+(\/)\w+)/', $result, $value);
    $file['type'] = $value['value'];
    preg_match('/Content-Type: \w+(\/)(?<value>\w+)/', $result, $value);
    $file['ext'] = $value['value'] == 'jpeg' ? 'jpg' : $value['value'];
    $file['tmp_name'] = $filename.'.'.$file['ext'];
    $from = fopen($url,'rb');
    $to = fopen($savepath.$file['tmp_name'], "wb");
    $size = 0;
    if ($from && $to){
        while(!feof($from)) {
            $size += fwrite($to, fread($from, 1024 * 8 ), 1024 * 8 );
        }
    } else
        return false;

    fclose($from);
    fclose($to);
    $file['size'] = $size;
    $file['tmp_name'] = basename($file['tmp_name']);
    return $file;
}

//===================================================
// load_photo
//---------------------------------------------------
// create thumbnail from image and attach it to user profile
//---------------------------------------------------
// $photos - array with ulogin photo and photo_big
//---------------------------------------------------
//
//===================================================

function load_photo($photos, $id){
    
    global $db;
    global $config;

    if(isset($photos['photo_big']) && isset($photos['photo'])){
        if (parse_url($photos['photo_big'], PHP_URL_HOST) != 'ulogin.ru')
            $photo = $photos['photo_big'];
        else if (parse_url($photos['photo'], PHP_URL_HOST) != 'ulogin.ru')
            $photo = $photos['photo'];
        else
            $photo = "";
    } else 
        $photo ="";

    $user_group = get_user_group($id);

    if( $photo && intval($user_group['max_foto'] ) > 0) {

        $res = uploadPhoto($photo, $id);

        if( $res ) {
            include_once ENGINE_DIR . '/classes/thumb.class.php';
            @chmod( ROOT_DIR . "/uploads/fotos/" . $res['tmp_name'], 0666 );
            $thumb = new thumbnail( ROOT_DIR . "/uploads/fotos/" . $res['tmp_name']);

            if( $thumb->size_auto($user_group['max_foto']) ) {
                $thumb->jpeg_quality( $config['jpeg_quality'] );
                $thumb->save( ROOT_DIR . "/uploads/fotos/foto_" . $res['tmp_name']);
            } else {
                if($res['ext'] == "gif" ) {
                    @rename( ROOT_DIR . "/uploads/fotos/" . $res['tmp_name'], ROOT_DIR . "/uploads/fotos/foto_" . $res['tmp_name'] );
                } else {
                    $thumb->jpeg_quality( $config['jpeg_quality'] );
                    $thumb->save( ROOT_DIR . "/uploads/fotos/foto_" . $res['tmp_name'] );
                }
            }
            @unlink( ROOT_DIR . "/uploads/fotos/".$res['tmp_name'] );
            @chmod( ROOT_DIR . "/uploads/fotos/foto_" . $res['tmp_name'], 0666 );
            $foto_name = "foto_" . $res['tmp_name'];

            $db->query( "UPDATE " . USERPREFIX . "_users set foto='$foto_name' WHERE user_id = '{$id}'" );
	    }

    }
}

//===================================================
// get_user_group
//---------------------------------------------------
// get user group from user id
//---------------------------------------------------
// $id - user id
//---------------------------------------------------
// return array with group settings
//===================================================

function get_user_group($id = 0){
    global $db;
    global $dle_api;
    $group_id = $dle_api->take_user_by_id($id, 'user_group');
    return $db->super_query('SELECT * FROM '.USERPREFIX.'_usergroups WHERE id = '.$group_id['user_group']);
}

//===================================================
// is_ulogin_user
//---------------------------------------------------
// count uLogin profiles attached to user
//---------------------------------------------------
// $id - user id
//---------------------------------------------------
// return count of uLogin profiles
//===================================================

function is_ulogin_user($id = 0){
    global $db, $dle_api;

    $accounts = $dle_api->load_table(USERPREFIX."_ulogin","count(ident)","user_id = ".$id);

    return count($accounts);

}

$db->super_query("CREATE TABLE IF NOT EXISTS `".USERPREFIX."_ulogin` (
                    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                    `user_id` int(10) unsigned NOT NULL,
                    `ident` char(255) NOT NULL,
                    `email` char(255) DEFAULT NULL,
                    `seed` int(10) unsigned NOT NULL,
                    PRIMARY KEY (`id`)
                    ) ENGINE=MyISAM;");

if(isset($_POST['token']) && !$_SESSION['dle_user_id']){ //reg

    $stopregistration = false;

    $ulogin_user = get_ulogin_user_from_token($_POST['token']);
    
    if (isset($ulogin_user['error'])){

        echo "<script>alert('".$ulogin_user['error']."');</script>";
        return;
    }
        
    $member = get_ulogin_member($ulogin_user['identity']);
    
    if(isset($member['user_id'])){

        $user = $dle_api->take_user_by_id($member['user_id'],"email,name, foto");

        if (!isset($user['foto']) || !file_exists(ROOT_DIR . "/uploads/fotos/".$user['foto'])){
            load_photo($ulogin_user, $member['user_id']);
        }

        $mail_parts = explode("@", $member['email']);
        if ($user['email'] == $mail_parts[0]."+".$user['name']."@".$mail_parts[1]){
            if (!$dle_api->take_user_by_email($member['email'],"user_id")){
                $db->query("UPDATE ".USERPREFIX."_users SET email = '".$member['email']."' WHERE user_id = ".$member['user_id']);
            }
        }
        login_ulogin_user($member['user_id'], $member['password']);
        
    }else if (email_exist($ulogin_user['email'])){
        global $config;
        $message = 'Пользователь с таким email уже зарегистрирован. Воспользуйтесь синхронизацией профилей uLogin, если у вас уже имеется аккаунт uLogin.';

        if ($config['charset'] != 'utf-8'){
            $message = iconv('utf-8',$config['charset'],$message);
        }
        echo '<script type="text/javascript">window.onload = function(e){alert("'.$message.'");}</script>';
    }else{
        
        $reg_user = register_user($ulogin_user);
        $reg_user['email'] = $ulogin_user['email'];
        $reg_user['ulogin_id'] = isset($member['ulogin_id']) ? $member['ulogin_id'] : 0;
        $reg_user['identity'] = $ulogin_user['identity'];
        register_ulogin_user($reg_user);
        $password =  md5($ulogin_user['identity'].$reg_user['seed']);
	    login_ulogin_user($reg_user['user_id'],$password);

    }
    
    unset($_POST['token']);
    
} else if(isset($_POST['token']) && $_SESSION['dle_user_id'] > 0 && is_ulogin_user(intval($_SESSION['dle_user_id']))){ //sync
    
   $user = $dle_api->take_user_by_id($_SESSION['dle_user_id']);
   if ($user['name'] == $_GET['user'] && $_GET['subaction'] == 'userinfo'){
       $ulogin_user = get_ulogin_user_from_token($_POST['token']);
        if (isset($ulogin_user['error'])){
            return;
        }
        
        $member = get_ulogin_member($ulogin_user['identity']);
        
        if(isset($member['user_id'])){
            
            if ($member['user_id'] != $_SESSION['dle_user_id']){

                $seed = $dle_api->load_table(USERPREFIX."_ulogin","seed", "user_id = ".$_SESSION['dle_user_id'], false, 0 , 1);

                if (isset($seed['seed'])){

                    $accounts = $db->get_row($db->query("SELECT count(user_id) as accounts FROM ".USERPREFIX."_ulogin WHERE user_id = ".$member['user_id']));

                    if ($accounts['accounts'] > 0){

                        $photo = $dle_api->load_table(USERPREFIX."_users","foto","user_id = ".$member['user_id']);
                        $photo = ROOT_DIR . "/uploads/fotos/".$photo['foto'];

                        if (file_exists($photo)){
                            @unlink($photo);
                        }

                        $db->query("DELETE FROM " . USERPREFIX . "_users WHERE user_id = ". $member['user_id']);

                    }

                    $db->query("UPDATE " . USERPREFIX . "_ulogin SET user_id =".$_SESSION['dle_user_id'].", seed = ".$seed['seed']." where ident ='".$db->safesql($ulogin_user['identity'])."'");

                }
            }
            
         }else{
            $seed = $dle_api->load_table(USERPREFIX."_ulogin","seed", "user_id = ".$_SESSION['dle_user_id'], false, 0 , 1);;

            if (isset($seed['seed']))
                $db->query("INSERT INTO " . USERPREFIX . "_ulogin (user_id, ident, email, seed) values (".$_SESSION['dle_user_id'].", '".$ulogin_user['identity']."','".$ulogin_user['email']."',".$seed['seed'].")");
         }
   }
   
}
?>