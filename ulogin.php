<?php 

//===================================================== 
// ULogin DataLife Engine 9.4 
//----------------------------------------------------- 
// Модуль авторизации и регистрации при помощи OpenID 
//----------------------------------------------------- 
// http://ulogin.ru/ 
//----------------------------------------------------- 
// Copyright (c) 2011-2012 uLogin  
// team@ulogin.ru
// License: GPL3
//===================================================== 


if(!defined('DATALIFEENGINE')) 
{ 
    die("Hacking attempt!"); 
} 


function translit($content){  
$transA=array('А'=>'a','Б'=>'b','В'=>'v','Г'=>'g','Ґ'=>'g','Д'=>'d','Е'=>'e','Є'=>'e','Ё'=>'yo','Ж'=>'zh','З'=>'z','И'=>'i','І'=>'i','Й'=>'y','Ї'=>'y','К'=>'k','Л'=>'l','М'=>'m','Н'=>'n','О'=>'o','П'=>'p','Р'=>'r','С'=>'s','Т'=>'t','У'=>'u','Ў'=>'u','Ф'=>'f','Х'=>'h','Ц'=>'c','Ч'=>'ch','Ш'=>'sh','Щ'=>'sch','Ъ'=>'','Ы'=>'y','Ь'=>'','Э'=>'e','Ю'=>'yu','Я'=>'ya');  
$transB=array('а'=>'a','б'=>'b','в'=>'v','г'=>'g','ґ'=>'g','д'=>'d','е'=>'e','ё'=>'yo','є'=>'e','ж'=>'zh','з'=>'z','и'=>'i','і'=>'i','й'=>'y','ї'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ў'=>'u','ф'=>'f','х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya','&quot;'=>'','&amp;'=>'','µ'=>'u','№'=>''); 
$content=trim(strip_tags($content));  
$content=strtr($content,$transA);  
$content=strtr($content,$transB);  
$content=preg_replace("/\s+/ms","_",$content);  
$content=preg_replace('/[\-]+/i','-',$content); 
$content=preg_replace('/[\.]+/u','_',$content); 
$content=preg_replace("/[^a-z0-9\_\-\.]+/mi","",$content);  
$content=str_replace("/[_]+/u","_",$content);     
return $content;  
} 
     
if(isset($_POST['token'])){ 

require_once ENGINE_DIR . '/classes/parse.class.php'; 

$parse = new ParseFilter( ); 
$parse->safe_mode = true; 
$parse->allow_url = false; 
$parse->allow_image = false; 
$stopregistration = FALSE; 

$s = file_get_contents('http://ulogin.ru/token.php?token=' . $_POST['token'] . '&host=' . $_SERVER['HTTP_HOST']); 
$user = json_decode($s, true); 

//print_r($user); //закоментировать 

if(isset($user['nickname'])){ $nickname = $user['nickname'];} else $nickname =""; 
if(isset($user['first_name'])){ $first_name = convert_unicode($user['first_name']);} else $first_name =""; 
if(isset($user['last_name'])){ $last_name = convert_unicode($user['last_name']);}  else $last_name =""; 



if($nickname!=''){$name = $nickname;} 
elseif($first_name!=''){ $name = translit($first_name);} 
elseif($first_name!=''){$name = translit($last_name);} 
else{$name='ulogin';} 
if(isset($user['identity'])) { $password = md5( trim($user['identity']) );} else  $password = md5( 'password') ; 
if(isset($user['photo'])){ $photo = $user['photo'];} else $photo =""; 
if(isset($user['email'])){ $email = $user['email'];} else $email = /*$name.'@'.clean_url($config['http_home_url'])*/clean_url($user['identity']).'@'.$user['network'].'.com'; 

$access_token = md5($email); 

$array[] = $name; 
$bday = explode('.',$bdate); 
if(trim($bday[2])=="") {$bdate = "20.07.1970"; $bday = explode('.',$bdate);} 
$array[] = $name.substr($bday[2],2,2); 
$array[] = $name.$bday[2]; 
$array[] = $name.$bday[1].$bday[2]; 
$array[] = $name.$bday[0].$bday[1].$bday[2]; 
$array[] = $name.$access_token; 
//print_r($array); 

foreach($array as $login){ 
    $row = $db->super_query( "SELECT COUNT(*) as count FROM " . USERPREFIX . "_users WHERE name = '$login'" ); 
     
    if( $row['count'] == 0 ) {$name = $login; break;} 
     
    else{     
        $member_id = $db->super_query( "SELECT * FROM " . USERPREFIX . "_users where name='$login' and password='" . md5($password). "'" ); 
         
        if( !empty($member_id['user_id']) ) {$name = $login; break;} 
        } 
         
    } 

        $member_id = $db->super_query( "SELECT * FROM " . USERPREFIX . "_users where name='$login' and password='" . md5( $password ) . "'" ); 

        if( $member_id['user_id'] ) { 

            set_cookie( "dle_user_id", $member_id['user_id'], 365 ); 
            set_cookie( "dle_password", $_POST['login_password'], 365 ); 

            @session_register( 'dle_user_id' ); 
            @session_register( 'dle_password' ); 
            @session_register( 'member_lasttime' ); 

            $_SESSION['dle_user_id'] = $member_id['user_id']; 
            $_SESSION['dle_password'] = $password; 
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

            } else $db->query( "UPDATE LOW_PRIORITY " . USERPREFIX . "_users set lastdate='{$_TIME}', logged_ip='" . $_IP . "' WHERE user_id='$member_id[user_id]'" ); 

            $is_logged = TRUE; 
        } else { 
                 $check_mail = $db->super_query( "SELECT COUNT(*) as how FROM " . USERPREFIX . "_users where email='".$email."'" ); 
                 if($check_mail['how'] > 0)  $email=preg_replace('![^\w\d]*!','',$user['identity']).'@'.$user['network'].'.com';
                 //$email = $login.'@'.clean_url($config['http_home_url']); 
            
                require_once ENGINE_DIR . '/classes/parse.class.php'; 
                $parse = new ParseFilter( ); 

                $parse->safe_mode = true; 
                $parse->allow_url = false; 
                $parse->allow_image = false; 
                $stopregistration = FALSE; 

                $config['reg_group_ulogin'] = intval( $config['reg_group_ulogin'] ) ? intval( $config['reg_group_ulogin'] ) : 4; 
                $regpassword = md5($password); 
                $name = $db->safesql( $parse->process( htmlspecialchars( trim( $login ) ) ) ); 
                $fullname = $db->safesql( $parse->process( $first_name.' '.$last_name ) ); 
                 
                $add_time = time() + ($config['date_adjust'] * 60); 
                $_IP = $db->safesql( $_SERVER['REMOTE_ADDR'] ); 
                 
                if( intval( $config['reg_group'] ) < 3 ) $config['reg_group'] = 4; 
             
                $db->query( "INSERT INTO " . USERPREFIX . "_users (name, fullname, password, email, reg_date, lastdate, user_group, info, signature, favorites, xfields, logged_ip) VALUES ('$name', '$fullname', '$regpassword', '$email', '$add_time', '$add_time', '" . $config['reg_group_ulogin'] . "', '', '', '', '', '" . $_IP . "')" ); 
                $id = $db->insert_id()+1; 
                 
                 if( $photo ) { 

                    $fparts = pathinfo($photo); 
                    $tmp_name = $fparts['basename']; 
                    $type = $fparts['extension']; 

                    include_once ENGINE_DIR . '/classes/thumb.class.php'; 

                    $res = @copy($photo, ROOT_DIR . "/uploads/fotos/".$tmp_name); 

                    if( $res ) { 

                        $thumb = new thumbnail( ROOT_DIR . "/uploads/fotos/".$tmp_name ); 
                        //$thumb->size_auto( $user_group[$config['reg_group_ulogin']]['max_foto'] ); 
                        $sz=$user_group[$config['reg_group_ulogin']]['max_foto'];
                        if(!$sz) $sz=110;
                        $thumb->size_auto( $sz ); 
                        $thumb->jpeg_quality( $config['jpeg_quality'] ); 
                        $thumb->save( ROOT_DIR . "/uploads/fotos/foto_" . $id . "." . $type ); 

                        @unlink( ROOT_DIR . "/uploads/fotos/".$tmp_name ); 
                        $foto_name = "foto_" . $id . "." . $type; 

                        $db->query( "UPDATE " . USERPREFIX . "_users set foto='$foto_name' where name='$name'" ); 

                    } 

                } 
             
        $member_id = $db->super_query( "SELECT * FROM " . USERPREFIX . "_users where name='$name' and password='" . md5( $password ) . "'" ); 
             if( $member_id['user_id'] ) { 
                  
                     
                    set_cookie( "dle_user_id", $member_id['user_id'], 365 ); 
                    set_cookie( "dle_password", $_POST['login_password'], 365 ); 

                    @session_register( 'dle_user_id' ); 
                    @session_register( 'dle_password' ); 
                    @session_register( 'member_lasttime' ); 

                    $_SESSION['dle_user_id'] = $member_id['user_id']; 
                    $_SESSION['dle_password'] = $password; 
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

                    } else $db->query( "UPDATE LOW_PRIORITY " . USERPREFIX . "_users set lastdate='{$_TIME}', logged_ip='" . $_IP . "' WHERE user_id='$member_id[user_id]'" ); 

                    $is_logged = TRUE; 
                    } 
            }   
             

unset($_POST['token']); 
}     

?>