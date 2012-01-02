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
*/

function indicatorAllowed($conn,$type,$type_name,$hosts,$sensors,$nets) {
	
	$has_perm = 1;
    
    if ($type == "host") 
		$has_perm = ( !empty($hosts[$type_name]) ) ? 1 : 0;
	elseif ($type == "sensor" || $type == "server") 
		$has_perm = ( !empty($sensors[$type_name]) ) ? 1 : 0;
	elseif ($type == "net") 
		$has_perm = ( !empty($nets[$type_name]) ) ? 1 : 0;
	elseif ($type == "host_group") 
	{
		$has_perm = ( Session::groupHostAllowed($conn,$type_name) ) ? 1 : 0;
	} 
    elseif ($type == "net_group") 
	{
		$has_perm = ( Session::groupAllowed($conn,$type_name) ) ? 1 : 0;
	} 
       	
	return $has_perm;
}

// convert risk value into risk semaphore
function get_value_by_digit($digit) {
    if (intval($digit) > 7) 
        return 'r';
    elseif(intval($digit) > 3) 
        return 'a';
    elseif($digit < 0) 
        return 'b';
    elseif($digit != "" || $digit > -1) 
        return 'v';
    else 
        return 'b';
}

// asset value in BBDD?
function is_in_assets($conn,$name,$type) {
	if ($type == "host") 
		$sql = "SELECT * FROM host WHERE hostname=\"$name\"";
	elseif ($type == "sensor") 
		$sql = "SELECT * FROM sensor WHERE name=\"$name\"";
    elseif ($type == "net") 
		$sql = "SELECT * FROM net WHERE name=\"$name\"";
	elseif ($type == "host_group") 
		$sql = "SELECT * FROM host_group WHERE name=\"$name\"";
	elseif ($type == "net_group") 
		$sql = "SELECT * FROM net_group WHERE name=\"$name\"";
	
    $result = $conn->Execute($sql);
	
    return (!$result->EOF) ? 1 : 0;
}

// get asset name, value and sensor
function get_assets($conn,$name,$type,$host_types) {
	
    // in_assets first
	$in_assets = is_in_assets($conn,$name,$type);
	
	// Asset values
	$ips  = $name;
	$what = "name";
	
	if(in_array($type, $host_types))
	{
    	if($type == "host") 
			$what = "hostname";                
        
		$query  = "SELECT ip FROM $type WHERE $what = ?";
					
        $params = array($name);
				        
		if ($rs3 = &$conn->Execute($query, $params)) 
		{
            $name = $rs3->fields["ip"];
            if ($rs3->EOF) 
                $in_assets = 0;
        }
				        
		// Related sensor
        $sensor = $name;
        
		if ($type == "host") 
		{
            require_once 'classes/Host.inc';
            $sensors = Host::get_related_sensors($conn,$name,false);
            $sensor  = ($sensors[0]!="") ? $sensors[0] : $name;
        }
    } 
	elseif ($type == "net") 
	{
        $query = "SELECT ips FROM net WHERE name = ?";
        $params = array($name);
        if ($rs3 = &$conn->Execute($query, $params)) {
            $ips = $rs3->fields["ips"];
            if ($rs3->EOF) $in_assets = 0;
        }
        // related sensor
        require_once 'classes/Net.inc';
        $sensors = Net::get_related_sensors($conn,$name);
        $sensor = ($sensors[0]!="") ? $sensors[0] : "";
    } 
    elseif ($type == "net_group") 
	{
        $query  = "SELECT ngr.net_name, n.ips FROM net_group_reference AS ngr, net AS n WHERE ngr.net_group_name = ? AND ngr.net_name = n.name";
        $params = array($name);
        if ($rs3 = &$conn->Execute($query, $params)) 
        {
            $ipng = array();
            while (!$rs3->EOF) 
            {
                $ipng[] = $rs3->fields["ips"];
                $rs3->MoveNext();
            }
            $ips = (count($ipng) > 0) ? implode(",",$ipng) : "'0.0.0.0/0'";
            if (count($ipng) == 0 ) $in_assets = 0;
        }
        // Related sensors
        require_once 'classes/Net_group.inc';
        $sensors = Net_group::get_related_sensors($conn,$name);
        $sensor  = ( $sensors[0] != "" ) ? $sensors[0] : $name;
    } 
	elseif ($type == "host_group") 
	{
        $query = "SELECT host_ip FROM host_group_reference WHERE host_group_name = ?";
        $params = array($name);
        if ($rs3 = &$conn->Execute($query, $params)) 
        {
            $iphg = array();
            while (!$rs3->EOF) 
            {
                $iphg[] = "'".$rs3->fields["host_ip"]."'";
                $rs3->MoveNext();
            }
            $ips = (count($iphg) > 0) ? implode(",",$iphg) : "'0.0.0.0'";
            if (count($iphg) == 0 ) $in_assets = 0;
        }
        // Related sensors
        require_once 'classes/Host_group.inc';
        $sensors = Host_group::get_related_sensors($conn,$name);
        $sensor  = ($sensors[0]!="") ? $sensors[0] : $name;
    }

	return array($name,$sensor,$type,$ips,$what,$in_assets);
}

// Get asset risk values
function get_values($conn,$host_types,$type,$name,$ips,$only_values = false) {
	
     if ($only_values) 
    {
        $RiskValue  = -1;
        $VulnValue  = -1;
        $AvailValue = -1;
	} 
    else 
    {
        $RiskValue  = 'b';
        $VulnValue  = 'b';
        $AvailValue = 'b';
	}

	$params = array($name);
	$nets = array();

    if (in_array($type, $host_types)) 
    {
	    $query = "select severity,member from bp_member_status where member = ? and measure_type = \"host_metric\"";
    } 
    elseif ($type == "host_group") 
    {
        $query = "select severity,member from bp_member_status where member = ? and measure_type = \"host_group_metric\"";
    } 
    elseif ($type == "net_group") 
    {
        $query = "select severity,member from bp_member_status where member = ? and measure_type = \"net_group_metric\"";
		#require_once 'classes/Net_group.inc';
		#$networks = Net_group::get_networks($conn, $name);
		#foreach($networks as $network) $nets[] = $network->get_net_name();
		#if (empty($nets)) $nets[] = "0.0.0.0/0";
        #$query = "select severity,member from bp_member_status where member in ('".implode("','",$nets)."') and measure_type = \"net_metric\" order by severity desc";
        #$params = array();
    } 
    else 
    {
        $query = "select severity,member from bp_member_status where member = ? and measure_type = \"net_metric\"";
    }
    
    //echo "$query\n<br>";
    if (!$rs2 = &$conn->Execute($query, $params)) 
    {
        print $conn->ErrorMsg();
    } 
    else 
    {
        $r_ip = $rs2->fields["member"];
    	if ($only_values) 
        {
        	$RiskValue = ($rs2->fields["severity"] == "") ? -1 : intval($rs2->fields["severity"]);
        } 
        else 
        {
        	$RiskValue = get_value_by_digit($rs2->fields["severity"]);
        }
    }
    
    
    if (in_array($type, $host_types)) 
    {
        $query = "select severity,member from bp_member_status where member = ? and measure_type = \"host_vulnerability\"";
    } 
    elseif ($type == "host_group") 
    {
        $query = "select severity,member from bp_member_status where member = ? and measure_type = \"host_group_vulnerability\"";
    } 
    elseif ($type == "net_group") 
    {
        $query = "select severity,member from bp_member_status where member = ? and measure_type = \"net_group_vulnerability\"";
        #$query = "select severity,member from bp_member_status where member in ('".implode("','",$nets)."') and measure_type = \"net_vulnerability\" order by severity desc";
        #$params = array();        
    } 
    else 
    {
        $query = "select severity,member from bp_member_status where member = ? and measure_type = \"net_vulnerability\"";
    }
   
    //echo "$query\n<br>";
    if (!$rs2 = &$conn->Execute($query, $params)) 
    {
        print $conn->ErrorMsg();
    } 
    else 
    {
        $v_ip = $rs2->fields["member"];
        if ($only_values) 
        {
        	$VulnValue = ($rs2->fields["severity"] == "") ? -1 : intval($rs2->fields["severity"]);
        }
        else 
        {
	        $VulnValue = get_value_by_digit($rs2->fields["severity"]);
        }
    }

    if (in_array($type, $host_types)) 
    {
        $query = "select severity,member from bp_member_status where member = ? and measure_type = \"host_availability\"";
    } 
    elseif ($type == "host_group") 
    {
        $query = "select severity,member from bp_member_status where member = ? and measure_type = \"host_group_availability\"";
    } 
    elseif ($type == "net_group") 
    {
        $query = "select severity,member from bp_member_status where member = ? and measure_type = \"net_group_availability\"";
        #$query = "select severity,member from bp_member_status where member in ('".implode("','",$nets)."') and measure_type = \"net_availability\" order by severity desc";
        #$params = array();
    } 
    else 
    {
        $query = "select severity,member from bp_member_status where member = ? and measure_type = \"net_availability\"";
    }
   
   //echo "$query\n<br>";
    if (!$rs2 = &$conn->Execute($query, $params)) 
    {
        print $conn->ErrorMsg();
    } 
    else 
    {
        $a_ip = $rs2->fields["member"];
        if ($only_values) 
        {
        	$AvailValue = ( $rs2->fields["severity"] == "" ) ? -1 : intval($rs2->fields["severity"]);
        } 
        else 
        {
	        $AvailValue = get_value_by_digit($rs2->fields["severity"]);
        }
    }
    return array($RiskValue,$VulnValue,$AvailValue,$v_ip,$a_ip,$r_ip);
}


// get risk values for linked map (recursive)
function get_map_values($conn,$map,$name,$type,$host_types, $map_already_visited = array()) {

	$map_already_visited[] = $map;
	$query = "select * from risk_indicators where name <> 'rect' AND map= ?";
	$params = array($map);
	$RiskValue_max  = -1;
	$VulnValue_max  = -1;
	$AvailValue_max = -1;
	$v_ip = $a_ip = $sensor = "";
	$ips  = $name;
	$what = "name";
	$in_assets = 0;
	if (!$rs4 = &$conn->Execute($query, $params)) {
		print $conn->ErrorMsg();
	} 
    else 
    {
		while (!$rs4->EOF) 
        {
			//print_r($rs4->fields);
			// Linked to other map? recursive
			if (preg_match("/view\.php\?map\=(\d+)/",$rs4->fields['url'],$found)) {
				if (!in_array($found[1], $map_already_visited)){
					return get_map_values($conn,$found[1],$rs4->fields["type_name"],$rs4->fields["type"],$host_types, $map_already_visited);
				}
			}
			// Asset Values per link. Get the most risk value
			list ($name,$sensor,$type,$ips,$what,$in_assets) = get_assets($conn,$rs4->fields["type_name"],$rs4->fields["type"],$host_types);
			list ($RiskValue_aux,$VulnValue_aux,$AvailValue_aux,$v_ip_aux,$a_ip_aux,$r_ip_aux) = get_values($conn,$host_types,$rs4->fields["type"],$name,$ips,true);
			if ($RiskValue_aux > $RiskValue_max) { $RiskValue_max = $RiskValue_aux; }
			if ($VulnValue_aux > $VulnValue_max) { $VulnValue_max = $VulnValue_aux; $v_ip = $v_ip_aux; }
			if ($AvailValue_aux > $AvailValue_max) { $AvailValue_max = $AvailValue_aux; $a_ip = $a_ip_aux; }
			//echo "$RiskValue_aux,$VulnValue_aux,$AvailValue_aux,$v_ip_aux,$a_ip_aux\n<br>";
			$rs4->MoveNext();
		}
	}
	$RiskValue  = get_value_by_digit($RiskValue_max);
	$VulnValue  = get_value_by_digit($VulnValue_max);
	$AvailValue = get_value_by_digit($AvailValue_max);
	return array($RiskValue,$VulnValue,$AvailValue,$v_ip,$a_ip,$name,$sensor,$type,$ips,$what,$in_assets);
}

// print risk indicator table 
function print_indicator_content($conn,$rs,$linked = 1) {

    $host_types = array("host", "server", "sensor");
    
    // Linked to another map: loop by this map indicators
    if (preg_match("/view\.php\?map\=(\d+)/",$rs->fields['url'],$found)) 
	{
		list ($RiskValue,$VulnValue,$AvailValue,$v_ip,$a_ip,$name,$sensor,$type,$ips,$what,$in_assets) = get_map_values($conn,$found[1],$rs->fields["type_name"],$rs->fields["type"],$host_types);
    } 
	else 
	{
    	// Asset Values
        
        $asset_type = $rs->fields["type"];
        $asset_key  = $rs->fields["type_name"];
        
		list ($name,$sensor,$type,$ips,$what,$in_assets) = get_assets($conn,$rs->fields["type_name"],$rs->fields["type"],$host_types);
    	list ($RiskValue,$VulnValue,$AvailValue,$v_ip,$a_ip,$r_ip) = get_values($conn,$host_types,$type,$name,$ips,false);
    }
		
    if ( $type == "net" || $type == "net_group")
    {
        $gtype = "net";
    }
    else
    {
        $gtype      = "host";
        $ips        =  $name;
        if ( $type == "host" )
            $asset_key  =  $name;
    }
   

    if ( $type == "host_group")
    {
        $r_data = $r_ip;
        $v_data = $v_ip;
    }
    else
    {
        $r_data = ( $type == "net_group" ) ? "group_".$name : $name;
        $v_data = $ips;
    }
       
    $a_data = $sensor;
       
    
    $r_url = "../control_panel/show_image.php?ip=".urlencode($r_data)."&range=week&what=compromise&start=N-1Y&end=N&type=$gtype&zoom=1&hmenu=Risk&smenu=Metrics";
    $v_url = "../vulnmeter/index.php?value=".urlencode($v_data)."&type=hn&hmenu=Vulnerabilities&smenu=Vulnerabilities";
    $a_url = "../nagios/index.php?sensor=".urlencode($a_data)."&hmenu=Availability&smenu=Availability&nagios_link=".urlencode("/cgi-bin/status.cgi?host=all");

    $size = ($rs->fields["size"] > 0 || $rs->fields["size"] == -1) ? $rs->fields["size"] : '';
	$icon = $rs->fields["icon"];
	
	if (preg_match("/\#/",$icon)) 
	{
		$aux = explode("#",$icon);
		$icon = $aux[0]; $bgcolor = $aux[1];
	} 
	else
		$bgcolor = "transparent";
	
	if ($linked==0)
	{
		$r_url = $v_url = $a_url = $url="javascript:;";
	}
    else
    {
		if ( $rs->fields["url"] == "REPORT" )
		{
			$url = "../report/host_report.php?asset_type=$asset_type&asset_key=$asset_key";
		}
		else
			$url =  ( $rs->fields["url"] != '' ) ? $rs->fields["url"] : "javascript:;";
	}
	
	if (!$in_assets) 
    {
		$icon = "../pixmaps/marker--exclamation.png";
		$size = "16";
	}
	
	$name = (mb_detect_encoding($rs->fields["name"]." ",'UTF-8,ISO-8859-1') == 'UTF-8') ?  $rs->fields["name"] : mb_convert_encoding($rs->fields["name"], 'UTF-8', 'ISO-8859-1');

	?>
		
		<table width="100%" border='0' cellspacing='0' cellpadding='1' style="background-color:<?php echo $bgcolor ?>; text-align:center; margin-left:2px; margin-right:2px">
        <?php
		if (!preg_match("/#NONAME/",$name,$null))
		{
        ?>
        <tr>
            <td align='center' nowrap='nowrap'>
				<a href="<?php echo $url ?>" class="ne"><i><?php echo $name?></i></a>
			</td>
		</tr>
		<?php
		}
		
		if ($size!=-1)
		{	
		?>		
		<tr>
			<td>
				<a href="<?php echo $url ?>"><img src="<?php echo $icon ?>" <?php if ($size > 0) { ?>width="<?php echo $size ?>"<?php } ?> border='0'/></a>
			</td>
        </tr>
        <?php
        }
        ?>
        <tr align='center'>
            <td style="margin-left:2px; margin-right:2px">
				<?php
                if(($size==-1) && preg_match("/#NONAME/",$name,$null))
                {
                ?>
                <table border='0' cellspacing='0' cellpadding='2' style="text-align:center; margin:auto;">
					<tr>
						<td><a class="ne11" target="main" href="<?php echo $r_url ?>"><img src="images/<?php echo $RiskValue ?>.gif" border='0'/></a></td>
						<td><a class="ne11" target="main" href="<?php echo $v_url ?>"><img src="images/<?php echo $VulnValue ?>.gif" border='0'/></a></td>
						<td><a class="ne11" target="main" href="<?php echo $a_url ?>"><img src="images/<?php echo $AvailValue ?>.gif" border='0'/></a></td>
					</tr>
				</table>
                <?php
                }else{
                ?>
                <table border='0' cellspacing='0' cellpadding='2' style="text-align:center; margin:auto;">
					<tr>
						<td><a class="ne11" target="main" href="<?php echo $r_url ?>">R</a></td>
						<td><a class="ne11" target="main" href="<?php echo $v_url ?>">V</a></td>
						<td><a class="ne11" target="main" href="<?php echo $a_url ?>">A</a></td>
					</tr>
					<tr>
						<td><img src="images/<?php echo $RiskValue ?>.gif" border='0'/></td>
						<td><img src="images/<?php echo $VulnValue ?>.gif" border='0'/></td>
						<td><img src="images/<?php echo $AvailValue ?>.gif" border='0'/></td>
					</tr>
				</table>
                <?php
                }
                ?>
			</td>
		</tr>
	</table>
	
	<?php
}

function print_indicators($map, $print_inputs = false, $linked = 1) {
	require_once 'classes/Host.inc';
	require_once 'classes/Net.inc';
	require_once 'ossim_db.inc';

	$db   = new ossim_db();
	$conn = $db->connect();
	list($sensors_aux, $hosts_aux, $icons_aux) = Host::get_ips_and_hostname($conn,true);
	$all_nets                                  = Net::get_list($conn);
	
	$hosts   = array_flip($hosts_aux);
	$sensors = array_flip($sensors_aux);
	
	$nets = array();
	foreach ($all_nets as $k => $v)
	{
		$nets[$v->get_name()] = $v->get_name();
	}
	
	$query  = "SELECT * FROM risk_indicators WHERE name <> 'rect' AND map= ?";
	$params = array($map);
	
	if (!$rs = &$conn->Execute($query, $params)) 
		print $conn->ErrorMsg();
	else 
	{
		while (!$rs->EOF) 
		{
			if (Session::am_i_admin()) 
				$has_perm = 1;
			else
				$has_perm = indicatorAllowed($conn, $rs->fields['type'], $rs->fields['type_name'], $hosts, $sensors, $nets);
						
			if ( $has_perm ) 
			{
				$id = $rs->fields["id"];
				
				if ($print_inputs) 
				{
					$name      = ( mb_detect_encoding($rs->fields["name"]." ",'UTF-8,ISO-8859-1') == 'UTF-8' ) ?  $rs->fields["name"] : mb_convert_encoding($rs->fields["name"], 'UTF-8', 'ISO-8859-1');
					$type 	   = $rs->fields["type"];
					$type_name = ( mb_detect_encoding($rs->fields["type_name"]." ",'UTF-8,ISO-8859-1') == 'UTF-8' ) ?  $rs->fields["type_name"] : mb_convert_encoding($rs->fields["type_name"], 'UTF-8', 'ISO-8859-1');
					$url  	   = $rs->fields["url"];
					$size 	   = $rs->fields["size"];
					$icon 	   = preg_replace("/\#.*/","",$rs->fields["icon"]);
					$val       = ( preg_match("/\#(.+)/", $rs->fields["icon"],$found) ) ? $found[1] : "";
					
					
					echo "<input type='hidden' name='dataname".$id."'     id='dataname".$id."'     value='".$name."'/>\n";
					echo "<input type='hidden' name='datatype".$id."'     id='datatype".$id."'     value='".$type."'/>\n";
					echo "<input type='hidden' name='type_name".$id."'    id='type_name".$id."'    value='".$type_name."'/>\n";
					echo "<input type='hidden' name='dataurl".$id."'      id='dataurl".$id."'      value='".$url."'/>\n";
					echo "<input type='hidden' name='dataicon".$id."'     id='dataicon".$id."'     value='".$icon."'/>\n";
					echo "<input type='hidden' name='dataiconsize".$id."' id='dataiconsize".$id."' value='".$size."'/>\n";
					echo "<input type='hidden' name='dataiconbg".$id."'   id='dataiconbg".$id."'   value='".$val."'/>\n";
				}
				
				if ($rs->fields["w"]>60) $rs->fields["w"]=60;
				if ($rs->fields["h"]>60) $rs->fields["h"]=60;
				$style = "z-index:10;				
						  cursor:pointer;
						  visibility:hidden;
						  position:absolute;
						  left:".$rs->fields["x"]."px;
						  top:".$rs->fields["y"]."px;
						  height:".$rs->fields["h"]."px;
						  width:".$rs->fields["w"]."px;
				";
				
				?>
				<div id="indicator<?php echo $id?>" class="itcanbemoved" style="<?php echo $style?>">
					<?php print_indicator_content($conn,$rs,$linked) ?>
				</div>
				<?php
			}
			
			$rs->MoveNext();
		}
		
		
	}
	
	$query  = "SELECT * FROM risk_indicators WHERE name='rect' AND map = ?";
	$params = array($map);

	if (!$rs = &$conn->Execute($query, $params))             
		print $conn->ErrorMsg();
	else 
	{
		while (!$rs->EOF) 
		{
						
            $id = $rs->fields["id"];
            
            if ($print_inputs) 
            {
                $name = $rs->fields["name"];
                $url  = $rs->fields["url"];
                
                echo "<input type='hidden' name='dataname".$id."' id='dataname".$id."' value='".$name."'/>\n";
                echo "<input type='hidden' name='dataurl".$id."' id='dataurl".$id."' value='".$url."'/>\n";
            }
				
            $style = "border:1px solid transparent;
                      cursor:pointer;
                      background:url(../pixmaps/1x1.png);
                      visibility:hidden;
                      position:absolute;
                      left:".$rs->fields["x"]."px;
                      top:".$rs->fields["y"]."px;
                      height:".$rs->fields["h"]."px;
                      width:".$rs->fields["w"]."px;
            ";
				
            $link_to_map = ( $print_inputs == false ) ? "onclick='document.location.href=\"".$rs->fields["url"]."\";'" : "";
            ?>
                     
            <div id="rect<?php echo $id?>" class="itcanbemoved" style="<?php echo $style?>" <?php echo $link_to_map?>>
                <?php print_rectangle_content($conn, $print_inputs) ?>
            </div>
            <?php
			
            
			$rs->MoveNext();
		}
	}
}

function print_rectangle_content($conn, $print_inputs=false) {
	if ( $print_inputs ) 
	{ 
		?>
		<div class="itcanberesized" style='position:absolute;bottom:0px;right:0px;cursor:nw-resize;'>
			<img src='../pixmaps/resize.gif' border='0'/>
        </div>
		<?php 
    } 
	?>
	
	<table border='0' cellspacing='0' cellpadding='0' width="100%" height="100%" style="border:0px;">
		<tr><td style="border:1px dotted black">&nbsp;</td></tr>
	</table>
	
	<?php
}

function print_small_error($message, $width="100%"){
	$style = '
		color: #D8000C; 
		background-color: #FFBABA; 
		background-image: url("../pixmaps/ossim_error.png");
		display: block;
		text-align:center;
		width=$width;';
		
        echo "<div class='ossim_success' style='$style' >$message</div>";
}

?>
