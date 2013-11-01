<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$key = $_SERVER['KEY'];
$key2 = $_SERVER['KEY2'];
$apiuser = $_SERVER['API_USER'];
$apisecret = $_SERVER['API_SECRET'];
$apibaseurl = $_SERVER['API_BASE_URL'];

function CallAPI($url, $data = false) {
    $apiconnectstring = $_SERVER['API_USER'] . ':' . $_SERVER['API_SECRET'];
    $curl = curl_init();
    // Optional Authentication:
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_USERPWD, $apiconnectstring);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec($curl);
    return $result;
}

/* 
	Geturn a json string of user information from the icommonsapi.
   	input: userid
   	return: json string
   	The json string is in the form:
   		{"people":[{"id":"","unknown":false,"firstName":"John","lastName":"Smith","email":"","phone":"","schoolAffiliations":[],"personAffiliation":"","departmentAffiliation":""}]}
*/
function getUserInfo($login){
	$url = $_SERVER['API_BASE_URL'].'/people/by_id/'.$login.'.json';
	$result = CallAPI($url);
	return json_decode($result);
}

/* 
	Get a list of the users groups. These are all groups in which the user is a member.
   	input: userid
   	return: array
   	The array is in the form: "groupid" => "group title" 
   	Example:  "IcGroup:1736" => "All Users"
*/
function getUserGroups($login){
	$url = $_SERVER['API_BASE_URL'].'/groups/membership_by_user/'.$login.'.json';
	$result = CallAPI($url);
	$groupsJSONArray = json_decode($result, true);
	$groupsArrayList = $groupsJSONArray['groups'];
	$data = array();
	foreach($groupsArrayList as $group){
		$data[htmlspecialchars($group['idType'], ENT_QUOTES).':'.htmlspecialchars($group['idValue'], ENT_QUOTES)] = htmlspecialchars($group['name'], ENT_QUOTES);
	}
	return $data;
}

// returns the raw data set of the users admin groups
function getUserAdminGroups($login){
	$url = $_SERVER['API_BASE_URL'].'/groups/administration_by_user/'.$login.'.json';
	$result = CallAPI($url);
	return $result;
}

/* 
	Returns a json string of group informaiton
	input: groupid
	result: json string
	The json string is in the form:
		{"group":{"name":"the name of the group","description":"a description of the group","idType":"","idValue":""}}	
*/
function getGroupInfo($groupid){
	$url = $_SERVER['API_BASE_URL'].'/groups/by_id/'.$groupid.'.json';
	$result = CallAPI($url);
	return json_decode($result);
}

/* 
	Returns an array of the users admin groups
   	The array is in the form: "groupid" => "group title" 
   	Example:  "IcGroup:1736" => "All Users"
*/
function getUserAdminGroupsArray($login){
	$result = getUserAdminGroups($login);
	$groupsJSONArray = json_decode($result, true);
	$groupsArrayList = $groupsJSONArray['groups'];
	$data = array();

	foreach($groupsArrayList as $group){
		$data[htmlspecialchars($group['idType'], ENT_QUOTES).':'.htmlspecialchars($group['idValue'], ENT_QUOTES)] = htmlspecialchars($group['name'], ENT_QUOTES);
	}
	
	return $data;
}


function getUserEmail($userInfo) {
    return $userInfo->{'people'}[0]->email ;
}

function getUserFullName($userInfo) {
    return $fullname = $userInfo->{'people'}[0]->firstName . ' ' .$userInfo->{'people'}[0]->lastName;
}

function createusernamefromexternalid($externalid){
	// value is base64 encoded when we get it here so we need to 
	// decode before encrypting. 
	$secret = base64_decode($_SERVER['KEY2']);
	return base64_encode(crypt($externalid, $secret));
}

?>
