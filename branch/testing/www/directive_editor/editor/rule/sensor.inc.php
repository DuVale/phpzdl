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
* Classes list:
*/
?>
<input type="hidden" name="sensor" id="sensor" value=""></input>
<input type="hidden" name="sensor_list" id="sensor_list" value=""></input>
<table class="transparent">
	<tr>
		<th style="white-space: nowrap; padding: 5px;font-size:12px">
			<?php echo gettext("Sensor"); ?>
		</th>
	</tr>
	<tr><td class="nobborder">&middot; <i><?php echo _("Empty selection means ANY sensor") ?></i></td></tr>
	<tr>
		<td class="nobborder">
		<select id="sensorselect" class="multiselect_sensor" multiple="multiple" name="sensorselect[]" style="display:none;width:600px">
		<?php if (isList($rule->sensor) && $rule->sensor != "") { ?>
		<?php 
		$sensor_list = $rule->sensor;
		if ($host_list = getSensorList()) {
		    foreach($host_list as $host) {
		        $hostname = $host->get_name();
		        $ip = $host->get_ip();
		        if (in_array($ip, split(',', $sensor_list))) {
		            echo "<option value='$ip' selected>$hostname</option>\n";
		        }
		    }
		}
		?>
		<?php } ?>
		</select>
		</td>
	</tr>
	<?php for ($i = 1; $i <= $rule->level - 1; $i++) {
			$sublevel = $i . ":SENSOR";
			//echo "<option value=\"$sublevel\">$sublevel</option>";
			?><tr><td class="center nobborder"><input type="button" value="<?php echo _("Sensor from rule of level")." $i" ?>"<?php if ($rule->sensor == $sublevel) { ?> style="background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important"<?php } ?> onclick="document.getElementById('sensor').value='<?php echo $sublevel ?>';wizard_next()"></td></tr><?php
			$sublevel = "!" . $i . ":SENSOR";
			?><tr><td class="center nobborder"><input type="button" value="<?php echo "!"._("Sensor from rule of level")." $i" ?>"<?php if ($rule->sensor == $sublevel) { ?> style="background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important"<?php } ?> onclick="document.getElementById('sensor').value='<?php echo $sublevel ?>';wizard_next()"></td></tr><?php
			//echo "<option value=\"$sublevel\">$sublevel</option>";?>
	<?php } ?>
	<tr><td class="center nobborder" style="padding-top:10px"><input type="button" style="background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important" value="<?php echo _("Next") ?>" onclick="wizard_next()"></td></tr>
</table>
<!-- #################### END: sensor ##################### -->