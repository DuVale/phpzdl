<?php
/*****************************************************************************
*
*    License:
*
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

ini_set('session.bug_compat_warn','off');
ini_set('memory_limit', '1024M');

session_start();
ob_start();

require_once 'classes/Session.inc';
require_once 'classes/Host.inc';
require_once 'classes/Sensor.inc';
require_once 'classes/Server.inc';
require_once 'classes/Security.inc';
require_once 'classes/Net.inc';
require_once 'classes/Net_scan.inc';
require_once 'ossim_db.inc';
require_once 'ossim_conf.inc';
require_once 'host_report_common.php';

Session::logcheck("MenuReports", "ReportsHostReport");

// Database Object
$db         = new ossim_db();
$conn       = $db->connect();
$conn_snort = $db->snort_connect();


//Parameters
$asset_type = GET('asset_type');
$asset_key  = GET('asset_key');

$greybox    = GET('greybox');
$greybox    = 0;

$date_from = GET('start_date');
$date_to   = GET('end_date');

$user    = Session::get_session_user();
$conf    = ( !$conf ) ? new ossim_conf() : $GLOBALS["CONF"];

//API Google Maps
$map_key = $conf->get_conf("google_maps_key", FALSE);
$map_key = ( $map_key == "" ) ? "ABQIAAAAbnvDoAoYOSW2iqoXiGTpYBTIx7cuHpcaq3fYV4NM0BaZl8OxDxS9pQpgJkMv0RxjVl6cDGhDNERjaQ" : $map_key;

//Geolocation
include ("geoip.inc");
$gi = geoip_open("/usr/share/geoip/GeoIP.dat", GEOIP_STANDARD);

$param['assets'] = null;

if ( $asset_type == 'host')
{
    if ( ossim_valid($asset_key, OSS_IP_ADDR, 'illegal:' . _("Host")) )
    {
        $param['assets']['type'] = $asset_type;
        $hostname                = Host::ip2hostname($conn, $asset_key);
        $param['assets']['data'] = array("name" => $hostname, "ip_cidr" => array($asset_key), "allowed" => Session::hostAllowed($conn, $asset_key) );
        
        $title       = ( $hostname == $asset_key || empty($hostname) ) ? _("Host Report").": $asset_key" : _("Host Report").": $hostname ($asset_key)";
        $title_graph = preg_replace ("/Host Report: /","",$title);
    }
}
elseif ( $asset_type == 'net')
{
    if ( ossim_valid($asset_key, OSS_NOECHARS, OSS_NET_NAME, 'illegal:' . _("Net")) )
    {
        $net    = Net::get_list($conn,"name='".$asset_key."'");
        $cidrs  = $net[0]->get_ips();
        $param['assets']['type'] = $asset_type;
        $param['assets']['data'] = array("name" => $asset_key, "ip_cidr" => explode(",",$cidrs), "net" => $net[0], "allowed" => Session::netAllowed($conn, $asset_key) );
        
        $title       = ( !empty($cidrs) ) ? "Network Report: $asset_key ($cidrs)" : "Network Report: $asset_key"; 
	    $title_graph = ( !empty($cidrs) ) ? $asset_key. " ($cidrs)" : $asset_key;
    }
}
elseif ( $asset_type == 'net_group')
{
    if ( ossim_valid($asset_key, OSS_NOECHARS, OSS_ALPHA, OSS_PUNC, 'illegal:' . _("Net Group")) )
    {
       
        if ($net_group_list = Net_group::get_list($conn))
        {
            $allowed_netgroups = array();
            foreach($net_group_list as $net_group)
            {
                $allowed_netgroups[$net_group->get_name()]  = $net_group->get_name();
            }	
        }
 
        $aux_nets = Net_group::get_networks($conn, $asset_key);
       
        $cidrs  = array();
        $nets   = array();
            
        if ( is_array($aux_nets) )
        {
            foreach ($aux_nets as $net)
            {
                $net_name             = $net->get_net_name();
                $net                  = Net::get_list($conn,"name='".$net_name."'");
                $nets[$net_name]      = $net[0];
                
                $aux_cidrs            = $net[0]->get_ips();
                if ( !empty($aux_cidrs) )
                {
                    $cidrs = array_merge($cidrs,explode(",",$aux_cidrs));
                }
            }
        }
   
        $param['assets']['type'] = $asset_type;
        $allowed                 = ( !empty($allowed_netgroups[$asset_key]) ) ? true : false;
        $param['assets']['data'] = array("name" => $asset_key, "ip_cidr" => $cidrs, "nets" => $nets, "allowed" => $allowed);
        
        $txt_nets    = implode(",", array_keys($nets));
        $title       = ( !empty($txt_nets) ) ? "Netgroup Report: $asset_key (".$txt_nets.")" : $asset_key; 
	    $title_graph = ( !empty($txt_nets) ) ? $asset_key." (".$txt_nets.")"                 : $asset_key;
    }
}
elseif ( $asset_type == 'sensor')
{
    if ( ossim_valid($asset_key, OSS_ALPHA, OSS_PUNC, 'illegal:' . _("Sensor")) )
    {
        $param['assets']['type'] = $asset_type;
        $sensor_ip               = Sensor::get_sensor_ip($conn, $asset_key);
        $allowed                 = ( Session::hostAllowed($conn, $sensor_ip) || Session::sensorAllowed($sensor_ip) ) && Sensor::sensor_exists($conn,$asset_key);
             
        $param['assets']['data'] = array("name" => $asset_key, "ip_cidr" => array($sensor_ip), "allowed" => $allowed);
        
        $title       = ( $sensor_ip == $asset_key || empty($sensor_ip) ) ? _("Host Report").": $sensor_ip" : _("Host Report").": $asset_key ($sensor_ip)";
        $title_graph = preg_replace ("/Host Report: /","",$title);
    }
}
elseif ( $asset_type == 'server')
{
    if ( ossim_valid($asset_key, OSS_ALPHA, OSS_PUNC, 'illegal:' . _("Server")) )
    {
        $param['assets']['type'] = $asset_type;
        $server_ip               = Server::get_server_ip($conn, $asset_key);
        $param['assets']['data'] = array("name" => $asset_key, "ip_cidr" => array($server_ip), "allowed" => Session::hostAllowed($conn, $server_ip) && Server::server_exists($conn,$asset_key) );
        
        $title       = ( $server_ip == $asset_key || empty($server_ip) ) ? _("Host Report").": $asset_key" : _("Host Report").": $asset_key ($server_ip)";
        $title_graph = preg_replace ("/Host Report: /","",$title);
    }
}
else
{
    $param['assets']['type'] = 'any';
    $param['assets']['data'] = array("name" => "any", "ip_cidr" => array("any"), "allowed" => true);
    
    $title       = _('System Report');
    $title_graph = _('System Report');
}



ossim_valid($greybox, OSS_ALPHA, OSS_NULLABLE,        'illegal:' . _("Greybox"));
ossim_valid($date_from, OSS_DIGIT, OSS_NULLABLE, '-', 'illegal:' . _("Date from"));
ossim_valid($date_to, OSS_DIGIT, OSS_NULLABLE, '-',   'illegal:' . _("Date to"));

if (ossim_error()) {
    die(ossim_error());
}


//Dates
if( $date_from == ''|| $date_to == '' )
{
	$date_from = date('Y-m-d', strtotime("-1 week")); 
	$date_to   = date('Y-m-d', time()); 
}

$date_range = array('date_from'=> $date_from, 'date_to'  => $date_to );

if( $date_from == date('Y-m-d', strtotime("-1 week")) && $date_to == date('Y-m-d', time()) )
	$type_active = 'lastWeek';
elseif( $date_from==date('Y-m-d', strtotime("-1 month")) && $date_to == date('Y-m-d', time()) )
	$type_active = 'lastMonth';
elseif( $date_from==date('Y-m-d', strtotime("-1 year")) && $date_to == date('Y-m-d', time()) )
	$type_active = 'lastYear';
else
	$type_active = 'null';

/*
echo "<pre>";
    print_r($param['assets']);
echo "</pre>";
exit;
*/
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title><?php echo $title?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<link rel="stylesheet" type="text/css" href="../style/top.css">
	<link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
	<link rel="stylesheet" type="text/css" href="../style/datepicker.css"/>
	
	<style type="text/css">
	
		* { padding: 0px; }
		
		body { 
			width: 99%; 
			margin: auto;
		}
        
        a {	font-size:10px; }
	
		.level11  {  background:url(../pixmaps/statusbar/level11.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px; }
		.level10  {  background:url(../pixmaps/statusbar/level10.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px; }
		.level9   {  background:url(../pixmaps/statusbar/level9.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px;  }
		.level8   {  background:url(../pixmaps/statusbar/level8.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px;  }
		.level7   {  background:url(../pixmaps/statusbar/level7.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px;  }
		.level6   {  background:url(../pixmaps/statusbar/level6.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px;  }
		.level5   {  background:url(../pixmaps/statusbar/level5.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px;  }
		.level4   {  background:url(../pixmaps/statusbar/level4.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px;  }
		.level3   {  background:url(../pixmaps/statusbar/level3.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px;  }
		.level2   {  background:url(../pixmaps/statusbar/level2.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px;  }
		.level1   {  background:url(../pixmaps/statusbar/level1.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px;  }
		.level0   {  background:url(../pixmaps/statusbar/level0.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px;  }
		.tag_cloud { padding: 3px; text-decoration: none; }
		.tag_cloud:link  { color: #17457C; }
		.tag_cloud:visited { color: #17457C; }
		.tag_cloud:hover { color: #ffffff; background: #17457C; }
		.tag_cloud:active { color: #ffffff; background: #ACFC65; }
		
				
		#cont_date {
			background: none;
		}
		
		#cont_date a{
			font-size: 8pt;
			color: #fff;
		}
		
		#cont_date a:link,#cont_date a:visited{
			color: #fff;
			text-decoration: none;
		}
		
		#cont_date a:hover{
			color: #fff !important;
			text-decoration: underline;
		}
		
		#cont_date a:active{
			color: #fff;
			text-decoration: none;
		}
		
		#cont_date #date_from, #cont_date #date_to{
			color: #C0C0C0;
		}
		
		
		#host_report table tr td {
			border-bottom: none !important;
			border-color: none !important;
		}
		
		#cont_loading {
			width: 220px;
			text-align: center;
			position:absolute;
			top:40%;
			left: 50%;
			margin-left: -110px;
		}
		
		#loading{
			position:relative;
			margin:auto; 
			width: 95%;
		}		
		
		#cont_title{
			font-size:18px;
			font-weight:bold;
			color:#EEEEEE;
			text-align:left;
			padding-left:10px
		}
		
		#cont_title_l{
			float:left; 
			width: 40%; 
            padding: 3px 0px 5px 0px;
			text-align: left;
		}
		
		#cont_title_r{
			float:right; 
			width: 58%; 
			color: #FFFFFF;
            padding: 3px 0px 5px 0px;
			text-align: right;
		}
		
		#cont_title_r #custom_date{
			float:right; 
			width: 330px; 
			padding: 3px 20px 5px 0px;
		}
		
		#cont_title_r span{
			font-size: 11px;
		}
		
		#cont_title_r #predefined_date{
			float:right; 
			padding: 12px 20px 5px 0px;
			width: 300px; 
        }
		
		#cont_title_r #predefined_date a {
			color: #FFFFFF;
		}
				
		.st_date { width: 115px;}
		.st_capsule {width: 75px;}
		
	</style>

	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script src="../forensics/js/jquery.flot.pack.js" language="javascript" type="text/javascript"></script>
	<script type="text/javascript" src="../js/jquery.progressbar.min.js"></script>
	<script type="text/javascript" src="../js/greybox.js"></script>
	<script src="../js/jquery.simpletip.js" type="text/javascript"></script>
	<script src="../js/datepicker.js" type="text/javascript"></script>
	<!--<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>-->
    <script type="text/javascript" src=" https://maps-api-ssl.google.com/maps/api/js?sensor=false"></script>
	
    <?php 
    if(  $param['assets']['type'] == 'any' ) 
    { 
        ?>
        <script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
        <script type="text/javascript" src="../js/jquery.tmpl.1.1.1.js"></script>
        <script type="text/javascript" src="../js/jquery.dynatree.js"></script>
        <link rel="stylesheet" type="text/css" href="../style/tree.css" />
        <?php 
    }
   		               
    $noready = 1; 
    include ("../host_report_menu.php") 
	?>

	<script type="text/javascript">
		//Tooltip
        var url = new Array(50);
        
		function showTooltip(x, y, contents, link) {
			if (typeof(url[link]) != "undefined") 
			{
				$('<div id="tooltip" class="tooltipLabel"><a href="' + url[link] + '" style="font-size:10px;">' + contents + '<a></div>').css( {
					position: 'absolute',
					display : 'none',
					top     : y + 5,
					left    : x + 8,
					border  : '1px solid #ADDF53',
					padding : '1px 2px 1px 2px',
					'background-color': '#CFEF95',
					opacity : 0.80
				}).appendTo("body").fadeIn(200);
			} 
			else 
			{
				$('<div id="tooltip" class="tooltipLabel">' + contents + '</div>').css( {
					position: 'absolute',
					display : 'none',
					top     : y + 5,
					left    : x + 8,
					border  : '1px solid #ADDF53',
					padding : '1px 2px 1px 2px',
					'background-color': '#CFEF95',
					opacity : 0.80
				}).appendTo("body").fadeIn(200);
			}
		}
        
        //Google Maps
        
        function initialize()
		{
			var latlng = new google.maps.LatLng(latitude, longitude);
			var myOptions = {
			  zoom: zoom,
			  center: latlng,
			  mapTypeId: google.maps.MapTypeId.ROADMAP
			};
			
			var map = new google.maps.Map(document.getElementById("map"), myOptions);
			
			var marker = new google.maps.Marker({
				position: latlng, 
				draggable: false,
				animation: google.maps.Animation.DROP,
				map: map, 
				title: '<?php echo " $title_graph "._("Location")?>'
			}); 
		}
        
        //NTOP Graphs
        
        function show_ntop_graph_1(host, title, id)
        {
            var elem_id = "graph_"+id+"_1";
             
            $.ajax({
                type: "GET",
                url: "ntop_graph.php?n=1&host="+host+"&title="+title,
                data: "",
                success: function(msg){
                    
                    if (msg != "") 
                    {
                        document.getElementById(elem_id).innerHTML = msg;
                        $("a.greybox").click(function(){
                            var t = this.title || $(this).text() || this.href;
                            var h = ($(this).attr('gbh')) ? $(this).attr('gbh') : 450;
                            var w = ($(this).attr('gbw')) ? $(this).attr('gbw') : 470;
                            GB_show(t,this.href,h,w);
                            return false;
                        });
                    }
                    else 
                        document.getElementById(elem_id).innerHTML = '<table align="center" class="noborder"><tr><td class="nobborder" style="text-align:center"><?php echo _("No data Available")?></td></tr></table>';
                    
                    graphs++;
                }
            });
        }
        
        function show_ntop_graph_2(host, title, id)
        {
            var elem_id = "graph_"+id+"_2";
             
            $.ajax({
                    type: "GET",
                    url: "ntop_graph.php?n=2&host="+host+"&title="+title,
                    data: "",
                    success: function(msg){
                        //alert (msg);
                        if (msg != "") 
                        {
                           
                            document.getElementById(elem_id).innerHTML = msg;
                            
                            $("a.greybox").click(function(){
                                var t = this.title || $(this).text() || this.href;
                                var h = ($(this).attr('gbh')) ? $(this).attr('gbh') : 300;
                                var w = ($(this).attr('gbw')) ? $(this).attr('gbw') : 370;
                                GB_show(t,this.href,h,w);
                                return false;
                            });
                        }
                        else 
                            document.getElementById(elem_id).innerHTML = '<table align="center" class="noborder"><tr><td class="nobborder" style="text-align:center"><?php echo _("No data Available")?></td></tr></table>';
                        graphs++;
                    }
                });
        }
        
        
        function executeRange(type){
                
            var o_date_from ='<?php echo $date_range['date_from']; ?>';
            var o_date_to   ='<?php echo $date_range['date_to']; ?>';
            var g_date_from = 'null';
            var g_date_to   = 'null';
            
            switch(type){
                case 'lastWeek':
                    g_date_from ='<?php echo date('Y-m-d', strtotime("-1 week")); ?>';
                    g_date_to   ='<?php echo date('Y-m-d', time()); ?>';
                    break;
                case 'lastMonth':
                    g_date_from ='<?php echo date('Y-m-d', strtotime("-1 month")); ?>';
                    g_date_to   ='<?php echo date('Y-m-d', time()); ?>';
                    break;
                case 'lastYear':
                    g_date_from ='<?php echo date('Y-m-d', strtotime("-1 year")); ?>';
                    g_date_to   ='<?php echo date('Y-m-d', time()); ?>';
                    break;
                default:
                    break;
            }
            
            if(g_date_from!='null'&&g_date_to!='null'){
                document.location.href='host_report.php?asset_type=<?php echo $asset_type;?>&asset_key=<?php echo $asset_key;?>&start_date='+g_date_from+'&end_date='+g_date_to;
            }
        }
                
		$(document).ready(function(){
			graphs = 0;
			$('#loading').toggle();
			
            <?php 
            $id_hr  = "#host_report";
            $id_hr .= ( $greybox ) ? "_mini" : "";
            ?>
            var id_hr  = '<?php echo $id_hr?>';
            $(id_hr).toggle();
			
            <?php 
                                  
            if( $param['assets']['type'] != 'any' )
            { 
                $ip_cidr = $param['assets']['data']['ip_cidr'];
                
                foreach ( $ip_cidr as $k => $v)
                {
                    ?>
                    show_ntop_graph_1('<?php echo $v?>', '<?php echo $v?>', '<?php echo generate_id($v)?>');
                    <?php
                }
            }             
            
            if( $param['assets']['type'] != 'any' && $param['assets']['type'] != 'net' && $param['assets']['type'] != 'net_group' )
            { 
                ?>
                show_ntop_graph_2('<?php echo $param['assets']['data']['ip_cidr'][0]?>', '<?php echo $title_graph ?>', '<?php echo generate_id($v)?>');
                <?php 
            } 
            
            ?>
			
            // CALENDAR
			<?php
			if ($date_from != "") 
			{
				$aux = split("-",$date_from);
				$y   = $aux[0]; $m = $aux[1]; $d = $aux[2];
			} 
			else 
			{
				$y = strftime("%Y", time() - (24 * 60 * 60));
				$m = strftime("%m", time() - (24 * 60 * 60 * 31));
				$d = strftime("%d", time() - (24 * 60 * 60));
			}
			
			if ($date_to != "") 
            {
				$aux = split("-",$date_to);
				$y2  = $aux[0]; $m2 = $aux[1]; $d2 = $aux[2];
			} 
			else 
				$y2 = date("Y"); $m2 = date("m"); $d2 = date("d");
			
			?>
			var datefrom = new Date(<?php echo $y ?>,<?php echo $m-1 ?>,<?php echo $d ?>);
			var dateto   = new Date(<?php echo $y2 ?>,<?php echo $m2-1 ?>,<?php echo $d2 ?>);
			var clicks   = 0;
			$('#widgetCalendar').DatePicker({
				flat: true,
				format: 'Y-m-d',
				date: [new Date(datefrom), new Date(dateto)],
				calendars: 3,
				mode: 'range',
				showCurrentAtPos: 0,
				starts: 1,
				onChange: function(formated) {
					if (formated[0]!="" && formated[1]!="" && clicks>0) {
						var f1 = formated[0].split(/-/);
						var f2 = formated[1].split(/-/);
						document.getElementById('date_from').value = f1[0]+'-'+f1[1]+'-'+f1[2];
						document.getElementById('date_to').value = f2[0]+'-'+f2[1]+'-'+f2[2];
						$('#date_str').css('text-decoration', 'underline');
						document.getElementById('queryform').submit();
					} 
					clicks++;
				}
			});
			
			var state = false;
			$('#widget>a').bind('click', function(){
				$('#widgetCalendar').stop().animate({height: state ? 0 : $('#widgetCalendar div.datepicker').get(0).offsetHeight}, 500);
				$('#imgcalendar').attr('src',state ? '../pixmaps/calendar.png' : '../pixmaps/tables/cross.png');
				state = !state;
				if(!state){
					var o_date_from='<?php echo $date_range['date_from']; ?>';
					var o_date_to='<?php echo $date_range['date_to']; ?>';
					
					if(o_date_from!=document.getElementById('date_from').value||o_date_to!=document.getElementById('date_to').value){
						document.location.href='host_report.php?asset_type=host&asset_key=<?php echo $host; ?>&start_date='+document.getElementById('date_from').value+'&end_date='+document.getElementById('date_to').value;
					}
				}
				return false;
			});
			
            $('#widgetCalendar div.datepicker').css('position', 'absolute');
                                   			
            $('.HostReportMenu').contextMenu({
				menu: 'myMenu'
            },
				function(action, el, pos) {
					var aux      = $(el).attr('id').split(/;/);
					var ip       = aux[0];
					var hostname = aux[1];
					var url      = "../report/host_report.php?asset_type=host&asset_key="+ip+"&greybox=0";
					
                    var title = ( hostname == ip ) ? "Host Report: " + ip : "Host Report: "+hostname+" ("+ip+")";
					var wnd   = window.open(url,'hostreport_'+ip,'fullscreen=yes,scrollbars=yes,location=no,toolbar=no,status=no,directories=no');
					wnd.focus()
				}
			);
			
			<?php 
			if ( $param['assets']['type'] != 'net' && $param['assets']['type'] != 'net_group' ) 
			{ 
				?>
				$("a.greybox_whois").click(function(){
					var t = "<?=_("Who is '")?>"+this.title+"'";
					var h = 120;
					var w = 400;
					GB_show(t,this.href,h,w);
					return false;
				});
				<?php 
			} 
			else
			{ 
				?>
				$(".scriptinfo_net").simpletip({
					position: 'left',
					baseClass: 'gtooltip',
					onBeforeShow: function() { 
						var data = this.getParent().attr('data');
						this.update(data);
					}
				});
				<?php 
			} 
			?>
			
			$(".scriptinfo").simpletip({
				position: 'bottom',
				onBeforeShow: function() { 
					var ip = this.getParent().attr('ip');
					this.load('whois.php?ip=' + ip);
				}
			});
			
			<?php 
            //Dynatree
            if( $param['assets']['type'] == 'any' ) 
            { 
                ?>
                $("#aptree").dynatree({
                    initAjax: { url: "../policy/asset_by_property_tree_wl.php" },
                    onActivate: function(dtnode) {
                        if(dtnode.data.url!='' && typeof(dtnode.data.url)!='undefined') {
                            GB_edit(dtnode.data.url+'&withoutmenu=1');
                        }
                    },
                    onLazyRead: function(dtnode){
                        dtnode.appendAjax({
                            url: "../policy/asset_by_property_tree_wl.php",
                            data: {key: dtnode.data.key, page: dtnode.data.page}
                        });
                        if (typeof(parent.doIframe2)=="function") parent.doIframe2();
                    }
                });
                <?php 
            } 
            ?>
		});
		
		
	</script>
</head>

<body>

<?php 
	if( $param['assets']['type'] == 'any' ) 
		include("../hmenu.php"); 
		
	if ( $param['assets']['data']['allowed'] == false ) 
	{ 
		?>
                <div class='ossim_error center'> 
                    <?php echo _("Asset")?><span style='font-style:italic; font-weight: bold;'> <?php echo $title_graph?> </span><?php echo _("not allowed")?>
                </div>
            </body>
		</html>
		<?php 
		exit; 
	} 
?>

	<form><input type="hidden" name="cursor"></form>
	<div id='cont_loading'>
		<div id="loading">
			<table class="noborder center" style="background-color:white; width:98%">
				<tr>
					<td class="nobborder" style="text-align:center">
						<span class="progressBar" id="pbar"></span>
					</td>
				</tr>
				<tr>
					<td class="nobborder" id="progressText" style="text-align:center"><?php echo gettext("Loading Report. Please, wait a few seconds...")?></td>
				</tr>
			</table>
		</div>
	</div>
	
	<script type="text/javascript">
		$("#pbar").progressBar();
		$("#pbar").progressBar(10);
        $("#progressText").html('<?php echo gettext("Loading <strong>Report</strong>. <br/>Please, wait a few seconds...")?>');
	</script>

	<?php
	ob_flush();
	flush();
	usleep(500000);
	?>

	<div id="host_report<?php if ($greybox) echo "_mini" ?>" style="display:none;">
		<table class="noborder" cellpadding="2" cellspacing="5" width="100%" height="100%">
			<tr>
				<td>
					<table style="background-color:#617F57" height="100%" cellpadding="5">
						<!-- ROW 0 - Report Title -->
						<tr>
							<td <?php echo $colspan;?> id='cont_title'>
								<div id='cont_title_l'>
									<?php 
                                    $ftitle = ( $param['assets']['type'] != 'any' ) ? ": <span style='font-size:16px'><i>$title_graph</i></span>" : "";
                                    echo _("General Data").$ftitle;
                                    ?>
								</div>
								<div id='cont_title_r'>
									<div id='predefined_date'>
										<?php 
											$title_week  = ( $type_active=='lastWeek' )  ? "<strong>"._("Last week")."</strong>"  : _("Last week");
											$title_month = ( $type_active=='lastMonth' ) ? "<strong>"._("Last month")."</strong>" : _("Last month");
											$title_year  = ( $type_active=='lastYear' )  ? "<strong>"._("Last year")."</strong>"  : _("Last year");
										?>												
										<a href="javascript:executeRange('lastWeek');"><?php echo $title_week; ?></a><span> | </span>
										<a href="javascript:executeRange('lastMonth');"><?php echo $title_month; ?></a><span> | </span>
										<a href="javascript:executeRange('lastYear');"><?php echo $title_year; ?></a> 
									</div>
																	
									<div id='custom_date'>
										<?php 
										if( $param['assets']['type'] != 'any' ) 
										{ 
											?>
											<div id="widget" style="display: inline;margin-right: 7px;">
												<a href="javascript:;"><img src="../pixmaps/calendar.png" id='imgcalendar' border="0" /></a>
												<div id="widgetCalendar"></div>
											</div>
											<span><?php echo _("From");?>: </span><input readonly="readonly" type="text" name="date_from" id="date_from"  value="<?php echo $date_from; ?>" style="width:80px;"/>
											<span><?php echo _("to"); ?>:  </span><input readonly="readonly" type="text" name="date_to" id="date_to" value="<?php echo $date_to; ?>" style="width:80px;"/>
											<?php 
										} 
										?>
									</div>
								</div>
							</td>
						</tr>
						
						<!-- ROW 1 -->
						<tr>
							<td class='noborder' valign='top'>
								<table style="background-color:#617F57" cellpadding="5">
									<tr>
										<td class="nobborder" valign="top" width="45%">
                                            <?php
                                            $asset_counter = 0;
                                            if( $param['assets']['type'] == 'net_group' || $param['assets']['type'] == 'net' )
                                            {
                                                foreach ( $param['assets']['data']['ip_cidr'] as $k => $asset)
                                                {
                                                    $host = $asset;
                                                    $asset_counter++; 
                                                    include ("host_report_status.php");
                                                }
                                            }
                                            else
                                            {
                                                $asset_counter++; 
                                                $host = ( $param['assets']['data']['ip_cidr'][0] == 'any' ) ? "" : $param['assets']['data']['ip_cidr'][0];
                                                include ("host_report_status.php");
                                            }
                                            ?>
                                        </td>
										
                                        <td valign="top" class="nobborder" height="100%" width="<?php echo ( $param['assets']['type'] == 'net' || $param['assets']['type'] == 'net_group' ) ? "35%" : "45%"?>">
										<?php
                                        $asset_counter = 0;
										if( $param['assets']['type'] != 'any' )
										{
											$asset_counter++; 
                                            if( $param['assets']['type'] == 'net' || $param['assets']['type'] == 'net_group' )
                                            {
                                                include ("net_report_inventory.php");
                                            }
                                            else
                                            {
                                                $host = $param['assets']['data']['ip_cidr'][0];
                                                include ("host_report_inventory.php");
                                            }
                                        }  
                                        else
										{
											?>
											<table border="0" width="100%" align="center" cellspacing="0" cellpadding="0">
												<tr>
													<td class="headerpr" height="20"><?php echo _("Inventory")?></td>
												</tr>
												<tr>
													<td class="nobborder">
														<div id="aptree" style="font-size:15px;text-align:left;width:98%;padding:8px"></div>
													</td>
												</tr>
											</table>
											<?php
										}
										?>
										</td>
										<?php 
										if ( $param['assets']['type'] == 'net' || $param['assets']['type'] == 'net_group' )
										{ 
											?>
											<td valign="top" class="nobborder" height="100%">
                                                <?php
                                                $asset_counter = 0;
                                                foreach ( $param['assets']['data']['ip_cidr'] as $k => $asset)
                                                {
                                                    $asset_counter++;
                                                    include ("net_report_network.php"); 
                                                }    
                                                ?>
											</td>
											<?php
										} 
										?>
									</tr>
								</table>
							</td>
						</tr>			
					</table>
				</td>
			</tr>
            
            <!-- ROW 2 -->
			<tr>
				<td>
					<table style="background-color:#727385" height="100%" cellpadding="5">
						<tr><td colspan="3" style="font-size:18px;font-weight:bold;color:#EEEEEE;text-align:left;padding-left:10px"><?php echo _("SIEM")?></td></tr>
                        <?php
                        $asset_counter = 0;
                        foreach ( $param['assets']['data']['ip_cidr'] as $k => $asset)
                        {
                            $host = $asset;
                            $asset_counter++;
                            ?>
                            <tr>
                                <td class="nobborder" valign="top" width="33%" height="100%"><?php include ("host_report_tickets.php");?></td>
                                <td class="nobborder" valign="top" width="33%" height="100%"><?php include ("host_report_alarms.php");?></td>
                                <td class="nobborder" valign="top" width="33%" height="100%"><?php include ("host_report_vul.php");?></td>
                            </tr>
                            <?php
                        }
                        ?>
                        <tr>
                            <td colspan="3" class="nobborder">
                                <div style='margin-top: 10px;'>
                                    <?php
                                    $asset_counter = 0;
                                    foreach ( $param['assets']['data']['ip_cidr'] as $k => $asset)
                                    {
                                        $host = ( $asset == 'any' ) ? "" : $asset;
                                        $asset_counter++;
                                        include ("host_report_sim.php");
                                    }
                                    ?> 
                                </div>
                            </td>
                        </tr>
					</table>
				</td>
			</tr>
			
			<script type="text/javascript">
				
				$("#pbar").progressBar(90);$("#progressText").html('<strong><?=gettext("Generating Report")?></strong>...');
							
				<?php
					ob_flush();
					flush();
					usleep(500000);
				?>
			
				$("#pbar").progressBar(95);
			
				<?php
					ob_flush();
					flush();
					usleep(500000);
				?>
			</script>
            
            
            <!-- ROW 3 -->
			<tr>
				<td>
					<table style="background-color:#8F6259" height="100%" cellpadding="5">
						<tr><td style="font-size:18px;font-weight:bold;color:#EEEEEE;text-align:left;padding-left:10px"><?php echo gettext("Logger"); ?></td></tr>
						<tr>
                            <td class="nobborder">
                                <?php 
                                $asset_counter = 0; 
                                foreach ( $param['assets']['data']['ip_cidr'] as $k => $asset)
                                {
                                    $host = ( $asset == 'any' ) ? "" : $asset;
                                    $asset_counter++; 
                                    include ("host_report_sem.php") ;
                                }
                                ?>
                                
                            </td>
                        </tr>
						
                        <script type="text/javascript">$("#pbar").progressBar(99);$("#progressText").html('<b><?=gettext("Finishing")?></b>...');</script>
						<?php
						ob_flush();
						flush();
						usleep(500000);
						?>
					</table>
				</td>
			</tr>
        
		</table>
	</div>
</body>
</html>

<?php
$db->close($conn);
$db->close($conn_snort);
ob_end_flush();
?>
