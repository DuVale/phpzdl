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
ini_set('memory_limit', '1024M');
set_time_limit(300);
require_once ('classes/Session.inc');
require_once ('classes/Reputation.inc');
require_once ('ossim_conf.inc');

Session::logcheck("MenuMonitors", "IPReputation");

$act  = GET('act');
$type = intval(GET('type'));

ossim_valid($act, OSS_INPUT,OSS_NULLABLE, 'illegal: act');

if (ossim_error()) {
    die(ossim_error());
}

$conf     = $GLOBALS["CONF"];
$version  = $conf->get_conf("ossim_server_version", FALSE);
$prodemo  = ( preg_match("/pro|demo/i",$version) ) ? true : false;
$Reputation = new Reputation();

if ( !$Reputation->existReputation() ) exit();

$nodes = array();

list($ips,$cou,$order,$total) = $Reputation->get_data($type,$act);
session_write_close();

foreach ($ips as $activity => $ip_data) {
    $ip_data = array_slice($ip_data,0,30000);
    foreach ($ip_data as $ip => $latlng) {
        if(preg_match("/-?\d+(\.\d+)?,-?\d+(\.\d+)?/",$latlng)) {
            $tmp = explode(",", $latlng);
			$node = "{ ip: '$ip', lat: '".$tmp[0]."', lng: '".$tmp[1]."'}";
			$nodes[$ip] = $node;
        } 
    }
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title></title>
<link rel="stylesheet" type="text/css" href="../style/style.css"/>
<!--<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>-->
<script type="text/javascript" src=" https://maps-api-ssl.google.com/maps/api/js?sensor=false"></script>
<script type="text/javascript">
      var script = '<script type="text/javascript" src="../js/markerclusterer.js"><' + '/script>';
      document.write(script);
</script>
<script type="text/javascript">

var points = [ <?php echo implode(",",$nodes) ?> ];

function init_map() {
	var map = new google.maps.Map(document.getElementById("map"));
	map.setMapTypeId(google.maps.MapTypeId.ROADMAP);
	map.setOptions({
	    navigationControl: true,
	    navigationControlOptions: { style: google.maps.NavigationControlStyle.ZOOM_PAN }
	});
	var zoom = 3;
	var pos = new google.maps.LatLng(37.1833,-3.6141);
	map.setCenter(pos);	
	map.setZoom(zoom);
	
	var markers = [];
	for (i in points) {
		var p = new google.maps.LatLng(points[i].lat, points[i].lng);
		var marker = new google.maps.Marker({
			position: p,
			title: points[i].ip
		});
		markers.push(marker);
    }
    
    var mcOptions = {gridSize: 80, maxZoom: 15};
    var markerCluster = new MarkerClusterer(map, markers, mcOptions);
    
    <?php if (count($nodes) < 1) { ?>
    var marker = new google.maps.Marker({'position': pos});
	google.maps.event.addListener(marker,'click',function(){
		var infoWin = new google.maps.InfoWindow({
			content: "<font style='font-family:arial;font-size:14px'><?php echo _("No external hosts found") ?></font>",
			position: pos
		});
    });
    <?php }?>

	if (typeof(parent.show_map)=='function') parent.show_map();
}
</script>
<style>
body, html {
	height:100%;
	width:100%;
	margin:0px;
	padding:0px;
}
</style>
</head>
<body onload="init_map()">
<div id="map" style="width: 100%; height: 100%"></div>
</body>
</html>