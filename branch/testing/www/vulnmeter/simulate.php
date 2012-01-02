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
require_once ('classes/Session.inc');
require_once ('ossim_db.inc');
require_once ('classes/Net.inc');
require_once ('classes/Host.inc');
require_once ('classes/Sensor.inc');
require_once ('classes/Scan.inc');
require_once ('ossim_conf.inc');

$nessus_path = $conf->get_conf("nessus_path", FALSE);

Session::logcheck("MenuEvents", "EventsVulnerabilities");
//
$hosts_alive = intval(GET('hosts_alive'));
$scan_locally = intval(GET('scan_locally'));
$scan_server = intval(GET('scan_server'));
$not_resolve = intval(GET('not_resolve'));
$targets = array();
$ip_targets = explode("\n", str_replace("\r","",GET('targets')));
foreach($ip_targets as $ip_target) if (trim($ip_target)!="") {
    $ip_target = trim($ip_target);
    ossim_set_error(false);
    ossim_valid($ip_target, OSS_NULLABLE, OSS_DIGIT, '.\/', 'illegal:' . _("Target"));
    if (ossim_error()) {
        $error_message .= _("Invalid target").": $ip_target<br>";
    }
    $targets[] = $ip_target;
}
$db                      = new ossim_db();
$conn                    = $db->connect();
$message_pre_scan        = _("Pre-scan localy");
$message_force_pre_scan  = _("Error: Need to force pre-scan locally");
$ctest                   = array(); // to save connection test to servers
$ttargets                = array(); // to save check for targets
$sensor_error            = false;
?>
<table border="0" cellpadding="0" cellspacing="0" width="80%">
<tr><td class="noborder headerpr"><?=_("Configuration Check Results")?></td></tr>
</table>
<table border="0" cellpadding="10" cellspacing="0" width="80%">
<tr><td class="noborder">
    <table border="0" cellpadding="2" cellspacing="1" class="noborder" align="center">
    <tr>
        <th><?=_("Target")?></th>
        <th><?=_("Inventory")?></th>
        <th><?=_("Target Allowed")?></th>
        <th><?=_("Sensors")?></th>
        <th><?=_("Sensor Allowed")?></th>
        <th><?=_("Vuln Scanner")?></th>
        <th><?=_("Nmap Scan")?></th>
        <th><?=_("Load")?></th>
    </tr>
    <?
    // sensors
    $all_sensors = array();
    $sensor_list = Sensor::get_all($conn,"",false);
    foreach ($sensor_list as $s) $all_sensors[$s->get_ip()] = $s->get_name();
    // force scanner
    if ($scan_server>0) {
        $result = $conn->Execute("SELECT name,hostname FROM vuln_nessus_servers WHERE id=$scan_server");
        while ( !$result->EOF ) {
            list($name, $hostname) = $result->fields;
            $all_sensors[$hostname] = $name;
            $result->MoveNext(); 
        }
    } elseif ($not_resolve) {
        $result = $conn->Execute("SELECT name,hostname FROM vuln_nessus_servers WHERE enabled=1");
        while ( !$result->EOF ) {
            list($name, $hostname) = $result->fields;
            if (Session::sensorAllowed($hostname)) $all_sensors[$hostname] = $name;
            $result->MoveNext(); 
        }
    }
    // remote nmap
    $rscan = new RemoteScan("","");
    $ids = array(); 
    if ($rscan->available_scan()) {
        $reports = $rscan->get_scans();
        if (is_array($reports)) {
            $agents = array_keys($reports);
            foreach ($agents as $agent) $ids[] = preg_replace("/.*\|/","",$agent);
        }
    }
    $withnmapforced = 0;
    if ($scan_server>0 && !$hosts_alive && $hostname!="") {
        $ids = array_merge(array($hostname),$ids);
        $withnmapforced = 1;
    }
    // targets
    foreach($targets as $target) {
        if (preg_match("/^!/",$target)) continue;
        
        $unresolved = (!preg_match("/\d+\.\d+\.\d+\.\d+/",$target) && $not_resolve) ? true : false;
        
        if (preg_match("/\//",$target)) { // Net
            $name = Net::get_name_by_ip($conn,$target);
            $perm = Session::netAllowed($conn, $name);
            $sensors = Net::get_related_sensors($conn,$name); 
        } else { // Host
            $name = ($unresolved) ? $target : Host::ip2hostname($conn,$target);
            if (!preg_match("/\d+\.\d+\.\d+\.\d+/",$target)) $target = Host::hostname2ip($conn,$name,true);
            $perm = ($unresolved) ? true : Session::hostAllowed($conn, $target);
            $sensors = Host::get_related_sensors($conn,$target);
        }
        
        if($unresolved || (Session::am_i_admin() && count($sensors)==0 && $scan_server=="0")) {
        	if ($unresolved) {
        		foreach ($all_sensors as $ip => $unused) $sensors[] = $ip; 
        	} else {
	            $local_ip = `grep framework_ip /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`;
	            $local_ip = trim($local_ip);
	            $result = $conn->Execute("SELECT name FROM vuln_nessus_servers WHERE hostname like '$local_ip'");
	            if($result->fields["name"]!="") {  
	                $sensors[] = $local_ip;  
	            }
	        }
        }
        if ($scan_server>0 && $hostname!="") $sensors = array_merge(array($hostname),$sensors);
        $sname = $vs = $sperm = $snmap = $load = array();
        $selected = false;
        // reorder sensors with load
        if (!$scan_server) $sensors = Sensor::reorder_sensors($conn, $sensors);
        $sensors = array_unique($sensors);
        // info per each related sensor
        
        $ttargets[$target]["perm"] = $perm; 
        foreach ($sensors as $sensor) {
            $properties = Sensor::get_properties($conn, $sensor);
            if (preg_match("/omp\s*$/i", $nessus_path)) {
                // check connection
                $connection = Sensor::check_omp_connection($conn, $sensor);
            }
            $load[]     = Sensor::get_load($conn, $sensor);
            $withnmap   = in_array($sensor,$ids) || $unresolved;
            
            $sensor_name = ($sensor=="Local") ? $sensor : $sensor." [".$all_sensors[$sensor]."]";
            
            if (preg_match("/omp\s*$/i", $nessus_path)) {
                $sensor_name = $sensor_name;
            }
            if (!$selected && (Session::sensorAllowed($sensor) || $scan_server>0) && ($withnmap || $scan_locally) && ($properties["has_vuln_scanner"] || $scan_server>0)) {
                $selected = true;
                $sensor_name = "<strong>$sensor_name</strong>";
                $ttargets[$target]["sensor"] = $sensor;
                if( $connection=="" ) {  // connection ok
                    $ctest[$sensor] = "";
                }
                else { // connection ko
                    $ctest[$sensor] = $connection;
                    $sensor_error   = true;
                }
            }
            $sname[] = $sensor_name;
            //$sperm[] = "<img src='../pixmaps/".(Session::sensorAllowed($sensor) ? "tick" : "cross").".png' border='0'>";
            //$vs[] = "<img src='../pixmaps/".(($scan_server>0 && $sensor==$hostname) ? "tickblue" : (($properties["has_vuln_scanner"]) ? "tick" : "cross")).".png' border='0'>";
            //$snmap[] = "<img src='../pixmaps/".(($scan_locally || ($withnmap && $withnmapforced)) ? "tickblue": (($withnmap) ? "tick" : "cross")).".png' border='0'>";  

            $sperm[] = "<img src='../pixmaps/".(Session::sensorAllowed($sensor) ? "tick" : "cross").".png' border='0'>";
            $vs[] = "<img src='../pixmaps/".(($scan_server>0 && $sensor==$hostname) ? "tick" : (($properties["has_vuln_scanner"]) ? "tick" : "cross")).".png' border='0'>";
            $snmap[] = "<img align='absmiddle' src='../pixmaps/".(($scan_locally || ($withnmap && $withnmapforced)) ? "tick": (($withnmap) ? "tick" : "cross")).".png' border='0'>".
            (($scan_locally || ($withnmap && $withnmapforced)) ? "<span style='font-size:9px;color:gray'>$message_pre_scan</span>": (($withnmap) ? "" : "<span style='font-size:9px;color:gray'>$message_force_pre_scan</span>"));
        
            if( $ttargets[$target]["sensor"] == $sensor ) {
                $ttargets[$target]["sperm"] = Session::sensorAllowed($sensor) ? true : false;
                $ttargets[$target]["vs"]    = ($scan_server>0 && $sensor==$hostname) ? true : (($properties["has_vuln_scanner"]) ? true : false);
                $ttargets[$target]["snmap"] = ($scan_locally || ($withnmap && $withnmapforced)) ? true: (($withnmap) ? true : false);
            }
        }

        $snames = implode("<br>",$sname);
        $sperms = implode("<br>",$sperm);
        $vulns = implode("<br>",$vs);
        $nmaps = implode("<br>",$snmap);
        $load = implode("<br>",$load);
    ?>
    <tr>
        <td><?=$target?></td>
        <td style="padding-left:10px;padding-right:10px" nowrap><?=$name?></td>
        <td><img src="../pixmaps/<?=($perm) ? "tick" : "cross"?>.png" border="0"></td>
        <td style="line-height:16px;padding-left:10px;padding-right:10px" nowrap><?=$snames?></td>
        <td><?=$sperms?></td>        
        <td><?=$vulns?></td>
        <td nowrap><?=$nmaps?></td>
        <td style="line-height:16px;padding-left:10px;padding-right:10px" nowrap><?=$load?></td>
    </tr>
    <?
    }
?>
    <tr>
        <td class="nobborder" style="padding-top:15px" colspan="8">
            <table class="transparent" align="center">
                <tr>
                    <th>
                        <strong><?php echo _("Sensor IP");?></strong>
                    </th>
                    <th>
                        <strong><?php echo _("Scanner connection");?></strong>
                    </th>
                </tr>
            <?php
            foreach ($ctest as $k => $v) {
                if( $v=="" ) {
                    echo "<tr><td class=\"nobborder\" style=\"text-align:center;padding:0px 10px;\">".$k."</td>";
                    echo "<td class=\"nobborder\" style=\"text-align:center;padding:0px 10px;\"><img src='../pixmaps/tick.png' border='0' /></td></tr>";
                }
                else {
                    echo "<tr><td class=\"nobborder\" style=\"text-align:center;padding:0px 10px;\">".$k."</td>";
                    echo "<td class=\"nobborder\" style=\"text-align:center;padding:0px 10px;\"> <span style='color: #ff0000;'><strong>"._($v)."</strong></span></td></tr>";
                }
            }
            ?>
            </table>
        </td>
    </tr>
    </table>
</td></tr>
</table>
<?php

$test_ok = true;

foreach ($ttargets as $target_data) { // Check if all targets can be scanned
    if($target_data["perm"] && $target_data["sperm"] && $target_data["vs"] && $target_data["snmap"]) {
        $test_ok = true;
    }
    else {
        $test_ok = false;
    }
    if(!$test_ok)
        break;
}
?>
<br><br>
<?
if($test_ok && !$sensor_error) { 
// we can enable button to run job
    echo "|1";
}
else {
    echo "|0";
}

$db->close($conn);
?>
