<?php
/*****************************************************************************
*
*    License:
*
*   Copyright (c) 2007-2010 AlienVault
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
require_once ('classes/Session.inc');
require_once ('classes/Server.inc');
require_once ('ossim_conf.inc');
require_once 'ossim_db.inc';
$db = new ossim_db();
$conn = $db->connect();
Session::logcheck("MenuEvents", "ControlPanelSEM");
$conf = $GLOBALS["CONF"];
// Change configuration from deprecated logger console to Siem remote logger
if ($conf->get_conf("server_remote_logger", FALSE) && GET('skip') == "") {
	$remote_user = $conf->get_conf("server_remote_logger_user", FALSE);
    $remote_pass = $conf->get_conf("server_remote_logger_pass", FALSE);
    $remote_url = $conf->get_conf("server_remote_logger_ossim_url", FALSE);
    $remote_ip = preg_replace("/https?\:\/\/([^\/]+).*/","\\1",$remote_url);
    $server_name = "RemoteLogs_".$remote_ip;
    $server_list = Server::get_list($conn, "WHERE ip='$remote_ip' AND remoteurl != ''");
    // Remote server already exists
    if (count($server_list) > 0 && $server_list[0]->get_remoteadmin() == $remote_user) {
    	// Is well configured
    	if (Server::set_remote_sshkey($remote_user, $remote_pass, $remote_url)) {
        	require_once 'classes/Config.inc';
			$config = new Config();
    		$config->update("server_remote_logger", "no");
        	header("location:index.php");
        // Some error found in ssh pubkey generator
    	} else {
        	echo "<center><br><br>"._("Remote logger is not well configured. Please, check configuration in Alienvault Components -> Servers section.");
        	echo "<br><a href='remote_index.php?skip=1'>Continue with Remote Logs Console</a></center>";
        	exit;
        }
    // Insert new entire server configuration
    } else {
    	$server_list = Server::get_list($conn, "WHERE name='$server_name'");
    	if (count($server_list) < 1) {
    		Server::insert($conn, $server_name, $remote_ip, 40001, "", 0, 0, 0, 0, 0, 0, 0, 1, 1, $remote_user, $remote_pass, $remote_url);
    		echo "<font style='font-family:arial'><center><br><br>"._("Remote Logs configuration has been successfully changed.");
    		echo "<br>New server created as '<b>$server_name</b>'<br>";
    	} else {
    		echo "<font style='font-family:arial'><center><br><br>"._("Server '<b>$server_name</b>' already exists.");
    	}
    	echo _("Now you can manage some Logs machines in Alienvault Components -> Servers section.");
    	echo "<br><a href='index.php'>"._("Continue with Logs section")."</a></center></font>";
    	require_once 'classes/Config.inc';
		$config = new Config();
    	$config->update("server_remote_logger", "no");
    	exit;
    }
}
// Not well-configured yet => logger console
if ($conf->get_conf("server_remote_logger", FALSE)) {
    $remote_user = $conf->get_conf("server_remote_logger_user", FALSE);
    $remote_pass = base64_encode($conf->get_conf("server_remote_logger_pass", FALSE));
    $remote_url = $conf->get_conf("server_remote_logger_ossim_url", FALSE);
    ?>
    <script> function redir(url) { document.location.href = url } </script>
    <img src="../pixmaps/loading.gif" border=0>
    <iframe src="<?=$remote_url."/session/login.php?user=".urlencode($remote_user)."&pass=".$remote_pass?>" style="display:none" onload="redir('<?=$remote_url?>/sem/index.php')"></iframe>
    <?
}
?>
