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
* Classes list:
*/
require_once ('classes/Session.inc');
Session::logcheck("MenuMonitors", "MonitorsNetflows");
require_once('ossim_db.inc');
require_once('classes/Webinterfaces.inc');
$db_aux              = new ossim_db();
$conn_aux            = $db_aux->connect();
$webinterfaces_list  = Webinterfaces::get_list($conn_aux, "where status=1");
$db_aux->close($conn_aux);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<title> <?php echo gettext("OSSIM"); ?> </title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
		<meta http-equiv="Pragma" content="no-cache"/>
		<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	</head>
<?
if (count($webinterfaces_list)>0)
{
?>
	<frameset rows="35,*" border="0" frameborder="0">
		<frame src="top.php?<?php echo $_SERVER['QUERY_STRING'] ?>" scrolling='no'>
		<frameset rows="35,*" border="0" frameborder="0">
			<frame id="fr_up" src="menu.php" name="menu_nfsen">
			<frame id="fr_down" src="main.php?<?php echo $_SERVER['QUERY_STRING'] ?>" name="nfsen">
		</frameset>
	</frameset>
<?
}else{   
?>
    <frameset rows="35,*" border="0" frameborder="0">
		<frame src="top.php?<?php echo $_SERVER['QUERY_STRING'] ?>" scrolling='no'>
		<frame id="fr_down" src="main.php?<?php echo $_SERVER['QUERY_STRING'] ?>" name="nfsen">
	</frameset>
<?
}
?>
</html>