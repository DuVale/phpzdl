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
require_once ('ossim_db.inc');
require_once ('ossim_conf.inc');
require_once ('classes/Sensor.inc');
require_once ('classes/Host.inc');
require_once ('classes/Host_group.inc');
require_once ('classes/Host_group_scan.inc');
require_once ('classes/Host_sensor_reference.inc');
require_once ('classes/RRD_config.inc');
Session::logcheck("MenuPolicy", "PolicyHosts");

$db   = new ossim_db();
$conn = $db->connect();

$conf     = $GLOBALS["CONF"];	
$map_key = $conf->get_conf("google_maps_key", FALSE);
if ($map_key=="") $map_key="ABQIAAAAbnvDoAoYOSW2iqoXiGTpYBTIx7cuHpcaq3fYV4NM0BaZl8OxDxS9pQpgJkMv0RxjVl6cDGhDNERjaQ";

$hgname  = GET('name');
$update  = intval(GET('update'));

$style_success = "style='display: none;'";

if( $update==1 ) 
{
    $success_message = gettext("Host group successfully updated");
    $style_success   = "style='display: block;text-align:center;'";
}

//Initialize variables

$descr     = ""; 
$nagios    = "";
$hosts     = array();
$sensors   = array();
$latitude  = "";
$longitude = "";
$zoom      = 2;


if ( isset($_SESSION['_assets_hg']) && !empty($_SESSION['_assets_hg']) )
{
	$hgname      = $_SESSION['_assets_hg']['hgname'];  
	$hosts       = $_SESSION['_assets_hg']['hosts']; 
	$descr       = $_SESSION['_assets_hg']['descr']; 
	$sensors     = $_SESSION['_assets_hg']['sensors'];   
	$threshold_a = $_SESSION['_assets_hg']['threshold_a']; 
	$threshold_c = $_SESSION['_assets_hg']['threshold_c']; 
	$rrd_profile = $_SESSION['_assets_hg']['rrd_profile'];  
	$nagios      = $_SESSION['_assets_hg']['nagios'];
    
    $latitude    = $_SESSION['_assets_hg']['latitude'];
    $longitude   = $_SESSION['_assets_hg']['longitude'];
    $zoom        = $_SESSION['_assets_hg']['zoom'];
	
	unset($_SESSION['_assets_hg']);
        
}
else
{
	$threshold_a = $threshold_c = $conf->get_conf("threshold");
	    
    if ($hgname != '')
	{
		ossim_valid($hgname, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE, OSS_SQL, 'illegal:' . _("Host Group Name"));
		
		if (ossim_error()) 
			die(ossim_error());
        
		if ($host_group_list = Host_group::get_list($conn, " AND g.name = '$hgname'"))
		{
			$host_group  = $host_group_list[0];
			$descr       = $host_group->get_descr();
			$threshold_c = $host_group->get_threshold_c();
			$threshold_a = $host_group->get_threshold_a();
			$obj_hosts   = $host_group->get_hosts($conn);
            
            $coordinates = $host_group->get_coordinates();

            $latitude    = $coordinates['lat'];
            $longitude   = $coordinates['lon'];
            $zoom        = $coordinates['zoom'];
			
			foreach($obj_hosts as $host)
				$hosts[] = $host->get_host_ip($conn);
					
			$nagios = ( Host_group_scan::in_host_group_scan($conn, $hgname, 2007) ) ? "1" : ''; 
			
			$rrd_profile = $host_group->get_rrd_profile();
			if (!$rrd_profile) 
				$rrd_profile = "None";
			
			$tmp_sensors = $host_group->get_sensors($conn);
			
			foreach($tmp_sensors as $sensor) 
				$sensors[] = $sensor->get_sensor_name();
		}
	}
	
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link type="text/css" rel="stylesheet" href="../style/style.css"/>
	<link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
	<link type="text/css" rel="stylesheet" href="../style/tree.css" />
    <link type="text/css" rel="stylesheet" href="../style/jquery.autocomplete.css" />
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
	<script type="text/javascript" src="../js/jquery.cookie.js"></script>
	<script type="text/javascript" src="../js/jquery.dynatree.js"></script>
	<script type="text/javascript" src="../js/combos.js"></script>
	<script type="text/javascript" src="../js/jquery.simpletip.js"></script>
	<script type="text/javascript" src="../js/ajax_validator.js"></script>
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
	<script type="text/javascript" src="../js/messages.php"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
    <script type="text/javascript" src=" https://maps-api-ssl.google.com/maps/api/js?sensor=false"></script>    
    <script type="text/javascript" src="../js/jquery.autocomplete_geomod.js"></script> 
	<script type="text/javascript" src="../js/geo_autocomplete.js"></script> 

	<script type="text/javascript">
        
        var map;
		var marker;
				
		function initialize()
		{
			var latitude  = '<?php echo $latitude;?>';
			var longitude = '<?php echo $longitude;?>';
			
			var latlng = new google.maps.LatLng(latitude, longitude);
			var myOptions = {
			  zoom: <?php echo $zoom;?>,
			  center: latlng,
			  mapTypeId: google.maps.MapTypeId.ROADMAP
			};
			
			map = new google.maps.Map(document.getElementById("map"), myOptions);
			
			marker = new google.maps.Marker({
				position: latlng, 
				draggable:true,
				animation: google.maps.Animation.DROP,
				map: map, 
				title: '<?php echo "$ip "._("Location")?>'
			}); 
			
			google.maps.event.addListener(marker, 'click',   toggleBounce);
			google.maps.event.addListener(marker, 'dragend', updatePosition);
			google.maps.event.addListener(map, 'zoom_changed', changeZoom);
		}
		
		function changeZoom()
		{
			$('#zoom').val(map.zoom);
		}
		
		function updatePosition()
		{
			$('#latitude').val(Math.round(marker.position.lat()*10000)/10000);
			$('#longitude').val(Math.round(marker.position.lng()*10000)/10000);
		}
				
		function toggleBounce()
		{

			if (marker.getAnimation() != null) 
				marker.setAnimation(null);
			else 
				marker.setAnimation(google.maps.Animation.BOUNCE);
		}
        
        function remove_success_message() {
            if ( $('#success_message').length == 1 )    $("#success_message").remove();
        }
	
        var layer = null;
		var nodetree = null;
		var i=1;
		var addnodes = false;
	
		function load_tree(filter) {
			
			combo = 'hosts';
			if (nodetree!=null) {
				nodetree.removeChildren();
				$(layer).remove();
			}
			layer = '#srctree'+i;
			$('#container').append('<div id="srctree'+i+'" style="width:100%"></div>');
			$(layer).dynatree({
				initAjax: { url: "draw_tree.php", data: {filter: filter} },
				clickFolderMode: 2,
				onActivate: function(dtnode) {
					if (!dtnode.hasChildren()) {
						if (!dtnode.data.url.match(/\:/)) {
							// add from a final node
							addto(combo,dtnode.data.url,dtnode.data.url)
						} else {
							// simulate expand and load
							addnodes = true;
							dtnode.toggleExpand();
						}
					} else {
						// add all children nodes
						var children = dtnode.tree.getAllNodes(dtnode.data.key.replace('.','\\.')+'\\.');
						for (c=0;c<children.length; c++)
							addto(combo,children[c].data.url,children[c].data.url)
					}
				},
				onDeactivate: function(dtnode) {;},
				onLazyRead: function(dtnode){
					// load nodes on-demand
					
					dtnode.appendAjax({
						url: "draw_tree.php",
						data: {key: dtnode.data.key, filter: filter, page: dtnode.data.page},
						success: function(options,selfnode) {
							if (addnodes) {
								addnodes = false;
								var children = dtnode.tree.getAllNodes(dtnode.data.key.replace('.','\\.')+'\\.');
								for (c=0;c<children.length; c++)
									addto(combo,children[c].data.url,children[c].data.url)
							}
						}
					});
				}
			});
			nodetree = $(layer).dynatree("getRoot");
			i=i+1

		}
	
		$(function(){
			load_tree("");
		});
		
		$(document).ready(function(){
            $('#location_search').geo_autocomplete(new google.maps.Geocoder, {
                mapkey: '<?php echo $map_key?>', 
                selectFirst: false,
                minChars: 3,
                cacheLength: 50,
                width: 300,
                scroll: true,
                scrollHeight: 330
            }).result(function(_event, _data) {
                if (_data) 
                {
                    map.fitBounds(_data.geometry.viewport);
                    map.setCenter(_data.geometry.location);
                    marker.setPosition(_data.geometry.location);  
                    $('#latitude').val(Math.round(marker.position.lat()*10000)/10000);
                    $('#longitude').val(Math.round(marker.position.lng()*10000)/10000);
                }
            });
        
			$(".sensor_info").simpletip({
				position: 'top',
				offset: [-60, -10],
				content: '',
				baseClass: 'ytooltip',
				onBeforeShow: function() {
						var txt = this.getParent().attr('txt');
						this.update(txt);
				}
			});
			
			$('textarea').elastic();
			
			$('.vfield').bind('blur', function() {
			     validate_field($(this).attr("id"), "newhostgroup.php");
			});
            
            initialize();
			
			$('#latitude').bind('change', function() {
				var latlng = new google.maps.LatLng($('#latitude').val(),$('#longitude').val());
				marker.setPosition(latlng);
				map.setCenter(latlng);
			});
			
			$('#longitude').bind('change', function() {
				var latlng = new google.maps.LatLng($('#latitude').val(),$('#longitude').val());
				marker.setPosition(latlng);
				map.setCenter(latlng);
			});

		});
	</script>
	
		
	
	<style type='text/css'>
		#table_form { background-color: transparent; width: 820px;} 
		#table_form th {width: 150px;}
		.std_inp, .std_select, .std_txtarea {width: 90%; height: 18px;}
		.std_inp2 {width: 85%; height: 18px;}
		.std_txtarea { height: 45px;}
		label {border: none; cursor: default;}
		.bold {font-weight: bold;}
		div.bold {line-height: 18px;}
		#del_selected {float:right; padding-top: 5px; width: 52px;}
	</style>
	
</head>
<body>

<?php

if (GET('withoutmenu') != "1") 
	include ("../hmenu.php"); 

?>
<div id='success_message' class='ossim_success' <?php echo $style_success ?>><?php echo $success_message;?></div>
<div id='info_error' class='ossim_error' style='display: none;'></div>

<form name='form_hg' id='form_hg' method="POST" action="<?php echo ( GET('name') != "") ? "modifyhostgroup.php" : "newhostgroup.php" ?>">
<input type="hidden" name="withoutmenu" id='withoutmenu' value="<?php echo GET('withoutmenu')?>"/>
<input type="hidden" name="insert" value="insert"/>
<table align="center" class='noborder' id='table_form'>
	<tr>
		<td valign="top" class="nobborder">
			<table align="center">
				<tr>
					<th><label for='hgname'><?php echo gettext("Name");?></label></th>
					<td class="left">
						<?php if (GET('name') == "" ) {?>
							<input type='text' name='hgname' id='hgname' class='std_inp vfield req_field' value="<?php echo $hgname?>"/>
							<span style="padding-left: 3px;">*</span>
						<?php } 
							  else {
						?>	
							<input type='hidden' name='hgname' id='hgname' class='vfield req_field' value="<?php echo $hgname?>"/>
							<div class='bold'><?php echo $hgname?></div>
						<?php }  ?>
					</td>
				</tr>

				<tr>
					<th>
						<label for='hosts'><?php echo gettext("Hosts");?></label><br/>
						<span><a href="newhostform.php"><?php echo gettext("Insert new host"); ?> ?</a></span><br/>
					</th>
					<td class="left nobborder">
						<select id="hosts" name="ips[]" class='req_field' size="19" multiple="multiple" style="width:250px">
						<?php
						foreach($hosts as $k => $v)
							echo "<option value='$v' selected='selected'>$v</option>";
						?>
						</select>
						<span style="padding-left: 3px; vertical-align: top;">*</span>
						<div id='del_selected'><input type="button" value=" [X] " onclick="deletefrom('hosts')" class="lbutton"/></div>
					</td>
				</tr>

				<tr>
					<th><label for='descr'><?php echo gettext("Description"); ?></label></th>
					<td class="left">
						<textarea name="descr" id='descr' class='std_txtarea vfield'><?php echo $descr;?></textarea>
					</td>
				</tr>

				<tr>
					<th> 
						<label for='sboxs1'><?php echo gettext("Sensors");?></label>
						<a style="cursor:pointer; text-decoration: none;" class="sensor_info" txt="<div style='width: 150px; white-space: normal; font-weight: normal;'>Define which sensors has visibility of this Host Group</div>">
						<img src="../pixmaps/help.png" width="16" border="0" align="absmiddle"/>
						</a><br/>
						<span><a href="../sensor/newsensorform.php"><?php echo gettext("Insert new sensor"); ?> ?</a></span>
					</th>
					<td class="left">
						<?php
						/* ===== sensors ==== */
						$i = 1;
						
						if ($sensor_list = Sensor::get_all($conn, "ORDER BY name"))
						{
							foreach($sensor_list as $sensor) 
                            {
								$sensor_name = $sensor->get_name();
								$sensor_ip   = $sensor->get_ip();
																
								$class = ($i == 1) ? "class='req_field'" : "";
																
								$sname = "sboxs".$i;
								$checked = ( in_array($sensor_name, $sensors) )  ? "checked='checked'"  : '';
								
								echo "<input type='checkbox' name='sboxs[]' $class id='$sname' value='$sensor_name' $checked/>";
								echo $sensor_ip . " (" . $sensor_name . ")<br/>"; 
							  
								$i++;
							}
						}
						?>
					</td>
				</tr>

				<tr>
					<td style="text-align: left; border:none; padding-top:3px;" colspan='2'>
						<a onclick="$('.advanced').toggle()" style="cursor:pointer;">
						<img border="0" align="absmiddle" src="../pixmaps/arrow_green.gif"/><?php echo gettext("Advanced"); ?></a>
					</td>
				</tr>
		 
				<tr class="advanced" style="display:none;">
					<th><label for='nagios'><?php echo gettext("Scan options");?></label></th>
					<td class="left">
						<input type="checkbox" name="nagios" id='nagios' value="1" <?php echo( $nagios == 1) ? "checked='checked'" : ""; ?>/>
						<?php echo gettext("Enable nagios scan"); ?> 
					</td>
				</tr>

				<tr class="advanced" style="display:none;">
					<th>
						<label for='rrd_profile'><?php echo gettext("RRD Profile");?></label><br/>
						<span><a href="../rrd_conf/new_rrd_conf_form.php"><?php echo gettext("Insert new profile"); ?> ?</a></span>
					</th>
					<td class="left">
						<select name="rrd_profile" id='rrd_profile' class='std_select vfield'>
							<option value=""><?=gettext("None");?></option>
							<?php
							foreach(RRD_Config::get_profile_list($conn) as $profile) {
								if (strcmp($profile, "global"))
								{
									$selected = ( $rrd_profile == $profile  ) ? " selected='selected'" : '';
									echo "<option value=\"$profile\" $selected>$profile</option>\n";
								}
							}
							?>
						</select>
					</td>
				</tr>

				<tr class="advanced" style="display:none;">
					<th><label for='threshold_c'><?php echo gettext("Threshold C"); ?></label></th>
					<td class="left">
						<input type="text" name="threshold_c" id="threshold_c" class='std_inp vfield req_field' value="<?php echo $threshold_c?>"/>
						<span style="padding-left: 3px; vertical-align: top;">*</span>
					</td>
				</tr>

				<tr class="advanced" style="display:none;">
					<th><label for='threshold_a'><?php echo gettext("Threshold A"); ?></label></th>
					<td class="left">
						<input type="text" name="threshold_a" id="threshold_a" class='std_inp vfield req_field' value="<?php echo $threshold_a?>"/>
						<span style="padding-left: 3px; vertical-align: top;">*</span>
					</td>
				</tr>

                <tr>
					<td style="text-align: left; border:none; padding-top:3px;" colspan='2'>
						<a onclick="$('.geolocation').toggle()" style="cursor:pointer;">
                        <img border="0" align="absmiddle" src="../pixmaps/arrow_green.gif"/><?=gettext("Host Group Location")?></a>
                    </td>
                </tr>
                    
                <tr class="geolocation">
                    <th><label for='latitude'><?php echo gettext("Latitude"); ?></label></th>
                    <td class="left"><input class="std_inp" type="text" id="latitude" name="latitude" value="<?php echo $latitude;?>"/></td>
                </tr>
                
                <tr class="geolocation">
                    <th><label for='longitude'><?php echo gettext("Longitude"); ?></label></th>
                    <td class="left"><input class="std_inp" type="text" id="longitude" name="longitude" value="<?php echo $longitude;?>"/></td>
                </tr>

                <tr class="geolocation noborder">
                    <td class="left" colspan=2><img src="../pixmaps/search_icon.png" border="0" align="top">
                        <input type="text" class="std_inp" id="location_search" name="location_search" style="margin-top:2px">
                    </td>
                </tr>
                
                <tr class="geolocation">
                    <td colspan="2">
                        <input type="hidden" id="zoom" name="zoom" value="<?php echo $zoom;?>"/>
                        <div id='map' style='height:200px; width:380px;'></div>
                    </td>
                </tr>
                
				<tr>
					<td colspan="2" class="nobborder" style="text-align:center;padding:10px">
						<input type="button" class="button" id='send' value="<?php echo _("Update") ?>" onclick="remove_success_message();selectall('hosts'); submit_form()">
						<input type="reset"  class="button" value="<?=_("Clear form")?>"/>
					</td>
				</tr>
			</table>
		</td>
	
		<td class="left nobborder" valign="top">
			<div style='float: left; width:280px; height:30px;'><?=_("Filter")?>: <input type="text" class='std_inp2' id="filter" name="filter"/></div>
			<div style='float: right; width:50px; height:30px;'><input type="button" value="<?=_("Apply")?>" onclick="load_tree(this.form.filter.value)" class="lbutton"/></div>
			<div id="container" style="width:350px; padding-top:10px; clear: both;"></div>
		</td>
	</tr>
	
	<tr>
		<td class='nobborder'>
			<p align="center" style="font-style: italic;"><?php echo gettext("Values marked with (*) are mandatory"); ?></p>
		</td>
		<td class='nobborder'></td>
	</tr>
</table>
</form>

<?php $db->close($conn); ?>

</body>
</html>

