<?php
/**
 * IcyCAS 1.0
 * Copyright 2012 IcyNetwork, All Rights Reserved
 *
 * Website: http://icyboards.com
 */
 
 /*
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

require_once "global.php";
require_once MYBB_ROOT."inc/plugins/inc/icommonsapi.php";

if(!$db->field_exists("externalid", "users")) die("Error: Plugin Not Enable");

$lang->load("member");
$lang->load("icycas");

//print_r(var_dump($_SERVER));

// We must be trying to log in
if(isset($_POST['username']) || isset($_GET['username'])) {

	//$validateURL = $mybb->settings['icycasvalidateurl'] . "?service=" . urlencode($mybb->settings['bburl'] . "/casauth.php") . "&ticket=" . $_GET['ticket'];
	//$file = file_get_contents($validateURL);

	//if(strpos($file, "<cas:authenticationSuccess>") > 0) {
		//preg_match_all("/<cas:user>(.*)<\/cas:user>/", $file, $externalid);
		
		if(isset($_POST['username'])) {
		$externalid = $_POST['username'];
		}
		
		if(isset($_GET['username'])) {
		$externalid = $_GET['username'];
		}
		$userinfo = getUserInfo($externalid);
		//print_r('userinfo='.$userinfo);
		
	    $useremail = getUserEmail($userinfo);
	    $userfullname = getUserFullName($userinfo);
	    $encryptedid = createusernamefromexternalid($externalid);
	    print_r('user='.$externalid.'<br />');
	    print_r('email='.$useremail.'<br />');
	    
		//$_SESSION['useremail'] = $useremail;
		//$_SESSION['userfullname'] = $userfullname;
		//$_SESSION['encryptedid'] = $encryptedid;
		//$_SESSION['externalid'] = $externalid;
		
		//error_log($externalid . ' : ' . $encryptedid);
		
		// query to look in the database to see if the user already has an account
		
		//$sql = "SELECT iserid FROM ".TABLE_PREFIX."userlookup where externalid='".strtolower($encryptedid)."'";
		$user_query = $db->simple_select("userlookup", "userid", "LOWER(externalid)='" . $db->escape_string(strtolower($encryptedid)) . "'");
		$userid = $db->fetch_field($user_query, "userid");
		$query = $db->simple_select("users", "*", "LOWER(username)='$userid'");
		
		//$query = $db->simple_select("userlookup", "*", "LOWER(externalid)='" . $db->escape_string(strtolower($externalid)) . "'");
		
		//$sql = "SELECT * FROM ".TABLE_PREFIX."userlookup where externalid='".strtolower($encryptedid)."'";
		//error_log($sql);
		
		//$query = $db->query($sql);
		
		print_r("count=".$db->num_rows($query).'<br />');
		
		// check to see if the user already has an account in myBB
		if($db->num_rows($query) == 0) {
			
			print_r('user does not exist<br />');
			
			// if the user does not have an account, let's create one
			if($mybb->settings['icycascreatenewaccounts']) {
			
				//eval("\$casregister = \"".$templates->get("member_cas_signup")."\";");
				//output_page($casregister);
				
				/**************************************************/
				
					require_once MYBB_ROOT."inc/datahandlers/user.php";
					print_r('username='.$mybb->input['username'].'<br />');
					if(!isset($mybb->input['username'])) icycas_member_login();
					if($mybb->settings['icycascreatenewaccounts'] == "no") icycas_member_login();
	
					//print_r(var_dump($_SESSION));
	
					$userhandler = new UserDataHandler("insert");
					$password = random_str();
	
					//$encryptedid = $_SESSION['encryptedid'];
					
					print_r('From Session: '.$encryptedid.'<br />');
					$sql = "INSERT INTO ".TABLE_PREFIX."userlookup (externalid) values('".$encryptedid."');";
					
					print_r($sql.'<br />');
					
					$db->write_query($sql);
					
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
					print_r('here<br />');
					if(!$userhandler->validate_user()) {
		
						$regerrors = inline_error($userhandler->get_friendly_errors());
						print_r('Hello -> '.$regerrors. '<br />');
						//$externalid = $_SESSION['encryptedid'];
						//eval("\$casregister = \"".$templates->get("member_cas_signup")."\";");
						//output_page($casregister);
					} else {
						$user_info = $userhandler->insert_user();
						print_r('user inserted...<br />');
						// Save the external id and try the CAS request again...
						$db->update_query("users", array("externalid" => $db->escape_string($encryptedid)), "username='" . $db->escape_string($mybb->input['username']) . "'");
						redirect("casauth.php?username=".$externalid);
					}
				
				
				
				/************************************************/
				
				
			
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
	//} else {
	//	error($lang->cas_ticket_validate_error);
	//}
}

// They must be registering
/*
elseif(isset($_POST['username'])) {
	require_once MYBB_ROOT."inc/datahandlers/user.php";
	error_log('username='.$mybb->input['username']);
	if(!isset($mybb->input['username'])) icycas_member_login();
	if($mybb->settings['icycascreatenewaccounts'] == "no") icycas_member_login();
	
	print_r(var_dump($_SESSION));
	
	$userhandler = new UserDataHandler("insert");
	$password = random_str();
	
	$externalid = $_SESSION['encryptedid'];
	error_log('From Session: '.$externalid);
	$sql = "INSERT INTO ".TABLE_PREFIX."userlookup (externalid) values('".$externalid."');";
	error_log($sql);
	$db->write_query($sql);
	$user_query = $db->simple_select("userlookup", "userid", "LOWER(externalid)='" . $db->escape_string(strtolower($externalid)) . "'");
	$userid = $db->fetch_field($user_query, "userid");
	
	$user = array(
		"username" => $userid, 
		"usertitle" => $_SESSION['userfullname'],
		"password" => $password,
		"password2" => $password,
		"email" => $mybb->input['email'],
		"email2" => $mybb->input['email2'],
		"usergroup" => (int)$mybb->settings['icycasnewaccountgid'],
		"referrer" => "",
		"timezone" => 0,
		"language" => "",
		"profile_fields" => "",
		"regip" => $session->ipaddress,
		"longregip" => my_ip2long($session->ipaddress),
		"coppa_user" => 0
	);
	
	$user['options'] = array(
		"allownotices" => $mybb->input['allownotices'],
		"hideemail" => $mybb->input['hideemail'],
		"subscriptionmethod" => $mybb->input['subscriptionmethod'],
		"receivepms" => $mybb->input['receivepms'],
		"pmnotice" => $mybb->input['pmnotice'],
		"emailpmnotify" => $mybb->input['emailpmnotify'],
		"invisible" => $mybb->input['invisible'],
		"dstcorrection" => $mybb->input['dstcorrection']
	);
	
	$userhandler->set_data($user);
	error_log('here');
	if(!$userhandler->validate_user()) {
	    
		$regerrors = inline_error($userhandler->get_friendly_errors());
		error_log('Hello -> '.$regerrors);
		$externalid = $_SESSION['encryptedid'];
		eval("\$casregister = \"".$templates->get("member_cas_signup")."\";");
		output_page($casregister);
	} else {
		$user_info = $userhandler->insert_user();
		error_log('user inserted...');
		// Save the external id and try the CAS request again...
		$db->update_query("users", array("externalid" => $db->escape_string($_SESSION['encryptedid'])), "username='" . $db->escape_string($mybb->input['username']) . "'");
		icycas_member_login();
	}
}
*/

else {
	//icycas_member_login();

$str = <<<EOD
<!DOCTYPE html>
<html>
<body>

<form action="http://discuss-dev.isites.harvard.edu/casauth.php" method="POST">
Username: <input type="text" name="username"><br>
<input type="submit" value="Submit">
</form>

</body>
</html>	
EOD;
	
	echo($str);
	}
?>


