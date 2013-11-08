<?php
/**
 * IcyCAS 1.0
 * Copyright 2012 IcyNetwork, All Rights Reserved
 *
 * Website: http://icyboards.com
 */
 
if(!defined("IN_MYBB")) 
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");

$plugins->add_hook("member_do_lostpw_start", "icycas_member_do_lostpw_start");
$plugins->add_hook("member_do_login_start", "icycas_member_do_login_start");
$plugins->add_hook("admin_formcontainer_end", "icycas_admin_formcontainer_end");
$plugins->add_hook("admin_user_users_edit_commit", "icycas_admin_user_users_edit_commit");
$plugins->add_hook('admin_user_users_delete', 'icycas_admin_user_users_delete_commit');
$plugins->add_hook('member_logout_end', 'icycas_member_logout_end');
$plugins->add_hook("pre_output_page", "icycas_pre_output_page");

function icycas_info() {
	global $lang;
	
	return array(
		"name"          => "IcyCAS",
		"description"   => "CAS Authentication for your MyBB forum. For free support or to provide feedback, please visit <a href='http://forums.icyboards.net/forumdisplay.php?fid=59'>our forum</a>.",
		"website"       => "http://icyboards.com",
		"author"        => "spork985",
		"authorsite"    => "http://icyboards.com",
		"version"       => "1.0",
		"guid"          => "b9d86b2a744f78e980a61aaacc63e2eb",
		"compatibility" => "16*",
	);
}

function icycas_activate() {	
	global $db, $mybb, $lang;
	
	$lang->load("icycas");
	
	$rows = $db->fetch_field($db->simple_select("settinggroups", "COUNT(*) as rows"), "rows");
	
	$insertarray = array(
		'name' => 'icycas',
		'title' => 'IcyCAS',
		'description' => 'Settings for your CAS integration',
		'disporder' => $rows+1,
		'isdefault' => 0);
	$group['gid'] = $db->insert_query("settinggroups", $insertarray);
	
	$insertarray = array(
		'name' => 'icycasloginurl',
		'title' => $lang->cas_settings_01_title,
		'description' => $lang->cas_settings_01_descr,
		'optionscode' => 'text',
		'value' => 'https://yourdomain.com/cas/login',
		'disporder' => 1,
		'gid' => $group['gid']);
	$db->insert_query("settings", $insertarray);
	
	$insertarray = array(
		'name' => 'icycasvalidateurl',
		'title' => $lang->cas_settings_02_title,
		'description' => $lang->cas_settings_02_descr,
		'optionscode' => 'text',
		'value' => 'http://yourdomain.com/cas/serviceValidate',
		'disporder' => 2,
		'gid' => $group['gid']);
	$db->insert_query("settings", $insertarray);
	
	$insertarray = array(
		'name' => 'icycascreatenewaccounts',
		'title' => $lang->cas_settings_03_title,
		'description' => $lang->cas_settings_03_descr,
		'optionscode' => 'yesno',
		'value' => 'yes',
		'disporder' => 3,
		'gid' => $group['gid']);
	$db->insert_query("settings", $insertarray);
	
	$insertarray = array(
		'name' => 'icycasnewaccountgid',
		'title' => $lang->cas_settings_04_title,
		'description' => $lang->cas_settings_04_descr,
		'optionscode' => 'text',
		'value' => '5',
		'disporder' => 4,
		'gid' => $group['gid']);
	$db->insert_query("settings", $insertarray);
	
	$insert_array = array(
		'title' => 'member_cas_signup',
		'template' => $db->escape_string("<html>
			<head>
			<title>{\$mybb->settings['bbname']} - {\$lang->registration}</title>
			{\$headerinclude}
			<script type='text/javascript' src='jscripts/validator.js'></script>
			</head>
			<body>
			{\$header}
			<br />
			<form action='casauth.php' method='post' id='registration_form'>
			{\$regerrors}
			<table border='0' cellspacing='{\$theme['borderwidth']}' cellpadding='{\$theme['tablespace']}' class='tborder'>
			<tr>
			<td class='thead' colspan='2'><strong>{\$lang->registration}</strong></td>
			</tr>
			<tr>
			<td width='50%' class='trow1' valign='top'>
			<fieldset class='trow2'>
			<legend><strong>{\$lang->account_details}</strong></legend>
			<table cellspacing='0' cellpadding='{\$theme['tablespace']}' width='100%'>
			<tr>
			<td colspan='2'><span class='smalltext'><label for='externalid'>External ID</label></span></td>
			</tr>
			<tr>
			<td colspan='2'><input type='text' class='textbox' name='externalid' id='externalid' style='width: 100%' value='{\$externalid}' disabled='disabled' /></td>
			</tr>
			<tr>
			<td colspan='2'><span class='smalltext'><label for='username'>Username</label></span></td>
			</tr>
			<tr>
			<td colspan='2'><input type='text' class='textbox' name='username' id='username' style='width: 100%' value='' /></td>
			</tr>
			<tr>
			<td><span class='smalltext'><label for='email'>{\$lang->email}</label></span></td>
			<td><span class='smalltext'><label for='email2'>{\$lang->confirm_email}</label></span></td>
			</tr>
			<tr>
			<td><input type='text' class='textbox' name='email' id='email' style='width: 100%' maxlength='50' value='{\$email}' /></td>
			<td><input type='text' class='textbox' name='email2' id='email2' style='width: 100%' maxlength='50' value='{\$email2}' /></td>
			</tr>
			<tr>
				<td colspan='2' style='display: none;' id='email_status'>&nbsp;</td>
			</tr>
			</table>
			</fieldset>
			</td>
			<td width='50%' class='trow1' valign='top'>
			<fieldset class='trow2'>
			<legend><strong>{\$lang->account_prefs}</strong></legend>
			<table cellspacing='0' cellpadding='{\$theme['tablespace']}' width='100%'>
			<tr>
			<td valign='top' width='1'><input type='checkbox' class='checkbox' name='allownotices' id='allownotices' value='1' {\$allownoticescheck} /></td>
			<td valign='top'><span class='smalltext'><label for='allownotices'>{\$lang->allow_notices}</label></span></td>
			</tr>
			<tr>
			<td valign='top' width='1'><input type='checkbox' class='checkbox' name='hideemail' id='hideemail' value='1' {\$hideemailcheck} /></td>
			<td valign='top'><span class='smalltext'><label for='hideemail'>{\$lang->hide_email}</label></span></td>
			</tr>
			<tr>
			<td valign='top' width='1'><input type='checkbox' class='checkbox' name='receivepms' id='receivepms' value='1' {\$receivepmscheck} /></td>
			<td valign='top'><span class='smalltext'><label for='receivepms'>{\$lang->receive_pms}</label></span></td>
			</tr>
			<tr>
			<td valign='top' width='1'><input type='checkbox' class='checkbox' name='pmnotice' id='pmnotice' value='1'{\$pmnoticecheck} /></td>
			<td valign='top'><span class='smalltext'><label for='pmnotice'>{\$lang->pm_notice}</label></span></td>
			</tr>
			<tr>
			<td valign='top' width='1'><input type='checkbox' class='checkbox' name='emailpmnotify' id='emailpmnotify' value='1' {\$emailpmnotifycheck} /></td>
			<td valign='top'><span class='smalltext'><label for='emailpmnotify'>{\$lang->email_notify_newpm}</label></span></td>
			</tr>
			<tr>
			<td valign='top' width='1'><input type='checkbox' class='checkbox' name='invisible' id='invisible' value='1' {\$invisiblecheck} /></td>
			<td valign='top'><span class='smalltext'><label for='invisible'>{\$lang->invisible_mode}</label></span></td>
			</tr>
			<tr>
			<td colspan='2'><span class='smalltext'><label for='subscriptionmethod'>{\$lang->subscription_method}</label></span></td>
			</tr>
			<tr>
			<td colspan='2'>
				<select name='subscriptionmethod' id='subscriptionmethod'>
					<option value='0' {\$no_subscribe_selected}>{\$lang->no_auto_subscribe}</option>
					<option value='1' {\$no_email_subscribe_selected}>{\$lang->no_email_subscribe}</option>
					<option value='2' {\$instant_email_subscribe_selected}>{\$lang->instant_email_subscribe}</option>
				</select>
			</td>
			</tr>

			</table>
			</fieldset>
			</td>
			</tr>
			</table>
			<br />
			<div align='center'>
			<input type='hidden' name='step' value='registration' />
			<input type='hidden' name='action' value='do_register' />
			<input type='submit' class='button' name='regsubmit' value='{\$lang->submit_registration}' />
			</div>
			</form>
			<script type='text/javascript'>
			<!--
				regValidator = new FormValidator('registration_form');
				regValidator.register('username', 'notEmpty', {failure_message:'{\$lang->js_validator_no_username}'});
				regValidator.register('email', 'regexp', {match_field:'email2', regexp:'^([a-zA-Z0-9_\\.\\+\\-])+\\@(([a-zA-Z0-9\\-])+\\.)+([a-zA-Z0-9]{2,4})+$', failure_message:'{\$lang->js_validator_invalid_email}'});
				regValidator.register('email2', 'matches', {match_field:'email', status_field:'email_status', failure_message:'{\$lang->js_validator_email_match}'});
			{\$validator_extra}
				regValidator.register('username', 'ajax', {url:'xmlhttp.php?action=username_availability', loading_message:'{\$lang->js_validator_checking_username}'}); // needs to be last
			// -->
			</script>
			{\$footer}
			</body>
			</html>"),
		'sid' => '-1',
		'version' => '',
		'dateline' => time());
	$db->insert_query("templates", $insert_array);
	
	rebuild_settings();
}

function icycas_deactivate() {
	global $db;
		
	$db->delete_query("settings", "name in ('icycasloginurl','icycasvalidateurl','icycascreatenewaccounts','icycasnewaccountgid')");
	$db->delete_query("settinggroups", "name='icycas'");
	$db->delete_query("templates","title='member_cas_signup'");
	
	rebuild_settings();
}

function icycas_install() {
	global $db;
	
	if(!$db->field_exists("externalid", "users"))
		$db->write_query("ALTER TABLE " . TABLE_PREFIX . "users ADD externalid varchar(100)");
	
	if($db->table_exists(TABLE_PREFIX."userlookup")) {
	
		$db->drop_table(TABLE_PREFIX."userlookup");
	
		$db->write_query("CREATE TABLE ".TABLE_PREFIX."userlookup (
		  userid int unsigned NOT NULL auto_increment,
		  externalid varchar(120) NOT NULL default '',
		  PRIMARY KEY  (userid),
		  UNIQUE (externalid)
		);");
		
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."userlookup AUTO_INCREMENT=101;");
		
	}
}

function icycas_uninstall() {
	global $db;
	/*
	if($db->field_exists("externalid", "users"))
		$db->write_query("ALTER TABLE " . TABLE_PREFIX . "users DROP externalid");
	
	if($db->table_exists(TABLE_PREFIX."userlookup"))
		$db->drop_table(TABLE_PREFIX."userlookup");
	*/
}

function icycas_is_installed() {
	global $db;
	
	return $db->field_exists("externalid", "users");
}

function icycas_member_login() {
	global $mybb;
	
	$loginURL = $mybb->settings['icycasloginurl'] . "?service=" . urlencode($mybb->settings['bburl'] . "/casauth.php");
	header("Location: " . $loginURL);
	
	exit();
}

function icycas_member_do_lostpw_start() {
	global $db, $mybb, $lang;
	
	$lang->load("icycas");
	
	$query = $db->simple_select("users", "*", "lower(email)='" . $db->escape_string(strtolower($mybb->input['email'])) . "' AND externalid is not null");

	if($db->num_rows($query) > 0)
		error($lang->cas_no_login_external_id);
}	

function icycas_member_do_login_start() {
	global $db, $mybb;
	
	$query = $db->simple_select("users", "uid", "lower(username)='" . $db->escape_string(strtolower($mybb->input['username'])) . "' AND externalid is not null");

	if($db->num_rows($query) > 0)
		icycas_member_login();
}

function icycas_admin_formcontainer_end() {
	global $mybb, $form, $form_container, $db, $lang;
		
	$lang->load("icycas");
	
	if(strpos($form_container->_title, "equired Profile Information")) {
		$externalid = "";
		$query = $db->simple_select("users", "externalid", "uid=" . (int)$mybb->input['uid']);
		
		if($db->num_rows($query) > 0) {
			$result = $db->fetch_array($query);
			$externalid = $result['externalid'];
		}
		
		$form_container->output_row($lang->cas_user_edit_01_title, $lang->cas_user_edit_01_descr, $form->generate_text_box("casexternalid", $externalid));					
	}
}

function icycas_admin_user_users_edit_commit() {
	global $mybb, $db;
	
	$externalid = $db->escape_string(trim($mybb->input['casexternalid']));
	$db->update_query("users", array("externalid" => $externalid), "uid=" . (int)$mybb->input['uid']);	
}

function icycas_admin_user_users_delete_commit() {
	global $mybb, $db;
	
	$query = $db->simple_select("users", "*", "uid='".intval($mybb->input['uid'])."'");
	if($db->num_rows($query) > 0) {
		$user = $db->fetch_array($query);
		$db->delete_query("userlookup", "userid='{$user['username']}'");
		log_admin_action("User {$user['displayname']} deleted.");
	}
}

function icycas_member_logout_end() {
	redirect($_SERVER['CAS_LOGOUT_URL']);
}


function convert_username_to_displayname($matchpattern, $beginpattern, $endpattern, $beginout, $endout, $page){
	global $db;
	
	preg_match_all("/".$matchpattern."/", $page, $matches);
	
	$userids = $matches[1];
	$patterns = array();
	$usertitles = array();
	foreach($userids as $key=>$id){
		$patterns[$key] = "/".$beginpattern.$id .$endpattern."/";
		$query = $db->simple_select("users", "displayname", "username=" . (int)$id);
		$result = $db->fetch_array($query);
		$displayname = $result['displayname'];
		$usertitles[$key] = $beginout.$displayname.$endout;
	}
	
	$page = preg_replace($patterns, $usertitles, $page);
	
	unset($patterns);
	unset($usertitles);
	
	return $page;
	
}

/* Replace the users username on the page with their display name.
 * usernames are 3 digit number such as 110, 117, 125, etc... we don't want to show those
 * so we replace them with the users display name.
 */
function icycas_pre_output_page($page){
	
	$page = convert_username_to_displayname("<a href=\"[^\"]*member.php\?action=profile[^\"]*\">(\d{3,4})\<\/a>", "\>", "\<\/a\>", ">", "</a>", $page );
    $page = convert_username_to_displayname("class=\"active\">Profile of (\d{3,4})\<\/span>", "class=\"active\"\>Profile of ", "\<\/span\>", "class=\"active\">Profile of ", "</span>", $page );
    $page = convert_username_to_displayname("<strong>(\d{3,4})\'s[^\"]*\<\/strong\>", "\<strong\>", "\'s", "<strong>", "'s", $page );
    $page = convert_username_to_displayname("<strong>Additional Info About (\d{3,4})\<\/strong>", "<strong>Additional Info About ", "\<\/strong\>", "<strong>Additional Info About ", "</strong>", $page );

	return $page;
}


?>