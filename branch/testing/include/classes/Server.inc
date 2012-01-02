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
* - Server()
* - get_name()
* - get_ip()
* - get_port()
* - get_descr()
* - get_foundrows()
* - get_list()
* - get_role()
* - get_all()
* - insert()
* - update()
* - delete()
* Classes list:
* - Server
*/
require_once 'classes/Net_sensor_reference.inc';
require_once 'classes/Host_sensor_reference.inc';
require_once 'classes/Sensor.inc';
require_once 'classes/Server.inc';
require_once 'classes/Role.inc';
require_once 'classes/Net.inc';
require_once 'classes/Log_action.inc';
require_once ("ossim_db.inc");
require_once 'classes/WebIndicator.inc';
class Server {
    var $name;
    var $ip;
    var $port;
    var $descr;
    var $remoteadmin;
    var $remotepass;
    var $remoteurl;
    function Server($name, $ip, $port, $descr, $remoteadmin, $remotepass, $remoteurl, $foundrows = 0) {
        $this->name = stripslashes($name);
        $this->ip = $ip;
        $this->port = $port;
        $this->descr = $descr;
        $this->remoteadmin = $remoteadmin;
        $this->remotepass = $remotepass;
        $this->remoteurl = $remoteurl;
        $this->foundrows = $foundrows;
    }
    function get_name() {
        return $this->name;
    }
    function get_ip() {
        return $this->ip;
    }
    function get_port() {
        return $this->port;
    }
    function get_descr() {
        return $this->descr;
    }
	function get_remoteadmin() {
        return $this->remoteadmin;
    }
	function get_remotepass() {
        return $this->remotepass;
    }
	function get_remoteurl() {
        return $this->remoteurl;
    }
    function get_foundrows() {
        return $this->foundrows;
    }
    function set_foundrows($val) {
    	$this->foundrows = $val;
    }
    function get_list($conn, $args = "") {
        $list = array();
        $iffoundrows = preg_match("/LIMIT/i", $args) ? true : false;
        $select = ($iffoundrows) ? "SQL_CALC_FOUND_ROWS *" : "*";
        $query = OssimQuery("SELECT $select FROM server $args");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $foundrows = 0;
            if ($iffoundrows) {
                if (!$rf = & $conn->Execute("SELECT FOUND_ROWS() as total")) print $conn->ErrorMsg();
                else $foundrows = $rf->fields["total"];
            }
            while (!$rs->EOF) {
                //if (Session::hostAllowed($conn, $rs->fields["ip"])) {
                    $list[] = new Server($rs->fields["name"], $rs->fields["ip"], $rs->fields["port"], $rs->fields["descr"], $rs->fields["remoteadmin"], $rs->fields["remotepass"], $rs->fields["remoteurl"], $foundrows);
                //}
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function get_role($conn, $args = "") {
        $list = array();
        $query = OssimQuery("SELECT server_role.*,ip FROM server_role LEFT JOIN server USING (name) $args");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                //if (Session::hostAllowed($conn, $rs->fields["ip"])) {
                    $list[] = new Role($rs->fields["name"], $rs->fields["correlate"], $rs->fields["cross_correlate"], $rs->fields["store"], $rs->fields["qualify"], $rs->fields["resend_alarm"], $rs->fields["resend_event"], $rs->fields["sign"], $rs->fields["sem"], $rs->fields["sim"], $rs->fields["reputation"]);
                //}
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function get_all($conn) {
        $list = array();
        $sql = "SELECT * FROM server ORDER BY name ASC";
        if (!$rs = $conn->Execute($sql)) {
            die($conn->ErrorMsg());
        }
        while (!$rs->EOF) {
            $list[] = new Server($rs->fields["name"], $rs->fields["ip"], $rs->fields["port"], $rs->fields["descr"]);
            $rs->MoveNext();
        }
        return $list;
    }
    function set_remote_sshkey($remoteadmin, $remotepass, $remoteurl) {
    	// Create public key in local machine 
		if (!file_exists("/etc/ossim/remotelogger.pub")) {
        	system("ssh-keygen -q -t rsa -N '' -C remotelogger -f /etc/ossim/remotelogger");
        }
        $publickey_output = file("/etc/ossim/remotelogger.pub");
        $publickey = trim($publickey_output[0]);
        // Check the remote authorized_keys
        ini_set( "display_errors", 0);
        
        if (is_array($output = file($remoteurl."/sem/insertsshkey.php?user=$remoteadmin&pass=$remotepass&key=".urlencode($publickey)))) {
        	if ($output[0] == "USERERROR") {
        		echo "<center>"._("Login error in remote machine. Remote logger is not configured...")." <a href='/ossim/server/server.php?hmenu=SIEM+Components&smenu=Servers'>"._("Continue with Server config")."</a></center>";
        		ini_set( "display_errors", 1);
        		return false;
        	} elseif ($output[0] != "OK") {
        		echo "<center>"._("Error writting SSH public key in remote machine. Remote logger is not configured...")." <a href='/ossim/server/server.php?hmenu=SIEM+Components&smenu=Servers'>"._("Continue with Server config")."</a></center>";
        		ini_set( "display_errors", 1);
        		return false;
        	}
        }
        else {
        	echo "<center>"._("Error connecting to $remoteurl. Remote logger is not configured...")." <a href='/ossim/server/server.php?hmenu=SIEM+Components&smenu=Servers'>"._("Continue with Server config")."</a></center>";
        	ini_set( "display_errors", 1);
        	return false;
        }
        ini_set( "display_errors", 1);
        
        return true;
    }
    function insert($conn, $name, $ip, $port, $descr, $r_correlate, $r_cross_correlate, $r_store, $r_reputation, $r_qualify, $r_resend_alarm, $r_resend_event, $r_sign, $r_sem, $r_sim, $remoteadmin, $remotepass, $remoteurl) {
        $sql = "INSERT INTO server (name, ip, port, descr, remoteadmin, remotepass, remoteurl)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $params = array(
            $name,
            $ip,
            $port,
            $descr,
            $remoteadmin,
            $remotepass,
            $remoteurl
        );
        if (!$conn->Execute($sql, $params)) {
            die($conn->ErrorMsg());
        }
        $sql = "INSERT INTO server_role (name, correlate, cross_correlate, store, reputation, qualify, resend_alarm, resend_event, sign, sem, sim) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = array(
            $name,
            $r_correlate,
            $r_cross_correlate,
            $r_store,
            $r_reputation,
            $r_qualify,
            $r_resend_alarm,
            $r_resend_event,
            $r_sign,
            $r_sem,
            $r_sim
        );
        if (!$conn->Execute($sql, $params)) {
            die($conn->ErrorMsg());
        }
        if ($remoteadmin != "" && $remotepass != "" && $remoteurl != "") {
        	if (!Server::set_remote_sshkey($remoteadmin, $remotepass, $remoteurl)) {
        		$sql = "UPDATE server SET remoteadmin='', remotepass='', remoteurl='' WHERE name=?";
		        $params = array($name);
        		if (!$conn->Execute($sql, $params)) {
		        	die($conn->ErrorMsg());
		        }
		        exit;
        	}
        }
        
        $infolog = array(
            $name,
            "[" . $ip . "]"
        );
        Log_action::log(76, $infolog);
        //        WebIndicator::set_on("Reload_servers");
        //        WebIndicator::set_on("ReloadPolicy");
        
    }
    function update($conn, $name, $ip, $port, $descr, $r_correlate, $r_cross_correlate, $r_store, $r_reputation, $r_qualify, $r_resend_alarm, $r_resend_event, $r_sign, $r_sem, $r_sim, $remoteadmin, $remotepass, $remoteurl, $setssh=false) {
        $sql = "UPDATE server SET name = ?, ip = ?, 
                port = ?, descr = ?, remoteadmin = ?, remotepass = ?, remoteurl = ? 
                WHERE name = ?";
        $params = array(
            $name,
            $ip,
            $port,
            $descr,
            $remoteadmin,
            $remotepass,
            $remoteurl,
            $name
        );
        if (!$conn->Execute($sql, $params)) {
            die($conn->ErrorMsg());
        }
        $sql = "UPDATE server_role SET name = ?, correlate = ?, cross_correlate = ?, store = ?, reputation = ?, qualify = ?, resend_alarm = ?, resend_event = ?, sign = ?, sem = ?, sim = ?
                WHERE name = ?";
        $params = array(
            $name,
            $r_correlate,
            $r_cross_correlate,
            $r_store,
            $r_reputation,
            $r_qualify,
            $r_resend_alarm,
            $r_resend_event,
            $r_sign,
            $r_sem,
            $r_sim,
            $name
        );
        if (!$conn->Execute($sql, $params)) {
            die($conn->ErrorMsg());
        }
    	if ($remoteadmin != "" && $remotepass != "" && $remoteurl != "" && $setssh) {
        	if (!Server::set_remote_sshkey($remoteadmin, $remotepass, $remoteurl)) {
        		$sql = "UPDATE server SET remoteadmin='', remotepass='', remoteurl='' WHERE name=?";
		        $params = array($name);
        		if (!$conn->Execute($sql, $params)) {
		        	die($conn->ErrorMsg());
		        }
		        exit;
        	}
        }
        $infolog = array(
            $name,
            "[" . $ip . "]"
        );
        Log_action::log(77, $infolog);
        //        WebIndicator::set_on("Reload_servers");
        //        WebIndicator::set_on("ReloadPolicy");
        
    }
    function delete($conn, $name) {
        $sql = "DELETE FROM server WHERE name = ?";
        if (!$conn->Execute($sql, array(
            $name
        ))) {
            die($conn->ErrorMsg());
        }
        $sql = "DELETE FROM server_role WHERE name = ?";
        if (!$conn->Execute($sql, array(
            $name
        ))) {
            die($conn->ErrorMsg());
        }
        $infolog = array(
            $name
        );
        Log_action::log(78, $infolog);
        //        WebIndicator::set_on("Reload_servers");
        //        WebIndicator::set_on("ReloadPolicy");
        
    }
    
    function get_server_ip($conn, $name = "") {
        $query = OssimQuery("SELECT ip FROM server WHERE name=?");
        if (!$rs = & $conn->Execute($query,array($name))) {
            print $conn->ErrorMsg();
        } elseif (!$rs->EOF) {
            return $rs->fields["ip"];
        }
        return $name;
    }
    
    function server_exists($conn,$server) {
		$server_list = Server::get_list($conn, "ORDER BY name");
		
		foreach ($server_list as $s) {
			if ($s->get_name() == $server || $s->get_ip() == $server) return true;
		}
		return false;
	}
    
}
?>
