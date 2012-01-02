<?php
//If the browser in IE v < 9, flash chart will be loaded due to compatibility problems.
$browser = browser_info();
if(!empty($browser['msie']) && $browser['msie'] < 9)
	header("Location: /ossim/graphs/draw_swf_graph.php?source_graph=events_by_sensor_type_data.php&width=440&height=280");
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

function browser_info($agent=null) {
  // Declare known browsers to look for
  $known = array('msie');

  // Clean up agent and build regex that matches phrases for known browsers
  // (e.g. "Firefox/2.0" or "MSIE 6.0" (This only matches the major and minor
  // version numbers.  E.g. "2.0.0.6" is parsed as simply "2.0"
  $agent = strtolower($agent ? $agent : $_SERVER['HTTP_USER_AGENT']);
  $pattern = '#(?<browser>' . join('|', $known) .
    ')[/ ]+(?<version>[0-9]+(?:\.[0-9]+)?)#';

  // Find all phrases (or return empty array if none found)
  if (!preg_match_all($pattern, $agent, $matches)) return array();

  // Since some UAs have more than one phrase (e.g Firefox has a Gecko phrase,
  // Opera 7,8 have a MSIE phrase), use the last one found (the right-most one
  // in the UA).  That's usually the most correct.
  $i = count($matches['browser'])-1;
  return array($matches['browser'][$i] => $matches['version'][$i]);
}

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

foreach($data as $sensor => $values) {
    foreach($values as $plugin => $val) {
        if (!in_array($plugin, $header)) $header[] = $plugin;
    }
}


foreach($data as $sensor => $values) {
    if ($sensor == "") continue;
	ksort($values);
    $arr = array();
    
    foreach($header as $plugin) if ($plugin != "") {
        $arr[] = ($values[$plugin] > 0) ? $values[$plugin] : 0;
    }
	
	$arr[] = $sensor; // last row series name
    $events[] = $arr;
	
}

$chart_color = array(
    "#ff4400",
    "#74808F",
	"#418CF0",
	"#FCB441",
	"#056492",
	"#BFBFBF",
	"#1A3B69",
	"#FFE382",
	"#CA6B4B",
	"#F1B9A8",
	"#E0830A",
	"#FF6347",
	"#7893BE",
	"#FF8000"
);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

    <link rel="stylesheet" type="text/css" href="../style/ext-all.css" />
    <script type="text/javascript" src="../js/ext-all.js"></script>
    <script type="text/javascript">
        Ext.require(['Ext.data.*']);

        Ext.onReady(function() {

            window.generateData = function(){
                var data = [];                   
               
                <?php foreach($header as $i => $name){?>
                        
                            data.push({
                                name: '<?php echo (strlen($name) > 15 ) ? substr($name, 0, 12)."..." : $name ?>',
                                <?php foreach($events as $j => $values){ ?>
                                    data<?php echo $j+1 ?> : <?php echo $values[$i].(($j<count($events)-1)? "," : "") ?>
                            
                                <?php  }  ?>
                            });
                <?php    }
                ?>
                return data;
            };
            
            
            window.store1 = Ext.create('Ext.data.JsonStore', {
                fields: ['name', 'data1', 'data2', 'data3', 'data4', 'data5', 'data6', 'data7', 'data9', 'data9', 'data10'],
                data: generateData()
            });
         
            
            
        });    
    
    
    </script>
    <script type="text/javascript" >
        Ext.onReady(function () {
            var chart;
            
            store1.loadData(generateData());

            chart = new Ext.chart.Chart({
                width: 430,
                height: 280,
                animate: true,
                store: store1,
                renderTo: Ext.getBody(),
                insetPadding: 0,
                theme: 'Category2',
                legend: {
                        position: 'left',
						boxFill: 'rgba(0,0,0,0)',
                        boxStroke: false,
						itemSpacing: 1,
						labelFont:'bold 10px Arial, Sans-Serif'
                    },
                axes: [{
                    type: 'Radial',
                    position: 'radial',     
                    label: {
                        display: 'categories' 
                    }
                }],
                series: [
				<?php foreach($events as $j => $values){ ?>
				{
                    type: 'radar',
                    xField: 'name',
                    yField: 'data<?php echo $j+1 ?>',
                    title: '<?php echo (strlen($values[count($values)-1]) > 23 ) ? substr($values[count($values)-1], 0, 20)."..." : $values[count($values)-1]?>',
                    showInLegend: true,					
                    style: {
                        opacity: 0.4,
                        fill: '<?php echo $chart_color[$j] ?>'
                    },
                    showMarkers: true,
                    markerConfig: {
						type: 'circle',
                        radius: 3,
                        size: 3,
                        fill: '<?php echo $chart_color[$j] ?>'
                    }
                }
				<?php echo ($j<count($events)-1)? "," : "" ?>
				<?php }  ?>
				]
            }); 
        });
    
    
    </script>




