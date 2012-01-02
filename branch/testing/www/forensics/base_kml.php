<?php
/*****************************************************************************
*
*    License:
*
*   Copyright (c) 2007-2011 AlienVault
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
require_once ('KmlClass.php');
include_once ("geoipcity.inc");
require_once 'ossim_db.inc';

$type = GET('type');
$ip = GET('ip');
ossim_valid($type, OSS_ALPHA, '_', "Invalid: type");
ossim_valid($ip, OSS_IP_ADDR,  "illegal: ip");
if (ossim_error()) {
    die(ossim_error());
}

$user = $_SESSION['_user'];

$db = new ossim_db();
$conn = $db->snort_connect();
$ips = array();
if ($type == "ip_src") {
	$sql = "SELECT DISTINCT INET_NTOA(ip_dst) as ip FROM acid_event WHERE ip_src=INET_ATON('$ip') $session_where LIMIT 10";
} elseif ($type == "ip_dst") {
	$sql = "SELECT DISTINCT INET_NTOA(ip_src) as ip FROM acid_event WHERE ip_dst=INET_ATON('$ip') $session_where LIMIT 10";
}
if (!$rs = & $conn->Execute($sql)) {
	print $conn->ErrorMsg();
} else {
	while (!$rs->EOF) {
		$ips[] = $rs->fields["ip"];
		$rs->MoveNext();
	}
}
$db->close($conn);

//$ips = array("87.216.165.176", "74.125.43.147", "192.168.10.2", "192.168.10.3");
$nodes = array();
$gi = geoip_open("/usr/share/geoip/GeoLiteCity.dat", GEOIP_STANDARD);
foreach ($ips as $ip) {
	$hostname = $ip;
	if (preg_match("/^192\.168/", $ip)) {
		$ext_ip = Session_activity::getExtIpAddr();
		if ($ext_ip != "") {
			$ip = $ext_ip;
		}
	}
	$record = geoip_record_by_addr($gi,$ip);
	if (!is_null($record) && $record->country_name != "") {
		$city = $record->city;
		$country = $record->country_name;
		$lat = $record->latitude;
		$lng = $record->longitude;
		$nodes[] = array("ip" => $ip, "lat" => $lat, "lng" => $lng, "hostname" => $hostname, "country" => $country, "city" => $city);
	}
}
geoip_close($gi);
//$nodes[] = array("ip" => "87.216.165.176", "lat" => "40.9667", "lng" => "-5.65", "hostname" => "Salamanca");
//$nodes[] = array("ip" => "74.125.43.147","lat" => "47.9667", "lng" => "-1.65", "hostname" => "Paris");
$kml= new kml('Route');
$kml->addTour("Pruebas", $nodes);
$kml->export();
?>
