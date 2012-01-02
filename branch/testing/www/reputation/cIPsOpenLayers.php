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
require_once ('classes/Reputation.inc');
require_once ('ossim_conf.inc');

Session::logcheck("MenuMonitors", "IPReputation");

$act  = GET('act');
$type = intval(GET("type"));

ossim_valid($act, OSS_INPUT,OSS_NULLABLE, 'illegal: act'); 

if (ossim_error()) {
    die(ossim_error());
}

$hmax     = 50000; 

$conf     = $GLOBALS["CONF"];
$map_key  = $conf->get_conf("google_maps_key", FALSE);
$version  = $conf->get_conf("ossim_server_version", FALSE);
$prodemo  = ( preg_match("/pro|demo/i",$version) ) ? true : false;

$Reputation = new Reputation();

//$rep_file = trim(`grep "reputation" /etc/ossim/server/config.xml | perl -npe 's/.*\"(.*)\".*/$1/'`);

if ( !$Reputation->existReputation() ) exit();

$nodes = array();

list($ips,$cou,$order,$last) = $Reputation->get_data($type,$act);

$i = 0;

foreach( $ips as $activity => $ip_data) {
    foreach ($ip_data as $ip => $latlng) {
        if(preg_match("/-?\d+\.\d+,-?\d+\.\d+/",$latlng)) {
            $tmp = explode(",", $latlng);
            
            if($act == "")
                $nodes[] = array("ip" => $ip, "lat" => $tmp[0], "lng" => $tmp[1], "act" => $activity);
            else
                $nodes[] = array("ip" => $ip, "lat" => $tmp[0], "lng" => $tmp[1]);

            $i++;
            
            if($i == $hmax) {
                break;
            }
        }
    }
    if($i == $hmax) {
        break;
    }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title><?php echo _("IP Reputation"); ?></title>
    <link rel="stylesheet" type="text/css" href="../style/style.css" />
    <style type="text/css">
        html, body, #map {
            margin: 0;
            width: 100%;
            height: 100%;
        }
    </style>

    <script type="text/javascript" src="/ossim/js/OpenLayers/OpenLayers.js"></script>
        <script type="text/javascript">
            var features = [];
            var IpsHash = new Array();
            
            <?php
            foreach($nodes as $node) {
            ?>
                features.push(new OpenLayers.Feature.Vector( new OpenLayers.Geometry.Point(<?php echo $node["lng"] ?>,<?php echo $node["lat"] ?>), {x:<?php echo $node["lng"] ?> , y: <?php echo $node["lat"] ?> }  ));
                
                <?php
                if ( $node["act"] == "" ) { ?>
                    IpsHash['<?php echo $node["lng"] ?>,<?php echo $node["lat"] ?>'] = '<?php echo $node["ip"] ?>';
                <?php
                }
                else {?>
                    IpsHash['<?php echo $node["lng"] ?>,<?php echo $node["lat"] ?>'] = '<?php echo $node["ip"]." - ".$node["act"] ?>';
                <?php
                }
            }
            ?>
            var map, strategy, clusters;
            function init() {
                map = new OpenLayers.Map('map');
                var base = new OpenLayers.Layer.WMS( "OpenLayers WMS",
                    "http://vmap0.tiles.osgeo.org/wms/vmap0",
                    {layers: 'basic'} );

                var style = new OpenLayers.Style({
                    pointRadius: "${radius}",
                    fillColor: "#ffcc66",
                    fillOpacity: 0.8,
                    strokeColor: "#cc6633",
                    strokeWidth: "${width}",
                    strokeOpacity: 0.8
                }, {
                    context: {
                        width: function(feature) {
                            return (feature.cluster) ? 1 : 1;
                        },
                        radius: function(feature) {
                            var pix = 2;
                            if(feature.cluster) {
                                pix = Math.min(feature.attributes.count, 7) + 2;
                            }
                            return pix;
                        }
                    }
                });
                
                strategy = new OpenLayers.Strategy.Cluster();

                clusters = new OpenLayers.Layer.Vector("Clusters", {
                    strategies: [strategy],
                    styleMap: new OpenLayers.StyleMap({
                        "default": style,
                        "select": {
                            fillColor: "#8aeeef",
                            strokeColor: "#32a8a9"
                        }
                    })
                });
                
                var select = new OpenLayers.Control.SelectFeature(
                    clusters, {hover: true}
                );
                map.addControl(select);
                select.activate();
                clusters.events.on({"featureselected": display});
                clusters.events.on({"featureunselected": hide_message});
                
                map.addLayers([base, clusters]);
                map.setCenter(new OpenLayers.LonLat(13.226559,33.815918), 3);
                
                reset();

            }
            
            function reset() {
                clusters.removeFeatures(clusters.features);
                clusters.addFeatures(features);
            }
            
            function display(event) {
                var f = event.feature;

                if (typeof window.parent.frames.update == "function") {
                    if(f.attributes.count!=1) {
                        window.parent.frames.update("Cluster of " + f.attributes.count + " IPs");
                    }
                    else if(f.attributes.count==1) {
                        window.parent.frames.update(IpsHash[ f.geometry.x + "," +f.geometry.y]);
                    }
                    else {
                        window.parent.frames.update("Unclustered " + f.geometry);
                    }
                }
            }
            
            function hide_message(event) {
                if (typeof window.parent.frames.hide_message == "function") {
                    window.parent.frames.hide_message();
                }
            }
        </script>
    </head>
    <body onload="init()">
        <!--<p><span id="output">Hover over a cluster to see details.</span></p>-->
        <div id="map" style="margin:0px auto"></div>
    </body>
</html>