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
* - check_writable_relative()
* Classes list:
*/
require_once 'classes/Session.inc';
require_once 'classes/Net.inc';
require_once 'classes/Net_group.inc';
Session::logcheck("MenuControlPanel", "BusinessProcesses");

if (!Session::menu_perms("MenuControlPanel", "BusinessProcessesEdit")) 
{
	print _("You don't have permissions to edit risk indicators");
	exit();
}

require_once 'ossim_db.inc';
$db   = new ossim_db();
$conn = $db->connect();

$map    = GET("map");
$type   = GET("type");
$url    = ( empty($_GET['url_data']) ) ? "" : GET("url_data");
$url    = ( $url == "" && GET('url') != "" ) ? GET('url') : $url;
$nolink = intval(GET("nolinks"));

ossim_valid($map, OSS_DIGIT,'illegal:'._("Map"));
ossim_valid($type, OSS_NULLABLE, OSS_ALPHA, OSS_SCORE, 'illegal:'._("Type"));
if ( !empty($url) && $url!="REPORT" )
	ossim_valid($url, OSS_SCORE, OSS_DOT, OSS_ALPHA, OSS_DIGIT,'REPORT','\/=%\.\?', 'illegal:'._("Url"));

if ( $type != 'rect')
{
	$chosen_icon  = GET("chosen_icon");
	$chosen_icon  = str_replace("url_slash","/",$chosen_icon);
	$chosen_icon  = str_replace("url_quest","?",$chosen_icon);
	$chosen_icon  = str_replace("url_equal","=",$chosen_icon);
	
	
	$asset_type   = GET('asset_type');
	$asset_name   = utf8_decode(GET("elem"));
	$alarm_name   = utf8_decode(GET("alarm_name"));
	$iconbg       = GET('iconbg');
	$iconsize     = ( GET('iconsize') != "" ) ? GET('iconsize') : 0;	
	$noname       = (GET('noname') != "") ? "#NONAME" : "";
	
	ossim_valid($chosen_icon, OSS_NULLABLE, OSS_SCORE, OSS_ALPHA, OSS_DIGIT, OSS_SPACE, ";,.:\/\?=&()%&", 'illegal:'._("Icon"));
	ossim_valid($asset_type , OSS_NULLABLE, OSS_ALPHA, OSS_SCORE, 'illegal:'._("Asset Type"));
	ossim_valid($asset_name , OSS_NULLABLE, OSS_ALPHA, OSS_PUNC_EXT, 'illegal:'._("Asset Name"));
    ossim_valid($alarm_name , OSS_NULLABLE, OSS_ALPHA, OSS_PUNC_EXT, "#", 'illegal:'._("Alarm name"));
	ossim_valid($iconbg     , OSS_ALPHA, OSS_NULLABLE, 'illegal:'._("Icon Background"));
	ossim_valid($iconsize   , OSS_DIGIT, "-", 'illegal:'._("Icon size"));	
	
	$alarm_name   = $alarm_name.$noname;
}


if (ossim_error()) 
{
	echo ossim_get_error_clean();
	exit;
}

if ( $type != "rect" && strtolower($alarm_name) == 'rect' ) 
{
    echo _("'Rect' is a reserved word.  Please, use another name");
	exit;
}


if ($type == "rect") 
{
	$sql    = "INSERT INTO risk_indicators (name,map,url,type,type_name,icon,x,y,w,h) VALUES ('rect',?,?,'','','',100,100,50,50)";
	
	$params = array($map, $url);
    if (!$rs = &$conn->Execute($sql, $params)) 
	{
		echo $conn->ErrorMsg();
		exit();
	}
	
	$sql = "SELECT last_insert_id() AS id";
		
	if (!$rs = &$conn->Execute($sql)) 
	{
		echo $conn->ErrorMsg();
		exit();
	}
	
	if(!$rs->EOF)
	{
		$id = $rs->fields["id"];
		echo "OK###drawRect('$id','$url',100,100,50,50);\n";
	}
} 
else 
{ 
	$ip   = $asset_name;
	
	$icon = ( $iconbg != "" && $iconbg != "transparent") ? $chosen_icon."#".$iconbg : $chosen_icon;
	
	$types_with_ip = array("host", "server", "sensor");
	
	if ( !empty($asset_type) )
	{
		if( in_array($asset_type, $types_with_ip))
		{
			$what = ( $asset_type == "host" ) ? "hostname" : "name";
			$sql  = "SELECT ip FROM $asset_type WHERE $what = '$asset_name'";
			
			if (!$rs = &$conn->Execute($sql))
			{
				echo $conn->ErrorMsg();
				exit();
			}
			else
				$ip = $rs->fields["ip"];
		}
		
		if( $asset_type == "sensor" || $asset_type == "server" ) {
			$asset_type_aux = "host";
		} else {
			$asset_type_aux = $asset_type;
		}
		
		$params = array($ip, $asset_type_aux);
		
		$sql = "SELECT member, member_type FROM bp_asset_member WHERE member=? AND member_type=?";
		
		if (!$rs = &$conn->Execute($sql, $params)) 
		{
			echo $conn->ErrorMsg();
			exit();
		}
        
		if ($rs->RecordCount() == "0") 
		{
			// check if asset exist
			$sql = "INSERT INTO bp_asset_member (asset_id, member, member_type) VALUES (0, ?, ?)";
			if (!$rs = &$conn->Execute($sql, $params)) 
			{
				echo $conn->ErrorMsg();
				exit();
			}
			
            // For net_group insert all related networks
            if ($asset_type=="net_group") 
            {
            	require_once 'classes/Net_group.inc';
            	$networks = Net_group::get_networks($conn, $ip);
				foreach($networks as $network) {
				     $sql = "INSERT INTO bp_asset_member (asset_id, member, member_type) VALUES (0, ?, ?)";
				     $conn->Execute($sql, array($network->get_net_name(),"net"));
				}
            }			
		}
	}	   
   
    $params = array( $alarm_name,
					 $map, 
					 $url,
					 $asset_type,
					 $asset_name, 					 
					 $icon, 
					 $iconsize);
    
	$sql = "INSERT INTO risk_indicators (name,map,url,type,type_name,icon, x,y,w,h,size) VALUES (?,?,?,?,?,?,100,100,90,107,?)";
		
	if (!$rs = &$conn->Execute($sql, $params))
	{
		echo $conn->ErrorMsg();
		exit();
	}
	
		
	$sql = "SELECT last_insert_id() AS id";
    
	if (!$rs = &$conn->Execute($sql)) 
	{
		echo $conn->ErrorMsg();
		exit();
	}
    
	if(!$rs->EOF)
	{
		$id = $rs->fields["id"];
		echo "OK###drawDiv('$id','".Util::htmlentities($alarm_name)."','','$icon','$url',100,100,90,107,'$asset_type','".Util::htmlentities($asset_name)."', $iconsize);\n";
	}
           
}
$conn->close();

?>
