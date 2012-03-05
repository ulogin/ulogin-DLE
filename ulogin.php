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


function register_user($id,$pass) {
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


if(isset($_POST['token'])){

	$stopregistration = false;

	$s = file_get_contents('http://ulogin.ru/token.php?token=' . $_POST['token'] . '&host=' . $_SERVER['HTTP_HOST']);
	$user = json_decode($s, true);

	$member_id = $db->super_query( "SELECT user_id,seed FROM " . USERPREFIX . "_ulogin where ident='".$db->safesql($user['identity'])."'" );
	if($member_id) {
		$password=md5($user['identity'].$member_id['seed']);
		$member_id = $db->super_query( "SELECT user_id FROM " . USERPREFIX . "_users where user_id=".$member_id['user_id'] );
		if(!$member_id) {
			$db->query('delete from '.USERPREFIX.'_ulogin where ident=\''.$db->safesql($user['identity'])."'");
		}
	}


	if($member_id)
		register_user($member_id['user_id'],$password);
	else {
	
	//For Non-Unicode version of DLE:
		$fullname = convert_unicode($user['first_name'].' '.$user['last_name'] );
		$login=convert_unicode($user['first_name'].'_'.$user['last_name']);
	//For Unicode version of DLE:
	//	$fullname = $user['first_name'].' '.$user['last_name'];
	//	$login=$user['first_name'].'_'.$user['last_name'];
		
		
		$fullname=addslashes($fullname);
		$login=addslashes($login);
		
		if(isset($user['photo'])){
			$photo = $user['photo'];
		} else $photo ="";
		$email = $user['email'];
		
		$cnt=$db->super_query( "SELECT COUNT(*) as how FROM " . USERPREFIX . "_users where email='".$email."'" );
		if($cnt['how']>0){
			$res=preg_match('/^([^\@]+)\@([^\@]+)$/',$email,$matches);
			$nemail=$matches[1].'_'.$user['network'];
			$i=0;
			do {
				$email=$nemail;
				$cnt=$db->super_query( "SELECT COUNT(*) as how FROM " . USERPREFIX . "_users where email='".$db->safesql($email.'@'.$matches[2])."'" );
				$nemail=$matches[1].'_'.$user['network'].'@'.$matches[2];
				$i++;
				$nemail=$matches[1].'_'.$user['network'].'_'.$i;
			} while($cnt['how']>0);
			$email.='@'.$matches[2];
		}
		
		$cnt=$db->super_query( "SELECT COUNT(*) as how FROM " . USERPREFIX . "_users where name='".$login."'" );
		if($cnt['how']>0){
			$i=0;
			do {
				$i++;
				$cnt=$db->super_query( "SELECT COUNT(*) as how FROM " . USERPREFIX . "_users where name='".$db->safesql($login.'_'.$i)."'" );				
			} while($cnt['how']>0);
			$login.='_'.$i;
		}
			
		$stopregistration = false;

		$config['reg_group_ulogin'] = intval( $config['reg_group_ulogin'] ) ? intval( $config['reg_group_ulogin'] ) : 4;
		$seed=mt_rand();
		$password = md5($user['identity'].$seed);
		$regpassword = md5($password);

		$add_time = time() + ($config['date_adjust'] * 60);
		$_IP = $db->safesql( $_SERVER['REMOTE_ADDR'] );
			
		if( intval( $config['reg_group'] ) < 3 ) $config['reg_group'] = 4;
			
		$db->query( "INSERT INTO " . USERPREFIX . "_users (name, fullname, password, email, reg_date, lastdate, user_group, info, signature, favorites, xfields, logged_ip) VALUES ('$login', '$fullname', '$regpassword', '$email', '$add_time', '$add_time', '" . $config['reg_group_ulogin'] . "', '', '', '', '', '" . $_IP . "')" );
		$user_id=$id = $db->insert_id();
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
		register_user($user_id,$password);
	}
	unset($_POST['token']);
}

?>