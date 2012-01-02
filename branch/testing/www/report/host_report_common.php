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
* - ip_max_occurrences()
* - event_max_occurrences()
* - event_max_risk()
* - port_max_occurrences()
* - less_stable_services()
* Classes list:
*/

/*******************************************************
----------------------- General ------------------------
********************************************************/

function generate_id($ip_cidr)
{
    $ip_cidr = ( empty($ip_cidr) ) ? 'any' : $ip_cidr;
    return preg_replace("/\.|\//", "_", $ip_cidr);
}


/*******************************************************
------------------ Host Report Status ------------------
********************************************************/

function html_service_level($conn, $host="", $date_range=null) 
{
    global $user;
	
	// For custom
	if($date_range != null)
	{
		$date_range['date_from']  =  date("Y-m-d", strtotime($date_range['date_from']));
		$date_range['date_to']    =  date("Y-m-d", strtotime($date_range['date_to']));
			
		$from = strtotime($date_range['date_from']);
		$to   = strtotime($date_range['date_to']);
		
		$diff = $to - $from;
		
		$limit_1 = 86400 + 86400;
		$limit_2 = 86400 + 604800;
		$limit_3 = 86400 + 1296000;
		$limit_4 = 86400 + 2678400;
								
		if ( $diff <= $limit_1 )
			$range = 'day';
		elseif ($diff > $limit_1 && $diff <= $limit_2)
			$range = "week";
		elseif ($diff > $limit_2 && $diff <= $limit_3)
			$range='month';
		elseif ($diff > $limit_3 && $diff <= $limit_4)
			$range='year';
	}
	else
		$range = "day";
	
	
    $level = 100;
    $class = "level4";
    
	if( $host != 'any' && $host != '')
	{
		$sql = "SELECT c_sec_level, a_sec_level FROM control_panel WHERE id = ? AND time_range = ?";
		$params = array(
			//"global_$user",
			$host,
			$range
		);
	}
	else
	{
		$sql = "SELECT c_sec_level, a_sec_level FROM control_panel WHERE id = ? AND time_range = ? AND rrd_type='global'";
		$params = array(
			'global_'.$user,
			$range
		);
	}
	
	if (!$rs = & $conn->Execute($sql, $params)) {
        die($conn->ErrorMsg());
    }
    if ($rs->EOF) {
        return array(
            $level,
            "level11"
        );
    }
    $level = number_format(($rs->fields["c_sec_level"] + $rs->fields["a_sec_level"]) / 2, 0);
	$level = ( $level > 100 ) ? 100 : $level;
	
    $class = "level" . round($level / 9, 0);
    return array(
        $level,
        $class
    );
}

function global_score($conn, $host) {
    global $conf_threshold;
    	
	if( $host != 'any' && $host != '')
		$sql = "SELECT host_ip, compromise, attack FROM host_qualification WHERE host_ip='$host'";
	else
		$sql = "SELECT host_ip, compromise, attack FROM host_qualification";
	
    if (!$rs = & $conn->Execute($sql)) {
        die($conn->ErrorMsg());
    }
    
	$score_a = 0;
    $score_c = 0;
    
	while (!$rs->EOF) {
        $score_a+= $rs->fields['attack'];
        $score_c = $rs->fields['compromise'];
        $rs->MoveNext();
    }
    
	$risk_a = round($score_a / $conf_threshold * 100);
    $risk_c = round($score_c / $conf_threshold * 100);
    $risk   = ($risk_a > $risk_c) ? $risk_a : $risk_c;
    $img    = 'green'; // 'off'
    $color  = '';
    
	if ($risk > 500)
        $img = 'red';
    elseif ($risk > 300)
        $img = 'yellow';
    elseif ($risk > 100) 
        $img = 'green';
    
    $alt = "$risk " . _("metric/threshold");
    
	return array(
        $img,
        $alt
    );
}

/*******************************************************
----------------- Host Report Inventory ----------------
********************************************************/

function orderArray($x, $y){
	if ( $x['date'] == $y['date'] )
		return 0;
	else if ( $x['date'] > $y['date'] )
		return -1;
	else
		return 1;
}


/*******************************************************
----------------- Net Report Inventory -----------------
********************************************************/

function get_net_data($conn, $net)
{
    require_once('classes/Net_scan.inc');
    $sensors = "";

    if ( $sensor_list = $net->get_sensors($conn) ) 
    {
        foreach($sensor_list as $sensor) {
            $sensors.= $sensor->get_sensor_name() . '<br/>';
        }
    }
        
    // Nessus
    if ($scan_list = Net_scan::get_list($conn, "WHERE net_name = '$name' AND plugin_id = 3001")) 
        $nessus = "<img src=\"../pixmaps/tables/tick.png\">";
    else 
        $nessus = "<img src=\"../pixmaps/tables/cross.png\">";
    
    // Nagios
    if ($scan_list = Net_scan::get_list($conn, "WHERE net_name = '$name' AND plugin_id = 2007")) 
        $nagios = "<img src=\"../pixmaps/tables/tick.png\">";

    else 
        $nagios = "<img src=\"../pixmaps/tables/cross.png\">";

    $net_data = '<table align="center" class="noborder" style="background-color:white" width="100%">
                    <tr>
                        <th>'.gettext("THR_C").'</th>
                        <th>'.gettext("THR_A").'</th>
                        <th>'.gettext("Asset").'</th>
                        <th>'.gettext("Sensor").'</th>
                        <th>'.gettext("Nessus").'</th>
                        <th>'.gettext("Nagios").'</th>
                    </tr>

                    <tr>
                        <td>'.$net->get_threshold_c().'</td>
                        <td>'.$net->get_threshold_a().'</td>
                        <td>'.$net->get_asset().'</td>
                        <td>'.$sensors.'</td>
                        <td>'.$nessus.'</td>
                        <td>'.$nagios.'</td>
                    </tr>
                </table>';
                    

    return $net_data;
}

/*******************************************************
------------------ Host Report Alarms ------------------
********************************************************/


function baseLong2IP($long_IP) {
    $tmp_IP = $long_IP;
    if ($long_IP > 2147483647) {
        $tmp_IP = 4294967296 - $tmp_IP;
        $tmp_IP = $tmp_IP * (-1);
    }
    $tmp_IP = long2ip($tmp_IP);
    return $tmp_IP;
}

function IPProto2str($ipproto_code) {
    switch ($ipproto_code) {
        case 0:
            return "IP";
        case 1:
            return "ICMP";
        case 2:
            return "IGMP";
        case 4:
            return "IPIP tunnels";
        case 6:
            return "TCP";
        case 8:
            return "EGP";
        case 12:
            return "PUP";
        case 17:
            return "UDP";
        case 22:
            return "XNS UDP";
        case 29:
            return "SO TP Class 4";
        case 41:
            return "IPv6 header";
        case 43:
            return "IPv6 routing header";
        case 44:
            return "IPv6 fragmentation header";
        case 46:
            return "RSVP";
        case 47:
            return "GRE";
        case 50:
            return "IPSec ESP";
        case 51:
            return "IPSec AH";
        case 58:
            return "ICMPv6";
        case 59:
            return "IPv6 no next header";
        case 60:
            return "IPv6 destination options";
        case 92:
            return "MTP";
        case 98:
            return "Encapsulation header";
        case 103:
            return "PIM";
        case 108:
            return "COMP";
        case 255:
            return "Raw IP";
        default:
            return $ipproto_code;
    }
}

function get_graph_url($index) {
	//var_dump($index);
	//$shortmonths = array('Jan'=>'01', 'Feb'=>'02', 'Mar'=>'03', 'Apr'=>'04', 'May'=>'05', 'Jun'=>'06', 'Jul'=>'07', 'Aug'=>'08', 'Sep'=>'09', 'Oct'=>'10', 'Nov'=>'11', 'Dec'=>'12');
	$months     = array('January'=>'01', 'February'=>'02', 'March'=>'03', 'April'=>'04', 'May'=>'05', 'June'=>'06', 'July'=>'07', 'August'=>'08', 'September'=>'09', 'October'=>'10', 'November'=>'11', 'December'=>'12');
	$daysmonths = array('January'=>'31', 'February'=>'28', 'March'=>'31', 'April'=>'30', 'May'=>'31', 'June'=>'30', 'July'=>'31', 'August'=>'31', 'September'=>'30', 'October'=>'31', 'November'=>'30', 'December'=>'31');
	$url        = "new=1&submit=Query+DB&num_result_rows=-1";

	//Today (8h)
	if (preg_match("/^(\d+) h/",$index,$found)) 
	{
		$url .= "&time_range=".$_SESSION['time_range']."&time[0][1]=".urlencode(">=");
		$url .= "&time[0][2]=".date("m");
		$url .= "&time[0][3]=".date("d");
		$url .= "&time[0][4]=".date("Y");
		$url .= "&time[0][5]=".$found[1];
		$url .= "&time[0][6]=00&time[0][7]=00";
		$url .= "&time_cnt=2";
		$url .= "&time[1][1]=".urlencode("<=");
		$url .= "&time[1][2]=".date("m");
		$url .= "&time[1][3]=".date("d");
		$url .= "&time[1][4]=".date("Y");
		$url .= "&time[1][5]=".$found[1];
		$url .= "&time[1][6]=59&time[1][7]=59";
	}
	// Last 24 Hours (21 8 -> 21h 8Sep)
	elseif (preg_match("/^(\d+) (\d+)/",$index,$found)) 
	{
		$desde= strtotime($found[2]."-".date("m")."-".date("Y")." ".$found[1].":00:00");
		$fecha_actual = strtotime(date("d-m-Y H:i:00",time()));
		
		if($fecha_actual<$desde)  
			$anio = strval((int)date("Y")-1);
		else 
			$anio = date("Y");
		
		$url .= "&time_range=".$_SESSION['time_range']."&time[0][1]=".urlencode(">=");
		$url .= "&time[0][2]=".date("m");
		$url .= "&time[0][3]=".$found[2];
		$url .= "&time[0][4]=".$anio;
		$url .= "&time[0][5]=".$found[1];
		$url .= "&time[0][6]=00&time[0][7]=00";
		$url .= "&time_cnt=2";
		$url .= "&time[1][1]=".urlencode("<=");
		$url .= "&time[1][2]=".date("m");
		$url .= "&time[1][3]=".$found[2];
		$url .= "&time[1][4]=".$anio;
		$url .= "&time[1][5]=".$found[1];
		$url .= "&time[1][6]=59&time[1][7]=59";
	}
	//Last Week, Last two Weeks, Last Month (5 September)
	elseif (preg_match("/^(\d+) ([A-Z].+)/",$index,$found)) 
	{
		$desde= strtotime($found[1]."-".$months[$found[2]]."-".date("Y")." 00:00:00");
		$fecha_actual = strtotime(date("d-m-Y H:i:00",time()));
		if($fecha_actual<$desde) 
			$anio = strval((int)date("Y")-1);
		else $anio = date("Y");
		
		$url .= "&time_range=".$_SESSION['time_range']."&time[0][1]=".urlencode(">=");
		$url .= "&time[0][2]=".$months[$found[2]];
		$url .= "&time[0][3]=".$found[1];
		$url .= "&time[0][4]=".$anio;
		$url .= "&time[0][5]=00";
		$url .= "&time[0][6]=00&time[0][7]=00";
		$url .= "&time_cnt=2";
		$url .= "&time[1][1]=".urlencode("<=");
		$url .= "&time[1][2]=".$months[$found[2]];
		$url .= "&time[1][3]=".$found[1];
		$url .= "&time[1][4]=".$anio;
		$url .= "&time[1][5]=23";
		$url .= "&time[1][6]=59&time[1][7]=59";
	}
	//All (October 2009)
	elseif (preg_match("/^([A-Z].+) (\d+)/",$index,$found)) 
	{
		$url .= "&time_range=".$_SESSION['time_range']."&time[0][1]=".urlencode(">=");
		$url .= "&time[0][2]=".$months[$found[1]];
		$url .= "&time[0][3]=01";
		$url .= "&time[0][4]=".$found[2];
		$url .= "&time[0][5]=00";
		$url .= "&time[0][6]=00&time[0][7]=00";
		$url .= "&time_cnt=2";
		$url .= "&time[1][1]=".urlencode("<=");
		$url .= "&time[1][2]=".$months[$found[1]];
		$url .= "&time[1][3]=".$daysmonths[$found[1]];
		$url .= "&time[1][4]=".$found[2];
		$url .= "&time[1][5]=23";
		$url .= "&time[1][6]=59&time[1][7]=59";
	}

	return $url;
}

function plot_graphic($id, $height, $width, $xaxis, $yaxis, $xticks, $xlabel, $display = false, $bgcolor="#EDEDED", $host="", $interval=1) {
	    
    $urls="";
    $plot = '<script language="javascript" type="text/javascript">';
    $plot.= '$( function () {';
    $plot.= 'var options = { ';
    $plot.= 'lines: { show:true, labelHeight:0, lineWidth: 0.7},';
    $plot.= 'points: { show:false, radius: 2 }, legend: { show: false },';
    $plot.= 'yaxis: { ticks:[] }, xaxis: { tickDecimals:0, ticks: [';
    if ( count($xticks) > 0) 
    {
        $cont = 1;
        foreach($xticks as $k => $v) 
        {
            $label = ( $cont%$interval == 0 ) ? $xlabel[$k] : "";
            $plot.= '[' . $v . ',"' . $label . '"],';
            $cont++;
			//echo "[".$k."] ";
			//$urls .= "url['".$yaxis[$k]."-".$v."'] = '../forensics/base_qry_main.php?".get_graph_url($k)."&ip=$host';\n";
        }
        
        $plot = preg_replace("/\,$/", "", $plot);
    }
    $plot.= ']},';
    $plot.= 'grid: { color: "#8E8E8E", labelMargin:0, backgroundColor: "#FFFFFF", tickColor: "#D2D2D2", hoverable:true, clickable:true}';
    $plot.= ', shadowSize:1 };';
    $plot.= 'var data = [{';
    //$plot.= 'color: "rgb(18,55,95)", label: "Events", ';
	$plot.= 'color: "rgb('.$bgcolor.')", label: "Events", ';
    $plot.= 'lines: { show: true, fill: true},'; //$plot .= 'label: "Day",';
    
    
    
    $plot.= 'data:[';
	$cont = 1;
    
    foreach($xticks as $k => $v)  
    {
        $plot.= '[' . $v . ',' . $yaxis[$xlabel[$k]] . '],';
        //$urls .= "url['".$yaxis[$k]."-".$v."'] = '?".get_graph_url($k)."';\n";
    }
    
    $plot = preg_replace("/\,$/", "]", $plot);
    $plot.= ' }];';
                
	$plot.= 'var plotarea = $("#' . $id . '");';
    if ($display == true) 
	{
        $plot.= 'plotarea.css("display", "");';
        $width = '((window.innerWidth || document.body.clientWidth)/2)';
    }
    $plot.= 'plotarea.css("height", ' . $height . ');';
    $plot.= 'plotarea.css("width", ' . $width . ');';
    $plot.= '$.plot( plotarea , data, options );';
    
    $plot.= 'var previousPoint = null;
			$("#' . $id . '").bind("plothover", function (event, pos, item) {
				if (item) {
					if (previousPoint != item.datapoint) {
						previousPoint = item.datapoint;
						$("#tooltip").remove();
						var x = item.datapoint[0].toFixed(0), y = item.datapoint[1].toFixed(0);
						showTooltip(item.pageX, item.pageY, y + " " + item.series.label,y+"-"+x);
					}
				}
				else {
					$("#tooltip").remove();
					previousPoint = null;
				}
			});';
    
    $plot.= "});\n";
    $plot.= $urls.'</script>';
    return $plot;
}

include "../graphs/charts.php";











?>