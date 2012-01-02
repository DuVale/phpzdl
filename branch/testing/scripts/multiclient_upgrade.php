<?php
/*****************************************************************************
*
*    License:
*
*   Copyright (c) 2007-2009 AlienVault
*   All rights reserved.
*
*   This package is free software; you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation; version 2 dated June, 1991.
*   You may not use, modify or distribute this program under any other version
*   of the GNU General Public License.
*
*   This package is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this package; if not, write to the Free Software
*   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
*   MA  02110-1301  USA
*
*
* On Debian GNU/Linux systems, the complete text of the GNU General
* Public License can be found in `/usr/share/common-licenses/GPL-2'.
*
* Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
****************************************************************************/
/**
* Class and Function List:
* Function list:
* Classes list:
*/
function insert_template($conn,$name,$nets,$sensors,$perms) {
	$params = array(
		$name,
		$nets,
		$sensors
	);
	$query = "INSERT INTO acl_templates (name,allowed_nets,allowed_sensors) VALUES (?,?,?)";
	
	if (!$res = & $conn->Execute($query,$params)) {
		return -1;
	} else {
		if ($id == "") {
			$res = $conn->query("SELECT LAST_INSERT_ID() as lastid");
			if ($rw = $res->fetchRow()) $id = $rw["lastid"];
			else return -1;
		}
		// Perms
		foreach ($perms as $perm_id=>$val) {
			$query = "INSERT INTO acl_templates_perms (ac_templates_id,ac_perm_id) VALUES ($id,$perm_id)";
			if (!$res = & $conn->Execute($query)) return -1;
		}
		return $id;
	}
}
function update_user($conn,$login,$template_id) {
	$params = array(
		$template_id,
		$template_id,
		$template_id,
		$template_id
	);
	$query = "UPDATE users SET template_sensors=?,template_assets=?,template_menus=?,template_policies=? WHERE login='$login'";
	
	if (!$res = & $conn->Execute($query,$params)) {
		return 0;
	} else return 1;
}
function get_permids($conn) {
	$ret = array();
	$res = $conn->query("SELECT id,name,value FROM acl_perm WHERE type='MENU'");
	while ($rw = $res->fetchRow()) {
		$ret[$rw['name']][$rw['value']] = $rw['id'];
	}
	return $ret;
}

ini_set("include_path", ".:/usr/share/ossim/include:/usr/share/phpgacl");
/* global configuration */
require_once 'ossim_conf.inc';
$conf = $GLOBALS["CONF"];
/*
if (!preg_match("/pro/",$conf->get_conf("ossim_server_version", FALSE))) {
	echo "This script is not available in opensource version\n";
	exit;
}
*/
$force_gacl = true;
require_once ('classes/Session.inc');
require_once ('classes/Net.inc');
require_once ('classes/Sensor.inc');
require_once ('ossim_acl.inc');
$gacl = $GLOBALS['ACL'];
require_once ('ossim_db.inc');

/* connect to db */
$db = new ossim_db();
$conn = $db->connect();

$net_list = Net::get_all($conn);
$sensor_list = Sensor::get_all($conn, "ORDER BY name ASC");
$permids = get_permids($conn);

$users = Session::get_list($conn);

foreach ($users as $user) {
	$nets = "";
	$sensors = "";
	$perms = array();
	$login = $user->get_login();
	if ($user->get_is_admin() || $login == ACL_DEFAULT_OSSIM_ADMIN) continue; // Skip admin user
	$query  = "SELECT * FROM users WHERE login=?";
	$params = array($login);
	if (!$rs = & $conn->Execute($query,$params)) {
		print $conn->ErrorMsg();
		exit;
	} else {
		if (!$rs->EOF) {
			if ($rs->fields['template_sensors'] > 0
			|| $rs->fields['template_assets'] > 0
			|| $rs->fields['template_menus'] > 0
			|| $rs->fields['template_policies'] > 0
			|| $rs->fields['inherit_sensors'] > 0
			|| $rs->fields['inherit_assets'] > 0
			|| $rs->fields['inherit_menus'] > 0
			|| $rs->fields['inherit_policies'] > 0) {
				continue;
			}
		} else {
			echo "User '$login' not found.\n";
			continue;
		}
	}
	echo "User '$login' has OpenSource perms. Trying to migrate...\n";
	foreach($net_list as $net) {
		$net_name = $net->get_name();
		if (false !== strpos(Session::allowedNets($login) , $net->get_ips())) {
			if ($nets == "") $nets = $net->get_ips();
            else $nets.= "," . $net->get_ips();
		}
	}
	
	foreach($sensor_list as $sensor) {
		$sensor_name = $sensor->get_name();
		$sensor_ip = $sensor->get_ip();
		if (false !== strpos(Session::allowedSensors($login) , $sensor_ip)) {
			if ($sensors == "") $sensors = $sensor_ip;
			else $sensors.= "," . $sensor_ip;
		}
	}
	
	foreach($ACL_MAIN_MENU as $mainmenu => $menus) {
		foreach($menus as $key => $menu) {
			if ($gacl->acl_check($mainmenu, $key, ACL_DEFAULT_USER_SECTION, $login)) {
				$perm_id = $permids[$mainmenu][$key];
				if ($perm_id > 0) $perms[$perm_id] = true;
			}
		}
	}
	$template_id = insert_template($conn,$login."_gacl",$nets,$sensors,$perms);
	if ($template_id > 0) {
		echo "Template '".$login."_gacl' ID$template_id successfully inserted into 'acl_templates'\n";
		if (update_user($conn,$login,$template_id)) {
			echo "...asigned to user $login\n\n";
		} else {
			echo "...not asigned to user $login. An error has occured\n\n";
		}
	} else echo "Error creating template '".$login."_gacl'\n";
}
?>