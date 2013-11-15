<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// call the icommonsapi method to get a list of the users admin groups. these are groups which the user has admin access. 
$go = getUserAdminGroups($_SESSION['externalid']);

// output the list of groups and the group select form.
$str = <<<EOD
<link href="/inc/plugins/inc/groupmenu.css" type="text/css" rel="stylesheet">
<!--<link href="/inc/plugins/inc/hu-permissions.css" type="text/css" rel="stylesheet">-->
<script type="text/javascript"> var go = $go;</script>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>
<script src="/inc/plugins/inc/hugrouptabs.js" language="javascript" type="text/javascript"></script>

	<div id="groups-container">
		<div id="groups-main ">
			<div id="groups-content">
				<div id="hugrouplist">
				<!--<input id="manageGroups" type="button" name="create/manage groups" value="create/manage groups" >-->
					<form name="add_new_groups" action="index.php?module=user-groups&amp;action=add_isites_group" method="POST">
					<input type="hidden" name="my_post_key" value="'.$mybb->post_code.'" />
						<hr>
						<div id="hu-groups-tabs">
							<ul>
								<li><a href="#tabs-1">General</a></li>
								<li><a href="#tabs-2">LDAP</a></li>
								<li><a href="#tabs-3">Custom</a></li>
								<li><a href="#tabs-4">Course</a></li>
							</ul>
							<div id="tabs-1"></div>
							<div id="tabs-2"></div>
							<div id="tabs-3"></div>
							<div id="tabs-4"></div>
						</div>
						<input type="hidden" name="form_name" value="add_new_groups">
						<hr>
						<input type="submit" value="Add Selected Group(s)">
					</form>
				</div>
			</div>
		</div>
	</div>
EOD;

?>
