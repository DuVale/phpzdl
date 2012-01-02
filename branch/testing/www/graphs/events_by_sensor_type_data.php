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
* - GetSensorName()
* Classes list:
*/
require_once ('ossim_db.inc');
require_once ('classes/Alarm.inc');
require_once ('classes/Util.inc');
require_once ('classes/CIDR.inc');
require_once ('classes/Security.inc');
require_once ('charts.php');

Session::logcheck("MenuControlPanel", "ControlPanelExecutive");

function GetSensorName($sid, $db) {
    $sname = "";
    $multiple = (preg_match("/\,/", $sid)) ? true : false;
    if ($multiple) $sid = preg_replace("/\,.*/", "", $sid);
    $temp_sql = "SELECT * FROM sensor WHERE sid='" . $sid . "'";
    $myrow = & $db->Execute($temp_sql);
    if ($myrow) {
    	$plugin = explode("-",preg_replace("/.*\]\s*/","",$myrow->fields['hostname']),2);
    	//$sname = ($myrow->fields["sensor"]) ? $myrow->fields["sensor"] : preg_replace("/-.*/","",preg_replace("/.*\]\s*/","",$myrow->fields['hostname'])) . '-' . $plugin[1];
    	$sname = preg_replace("/-.*/","",preg_replace("/.*\]\s*/","",$myrow->fields['hostname'])) . '-' . $plugin[1];
        if (!$multiple) {
            $sname .= ':' . $myrow->fields["interface"];
            if ($myrow->fields["filter"] != "") $sname .= ':' . $myrow->fields["filter"];
        }
    }
    return $sname;
}
function GetSensorSids($db) {
    $sensors = array();
    $temp_sql = "SELECT * FROM sensor";
    //echo $temp_sql;
    $tmp_result = $db->Execute($temp_sql);
    while ($myrow = $tmp_result->FetchRow()) {
    	$ipname = ($myrow["sensor"]!="") ? $myrow["sensor"] : preg_replace("/-.*/","",preg_replace("/.*\]\s*/","",$myrow["hostname"]));
    	$sensors[$ipname][] = $myrow["sid"];
    }
    return $sensors;
}
$db = new ossim_db();
$conn = $db->snort_connect();
$conn_ossim = $db->connect();
// sensors to resolv
$sensors = array();
$query1 = "SELECT name,ip from sensor";
if (!$rs = & $conn_ossim->Execute($query1)) {
    print $conn_ossim->ErrorMsg();
    exit();
}
while (!$rs->EOF) {
    $sensors[$rs->fields["ip"]] = $rs->fields["name"];
    $rs->MoveNext();
}
$use_ac = true;
//$use_ac = false;

// Allowed Sensors filter
$criteria_sql = "WHERE 1";
if (Session::allowedSensors() != "") {
	$user_sensors = explode(",",Session::allowedSensors());
	$snortsensors = GetSensorSids($conn);
	$sensor_str = "";
	foreach ($user_sensors as $user_sensor)
		if (count($snortsensors[$user_sensor]) > 0) $sensor_str .= ($sensor_str != "") ? ",".implode(",",$snortsensors[$user_sensor]) : implode(",",$snortsensors[$user_sensor]);
	if ($sensor_str == "") $sensor_str = "0";
	$criteria_sql .= ($use_ac) ? " AND ac_sensor_sid.sid in (" . $sensor_str . ")" : " AND acid_event.sid in (" . $sensor_str . ")";
}

if ($use_ac) {
	// ac_ unique sensors (removed LIMIT 10 at 12/12/11)
	$query = "SELECT DISTINCT ac_sensor_sid.sid, sum(ac_sensor_sid.cid) as event_cnt, (select count(distinct plugin_id, plugin_sid) from ac_sensor_signature where ac_sensor_signature.sid=ac_sensor_sid.sid and ac_sensor_sid.day=ac_sensor_signature.day) as sig_cnt, (select count(distinct(ip_src)) from ac_sensor_ipsrc where ac_sensor_sid.sid=ac_sensor_ipsrc.sid and ac_sensor_sid.day=ac_sensor_ipsrc.day) as saddr_cnt, (select count(distinct(ip_dst)) from ac_sensor_ipdst where ac_sensor_sid.sid=ac_sensor_ipdst.sid and ac_sensor_sid.day=ac_sensor_ipdst.day) as daddr_cnt, min(ac_sensor_sid.first_timestamp) as first_timestamp, max(ac_sensor_sid.last_timestamp) as last_timestamp FROM ac_sensor_sid FORCE INDEX(primary) $criteria_sql GROUP BY ac_sensor_sid.sid ORDER BY event_cnt DESC";
} else {
	// Allowed Nets filter
	$domain = Session::allowedNets();
	if ($domain != "") {
	    $cidrs = array();
	    $nets = explode(",", $domain);
	    foreach ($nets as $cidr) {
	        $cr = CIDR::expand_CIDR($cidr,"SHORT","LONG");
	        $cidrs[] = "((acid_event.ip_src>= ".$cr[0]." AND acid_event.ip_src<= ".$cr[1].")OR(acid_event.ip_dst>= ".$cr[0]." AND acid_event.ip_dst<= ".$cr[1]."))";
	    }
	    $criteria_sql .= " AND (".implode(" OR ",$cidrs).")";
	}
	$query = "SELECT DISTINCT sid, count(cid) as event_cnt FROM acid_event $criteria_sql GROUP BY sid ORDER BY event_cnt DESC";
}
if (!$rs = & $conn->Execute($query)) {
    print $conn->ErrorMsg();
    exit();
}
$s=0;
$p=0;
$data = array();
$already_plugin = array();
$already_sensor = array();
while (!$rs->EOF) {
	$sensor_plugin = explode("-", GetSensorName($rs->fields["sid"], $conn), 2);
	$plugin = ($sensor_plugin[1] != "") ? preg_replace("/:.*/", "", $sensor_plugin[1]) : "snort";
	if ($plugin=="") $plugin="snort";
	$plugin= preg_replace("/ossec-.*/", "ossec", $plugin);
    $sensor_plugin[0] = preg_replace("/:.*/", "", $sensor_plugin[0]);
	$sensor = ($sensors[$sensor_plugin[0]] != "") ? $sensors[$sensor_plugin[0]] : $sensor_plugin[0];
	// Post limit: 10 sensors / 10 plugins
	if (($s < 10 && $p < 10) || $data[$sensor][$plugin] > 0) {
		$data[$sensor][$plugin]+= $rs->fields["event_cnt"];
		if (!$already_plugin[$plugin]) { $p++; }
		if (!$already_sensor[$sensor]) { $s++; }
		$already_plugin[$plugin]++;
		$already_sensor[$sensor]++;
	}
    $rs->MoveNext();
}
$header = $events = array();
$header[] = ""; // first row blank
foreach($data as $sensor => $values) {
    foreach($values as $plugin => $val) {
        if (!in_array($plugin, $header)) $header[] = $plugin;
    }
}
foreach($data as $sensor => $values) {
    if ($sensor == "") continue;
	ksort($values);
    $arr = array();
    $arr[] = $sensor; // first row series name
    foreach($header as $plugin) if ($plugin != "") {
        $arr[] = ($values[$plugin] > 0) ? $values[$plugin] : 0;
    }
    $events[] = $arr;
}
$results_array = array_merge(array(
    $header
) , $events);
//print_r($results_array);
$chart['chart_data'] = $results_array;
// PHP/SWF Chart License - Licensed to ossim.com. For distribution with ossim only. No other redistribution / usage allowed.
// For more information please check http://www.maani.us/charts/index.php?menu=License_bulk
$chart['license'] = "J1XF-CMEW9L.HSK5T4Q79KLYCK07EK";
$chart['axis_category'] = array(
    'size' => 13,
    'color' => "74808F",
    'alpha' => 75,
    'orientation' => "circular"
);
$chart['axis_ticks'] = array(
    'value_ticks' => false,
    'category_ticks' => false
);
$chart['axis_value'] = array(
    'alpha' => 30
);
$chart['chart_border'] = array(
    'bottom_thickness' => 0,
    'left_thickness' => 0
);
$chart['chart_grid_h'] = array(
    'alpha' => 20,
    'color' => "000000",
    'thickness' => 1,
    'type' => "dashed"
);
$chart['chart_grid_v'] = array(
    'alpha' => 5,
    'color' => "000000",
    'thickness' => 20,
    'type' => "solid"
);
$chart['chart_pref'] = array(
    'point_shape' => "circle",
    'point_size' => 8,
    'fill_shape' => true,
    'grid' => "circular"
);
$chart['chart_rect'] = array(
    'x' => 80,
    'y' => 30,
    'width' => 400,
    'height' => 230,
    'positive_color' => "BBC6D0",
    'positive_alpha' => 40
);
/*$chart['chart_transition'] = array(
    'type' => "zoom",
    'delay' => .5,
    'duration' => .5,
    'order' => "series"
);*/
$chart['chart_type'] = "polar";
$chart['draw'] = array(
    array(
        'type' => "text",
        'transition' => "slide_right",
        'delay' => 0,
        'duration' => 3,
        'color' => "000000",
        'width' => 500,
        'alpha' => 8,
        'size' => 70,
        'x' => 0,
        'y' => - 20,
        'text' => _("data sources")
    ) ,
    array(
        'type' => "text",
        'transition' => "slide_right",
        'delay' => 0,
        'duration' => 5,
        'color' => "000000",
        'alpha' => 3,
        'size' => 80,
        'x' => 450,
        'y' => 150,
        'text' => _("sensors")
    ) ,
    array(
        'type' => "text",
        'transition' => "slide_right",
        'delay' => 0,
        'duration' => 5,
        'color' => "000000",
        'alpha' => 3,
        'size' => 50,
        'x' => 450,
        'y' => 220,
        'text' => _("amount")
    )
);
$chart['legend_label'] = array(
    'layout' => "vertical",
    'bullet' => "circle",
    'size' => 12,
    'color' => "74808F",
    'alpha' => 75
);
$chart['legend_rect'] = array(
    'x' => 20,
    'y' => 100,
    'width' => 20,
    'height' => 40,
    'margin' => 3,
    'fill_alpha' => 0
);
$chart['series_color'] = array(
    "ff4400",
    "74808F",
	"418CF0",
	"FCB441",
	"056492",
	"BFBFBF",
	"1A3B69",
	"FFE382",
	"CA6B4B",
	"F1B9A8",
	"E0830A",
	"FF6347",
	"7893BE",
	"FF8000"
);
//handle.php?target_url=events_sensor&target_var=category
$chart['link_data'] = array(
    'url' => "../top.php?hmenu=".md5("Analysis")."&smenu=".md5("Forensics"),
    'target' => "topmenu"
);
SendChartData($chart);
?>
