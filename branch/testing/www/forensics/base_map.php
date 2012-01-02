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
require_once 'classes/Security.inc';
Session::useractive("session/login.php");
require_once ('ossim_conf.inc');
$conf    = $GLOBALS["CONF"];
$map_key = $conf->get_conf("google_maps_key", FALSE);
$type = GET('type');
$ip = GET('ip');
ossim_valid($type, OSS_ALPHA, '_', "Invalid: type");
ossim_valid($ip, OSS_IP_ADDR, "illegal: ip");
if (ossim_error()) {
    die(ossim_error());
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
   <head>
      <title>AlienVault Unified SIEM: Attacked host earth tour</title>
      <script src="//www.google.com/jsapi?key=<?=$map_key ?>"></script>
      <script src="http://earth-api-samples.googlecode.com/svn/trunk/lib/kmldomwalk.js" type="text/javascript"></script>
      <script type="text/javascript">

         var ge;
			var tour;
         google.load("earth", "1");

         function init() {
            google.earth.createInstance('map3d', initCB, failureCB);
         }

         function initCB(instance) {
            ge = instance;
            ge.getWindow().setVisibility(true);
            ge.getNavigationControl().setVisibility(ge.VISIBILITY_SHOW);

	        <?php
	        if(empty($_SERVER["HTTPS"])){
	            $url = 'http://'.$_SERVER['SERVER_NAME'];
	        }else{
	            $url = 'https://'.$_SERVER['SERVER_NAME'];
	        }
	        if(!empty($_SERVER["SERVER_PORT"])){
	            $url .= ':'.$_SERVER['SERVER_PORT'];
	        }
	        ?>

            var href = '<?php echo $url ?>/ossim/forensics/base_kml.php?type=<?php echo $type ?>&ip=<?php echo $ip ?>&u=<?php echo rand()?>';
            google.earth.fetchKml(ge, href, fetchCallback);

            function fetchCallback(fetchedKml) {
               // Alert if no KML was found at the specified URL.
               if (!fetchedKml) {
                  setTimeout(function() {
                     alert('Bad or null KML at '+href);
                  }, 0);
                  return;
               }

               // Add the fetched KML into this Earth instance.
               ge.getFeatures().appendChild(fetchedKml);

               // Walk through the KML to find the tour object; assign to variable 'tour.'
               walkKmlDom(fetchedKml, function() {
                  if (this.getType() == 'KmlTour') {
                     tour = this;
                     ge.getTourPlayer().setTour(tour);
                     ge.getTourPlayer().play();
                     return false;
                  }
               });
            }
         }

         function failureCB(errorCode) {
         }

         // Tour control functions
         function enterTour() {
            if (!tour) {
               alert('No tour found!');
               return;
            }
            ge.getTourPlayer().setTour(tour);
         }
         function playTour() {
            ge.getTourPlayer().play();
         }
         function pauseTour() {
            ge.getTourPlayer().pause();
         }
         function resetTour() {
            ge.getTourPlayer().reset();
         }
         function exitTour() {
            ge.getTourPlayer().setTour(null);
         }

         google.setOnLoadCallback(init);

         function playall() {
        	 ge.getTourPlayer().setTour(tour);
             ge.getTourPlayer().play();
         }
      </script>
      <style>
      html, body
{
height: 100%;
min-height: 100%;
margin:0px;
} 
      </style>
   </head>
   <body>

      <div id="map3d" style="height:100%;width:100%" style=""></div>
         <!--
         <div id ="controls" style="">
            <input type="button" onClick="enterTour()" value="Enter Tour"/>
            <input type="button" onClick="playTour()" value="Play Tour"/>
            <input type="button" onClick="pauseTour()" value="Pause Tour"/>
            <input type="button" onClick="resetTour()" value="Stop/Reset Tour"/>
            <input type="button" onClick="exitTour()" value="Exit Tour"/>
         </div>
 -->
   </body>
</html>
