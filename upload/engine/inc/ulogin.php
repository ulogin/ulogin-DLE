<?php
if( !defined( 'DATALIFEENGINE' ) OR !defined( 'LOGGED_IN' ) ) {
	die("Hacking attempt!");
}

if($member_id['user_group'] != 1) {
	msg("error", $lang['index_denied'], $lang['index_denied']);
}

require_once ENGINE_DIR . '/modules/ulogin/ulogin_model.class.php';
$ulogin_model = new UloginModel();

$ulogin_config = $ulogin_model->getUloginConfig();

function showRow($title = "", $description = "", $field = "", $class = "") {
	echo "<tr>
       <td class=\"col-xs-10 col-sm-6 col-md-7 {$class}\"><h6>{$title}</h6><span class=\"note large\">{$description}</span></td>
       <td class=\"col-xs-2 col-md-5 settingstd {$class}\">{$field}</td>
       </tr>";
}

function makeDropDown($options, $name, $selected) {
	$output = "<select class=\"uniform\" style=\"min-width:100px;\" name=\"$name\">\r\n";
	foreach ( $options as $value => $description ) {
		$output .= "<option value=\"$value\"";
		if( $selected == $value ) {
			$output .= " selected ";
		}
		$output .= ">$description</option>\n";
	}
	$output .= "</select>";
	return $output;
}

function makeCheckBox($name, $selected) {
	$selected = $selected ? "checked" : "";

	return "<input class=\"iButton-icons-tab\" type=\"checkbox\" name=\"$name\" value=\"1\" {$selected}>";
}

function makeDropDownGroups($name, $selected) {
	$output = "<select class=\"uniform\" style=\"min-width:100px;\" name=\"$name\">\r\n";
	$output .= get_groups($selected);
	$output .= "</select>";
	return $output;
}


if( $action == "save" ) {
	if( $_REQUEST['user_hash'] == "" or $_REQUEST['user_hash'] != $dle_login_hash ) {
		die( "Hacking attempt! User not found" );
	}


	$save_con = $_POST['save_con'];
	$save_con['uloginid'] = $save_con['uloginid'];
	$save_con['uloginid_profile'] = $save_con['uloginid_profile'];
	$save_con['ulogin_group_id'] = intval($save_con['ulogin_group_id']);

	$find = array();
	$replace = array();

	$find[] = "'\r'";
	$replace[] = "";
	$find[] = "'\n'";
	$replace[] = "";

	$save_con = $save_con + $ulogin_config;

	$handler = fopen( ENGINE_DIR . '/data/uloginconfig.php', "w" );

	fwrite( $handler, "<?PHP \n\n//uLogin Configurations\n\n\$ulogin_config = array (\n\n" );
	foreach ( $save_con as $name => $value ) {

		$value = trim(strip_tags(stripslashes( $value )));
		$value = htmlspecialchars( $value, ENT_QUOTES, $config['charset']);
		$value = preg_replace( $find, $replace, $value );

		$name = trim(strip_tags(stripslashes( $name )));
		$name = htmlspecialchars( $name, ENT_QUOTES, $config['charset'] );
		$name = preg_replace( $find, $replace, $name );

		$value = str_replace( "$", "&#036;", $value );
		$value = str_replace( "{", "&#123;", $value );
		$value = str_replace( "}", "&#125;", $value );
		$value = str_replace( ".", "", $value );
		$value = str_replace( '/', "", $value );
		$value = str_replace( chr(92), "", $value );
		$value = str_replace( chr(0), "", $value );
		$value = str_replace( '(', "", $value );
		$value = str_replace( ')', "", $value );
		$value = str_ireplace( "base64_decode", "base64_dec&#111;de", $value );

		$name = str_replace( "$", "&#036;", $name );
		$name = str_replace( "{", "&#123;", $name );
		$name = str_replace( "}", "&#125;", $name );
		$name = str_replace( ".", "", $name );
		$name = str_replace( '/', "", $name );
		$name = str_replace( chr(92), "", $name );
		$name = str_replace( chr(0), "", $name );
		$name = str_replace( '(', "", $name );
		$name = str_replace( ')', "", $name );
		$name = str_ireplace( "base64_decode", "base64_dec&#111;de", $name );

		fwrite( $handler, "'{$name}' => '{$value}',\n\n" );

	}
	fwrite( $handler, ");\n\n?>" );
	fclose( $handler );

	clear_cache();
	msg( "info", $lang['opt_sysok'], $lang['opt_sysok_1'], "$PHP_SELF?mod=ulogin" );


}


//--------------------------------------------------------------

echoheader( "<i class=\"icon-user\"></i>".$ulogin_lang['opt_ulogin'], $ulogin_lang['opt_uloginc'] );

echo "
	<div class=\"well relative\">
		<span class=\"triangle-button green\"><i class=\"icon-bell\"></i></span>
		{$ulogin_lang['admin_ulogin_title_explain']}
	</div>
	";

echo <<<HTML
	<form action="$PHP_SELF?mod=ulogin&action=save" name="conf" id="conf" method="post">
		<div class="box">
			<div class="box-header">
				<div class="title">{$ulogin_lang['admin_ulogin_title']}</div>
			</div>
			<div class="box-content">
				<table class="table table-normal">
HTML;


showRow( $ulogin_lang['admin_uloginid'], $ulogin_lang['admin_uloginid_explain'], "<input type=text name=\"save_con[uloginid]\" value=\"{$ulogin_config['uloginid']}\" maxlength=\"8\">" );
showRow( $ulogin_lang['admin_uloginid_profile'], $ulogin_lang['admin_uloginid_profile_explain'], "<input type=text name=\"save_con[uloginid_profile]\" value=\"{$ulogin_config['uloginid_profile']}\" maxlength=\"8\">" );
showRow( $ulogin_lang['admin_ulogin_group_id'], $ulogin_lang['admin_ulogin_group_id_explain'], makeDropDownGroups("save_con[ulogin_group_id]", "{$ulogin_config['ulogin_group_id']}" ) );


echo <<<HTML
				</table>
			</div>
		</div>
		<div style="margin-bottom:30px;">
			<input type="hidden" name="user_hash" value="{$dle_login_hash}" />
			<input type="submit" class="btn btn-green" value="{$lang['user_save']}">
		</div>
	</form>
HTML;


	if(!is_writable(ENGINE_DIR . '/data/uloginconfig.php') && file_exists(ENGINE_DIR . '/data/uloginconfig.php')) {
		$lang['stat_system'] = str_replace ("{file}", "engine/data/uloginconfig.php", $lang['stat_system']);
		echo "<div class=\"alert alert-error\">{$lang['stat_system']}</div>";
	}

echofooter();
?>