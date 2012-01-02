<?php
/*****************************************************************************
*
*    License:
*
*   Copyright (c) 2003-2006 ossim.net
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
* - html_service_level()
* - global_score()
* Classes list:
*/
?>
<tr id="fullsrc" style="display:none"><td><img src="../pixmaps/1x1.png" height="22px" border="0"></td><tr>
<tr>
	<td id="ossimlogo" style="background:url('../pixmaps/top/bg_header.gif') repeat-x bottom left;height:60">
		<table border=0 cellpadding=0 cellspacing=0 height="60">
		<tr>
			<td style="padding-left:10px"><img src="../pixmaps/top/logo<?= (preg_match("/.*pro.*/i",$version)) ? "_siem" : ((preg_match("/.*demo.*/i",$version)) ? "_siemdemo" : "") ?>.png" border='0'></td>
			<td align="right" style="padding:0px 0px 0px 10px">
				<form action="mobile.php" method="GET" style="margin:0px">  		
				<input type="hidden" name="login" value="<?php echo $_SESSION["_remote_login"]?>">		
				<select name="screen" onchange="this.form.submit()" style="width:85px">

					<option value="status" <?=($screen=="status") ? "selected" : ""?>><?php echo _("Status") ?></option>
					
					<? if (Session::menu_perms("MenuIncidents", "ReportsAlarmReport")) { ?>
					<option value="alarms" <?=($screen=="alarms") ? "selected" : ""?>><?php echo _("Alarms") ?></option>
					<? } ?>
					
					<? if (Session::menu_perms("MenuIncidents", "IncidentsReport")) { ?>
					<option value="tickets" <?=($screen=="tickets") ? "selected" : ""?>><?php echo _("Tickets") ?></option>
					<? } ?>

					<? if (Session::menu_perms("MenuEvents", "EventsForensics")) { ?>
					<option value="unique_siem" <?=($screen=="unique_siem") ? "selected" : ""?>><?php echo _("Unique Events") ?></option>
					<? } ?>
														
				</select>
				</form>
			</td>
		</tr>
	  </table>
	</td>
</tr>
