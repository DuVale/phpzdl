<?php
/*****************************************************************************
*
*    License:
*
*   Copyright (c) 2007-2011 AlienVault
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
require_once 'classes/Security.inc';
require_once 'classes/Session.inc';
require_once 'classes/Plugingroup.inc';
require_once 'ossim_db.inc';
Session::logcheck("MenuConfiguration", "PluginGroups");
$db = new ossim_db();
$conn = $db->connect();
$plugin_id = GET('plugin_id');
$plugin_sid = GET('plugin_sid');
$group_id = GET('group_id');
$close = false;
$msg = "";
$islist = (preg_match("/\,/",$plugin_id)) ? true : false;

ossim_valid($plugin_id, OSS_DIGIT, ',', 'illegal:' . _("plugin_id"));
ossim_valid($plugin_sid, OSS_DIGIT, ',', 'illegal:' . _("plugin_sid"));
ossim_valid($group_id, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("group_id"));
if (ossim_error()) {
    die(ossim_error());
}

if ($group_id > 0) {
	// List of ID,SID
	if (preg_match("/\,/",$plugin_id)) {
		$ids = explode(",",$plugin_id);
		$sids = explode(",",$plugin_sid);
	} else {
		$ids = array($plugin_id);
		$sids = array($plugin_sid);
	}
	for ($i = 0; $i < count($ids); $i++) {
		$pid = $ids[$i];
		$psid = $sids[$i];
		$error_code = Plugingroup::insert_plugin_id_sid($conn, $group_id, $pid, $psid);
		if ($error_code == 2) {
			$msg .= _("Skip: ($pid $psid) is already into selected DS Group")."<br>";
		} elseif ($error_code == 1) {
			$msg .= _("Error: Cannot insert $pid $sid")."<br>";
		} else {
			$close = true;
		}
	}
	if ($msg != "") { $close = false; }
}

$groups = Plugingroup::get_list($conn);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <?php if ($close) { ?>
  <script type="text/javascript">
  $(document).ready(function(){
	parent.GB_hide();
  });
  </script>
  <?php } ?>
  <script type="text/javascript">
	function check_checked(group_id) {
		document.f.group_id.value = group_id;
		document.f.submit();
	}
  </script>
</head>
<body style="height: auto; margin: 0 0 10px 0">
<form name="f">
<input type="hidden" name="plugin_id" value="<?php echo $plugin_id ?>">
<input type="hidden" name="plugin_sid" value="<?php echo $plugin_sid ?>">
<input type="hidden" name="group_id" value="">
<table width="90%" align="center" class="noborder" cellspacing="0" cellpadding="0">
    <?php if ($msg != "") { ?>
    <tr><td colspan="3"><?php echo $msg ?></td></tr>
    <?php } ?>
    <tr>
        <td height="34" class="plfieldhdr pall"><?php echo _("Action") ?></td>
        <td height="34" class="plfieldhdr ptop pbottom pright"><?php echo _("DS Group Name") ?></td>
        <td height="34" class="plfieldhdr ptop pbottom pright"><?php echo _("Description") ?></td>
    </tr>
    <?php
$i = 0;
foreach($groups as $group) {
    $id = $group->get_id();
    $plugins = $group->get_plugins();
    $color = ($i%2==0) ? "lightgray" : "blank";
?>
        <tr class="<?=$color?>" txt="<?=$id?>">
            <td width="70" class="pleft" style="text-align:left;padding:2px" nowrap>
                <?php if (!$islist && $plugins[$plugin_id] != "" && preg_match("/(^|\,)$plugin_sid($|\,)/",$plugins[$plugin_id]['sids'])) { ?>
                <font style="color:gray">[<?php echo _("Already in this Group") ?>]</font>
                <?php } else { ?>
                <input type="button" onclick="check_checked(<?php echo $id ?>)" class="button" value="<?php echo _("Add Event Type") ?>">
                <?php } ?>
            </td>
            <td style="padding-left:4px;padding-right:4px" width="200"><b><?php echo htm($group->get_name()) ?></b></td>
            <td style="text-align:left;padding-left:5px" class="pright"><?php echo htm($group->get_description()) ?>&nbsp;</td>
        </tr>
    <?php $i++;
} ?>
</table>
