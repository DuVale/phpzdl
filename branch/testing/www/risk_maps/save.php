<?
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
require_once 'ossim_db.inc';
require_once 'classes/Security.inc';
require_once 'classes/Session.inc';
require_once 'classes/Log_action.inc';
include("riskmaps_functions.php");

Session::logcheck("MenuControlPanel", "BusinessProcesses");

$infolog = array("Indicator Risk Maps");
Log_action::log(49, $infolog);

if (!Session::menu_perms("MenuControlPanel", "BusinessProcessesEdit")) 
{
	print _("You don't have permissions to edit risk indicators");
	exit();
}

$db   = new ossim_db();
$conn = $db->connect();

$map  = GET("map");
$data = GET("data");
$name = GET("alarm_name");
$icon = GET("icon");
$url  = GET("url");
$ida  = GET("id");
$type      = GET("type");
$type_name = GET("elem");
$iconbg    = GET('iconbg');
$iconsize  = (GET('iconsize') != "") ? GET('iconsize') : 0;
$noname    = (GET('noname') != "") ? "#NONAME" : "";

$name = $name.$noname;

$icon = str_replace("url_slash","/",$icon);
$icon = str_replace("url_quest","?",$icon);
$icon = str_replace("url_equal","=",$icon);
$url  = str_replace("url_slash","/",$url);
$url  = str_replace("url_quest","?",$url);
$url  = str_replace("url_equal","=",$url);

ossim_valid($map,  OSS_DIGIT,                                                            'illegal:'._("Map"));
ossim_valid($ida,  OSS_DIGIT, OSS_NULLABLE,                                              'illegal:'._("Map"));
ossim_valid($data, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA,  ";,.",                           'illegal:'._("Data"));
ossim_valid($url,  OSS_NULLABLE, OSS_SCORE, OSS_ALPHA, OSS_SPACE, ";,.:\/\?=&()%&",      'illegal:'._("Data"));
ossim_valid($name, OSS_NULLABLE, OSS_SCORE, OSS_ALPHA, OSS_SPACE, ";,.:\/\?=&()%&#",     'illegal:'._("Name"));
ossim_valid($icon, OSS_NULLABLE, OSS_SCORE, OSS_ALPHA, OSS_SPACE, ";,.:\/\?=&()%&",      'illegal:'._("Icon"));
ossim_valid($type, OSS_NULLABLE, OSS_SCORE, OSS_ALPHA, OSS_SPACE, ";,.:\/\?=&()%&",      'illegal:'._("Type"));
ossim_valid($type_name, OSS_NULLABLE, OSS_SCORE, OSS_ALPHA, OSS_SPACE, ";,.:\/\?=&()%&", 'illegal:'._("Type_name"));
ossim_valid($iconbg, OSS_ALPHA, OSS_NULLABLE,                                            'illegal:'._("Iconbg"));
ossim_valid($iconsize, OSS_DIGIT, "-",                                                   'illegal:'._("Iconsize"));


//var_dump($type);
//var_dump($type_name);

$path = explode("pixmaps",$icon);
if (count($path)>1) $icon = "pixmaps".$path[1];

if (ossim_error()) 
{
	echo ossim_get_error(); 
	exit();
}

//Clean bp_asset_member
$query = "DELETE FROM bp_asset_member WHERE member = '' OR member_type = ''";
if (!$rs = &$conn->Execute($query)) {
    print $conn->ErrorMsg();
}

$indicators  = array();
$delete_list = array();
$elems       = explode(";",$data);
foreach ($elems as $elem) 
{
    if (trim($elem)!="") 
    {
        $param = explode(",",$elem);
        $id = str_replace("rect","",str_replace("indicator","",$param[0]));
        $indicators[$id]["x"] = str_replace("px","",$param[1]);
        $indicators[$id]["y"] = str_replace("px","",$param[2]);
        $indicators[$id]["w"] = str_replace("px","",$param[3]);
        $indicators[$id]["h"] = str_replace("px","",$param[4]);
    }
}

$active = array_keys($indicators);
$query  = "SELECT id, name, type, type_name from risk_indicators where map=?";
$params = array($map);

if (!$rs = &$conn->Execute($query, $params)) {
    $log = $conn->ErrorMsg();
} 
else 
{
    $indicator_type = null;
    while (!$rs->EOF) 
    {
        if (in_array($rs->fields["id"],$active)) 
        {
            
            if ( $rs->fields["id"] == $ida)
                $indicator_type = ( $rs->fields["name"] == 'rect' ) ? "rectangle" : "indicator";
             
           	$pos = $indicators[$rs->fields["id"]];
            
			// RISK INDICATOR
            $query = "UPDATE risk_indicators SET x= ?,y= ?, w= ?, h= ? WHERE id= ?";
            $params = array($pos["x"],$pos["y"],$pos["w"],$pos["h"], $rs->fields["id"]);
            $conn->Execute($query, $params);
            
			// BP ASSET
            if ($rs->fields["type_name"] != "" && $rs->fields["type"] != "" )
            {
                $params = array($rs->fields["type_name"],$rs->fields["type"]);
                $sql = "SELECT member, member_type FROM bp_asset_member WHERE member=? AND member_type=?";
                
                if ($ri = &$conn->Execute($sql, $params))
                {
                    if ($ri->RecordCount() == "0") 
                    {
                        // check if asset exist
                        $sql = "INSERT INTO bp_asset_member (asset_id, member, member_type) VALUES (0, ?, ?)";
                        $conn->Execute($sql, $params);
                        
                    }						
                }

	            // For net_group insert all related networks
	            if ($rs->fields["type"]=="net_group") 
	            {
	            	require_once 'classes/Net_group.inc';
	            	$networks = Net_group::get_networks($conn, $rs->fields["type_name"]);
					foreach($networks as $network) {
						$sql = "SELECT member, member_type FROM bp_asset_member WHERE member=? AND member_type=?";
						$rn = &$conn->Execute($sql, array($network->get_net_name(),"net"));
						
						if ($rn && $rn->RecordCount() == "0") 
                    	{
							$sql = "INSERT INTO bp_asset_member (asset_id, member, member_type) VALUES (0, ?, ?)";
							$conn->Execute($sql, array($network->get_net_name(),"net"));
						}
					}
	            }
          
            }

        } 
        else 
            $delete_list[] = array($rs->fields["id"],$rs->fields["type"],$rs->fields["type_name"]);
        
        $rs->MoveNext();
    }
}
	
	$name = (mb_detect_encoding($name." ",'UTF-8,ISO-8859-1') == 'UTF-8') ?  mb_convert_encoding($name, 'ISO-8859-1', 'UTF-8') : $name;
	
	$update_status = false;
   			
	if ( $indicator_type == 'rectangle' )
	{
		$query = "UPDATE risk_indicators set url= ? WHERE id= ?";
        $params = array($url,$ida);
           
        $conn->Execute($query,$params);
		$update_status = true;
	}
	else
	{
		if($icon != "")
		{ 
			$icon       = ($iconbg != "" && $iconbg != "transparent") ? $icon."#".$iconbg : $icon;
			$params     = array($name, $url, $type, $type_name, $iconsize, $icon, $ida);
			$icon_query = ", icon= ?";
		}
		else
		{
			$params     = array($name, $url, $type, $type_name, $iconsize, $ida);
			$icon_query = "";
		}
		if ( $ida !="" && $name !="" ) 
		{
			$query = "UPDATE risk_indicators set  name= ?, url= ?, type= ?, type_name= ?, size= ? ".$icon_query." WHERE id= ?";
			$conn->Execute($query,$params);
			$update_status = true;
		}
	}

	
    if($update_status == true) 
	{
        echo "OK###refresh_indicators();";
    } 
	
    
	foreach ($delete_list as $idb) 
	{
        $host_types = array("host", "server", "sensor");

        list ($name,$sensor,$type,$ips,$what,$in_assets) = get_assets($conn,$idb[2],$idb[1],$host_types);
        
        if($type == "sensor" || $type == "server")      
            $type="host";
        
        $query  = "DELETE FROM bp_asset_member WHERE asset_id=0 AND member=? AND member_type=?";
        $params = array($name,$type);
        $conn->Execute($query, $params);
        
        $query = "DELETE FROM risk_indicators WHERE id= ?";
        $conn->Execute($query, array($idb[0]));
    }
	
	shell_exec('/usr/bin/sudo /usr/share/ossim/scripts/framework-restart > /dev/null 2>/dev/null &');
	
    $conn->close();
?>
