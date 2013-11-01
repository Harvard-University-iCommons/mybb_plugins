<?php
/**
 * MyBB 1.6 
 * Copyright 2010 MyBB Group, All Rights Reserved
 *
 * Website: http://mybb.com
 * License: http://mybb.com/about/license
 *
 * $Id: hello.php 5754 2012-03-09 14:58:03Z Tomm $
 */ 
 
require_once MYBB_ROOT."inc/plugins/inc/icommonsapi.php";
 
// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}


$plugins->add_hook('admin_user_groups_add', 'icgroups_admin_user_groups_add');
$plugins->add_hook('admin_page_output_nav_tabs_start', 'icgroups_page_output_nav_tabs_start');
$plugins->add_hook('admin_user_groups_begin', 'icgroups_admin_user_groups_begin');

function icgroups_info() {
	global $lang;
	$lang->load("icgroups");
	return array(
		"name"			=> $lang->plugin_title,
		"description"	=> $lang->plugin_description,
		"website"		=> "https://isites.harvard.edu/groups",
		"author"		=> "Eric Parker",
		"authorsite"	=> "",
		"version"		=> "1.0",
		"guid" 			=> "",
		"compatibility" => "*"
	);
}

function icgroups_install() {
	global $db;
	
	if(!$db->field_exists("externalgid", "usergroups")) {
		$db->write_query("ALTER TABLE " . TABLE_PREFIX . "usergroups ADD externalgid varchar(10)");
	}
}

function icgroups_is_installed() {
	global $db;
	
	return $db->field_exists("externalgid", "usergroups");
}

function icgroups_uninstall() {
	global $db;
	
	if($db->field_exists("externalgid", "usergroups")) {
		$db->write_query("ALTER TABLE " . TABLE_PREFIX . "usergroups DROP externalgid");
	}
}

function icgroups_admin_user_groups_begin() {
	global $mybb, $lang, $page, $plugins;
	
	$plugins->run_hooks("admin_user_groups_add");
	
	$lang->load("icgroups");
	
	if($mybb->input['action'] == 'add_isites_group'){
	
		$sub_tabs['manage_groups'] = array(
			'title' => $lang->manage_user_groups,
			'link' => "index.php?module=user-groups",
			'description' => $lang->manage_user_groups_desc
		);
		$sub_tabs['add_group'] = array(
			'title' => $lang->add_user_group,
			'link' => "index.php?module=user-groups&amp;action=add",
			'description' => $lang->add_user_group_desc
		);

    	$page->add_breadcrumb_item($lang->find_isites_groups);
		$page->output_header($lang->find_isites_groups);
    	$page->output_nav_tabs($sub_tabs, 'add_isites_group');
    	
    	include MYBB_ROOT.'inc/plugins/inc/isitesgroups.php';
		echo $str;
    	
    	$page->output_footer();
    	
    }
    error_log('fired -> icgroups_admin_user_groups_begin: '.$mybb->input['action']);
}

function icgroups_admin_user_groups_add() {
	global $page, $mybb, $db, $usergroup_permissions, $plugins, $cache, $lang;
	
	$lang->load("icgroups");
		
	if(isset($mybb->input['selectedgroups'])) {	
		$groups = $mybb->input['selectedgroups'];
		
		
		foreach($groups as $group){
			
			// get the group info from the icommonsapi
			$groupinfo = getGroupInfo($group);
			
			// make sure we got a response otherwise create an error
			if(!trim($groupinfo->{'group'}->name))
			{
				$errors[] = $lang->error_missing_title;
			}
			
			// if there are no error, go ahead and try to add the group
			if(!$errors) {
			
				error_log('Name: '. $groupinfo->{'group'}->name);
				$new_usergroup = array(
					"type" => 2,
					"title" => $db->escape_string($groupinfo->{'group'}->name),
					"description" => $db->escape_string($groupinfo->{'group'}->description),
					"namestyle" => "{username}",
					"externalgid" => $db->escape_string($group),
					"disporder" => 0
				);
			
				// create the group with the default mybb group permissions
				$new_usergroup = array_merge($new_usergroup, $usergroup_permissions);
				$plugins->run_hooks("admin_user_groups_add_commit");
				
				// check to see if the group already exists
				$query = $db->simple_select("usergroups", "*", "externalgid='".$group."'");
				//$usergroup = $db->fetch_array($query);
				
				// if the group does not exist, insert the group into the usergroups table
				if($db->num_rows($query) == 0){
			
					$gid = $db->insert_query("usergroups", $new_usergroup);
			
					// Update the caches
					$cache->update_usergroups();
					$cache->update_forumpermissions();

					// Log admin action
					log_admin_action($gid, $groupinfo->{'group'}->name);
				}
			}
			else {
				$page->output_inline_error($errors);
			}
		}
		admin_redirect("index.php?module=user-groups");	
	}
}

function icgroups_page_output_nav_tabs_start(&$tabs) {	
	global $db, $mybb, $lang, $page;
    $lang->load("icgroups");	
    
	if($mybb->input['module'] == 'user-groups') {    
		
		$tabs['add_isites_group'] = array(
			'title' => $lang->find_isites_groups,
			'link' => "index.php?module=user-groups&amp;action=add_isites_group",
			'description' => $lang->add_isites_groups_desc
		);
		
		$tabs['create_isites_group'] = array(
			'title' => $lang->create_isites_groups,
			'link' => 'https://isites.harvard.edu/groups',
			'link_target' => 'new',
			'description' => $lang->create_isites_groups
		);	          
	}
}


/**
 * ADDITIONAL PLUGIN INSTALL/UNINSTALL ROUTINES
 *
 *
 * function icgroups_activate()
 * {
 * }
 *
 *
 * function icgroups_deactivate()
 * {
 * }
 */

?>
