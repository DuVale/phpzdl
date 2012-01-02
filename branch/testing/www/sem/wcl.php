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
ob_implicit_flush();
require_once ('classes/Security.inc');
require_once ('classes/Session.inc');
require_once ('classes/Util.inc');
Session::logcheck("MenuEvents", "ControlPanelSEM");
$start = GET("start");
$end = GET("end");
$ips = GET("ips");
$tzone = intval(GET("tzone"));
$lastupdate = (intval(GET("lastupdate"))==1) ? "lastupdate" : "";
ossim_valid($start, OSS_DIGIT, OSS_COLON, OSS_SCORE, OSS_SPACE, 'illegal:' . _("start date"));
ossim_valid($end, OSS_DIGIT, OSS_COLON, OSS_SCORE, OSS_SPACE, 'illegal:' . _("end date"));
ossim_valid($ips, OSS_DIGIT, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("ips"));
if (ossim_error()) {
    die(ossim_error());
}

$allowed_sensors = implode("|",$_SESSION["_allowed_sensors"]);

require_once ('ossim_db.inc');
$db = new ossim_db();
$conn = $db->connect();
if ($tzone!=0) {
	$start = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($conn,$start)+(-3600*$tzone));
	$end = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($conn,$end)+(-3600*$tzone));
}
$db->close($conn);

$user = $_SESSION["_user"];
$result = array();
session_write_close();

if ($ips != "" && $ips != "127.0.0.1") {
	$cmd = "sudo ./fetchremote_wcl.pl '$user' '$start' '$end' '$ips' '$allowed_sensors' $lastupdate";
	$ips_arr = explode(",",$ips);
	$ip_to_name = array();
	foreach ($_SESSION['logger_servers'] as $name=>$ip) {
		$ip_to_name[$ip] = $name;
	}
} else {
	$cmd = "perl wcl.pl '$user' '$start' '$end' '$allowed_sensors' $lastupdate";
}
$debuglog = GET("debug_log");

ossim_valid($debuglog, OSS_FILENAME, OSS_NULLABLE, 'illegal:' . _("debug log"));

if (ossim_error()) {
    die(ossim_error());
}

if($debuglog != ""){
	$handle = fopen($debuglog, "a+");
	fputs($handle,"============================== WCL.php ".date("Y-m-d H:i:s")." ==============================\n");
	$cmd.= " '$debuglog'";
	fputs($handle,"WCL.php: $cmd\n");
	fclose($handle);
}
$fp = popen("$cmd 2>/dev/null", "r");
while (!feof($fp)) {
    $line = trim(fgets($fp));
    if ($line != "") $result[] = $line;
}
fclose($fp);

# last indexer update date
if ($lastupdate=="lastupdate" && trim($result[0])!="") {
	$mtime = gmdate("Y-m-d H:i:s",strtotime(trim($result[0]))+(3600*$tzone));
	echo "Latest indexed date: <b>$mtime</b>\n";
	exit;
}

$ok = 0;
$i = 0;
$hide = 0;
$hidelimit = 40000000;
foreach($result as $line) if (trim($line) != "") {
	$wc = floatval(trim($line));
	if ($ips != "") {
    	$current_server = $ip_to_name[$ips_arr[$i]];
    	echo "<table class='transparent' width='100%'><tr><td style='padding-left:5px;text-align:left;padding-right:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;border:0px;background-color:#".$_SESSION['logger_colors'][$current_server]['bcolor'].";color:#".$_SESSION['logger_colors'][$current_server]['fcolor']."'>$current_server</td><td class='nobborder' nowrap style='padding-left:5px;text-align:right'>"."<b>" . Util::number_format_locale($wc, 0) . "</b> "._("logs");
		echo " <a href='' class='scriptinfoleft' style='text-decoration:none' txt=\"".$ips_arr[$i]."\"><img src='../pixmaps/ico-clock.png' border='0' align='absmiddle'></a>
			</td></tr></table>";
    	$ok = 1;
    	if ($wc > $hidelimit) $hide=1;
    	$i++;
    } else {
		echo _("About")." <b>" . Util::number_format_locale($wc, 0) . "</b> "._("logs");
		echo " <a href='' class='scriptinfoleft' style='text-decoration:none' txt=\"127.0.0.1\"><img src='../pixmaps/ico-clock.png' border='0' align='absmiddle'></a>\n";
    	$ok = 1;
    	if ($wc > $hidelimit) $hide=1;
    	break;
    }
}
if (!$ok) echo _("About")." <b>0</b> "._("logs")."\n";
if ($hide) echo "<!-- HIDE -->\n";
?>
