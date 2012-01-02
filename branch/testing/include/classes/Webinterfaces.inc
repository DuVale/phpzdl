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
*/
require_once ("ossim_db.inc");
require_once 'classes/Log_action.inc';

class Webinterfaces {
    var $id;
    var $ip;
    var $name;
    var $status;
    var $foundrows;
	
    function Webinterfaces($id, $ip, $name, $status, $foundrows = 0) {
        $this->id        = $id;
        $this->ip        = $ip;
        $this->name      = $name;
        $this->status    = $status;
        $this->foundrows = $foundrows;
    }
    
    function get_id() {
        return $this->id;
    }
	
    function get_ip() {
        return $this->ip;
    }
	
    function get_name() {
        return $this->name;
    }
	
    function get_status() {
        return $this->status;
    }
	
    function get_foundrows() {
        return $this->foundrows;
    }
	
    function get_list($conn, $args = "") {
        $list = array();
        $iffoundrows = preg_match("/LIMIT/i", $args) ? true : false;
		$select      = ($iffoundrows) ? "SQL_CALC_FOUND_ROWS *, inet_ntoa(ip) as ip_decode" : "*, inet_ntoa(ip) as ip_decode";
        
		$query       = OssimQuery("SELECT $select FROM `web_interfaces` $args");
		if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } 
		else 
		{
            $foundrows = 0;
            if ($iffoundrows) {
                if (!$rf = & $conn->Execute("SELECT FOUND_ROWS() as total")) print $conn->ErrorMsg();
                else $foundrows = $rf->fields["total"];
            }
            while (!$rs->EOF) {
                $list[] = new Webinterfaces($rs->fields["id"],
                                            $rs->fields["ip_decode"], 
                                            $rs->fields["name"], 
                                            $rs->fields["status"], 
                                            $foundrows);
                $rs->MoveNext();
            }
        }
        return $list;
    }
	
    function get_webinterface_by_id($conn, $id)
    {
        $query = "SELECT *, inet_ntoa(ip) as ip_decode FROM `web_interfaces` where id=".$id;
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } 
		else 
		{
            return new Webinterfaces($rs->fields["id"],
                                            $rs->fields["ip_decode"], 
                                            $rs->fields["name"], 
                                            $rs->fields["status"], 
                                            1);
        }
    }
    
    function insert($conn, $ip, $name, $status) {
        
        $sql      = "INSERT INTO `web_interfaces` (ip, name, status) VALUES (inet_aton(?), ?, ?)";
		$params   = array(
						$ip,
						$name,
						$status
					);
		
		
        if (!$conn->Execute($sql, $params)) {
            die($conn->ErrorMsg());
        }
        $infolog = array(
            $name,
            "[" . $ip . "]"
        );
        Log_action::log(95, $infolog);
    }
	
    function update($conn, $id, $ip, $name, $status) {
        
        $sql      = "UPDATE `web_interfaces` SET name = ?, ip = inet_aton(?), status = ? WHERE id = ?";
        $params   = array(
                        $name,
                        $ip,
                        $status,
                        $id
                    );
		
		if (!$conn->Execute($sql, $params)) {
            die($conn->ErrorMsg());
        }
        $infolog = array(
            $name,
            "[" . $ip . "]"
        );
        Log_action::log(96, $infolog);
    }
    function delete($conn, $id) {
    	$sql = "DELETE FROM `web_interfaces` WHERE id = ?";
        if (!$conn->Execute($sql, array(
            $id
        ))) {
            die($conn->ErrorMsg());
        }
        $infolog = array(
            $id
        );
        Log_action::log(97, $infolog);
    }
}
?>
