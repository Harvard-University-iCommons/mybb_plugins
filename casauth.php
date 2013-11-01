<?php
/**
 * IcyCAS 1.0
 * Copyright 2012 IcyNetwork, All Rights Reserved
 *
 * Website: http://icyboards.com
 */
 
/*
 List of Templates modified for CAS auth. This is optional, but it makes it look better and
 removes choices from end users that would be confusing because we are not using the built in method 
 of creating user accounts. 
 
 header
 header_welcomeblock_guest
 header_welcomeblock_member
 usercp_nav_editsignature
 usercp_nav_profile
 usercp_nav_misc
 
 */
 
session_start();
define("IN_MYBB", 1);
define("THIS_SCRIPT", "casauth.php");

require_once MYBB_ROOT."global.php";
require_once MYBB_ROOT."inc/plugins/inc/icommonsapi.php";

if(!$db->field_exists("externalid", "users")) die("Error: Plugin Not Enable");

$lang->load("member");
$lang->load("icycas");

// We must be trying to log in
if(isset($_GET['ticket'])) {

	$validateURL = $mybb->settings['icycasvalidateurl'] . "?service=" . urlencode($mybb->settings['bburl'] . "/casauth.php") . "&ticket=" . $_GET['ticket'];
	$file = file_get_contents($validateURL);

	if(strpos($file, "<cas:authenticationSuccess>") > 0) {
		preg_match_all("/<cas:user>(.*)<\/cas:user>/", $file, $externalid);
		
		$externalid = $externalid[1][0];
		$userinfo = getUserInfo($externalid);
	    $useremail = getUserEmail($userinfo);
	    $userfullname = getUserFullName($userinfo);
	    $encryptedid = createusernamefromexternalid($externalid);
	    
		$_SESSION['useremail'] = $useremail;
		$_SESSION['userfullname'] = $userfullname;
		$_SESSION['encryptedid'] = $encryptedid;
		$_SESSION['externalid'] = $externalid;
		
		$user_query = $db->simple_select("userlookup", "userid", "LOWER(externalid)='" . $db->escape_string(strtolower($encryptedid)) . "'");
		$userid = $db->fetch_field($user_query, "userid");
		
		$query = $db->simple_select("users", "*", "LOWER(username)='$userid'");
				
		// check to see if the user already has an account in myBB
		if($db->num_rows($query) == 0) {
			error_log('user does not exist');
			
			// if the user does not have an account, let's create one
			if($mybb->settings['icycascreatenewaccounts']) {
					
					require_once MYBB_ROOT."inc/datahandlers/user.php";		
					if(!isset($mybb->input['username'])) icycas_member_login();
					if($mybb->settings['icycascreatenewaccounts'] == "no") icycas_member_login();
	
					$userhandler = new UserDataHandler("insert");
					$password = random_str();

					// add the new user to the userlookup table
					$sql = "INSERT INTO ".TABLE_PREFIX."userlookup (externalid) values('".$encryptedid."');";
					$db->write_query($sql);
					
					// get the new userid from the userlookup table, this will be the username in mybb.
					$user_query = $db->simple_select("userlookup", "userid", "LOWER(externalid)='" . $db->escape_string(strtolower($encryptedid)) . "'");
					$userid = $db->fetch_field($user_query, "userid");
					
					print_r('userid='.$userid.'<br />');
					
					$user = array(
						"username" => $userid, 
						"usertitle" => $userfullname,
						"password" => $password,
						"password2" => $password,
						"email" => $useremail,
						"email2" => $useremail,
						"usergroup" => (int)$mybb->settings['icycasnewaccountgid'],
						"referrer" => "",
						"timezone" => 0,
						"language" => "",
						"profile_fields" => "",
						"regip" => $session->ipaddress,
						"longregip" => my_ip2long($session->ipaddress),
						"coppa_user" => 0
					);
					
					$option_value = 0;
					$user['options'] = array(
						"allownotices" => $option_value,
						"hideemail" => $option_value,
						"subscriptionmethod" => $option_value,
						"receivepms" => $option_value,
						"pmnotice" => $option_value,
						"emailpmnotify" => $option_value,
						"invisible" => $option_value,
						"dstcorrection" => $option_value
					);
	
					$userhandler->set_data($user);
					if(!$userhandler->validate_user()) {
						$regerrors = inline_error($userhandler->get_friendly_errors());
						print_r('Hello -> '.$regerrors. '<br />');
					} else {
						$user_info = $userhandler->insert_user();
						// Save the external id and try the CAS request again...
						$db->update_query("users", array("externalid" => $db->escape_string($encryptedid)), "username='" . $db->escape_string($mybb->input['username']) . "'");
						icycas_member_login();
					}
				
			} else {
				error($lang->cas_no_matching_accounts);
			}
			
		} else {
			$user = $db->fetch_array($query);
			
			$newsession = array("uid" => $user['uid']);
			$db->delete_query("sessions", "ip='" . $db->escape_string($session->ipaddress) . "' AND sid != '" . $session->sid . "'");
			$db->update_query("sessions", $newsession, "sid='" . $session->sid . "'");
			$db->update_query("users", array("loginattempts" => 1), "uid='" . $user['uid'] . "'");
			
			my_setcookie("mybbuser", $user['uid'] . "_" . $user['loginkey'], -1, true);
			my_setcookie("sid", $session->sid, -1, true);
			
			// all myBB users have one primary group which should be set to "registered user -> 2". 
			// Users can also secondary groups.
					
			$group_query = $db->query("select gid, externalgid from mybb_usergroups where length(externalgid) > 0");
						
			$mybb_groups = array();
			while($group = $db->fetch_array($group_query)) {
				$mybb_groups[$group['externalgid']] = $group['gid'];	
			}
			
			$user_isites_groups = getUserGroups($externalid);
			$user_local_groups = array();
			foreach($user_isites_groups as $id => $value){
				if($mybb_groups[$id] > 0){
					array_push($user_local_groups, $mybb_groups[$id]);
				}
				
			}
			$additionalgroups = implode(",", $user_local_groups);
			
			$db->update_query("users", array("usertitle" => $userfullname), "uid='" . $user['uid'] . "'");
			
			// delete all users groups
			$db->update_query("users", array("additionalgroups" => ""), "uid='" . $user['uid'] . "'");
			
			// add new groups to user
			$db->update_query("users", array("additionalgroups" => $additionalgroups), "uid='" . $user['uid'] . "'");
			
			redirect("index.php", $lang->redirect_loggedin);
		}
	} else {
	
		error($lang->cas_ticket_validate_error);
	
	}
	
} else {

	icycas_member_login();

}
?>