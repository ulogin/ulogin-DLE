<?php 

//=====================================================
// ULogin DataLife Engine 9.4
//-----------------------------------------------------
// Модуль авторизации и регистрации при помощи uLogin
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

$parse = new ParseFilter( );
$parse->safe_mode = true;
$parse->allow_url = false;
$parse->allow_image = false;

function login_ulogin_user($id,$pass) {
	global $db;
	global $config;
	global $is_logged;
	global $_IP;
	global $_TIME;
	global $user;
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

	$dle_login_hash = md5( strtolower( $_SERVER['HTTP_HOST'] . $member_id['name'] . sha1($password) . $config['key'] . date( "Ymd" ) ) );

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
		$member_id['hash'] = $hash;

	}
	else
		$db->query( "UPDATE LOW_PRIORITY " . USERPREFIX . "_users set lastdate='{$_TIME}', logged_ip='" . $_IP . "' WHERE user_id='$member_id[user_id]'" );

	$is_logged = TRUE;
}

function check_ulogin_register($name, $email) {
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
		
	$row = $db->super_query( "SELECT COUNT(*) as count FROM " . USERPREFIX . "_users WHERE email = '$email' OR LOWER(name) REGEXP '[[:<:]]{$search_name}[[:>:]]' OR name = '$name'" );
	if( $row['count'] ) $stop = true;

	return $stop;

}

if(isset($_POST['token'])){

	$stopregistration = false;

	$s = file_get_contents('http://ulogin.ru/token.php?token=' . $_POST['token'] . '&host=' . $_SERVER['HTTP_HOST']);
	$user = json_decode($s, true);
        if (isset($user['error'])){
            return;
        }
        $db->super_query("CREATE TABLE IF NOT EXISTS `".USERPREFIX."_ulogin` (
                              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                              `user_id` int(10) unsigned NOT NULL,
                              `ident` char(255) NOT NULL,
                              `email` char(255) DEFAULT NULL,
                              `seed` int(10) unsigned NOT NULL,
                              PRIMARY KEY (`id`)
                            ) ENGINE=MyISAM;");
	$ulogin_id = $db->super_query( "SELECT user_id,seed FROM " . USERPREFIX . "_ulogin where ident='".$db->safesql($user['identity'])."'" );
        $member_id = FALSE;
        if($ulogin_id) {
		$password = md5($user['identity'].$ulogin_id['seed']);
		$member_id = $db->super_query( "SELECT user_id FROM " . USERPREFIX . "_users where user_id=".$ulogin_id['user_id'] );
	}

	if($member_id)
                login_ulogin_user($member_id['user_id'],$password);
	else {
		$fullname = $config['charset'] != 'utf-8' ? convert_unicode($user['first_name'].' '.$user['last_name'], $config['charset']) : $user['first_name'].' '.$user['last_name'];
                $fullname = $db->safesql( $parse->process( $fullname) );
                $login = isset ($user['nickname']) ? $user['nickname'] : $user['first_name'];
		$login = $config['charset'] != 'utf-8' ? convert_unicode($login) : $login;
                $login = $db->safesql( $parse->process( htmlspecialchars( trim( $login ) ) ) );
                $login = preg_replace('#\s+#i', ' ', $login);

                $not_allow_symbol = array ("\x22", "\x60", "\t", '\n', '\r', "\n", "\r", '\\', ",", "/", "¬", "#", ";", ":", "~", "[", "]", "{", "}", ")", "(", "*", "^", "%", "$", "<", ">", "?", "!", '"', "'", " " );
                $email = $user['email'];
                $email = $db->safesql(trim( str_replace( $not_allow_symbol, '', strip_tags( stripslashes( $email)))));
                
                
		if(isset($user['photo'])){
			$photo = $user['photo'];
		} else $photo ="";
                
                $idx = 0;
                $email_parts = explode('@', $email);
                $test_login = $login;
		while ($reg_error = check_ulogin_register($test_login, $email)){
                    $idx ++;
                    $test_login = $login.'_'.$idx;
                    $email = $email_parts[0].'+'.$test_login.'@'.$email_parts[1];
                }
                $login = $test_login;    
                
		$stopregistration = false;

		$config['reg_group_ulogin'] = intval( $config['reg_group_ulogin'] ) ? intval( $config['reg_group_ulogin'] ) : 4;
		$seed=mt_rand();
		$password = md5($user['identity'].$seed);
		$regpassword = md5($password);

		$add_time = time() + ($config['date_adjust'] * 60);
		$_IP = $db->safesql( $_SERVER['REMOTE_ADDR'] );
			
		if( intval( $config['reg_group'] ) < 3 ) $config['reg_group'] = 4;
			
		$db->query( "INSERT INTO " . USERPREFIX . "_users (name, fullname, password, email, reg_date, lastdate, user_group, info, signature, favorites, xfields, logged_ip) VALUES ('$login', '$fullname', '$regpassword', '$email', '$add_time', '$add_time', '" . $config['reg_group_ulogin'] . "', '', '', '', '', '" . $_IP . "')" );
		$user_id = $id = $db->insert_id();
                if ($ulogin_id)
                    $db->query("UPDATE " . USERPREFIX . "_ulogin SET user_id =".$user_id." where ident ='".$db->safesql($user['identity'])."'");
                else
                    $db->query("INSERT INTO " . USERPREFIX . "_ulogin (user_id, ident, email, seed) values ($id, '".$user['identity']."','".$user['email']."', $seed)");
                
		$id++;
		if( $photo ) {
			$fparts = pathinfo($photo);
			$tmp_name = $fparts['basename'];
			$type = $fparts['extension'];
			include_once ENGINE_DIR . '/classes/thumb.class.php';
			$res = @copy($photo, ROOT_DIR . "/uploads/fotos/".$tmp_name);
			if( $res ) {
				$thumb = new thumbnail( ROOT_DIR . "/uploads/fotos/".$tmp_name );
				$thumb->size_auto( 100 );
				$thumb->jpeg_quality( $config['jpeg_quality'] );
				$thumb->save( ROOT_DIR . "/uploads/fotos/foto_" . $id . "." . $type );
				@unlink( ROOT_DIR . "/uploads/fotos/".$tmp_name );
				$foto_name = "foto_" . $id . "." . $type;
				$db->query( "UPDATE " . USERPREFIX . "_users set foto='$foto_name' where user_id=$user_id" );
			}
		}
		login_ulogin_user($user_id,$password);
	}
	unset($_POST['token']);
}

?>