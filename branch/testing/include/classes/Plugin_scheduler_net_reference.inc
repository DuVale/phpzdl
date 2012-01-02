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
* - Plugin_scheduler_net_reference()
* - get_plugin_scheduler_id()
* - get_net_name()
* - get_list()
* - insert()
* - in_plugin_scheduler_net_reference()
* - delete()
* Classes list:
* - Plugin_scheduler_net_reference
*/
require_once ("ossim_db.inc");
class Plugin_scheduler_net_reference {
    var $plugin_scheduler_id;
    var $net_name;
    function Plugin_scheduler_net_reference($plugin_scheduler_id, $net_name) {
        $this->plugin_scheduler_id = $plugin_scheduler_id;
        $this->net_name = $net_name;
    }
    function get_plugin_scheduler_id() {
        return $this->plugin_scheduler_id;
    }
    function get_net_name() {
        return $this->net_name;
    }
    function get_list($conn, $plugin_scheduler_id) {
        $list = array();
        $plugin_scheduler_id = intval($plugin_scheduler_id);
        $query = OssimQuery("SELECT * FROM plugin_scheduler_net_reference 
                    WHERE plugin_scheduler_id = $plugin_scheduler_id");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list[] = new Plugin_scheduler_net_reference($rs->fields["plugin_scheduler_id"], $rs->fields["net_name"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $net_list, $plugin_scheduler_id) {
        foreach($net_list as $net_name) {
            $sql = "INSERT INTO plugin_scheduler_net_reference VALUES (?, ?)";
            $params = array(
                $plugin_scheduler_id,
                $net_name
            );
            if (!$conn->Execute($sql, $params)) {
                die($conn->ErrorMsg());
            }
        }
    }
    function in_plugin_scheduler_net_reference($conn, $plugin_scheduler_id, $net_name) {
        $plugin_scheduler_id = intval($plugin_scheduler_id);
        $sql = "SELECT * FROM plugin_scheduler_net_reference WHERE plugin_scheduler_id = ? AND net_name = ?";
        $params = array(
            $plugin_scheduler_id,
            $net_name
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        }
        return $rs->fields[0];
    }
    function delete($conn, $plugin_scheduler_id) {
        $plugin_scheduler_id = intval($plugin_scheduler_id);
        $sql = "DELETE FROM plugin_scheduler_net_reference WHERE plugin_scheduler_id = ?";
        $params = array(
            $plugin_scheduler_id
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
}
?>