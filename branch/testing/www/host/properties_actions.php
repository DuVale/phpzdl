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

require_once 'classes/Session.inc';
require_once 'classes/Security.inc';
require_once 'classes/Host.inc';
require_once 'classes/Host_services.inc';
require_once 'ossim_db.inc';
require_once 'classes/Frameworkd_socket.inc';

$action = POST('action');
$ip     = POST('ip');

ossim_valid($ip, OSS_IP_ADDR, 'illegal:' . _("Ip Address"));
ossim_valid($action, OSS_LETTER, OSS_SCORE, 'illegal:' . _("action"));

if ( ossim_error() || empty($action) )
	exit();


$db    = new ossim_db();
$conn  = $db->connect();

switch ($action){

	case "delete":
		
		$items  = POST('data');
		
		if ( !empty($items) )
			$items = explode(',', $items);
		else
			exit();
				
		foreach ($items as $k => $v)
		{
			$item = explode("###", $v);
			
			if ( preg_match ("/item_prop_8_/", $item[0]) )
			{
				$host     = $item[1];
				$port     = $item[2];
				$protocol = $item[3];
				$version  = $item[4];
                $date     = $item[5];
                
				
				ossim_valid($host     , OSS_IP_ADDR,             'illegal:' . _("Ip Address"));
				ossim_valid($port     , OSS_PORT,                'illegal:' . _("Port"));
				ossim_valid($protocol , OSS_DIGIT,               'illegal:' . _("Protocol"));
                ossim_valid($version  , OSS_ALPHA, OSS_PUNC_EXT, 'illegal:' . _("Version"));
                ossim_valid($date     , OSS_DATETIME,            'illegal:' . _("Date"));

				if ( !ossim_error() )
					Host_services::deleteUnit($conn, $host, $port, $protocol, $version, $date);
				else{
                    echo ossim_get_error();				
                    ossim_clean_error();
                }
			
			}
			else
			{
				$id = $item[1];
								
				ossim_valid($id, OSS_DIGIT, 'illegal:' . _("Id property host reference"));
												
				if ( !ossim_error() )
					$ret = Host::delete_property($conn, $ip, $id);
				else
					ossim_clean_error();

			}
		}
	
	break;
	
	case "nagios":
		
		$items  = POST('data');
		
		if ( !empty($items) )
			$items = explode(',', $items);
		else
			exit();
		
		foreach ($items as $k => $item)
		{
			$item   = explode("###", $item);
			
			ossim_valid($item[1], OSS_PORT,                'illegal:' . _("Port"));
            ossim_valid($item[2], OSS_DIGIT,               'illegal:' . _("Protocol"));
            ossim_valid($item[3], OSS_ALPHA, OSS_PUNC_EXT, 'illegal:' . _("Version"));
            ossim_valid($item[4], OSS_DATETIME,            'illegal:' . _("Date"));
			
			if ( !ossim_error() ) 
			{
				if ( $item[5] == "nagios_ok")
					Host_services::set_nagios($conn, $ip, $item, 1);
				else
					Host_services::set_nagios($conn, $ip, $item, 0);
			}
			else
				ossim_clean_error();
			
						
		}
		
		$s = new Frameworkd_socket();
		if ($s->status) {
			if ( !$s->write('nagios action="reload"') ) 
				echo _("Frameworkd couldn't recieve a nagios command");
				
			$s->close();
		} 
		else 
			echo _("Couldn't connect to frameworkd");
		
	break;
	
	case "anom":
		
		$items  = POST('data');
		
		if ( !empty($items) )
			$items = explode(',', $items);
		else
			exit();
		
		foreach ($items as $k => $item)
		{
			$item   = explode("###", $item);
			
            ossim_valid($item[1], OSS_PORT,                'illegal:' . _("Port"));
            ossim_valid($item[2], OSS_DIGIT,               'illegal:' . _("Protocol"));
            ossim_valid($item[3], OSS_ALPHA, OSS_PUNC_EXT, 'illegal:' . _("Version"));
            ossim_valid($item[4], OSS_DATETIME,            'illegal:' . _("Date"));
            
			if ( !ossim_error() ) 
			{
				Host_services::set_anom($conn, $ip, $item, 0);
			}
			else
				ossim_clean_error();
			
						
		}
		
	break;
    
	case "delete_anom":
		
		$items  = POST('data');
		
		if ( !empty($items) )
			$items = explode(',', $items);
		else
			exit();
		
		foreach ($items as $k => $item)
		{
			$item   = explode("###", $item);
		
			ossim_valid($item[1], OSS_PORT,               'illegal:' . _("Port"));
            ossim_valid($item[2], OSS_DIGIT,              'illegal:' . _("Proto"));
            ossim_valid($item[3], OSS_ALPHA, OSS_PUNC_EXT, 'illegal:' . _("Version"));
            ossim_valid($item[4], OSS_DATETIME,           'illegal:' . _("Date"));
            
			if ( !ossim_error() ) 
			{
				Host_services::delete_anom($conn, $ip, $item, 0);
			}
			else
				ossim_clean_error();
			
						
		}
		
	break;

	case "add":
	
		$sensor       = null;
		$property_ref = POST('inv_prop_ref');
		$value        = POST('inv_prop_value'); 
		$extra        = POST('inv_prop_version'); 
		$extra        = ( empty($extra) ) ? "None" : $extra;
				
		ossim_valid($ip, OSS_IP_ADDR, 'illegal:' . _("Ip Address"));
		ossim_valid($property_ref, OSS_DIGIT, 'illegal:' . _("Property reference"));
		ossim_valid($value, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_AT, OSS_NL, 'illegal:' . _("Value"));
					
		if ( ossim_error() ) 
			echo ossim_get_error();
		else
		{
			$ret = Host::insert_property($conn, $ip, $sensor, $property_ref, $value, $extra);
						
			if ( $ret !== true ) 
				echo $ret;
		}
	
	
	break;
	
	
	case "update":
		
		
		$value        = POST('inv_prop_value'); 
		$extra        = POST('inv_prop_version'); 
		$extra        = ( empty($extra) ) ? "None" : $extra;
		$id_prop      = POST('inv_prop_id');
		$source_id    = POST('inv_prop_source_id'); 	
		$anom         = POST('inv_prop_anom');	
		$date 	      = date("Y-m-d H:i:s");	
		
		ossim_valid($id_prop,   OSS_DIGIT, 'illegal:' . _("Property id"));
		ossim_valid($source_id, OSS_DIGIT, 'illegal:' . _("Source id"));
		ossim_valid($anom,      OSS_DIGIT, 'illegal:' . _("Anomaly"));
			
		if ( ossim_error() ) 
			echo ossim_get_error();
		else
		{
			$ret = Host::update_property($conn, $ip, $id_prop, $value, $extra, $date, $source_id);
			
			if ( $ret !== true ) 
				echo $ret;
		}
		
	break;
	
	case "accept_change":
		
		$id_prop = POST('id_change');
						
		ossim_valid($id_prop,   OSS_DIGIT, 'illegal:' . _("Property id"));
					
		if ( ossim_error() ) 
			echo ossim_get_error();
		else
		{
			$ret = Host::accept_change($conn, $ip, $id_prop);
			
			if ( $ret !== true ) 
				echo $ret;
		}
		
	break;
	
	case "discard_change":
		
		$id_prop = POST('id_change');
						
		ossim_valid($id_prop,   OSS_DIGIT, 'illegal:' . _("Property id"));
					
		if ( ossim_error() ) 
			echo ossim_get_error();
		else
		{
			$ret = Host::delete_property($conn, $ip, $id_prop);
			
			if ( $ret !== true ) 
				echo $ret;
		}
		
	break;
}

$db->close($conn);	
?>