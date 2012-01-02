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
require_once 'classes/Session.inc';
require_once 'classes/Security.inc';
require_once 'classes/Util.inc';

Session::logcheck("MenuIncidents", "ControlPanelAlarms");
ini_set("max_execution_time","300");

$unique_id                    = uniqid("alrm_");
$prev_unique_id               = $_SESSION['alarms_unique_id'];
$_SESSION['alarms_unique_id'] = $unique_id;

require_once ('ossim_db.inc');
require_once ('classes/Host.inc');
require_once ('classes/Net.inc');
require_once ('classes/Host_os.inc');
require_once ('classes/Alarm.inc');
require_once ('classes/Reputation.inc');
require_once ('classes/Plugin.inc');
require_once ('classes/Plugin_sid.inc');
require_once ('classes/Port.inc');
require_once ('classes/Sensor.inc');
require_once ('classes/Util.inc');
require_once ('classes/Tags.inc');
require_once ('classes/GeoLoc.inc');
$GeoLoc = new GeoLoc("/usr/share/geoip/GeoIP.dat");

/* default number of events per page */
$ROWS = 50;

/* connect to db */
$db              = new ossim_db();
$conn            = $db->connect();

$delete          = GET('delete');
$close           = GET('close');
$open            = GET('open');
$delete_day      = GET('delete_day');
$order           = GET('order');
$src_ip          = GET('src_ip');
$dst_ip          = GET('dst_ip');
$backup_inf      = $inf = GET('inf');
$sup             = GET('sup');
$hide_closed     = GET('hide_closed');
$no_resolv       = intval(GET('no_resolv'));

$autorefresh     = "";
$refresh_time    = "";

if ( isset($_GET['search']) )
{
    unset($_SESSION['_alarm_autorefresh']);
    if ( isset($_GET['autorefresh']) )
    {
        $autorefresh  = ( GET('autorefresh') != '1' ) ? 0 : 1;
        $refresh_time = GET('refresh_time');
        $_SESSION['_alarm_autorefresh'] = GET('refresh_time');
    }
}
else
{
    if ( $_SESSION['_alarm_autorefresh'] != '' )
    {
        $autorefresh  = 1;
        $refresh_time = $_SESSION['_alarm_autorefresh'];
    }
}


$query           = (GET('query') != "") ? GET('query') : "";
$directive_id    = GET('directive_id');
$sensor_query    = GET('sensor_query');
$tag             = GET('tag');
$num_events      = GET('num_events');
$num_events_op   = GET('num_events_op');
$date_from       = GET('date_from');
$date_to         = GET('date_to');
$num_alarms_page = GET('num_alarms_page');
$param_unique_id = GET('unique_id');
$ds_id           = GET('ds_id');
$ds_name         = GET('ds_name');
$beep            = intval(GET('beep'));

$sensors = $hosts = $ossim_servers = array();

list($sensors, $hosts, $icons) = Host::get_ips_and_hostname($conn,true);

$hosts_ips       = array_keys($hosts);
$tags            = Tags::get_list($conn);
$tags_html       = Tags::get_list_html($conn);


ossim_valid($order,           OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, '.',           'illegal:' . _("Order"));
ossim_valid($delete,          OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Delete"));
ossim_valid($close,           OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Close"));
ossim_valid($open,            OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Open"));
ossim_valid($delete_day,      OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE,                 'illegal:' . _("Delete_day"));
ossim_valid($query,           OSS_ALPHA, OSS_PUNC_EXT, OSS_SPACE, OSS_NULLABLE,             'illegal:' . _("Query"));
ossim_valid($autorefresh,     OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Autorefresh"));
ossim_valid($refresh_time,    OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Refresh_time"));
ossim_valid($directive_id,    OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Directive_id"));
ossim_valid($src_ip,          OSS_IP_ADDRCIDR, OSS_NULLABLE,                                'illegal:' . _("Src_ip"));
ossim_valid($dst_ip,          OSS_IP_ADDRCIDR, OSS_NULLABLE,                                'illegal:' . _("Dst_ip"));
ossim_valid($inf,             OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Inf"));
ossim_valid($sup,             OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Order"));
ossim_valid($hide_closed,     OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Hide_closed"));
ossim_valid($date_from,       OSS_DIGIT, OSS_SCORE, OSS_NULLABLE,                           'illegal:' . _("From date"));
ossim_valid($date_to,         OSS_DIGIT, OSS_SCORE, OSS_NULLABLE,                           'illegal:' . _("To date"));
ossim_valid($param_unique_id, OSS_ALPHA, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE,                'illegal:' . _("Unique id"));
ossim_valid($num_alarms_page, OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Field number of alarms per page"));
ossim_valid($sensor_query,    OSS_IP_ADDR, OSS_ALPHA, OSS_DIGIT, OSS_PUNC, OSS_NULLABLE,    'illegal:' . _("Sensor_query"));
ossim_valid($tag,             OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Tag"));
ossim_valid($num_events,      OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Num_events"));
ossim_valid($num_events_op,   OSS_ALPHA, OSS_NULLABLE,                                      'illegal:' . _("Num_events_op"));
ossim_valid($ds_id,           OSS_DIGIT, "-", OSS_NULLABLE,                                 'illegal:' . _("Datasource"));
ossim_valid($beep,            OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Beep"));

if ( ossim_error() ) 
    die(ossim_error());

$_SESSION["_no_resolv"] = $no_resolv;

$parameters['query']                  = "query="          .$query;
$parameters['directive_id']           = "directive_id="   .$directive_id;
$parameters['inf']                    = "inf="            .$inf;
$parameters['sup']                    = "sup="            .$sup;
$parameters['no_resolv']              = "no_resolv="      .$no_resolv;
$parameters['hide_closed']            = "hide_closed="    .$hide_closed;
$parameters['order'] 		          = "order="          .$order;
$parameters['src_ip'] 		          = "src_ip="         .$src_ip;
$parameters['dst_ip'] 		          = "dst_ip="         .$dst_ip;
$parameters['num_alarms_page']        = "num_alarms_page=".$num_alarms_page;
$parameters['date_from']              = "date_from="      .urlencode($date_from);
$parameters['date_to']                = "date_to="        .urlencode($date_to);
$parameters['sensor_query']           = "sensor_query="   .$sensor_query;
$parameters['tag']                    = "tag="            .$tag;
$parameters['num_events']             = "num_events="     .$num_events;
$parameters['num_events_op']          = "num_events_op="  .$num_events_op;
$parameters['refresh_time']           = "refresh_time="   .$refresh_time;
$parameters['autorefresh']            = "autorefresh="    .$autorefresh;
$parameters['ds_id']                  = "ds_id="          .$ds_id;
$parameters['ds_name']         		  = "ds_name="        .urlencode($ds_name);
$parameters['bypassexpirationupdate'] = "bypassexpirationupdate=1";
$parameters['beep']                   = "beep="           .$beep;

if (empty($refresh_time) || ($refresh_time != 30000 && $refresh_time != 60000 && $refresh_time != 180000 && $refresh_time != 600000))
    $refresh_time = 60000;

$refresh_time_secs = ($refresh_time>0) ? $refresh_time/1000 : 0;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?=_("Control Panel")?> </title>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" href="../style/style.css"/>
	<link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
	<link rel="stylesheet" type="text/css" href="../style/datepicker.css"/>
	<link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css">
	<?php 
	if ( $autorefresh ) 
	{ 
		?>
		<script type="text/javascript">
			<?php $refresh_url = $_SERVER['SCRIPT_NAME']."?".implode("&", $parameters); ?>
			setInterval("document.location.href='<?php echo $refresh_url ?>'", <?php echo $refresh_time ?>);
		</script>
		<?php 
	} 
	?>
	
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/greybox.js"></script>
	<script src="../js/jquery.simpletip.js" type="text/javascript"></script>
	<script src="../js/datepicker.js" type="text/javascript"></script>
	<script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>
	
	<?php include ("../host_report_menu.php");?>
	<script language="javascript">
		function confirm_delete(url) {
			if (confirm('<?php echo  Util::js_entities(_("Are you sure you want to delete this Alarm and all its events?"))?>')) 
			{
				window.location=url;
			}
		}
  
		function show_alarm (id,tr_id) {
			tr = "tr"+tr_id;
			document.getElementById(tr).innerHTML = "<div style='padding: 10px 0px'><img src='../pixmaps/loading3.gif'/><span style='margin-left: 5px;'><?php echo _("Loading alarm")?>...</span></div>";
			//alert (id);
			$.ajax({
				type: "GET",
				url: "events_ajax.php?backlog_id="+id,
				data: "",
				success: function(msg){
					//alert (msg);
					document.getElementById(tr).innerHTML = msg;
					plus = "plus"+tr_id;
					document.getElementById(plus).innerHTML = "<a href='' onclick=\"hide_alarm('"+id+"','"+tr_id+"');return false\"><img align='absmiddle' src='../pixmaps/minus-small.png' border='0'/></a>"+tr_id;

					// GreyBox
					$(document).ready(function(){
						GB_TYPE = 'w';
						$("a.greybox").click(function(){
							var t = this.title || $(this).text() || this.href;
							GB_show(t,this.href,450,'90%');
							return false;
						});
					});
					load_contextmenu();
				}
				});
		}
		  
		function hide_alarm (id,tr_id) {
			tr = "tr"+tr_id;
			document.getElementById(tr).innerHTML = "";
			plus = "plus"+tr_id;
			document.getElementById(plus).innerHTML = "<a href='' onclick=\"show_alarm('"+id+"','"+tr_id+"');return false\"><img align='absmiddle' src='../pixmaps/plus-small.png' border='0'/></a>"+tr_id;
		}
		
		function checkall () {
			$("input[type=checkbox]").each(function() {
				if (this.id.match(/^check_\d+/)) {
					this.checked = (this.checked) ? false : true;
				}
			});
		}
		
		function checkall_date (d) {
			$("input[datecheck='"+d+"']").each(function() {
				this.checked = (this.checked) ? false : true;
			});
		}
		
		function tooglebtn() {
			$('#searchtable').toggle();
			if ($("#timg").attr('src').match(/toggle_up/)) 
				$("#timg").attr('src','../pixmaps/sem/toggle.gif');
			else
				$("#timg").attr('src','../pixmaps/sem/toggle_up.gif');

			if (!showing_calendar) calendar();
		}
		
		var showing_calendar = false;
		
		function calendar() {
			showing_calendar = true;
			// CALENDAR
			<?php
			if ($date_from != "") {
				$aux = split("-",$date_from);
				$y = $aux[0]; $m = $aux[1]; $d = $aux[2];
			} 
			else 
			{
				/*
				$y = strftime("%Y", time() - (24 * 60 * 60));
				$m = strftime("%m", time() - (24 * 60 * 60));
				$d = strftime("%d", time() - (24 * 60 * 60));
				*/
				$y = date("Y"); $m = date("m"); $d = date("d");
			}
			
			if ($date_to != "") {
				$aux = split("-",$date_to);
				$y2 = $aux[0]; $m2 = $aux[1]; $d2 = $aux[2];
			} 
			else 
			{
				$y2 = date("Y"); $m2 = date("m"); $d2 = date("d");
			}
			?>
			
			var datefrom = new Date(<?php echo $y ?>,<?php echo $m-1 ?>,<?php echo $d ?>);
			var dateto   = new Date(<?php echo $y2 ?>,<?php echo $m2-1 ?>,<?php echo $d2 ?>);
			var clicks = 0;
			$('#widgetCalendar').DatePicker({
				flat: true,
				format: 'Y-m-d',
				date: [new Date(datefrom), new Date(dateto)],
				calendars: 3,
				mode: 'range',
				starts: 1,
				onChange: function(formated) {
					if (formated[0]!="" && formated[1]!="" && clicks>0) {
						var f1 = formated[0].split(/-/);
						var f2 = formated[1].split(/-/);
						document.getElementById('date_from').value = f1[0]+'-'+f1[1]+'-'+f1[2];
						document.getElementById('date_to').value = f2[0]+'-'+f2[1]+'-'+f2[2];
						$('#date_str').css('text-decoration', 'underline');
						document.getElementById('queryform').submit();
					} clicks++;
				}
			});
			
			var state = false;
			$('#widget>a').bind('click', function(){
				$('#widgetCalendar').stop().animate({height: state ? 0 : $('#widgetCalendar div.datepicker').get(0).offsetHeight}, 500);
				$('#imgcalendar').attr('src',state ? '../pixmaps/calendar.png' : '../pixmaps/tables/cross.png');
				state = !state;
				return false;
			});
			
			$('#widgetCalendar div.datepicker').css('position', 'absolute');
		}

		
		function set_hand_cursor() {
			document.body.style.cursor = 'pointer';
		}
		
		function set_pointer_cursor() {
			document.body.style.cursor = 'default';
		}
        
        <?php
        if ( Session::menu_perms("MenuIncidents", "ControlPanelAlarmsDelete") )
        {
            ?>
            function bg_delete() {
                
                $('#info_delete').show();
                
                var params = "";
                $(".alarm_check").each(function()
                {
                    if ($(this).attr('checked') == true) {
                        params += "&"+$(this).attr('name')+"=1";
                    }
                });
                
                $.ajax({
                    type: "POST",
                    url: "alarms_check_delete.php",
                    data: "background=1&unique_id=<?php echo $unique_id ?>"+params,
                    success: function(msg){
                        $('#delete_data').html('<?php echo _("Reloading data ...") ?>');
                        document.location.href='<?php echo $refresh_url?>';
                    }
                });
                
            }
        
            function delete_all_alarms(id) {
                if(confirm('<?php echo  Util::js_entities(_("Alarms should never be deleted unless they represent a false positive. Do you want to Continue?"))?>')) 
                {
                    
                    <?php
                    
                        $delete_all_url =  $close_all_url = $_SERVER['SCRIPT_NAME']."?";
                        $p_delete_all   = $p_close_all    = $parameters;
                        
                        
                        $p_delete_all['delete_backlog'] = "delete_backlog=all";
                                            
                        $p_close_all['delete_backlog'] = "delete_backlog=all";
                        $p_close_all['only_close'] = "only_close=1";
                                            
                        $delete_all_url  .=  implode("&", $p_delete_all);
                        $close_all_url   .=  implode("&", $p_close_all);
                    
                    ?>
                    
                    
                    if(confirm('<?php echo  Util::js_entities(_("Would you like to close the alarm instead of deleting it? Click cancel to close, or Accept to continue deleting."))?>')) 
                    {
                        $('#delete_data').html('<?php echo _("Deleting ALL alarms ...") ?>');
                        $('#info_delete').show();
                        document.location.href='<?php echo $delete_all_url?>&unique_id='+id;
                    } 
                    else 
                    {
                        $('#delete_data').html('<?php echo _("Closing ALL alarms ...") ?>');
                        $('#info_delete').show();
                        document.location.href='<?php echo $close_all_url ?>&unique_id='+id;
                    }
                }
            }

            function ondelete_day(url){
                if(confirm('<?php echo  Util::js_entities(_("Alarms should never be deleted unless they represent a false positive. Do you want to Continue?"))?>')) 
                {
                    document.location.href='<?php echo($_SERVER["SCRIPT_NAME"]."?") ?>'+url;
                }
            }	
            
            <?php
        }
        ?>
	</script>
	
	<style type='text/css'>
			
		.label_filter_l { 
			text-align: left;
			border: none;
		}
		
		.filters { 
			text-align: left; 
			border: none;
			width: 200px;
		}
		
		.width100 { width: 100px; }
		.width20 { width: 20%; }
		
		#sensor_query {margin-left: 0px; width: 200px;}
		
		.pl4{padding-left: 4px;}
		
		.inpw_200 { width: 200px;}
		.inpw_220 { width: 220px;}
		.label_ip_s {padding-bottom: 3px; height: 20px;}
		
		input {height: 16px;}
		
		#info_delete{ display: none; }
		
		#delete_data{
			margin-left: 5px; 
			font-size: 11px;
		}
        
        .disabled img {
            filter:alpha(opacity=50);
            -moz-opacity:0.5;
            -khtml-opacity: 0.5;
            opacity: 0.5;
        }
		
	</style>
</head>

<body>

<?php
if (GET('withoutmenu') != "1") include ("../hmenu.php");

if (!empty($delete)) {
	if (!Session::menu_perms("MenuIncidents", "ControlPanelAlarmsDelete"))
		die(ossim_error("You don't have required permissions to delete Alarms"));
	else {
		if (check_uniqueid($prev_unique_id,$param_unique_id)) Alarm::delete($conn, $delete);
		else die(ossim_error("Can't do this action for security reasons."));
	}
}

if (!empty($close)) {
	if (check_uniqueid($prev_unique_id,$param_unique_id)) Alarm::close($conn, $close);
	else die(ossim_error("Can't do this action for security reasons."));
}

if (!empty($open)) {
    if (check_uniqueid($prev_unique_id,$param_unique_id)) Alarm::open($conn, $open);
	else die(ossim_error("Can't do this action for security reasons."));
}

if ($list = GET('delete_backlog')) 
{
	if (!Session::menu_perms("MenuIncidents", "ControlPanelAlarmsDelete"))
		die(ossim_error("You don't have required permissions to delete Alarms"));
	else {
	    if (check_uniqueid($prev_unique_id,$param_unique_id)) {
			if (!strcmp($list, "all")) {
				$backlog_id = $list;
				$id = null;
			} else {
				list($backlog_id, $id) = split("-", $list);
			}
			if (GET('only_close') != "") { $backlog_id = "closeall"; }
			Alarm::delete_from_backlog($conn, $backlog_id, $id, $hide_closed, $src_ip, $dst_ip, $date_from, $date_to, $sensor_query);
		}
		else die(ossim_error("Can't do this action for security reasons."));
	}
}

if (!empty($delete_day)) {
	if (!Session::menu_perms("MenuIncidents", "ControlPanelAlarmsDelete"))
		die(ossim_error("You don't have required permissions to delete Alarms"));
	else {
		if (check_uniqueid($prev_unique_id,$param_unique_id))
			Alarm::delete_day($conn, $delete_day);
		else
			die(ossim_error("Can't do this action for security reasons."));
	}
}


if (empty($order)) 
	$order = " a.timestamp DESC";

if ((!empty($src_ip)) && (!empty($dst_ip))) {
    $where = "WHERE inet_ntoa(src_ip) = '$src_ip' OR inet_ntoa(dst_ip) = '$dst_ip'";
} elseif (!empty($src_ip)) {
    $where = "WHERE inet_ntoa(src_ip) = '$src_ip'";
} elseif (!empty($dst_ip)) {
    $where = "WHERE inet_ntoa(dst_ip) = '$dst_ip'";
} else {
    $where = '';
}

if ($num_alarms_page) {
    $ROWS = $num_alarms_page;
}
if (empty($inf)) $inf = 0;
if (!$sup) $sup = $ROWS;

//Autocompleted
$sensors_str = "";
$hosts_str   = "";

foreach ($sensors as $s_ip=>$s_name) {
	if ($s_name!=$s_ip) $sensors_str .= '{ txt:"'.$s_ip.' ['.$s_name.']", id: "'.$s_ip.'" },';
    else $sensors_str .= '{ txt:"'.$s_ip.'", id: "'.$s_ip.'" },';
}

foreach ($hosts as $h_ip=>$h_name) {
	if ($h_name!=$h_ip) $hosts_str .= '{ txt:"'.$h_ip.' ['.$h_name.']", id: "'.$h_ip.'" },';
    else $hosts_str .= '{ txt:"'.$h_ip.'", id: "'.$h_ip.'" },';
}

//Datasource filter
$plugin_id  = "";
$plugin_sid = "";

if ( !empty($ds_id) )
{
	$ds = explode("-", $ds_id);
	$plugin_id  = $ds[0];
	$plugin_sid = $ds[1];
}


// Improved efficiency (Granada, junio 2009)
list($alarm_list, $count) = Alarm::get_list3($conn, $src_ip, $dst_ip, $hide_closed, "ORDER BY $order", $inf, $sup, $date_from, $date_to, $query, $directive_id, $sensor_query, $tag, $num_events, $num_events_op, 0, $plugin_id, $plugin_sid);

if (!isset($_GET["hide_search"])) 
{
	?>

	<form method="GET" id="queryform" name="filters">

		<input type="hidden" name="tag" value="<?php echo $tag ?>">
		<input type="hidden" name="date_from" id="date_from"  value="<?php echo $date_from ?>">
		<input type="hidden" name="date_to" id="date_to" value="<?php echo $date_to ?>">


		<table width="98%" align="center" class="transparent">
			<tr>
				<td class="filters">
					<a href="javascript:;" onclick="tooglebtn()">
						<img src="../pixmaps/sem/toggle.gif" border="0" id="timg" title="Toggle"> 
						<small><span style='color:black'><?php echo _("Filters, Options and Actions") ?></span></small>
					</a>
				</td>
				<td class='noborder left'>
					<div id='info_delete'>
						<img src='../pixmaps/loading3.gif' alt='<?php echo _("Deleting grouped alarms")?>'/>
						<span id='delete_data'><?php echo _("Deleting grouped alarms.  Please, wait a few seconds")?>...</span>
					</div>
				</td>
			</tr>
		</table>
        
        
		<table width="98%" align="center" id="searchtable" style="display:none">
			<tr>
				<th width="65%"><?=_("Filter") ?></th>
				<th><?php echo _("Options")?></th>
				<th width="120px"><?=_("Actions")?></th>
			</tr>
			<tr>
				<td class="nobborder">
					<table class="transparent" style='width: 100%'>
						<tr>
							<td class='label_filter_l'><strong><?php echo _("Sensor")?></strong>:</td>
							<td class='noborder left' nowrap='nowrap'>
								<select name="sensor_query" id='sensor_query'>
									<option value=""></option>
									<?php 
									foreach ($sensors as $sensor_ip=>$sensor_name) if ($sensor_ip != "")
									{ 
										$selected = ( $sensor_query == $sensor_ip ) ? "selected='selected'" : "";
										?>
										<option value="<?php echo $sensor_ip ?>" <?php echo $selected?>><?php echo $sensor_name ?> (<?php echo $sensor_ip ?>)</option>
										<?php 
									} 
									?>
								</select>
							</td>
							<td class='noborder left'>
								<span style='font-weight: bold;'><?php echo _("Contains the event")?></span>: 
								<input type="text" class='inpw_200' style='width:160px;margin-left: 10px;' name="ds_name" id='ds_name' value="<?php echo htmlentities($ds_name)?>" onchange="if (this.value=='') $('#ds_id').val('')"/>
								<input type="hidden" name="ds_id" id='ds_id' value="<?php echo $ds_id?>"/>
							</td>
						</tr>
						<tr>
							<td class='label_filter_l width20'><strong><?php echo _("Alarm name")?></strong>: </td>
							<td class='label_filter_l pl4' nowrap='nowrap'><input type="text" class='inpw_200' name="query" value="<?php echo $query ?>"/></td>
							<td class='noborder left'>
								<span style='font-weight: bold;'><?php echo _("Directive ID")?>:</span>
								<input type="text" class='inpw_200' style='margin-left: 10px;' name="directive_id" value="<?=$directive_id?>"/>
							</td>
						</tr>
						<tr>
							<td class='label_filter_l width100'><strong><?php echo _("IP Address") ?></strong>:</td>
							<td class='label_filter_l pl4' nowrap='nowrap'>
								<div class='label_ip_s'>
									<div style='width: 60px; float: left;'><?php echo _("Source") ?>:</div> 
									<div style='float: left;'><input type="text" id="src_ip" name="src_ip" value="<?php echo $src_ip ?>"/></div> 
								</div>
								<div class='label_ip_d'>
									<div style='width: 60px; float: left;'><?php echo _("Destination") ?>:</div> 
									<div style='float: left;'><input type="text" id="dst_ip" name="dst_ip" value="<?php echo $dst_ip ?>"/></div> 
								</div>
							</td>
							<td class='noborder left'>
								<div style='padding-bottom: 3px;'>
									<strong><?php echo _("Num. alarms per page") ?></strong>: <input type="text" size='3' name="num_alarms_page" value="<?php echo $ROWS ?>"/>
								</div>
								<div>
									<strong><?php echo _("Number of events in alarm") ?></strong>:
									<select name="num_events_op">
										<option value="less" <?php if ($num_events_op == "less") echo "selected='selected'"?>>&lt;=</option>
										<option value="more" <?php if ($num_events_op == "more") echo "selected='selected'"?>>&gt;=</option>
									</select>
									&nbsp;<input type="text" name="num_events" size='3' value="<?php echo $num_events ?>"/>
								</div>
							</td>
						</tr>
						<tr>
							<td class='label_filter_l width100' nowrap='nowrap'></td>
							<td class='label_filter_l pl4' nowrap='nowrap'></td>
							<td class='noborder left'>
						</td>
						</tr>
						<tr>
							<td id="date_str" class='label_filter_l width100' style='<?php echo $underlined ?>'><strong><?php echo _('Date') ?></strong>:</td>
							<td class="nobborder">
								<div id="widget" style="display:inline">
									<a href="javascript:;"><img src="../pixmaps/calendar.png" id="imgcalendar" border="0"></a>
									<div id="widgetCalendar"></div>
								</div>
								<?php 
									if ($date_from != "" && $date_to != "") 
									{ 
										?>
										<a href="" onclick="document.getElementById('date_from').value='';document.getElementById('date_to').value='';form_submit();return false">
											[<?php echo $date_from ?> - <?php echo $date_to ?>]
										</a>
										<?php 
									} 
									?>
							</td>
							<td class='noborder left'>&nbsp;</td>
						</tr>
					</table>
				</td>
				
				
				<?php
					$hide_closed     = ( $hide_closed == 1 ) ? 1 : 0;
					$not_hide_closed = !$hide_closed;
					$not_no_resolv   = !$no_resolv;
					$not_beep        = !$beep;
					
					$checked_resolv  = ( $no_resolv )   ? " checked='checked'" : "";
					$checked_hclosed = ( $hide_closed ) ? " checked='checked'" : "";
					$checked_beep    = ( $beep )        ? " checked='checked'" : "";
					
					$no_resolv_url =  $hclosed_url = $refresh_url = $beep_url = $_SERVER['SCRIPT_NAME']."?";
					
					$p_no_resolv              = $parameters;
					$p_no_resolv['no_resolv'] = "no_resolv=".$not_no_resolv;
					$no_resolv_url           .=  implode("&", $p_no_resolv);
											
					$p_hclosed                = $parameters;
					$p_hclosed['hide_closed'] = "hide_closed=".$not_hide_closed;
					$hclosed_url             .=  implode("&", $p_hclosed);

					$p_beep                   = $parameters;
					$p_beep['beep']           = "beep=".$not_beep;
					$beep_url                .=  implode("&", $p_beep);
					
					$refresh_url            .=  implode("&", $parameters);
								
					$refresh_sel1 = $refresh_sel2 = $refresh_sel3 = $refresh_sel4 = "";
					
					if ($refresh_time == 30000)  $refresh_sel1 = 'selected="selected"';
					if ($refresh_time == 60000)  $refresh_sel2 = 'selected="selected"';
					if ($refresh_time == 180000) $refresh_sel3 = 'selected="selected"';
					if ($refresh_time == 600000) $refresh_sel4 = 'selected="selected"';
								
					if ($autorefresh) 
					{
						$hide_autorefresh    = 'checked="checked"';
						$disable_autorefresh = '';
					}
					else 
					{
						$hide_autorefresh    = '';
						$disable_autorefresh = 'disabled="disabled"';
					}
					
				?>
				
				<td class="nobborder" style="text-align:center">
					<table class="noborder" align="center" style="width:80%; ">
						<tr>
							<td style="text-align: left; border-width: 0px">
								<input style="border:none" name="no_resolv" type="checkbox" value="1"  onClick="document.location='<?php echo $no_resolv_url?>'" <?php echo $checked_resolv?>/><?php echo gettext("Do not resolv ip names"); ?>
							</td>
						</tr>		
						<tr>
							<td style="text-align: left; border-width: 0px">
								<input style="border:none" name="hide_closed" type="checkbox" value="1"  onClick="document.location='<?php echo $hclosed_url?>'" <?php echo $checked_hclosed?>/><?php echo gettext("Hide closed alarms"); ?>
							</td>
						</tr>
						<tr>
							<td style="text-align: left; border-width: 0px">
								<input style="border:none" name="beep" type="checkbox" value="1"  onClick="document.location='<?php echo $beep_url?>'" <?php echo $checked_beep?>/><?php echo gettext("Beep on new alarm"); ?>
							</td>
						</tr>
						<tr>
							<td style="text-align: left; border-width: 0px">
								<input type="checkbox" name="autorefresh" onclick="javascript:document.filters.refresh_time.disabled=!document.filters.refresh_time.disabled;" <?php echo $hide_autorefresh ?> value='1'/><?php echo gettext("Autorefresh") ?>&nbsp;
								<select name="refresh_time" <?php echo $disable_autorefresh ?> >
									<option value="30000"  <?php echo $refresh_sel1 ?> ><?php echo _("30 sec") ?></option>
									<option value="60000"  <?php echo $refresh_sel2 ?>><?php echo _("1 min") ?></option>
									<option value="180000" <?php echo $refresh_sel3 ?>><?php echo _("3 min") ?></option>
									<option value="600000" <?php echo $refresh_sel4 ?>><?php echo _("10 min") ?></option>
								</select>&nbsp;
								<a href="<?php echo $refresh_url ?>">[<?php echo _("Refresh") ?>]</a>
							</td>
						</tr>
					</table>
				</td>		
				
				<td style="text-align: center;border-bottom:0px solid white" nowrap='nowrap'>
					<table class="noborder" width='100%'>		
                        <?php
                        if ( Session::menu_perms("MenuIncidents", "ControlPanelAlarmsDelete") )
                        {
                            $confirm_text =  Util::js_entities(_("Alarms should never be deleted unless they represent a false positive. Would you like to close the alarm instead of deleting it? Click cancel to close, or Accept to continue deleting."));
                            ?>
                            <tr>
                                <td class="nobborder center" style="padding-bottom:5px">
                                    <input type="button" style="width:80%;" onclick="delete_all_alarms('<?php echo $unique_id?>');" value="<?php echo gettext("Delete ALL alarms"); ?>" class="lbutton">
                                </td>
                            </tr>
                            <tr>	
                                <td class="nobborder center" style="padding-bottom:5px">
                                    <input type="button" style="width:80%;" value="<?=_("Delete selected")?>" onclick="if (confirm('<?php echo $confirm_text?>')) bg_delete(); else { document.fchecks.only_close.value='1';document.fchecks.submit(); }" style="width:110px" class="lbutton"/>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                        
						<tr>	
							<td class="nobborder center">
								<input type="button" style="width:80%;" value="<?=_("Close selected")?>" onclick="document.fchecks.only_close.value='1';document.fchecks.submit();" class="lbutton"/>
							</td>
						</tr>

								<?php
							/* OBSOLETE. DO NOT USE FROM ALARM CONSOLE <br><br><a href="" onclick="$('#divadvanced').toggle();return false;"><img src="../pixmaps/plus-small.png" border="0" align="absmiddle"> <?=_("Advanced")?></a>
							<div id="divadvanced" style="display:none"><a href="<?php
				echo $_SERVER["SCRIPT_NAME"] ?>?purge=1&unique_id=<?=$unique_id?>"><?php
				echo gettext("Remove events without an associated alarm"); ?></a></div> */
							?>
							
						<tr>
							<td class="nobborder center">
								<table class="transparent" width="100%">
									<?php
									if (count($tags) < 1) 
									{ 
										?>
										<tr>
											<td class="nobborder"><?php echo _("No tags found.") ?> <a href="tags_edit.php"><?php echo _("Click here to create") ?></a></td>
										</tr>
										<?php 
									} 
									else 
									{ 
										?>
										<tr>
											<td class="nobborder">
												<div style='text-align: center; margin: auto;'>
													<a style='cursor:pointer; font-weight:bold;' class='ndc' onclick="$('#tags_filter').toggle()">
														<img src="../pixmaps/arrow_green.gif" align="absmiddle" border="0"/>&nbsp;<?php echo _("Filter by label") ?>
													</a>
												</div>
											</td>
											<td class="nobborder" nowrap='nowrap'>
												<?php 
												
												$tag_url              = $_SERVER['SCRIPT_NAME']."?";
												$p_tag                = $parameters;
												$p_tag['hide_closed'] = "hide_closed=".$not_hide_closed;
												$p_tag['tag']         = "tag=";
												$tag_url             .=  implode("&", $p_tag);
												
												if ($tag != "") 
												{ 
													?>
												<table class="transparent">
													<tr>
														<td class="nobborder"><?php echo $tags_html[$tag] ?></td>
														<td class="nobborder"><a href="<?php echo $tag_url?>"><?php echo _("Remove filter")?></a></td>
													</tr>
												</table>
													<?php 
												} 
												?>
											</td>
										</tr>
										<tr>
											<td class="nobborder">
												<div style="position:relative">
													<div id="tags_filter" style="display:none;border:0px;position:absolute">
													
													<table cellpadding='0' cellspacing='0' align="center" style="border-radius:0">
														<tr>
															<th style="padding-right:3px;border-radius:0px;border-top:0px;border-right:0px;border-left:0px">
																<div style='float:left; width:60%; text-align: right;padding:3px'><?php echo _("Labels")?></div>
																<div style='float:right; width:18%; padding: 3px; text-align: right;'><a style="cursor:pointer; text-align: right;" onclick="$('#tags_filter').toggle()"><img src="../pixmaps/cross-circle-frame.png" alt="<?php echo _("Close"); ?>" title="<?php echo _("Close"); ?>" border="0" align='absmiddle'/></a></div>
															</th>
														</tr>
														<?php 
																																																			
														foreach ($tags as $tg) 
														{ 
															?>
															<tr>
																<td class="nobborder">
																	<table class="transparent" cellpadding="4">
																		<tr>
																			<?php
																				$tag_url        = $_SERVER['SCRIPT_NAME']."?";
																				$p_tag['tag']   = "tag=".$tg->get_id();
																				$tag_url       .=  implode("&", $p_tag);
																				
																				$style          = "border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;border:0px;";
																				$style         .= "background-color: #".$tg->get_bgcolor().";";
																				$style         .= "color: #".$tg->get_fgcolor().";";
																				$style         .= ( $tg->get_bold() )   ? "font-weight: bold;"  : "font-weight: normal;";
																				$style         .= ( $tg->get_italic() ) ? "font-style: italic;" : "font-style: none;";
																			?>
																			<td onmouseover="set_hand_cursor()" onmouseout="set_pointer_cursor()" onclick="document.location='<?php echo $tag_url?>'" style="<?php echo $style?>"><?php echo $tg->get_name()?></td>
																		</tr>
																	</table>
																</td>
															<td class="nobborder">
															<?php 
															if ( $tag == $tg->get_id() ) 
															{ 
																
																$p_tag['tag']  = "tag=";
																$tag_url      .=  implode("&", $p_tag);
																											
																?>
																<a href="<?php echo $tag_url?>"><img src="../pixmaps/cross-small.png" border="0" alt="<?php echo _("Remove filter") ?>" title="<?php echo _("Remove filter") ?>"/></a>
																<?php 
															} 
															?>
															</td>
														</tr>
															<?php 
														} 
														?>
													</table>
													</div>
												</div>
											</td>
											<td class="nobborder"></td>
										</tr>
										<?php 
									} 
										?>
								</table>
							</td>
						</tr>
					</table>
				</td>
			</tr>

			<tr>
				<td colspan="4" style="padding:5px; background: #F2F2F2" class='noborder'><input type="submit" name='search' class="button" value="<?php echo _("Search") ?>"/></td>
			</tr>
		</table>
	</form>
	<?php
} 
?>

	<br/>
    
	<table width="98%" border='0' cellspacing='1' cellpadding='0' align="center">
		<tr><td colspan="12" id="loading_div" style="border:0px"></td></tr>
		<tr>
			<td colspan="12" style="border-bottom:0px solid white" nowrap='nowrap'>
				<table class="transparent" width="100%">
					<tr>
						<td width="150" class="nobborder">
							<table class="transparent">
								<tr>
									<td class="nobborder" style='padding: 0px 0px 10px 10px;'><input type="button" value="<?=_("Ungrouped")?>" class="buttonon" disabled='disabled'/></td>
									<td class="nobborder" style='padding: 0px 0px 10px 0px;'><input type="button" onclick="document.location.href='alarm_group_console.php?hide_closed=1'" value="<?=_("Grouped")?>" class="button"/></td>
								</tr>
							</table>
						</td>
						
						<td class="nobborder center">
							<?php
							/*
							* Pagination
							*/
							
							$pagination_url = $_SERVER['SCRIPT_NAME']."?";
							$p_pagination   = $parameters;
							
							$p_pagination['autorefresh'] = $norefresh;
							
							//Inf. link
							$p_pagination['inf']  = "inf=".($inf - $ROWS);
							$p_pagination['sup']  = "sup=".($sup - $ROWS);
							$inf_link            .=  $pagination_url.implode("&", $p_pagination);
							
							//Sup. link
							$p_pagination['inf']  = "inf=".($inf + $ROWS);
							$p_pagination['sup']  = "sup=".($sup + $ROWS);
							$sup_link            .=  $pagination_url.implode("&", $p_pagination);
							
							
							//First. link
							$p_pagination['inf']  = "inf=0";
							$p_pagination['sup']  = "sup=".$ROWS;
							$first_link          .=  $pagination_url.implode("&", $p_pagination);
							
							//Last. link
							$p_pagination['inf']  = "inf=".(floor($count/$ROWS)*$ROWS);
							$p_pagination['sup']  = "sup=".((floor($count/$ROWS)*$ROWS) + $ROWS);
							$last_link           .=  $pagination_url.implode("&", $p_pagination);
							
							
							if ($inf >= $ROWS) 
							{
								echo "<a href=\"$first_link\">&lt;&lt;- ";
								printf(gettext("First") , $ROWS);
								echo "</a>";
								echo "&nbsp;<a href=\"$inf_link\">&lt;- ";
								printf(gettext("Prev %d") , $ROWS);
								echo "</a>";
							}

							if ($sup < $count) 
							{
								echo "&nbsp;&nbsp;(";
								printf(gettext("%d-%d of %d") , $inf+1, $sup, $count);
								echo ")&nbsp;&nbsp;";
								echo "<a href=\"$sup_link\">";
								printf(gettext("Next %d") , $ROWS);
								echo " -&gt;</a>";
								echo "&nbsp;<a href=\"$last_link\">";
								printf(gettext("Last") , $ROWS);
								echo " -&gt;&gt;</a>";
							} 
							else 
							{
								echo "&nbsp;&nbsp;(";
								$aux_inf = ( $count == 0 ) ? 0 : $inf+1;
								$aux_inf = ( $aux_inf > $count ) ? $count : $aux_inf;
								printf(gettext("%d-%d of %d") , $aux_inf, $count, $count);
								echo ")&nbsp;&nbsp;";
							}
							?>
						</td>
				
						<td width="200" class="nobborder right">
							<a style='cursor:pointer; font-weight:bold;' class='ndc' onclick="$('#tags').toggle()"><img src="../pixmaps/arrow_green.gif" align="absmiddle" border="0"/>&nbsp;<?php echo _("Apply label to selected alarms") ?></a> 
					   
							<div style="position:relative"> 
								<div id="tags" style="position:absolute;right:0;top:0;display:none">
									<table cellpadding='0' cellspacing='0' align="center" style="border-radius:0">
										<tr>
											<th style="padding-right:3px;border-radius:0px;border-top:0px;border-right:0px;border-left:0px">
												<div style='float:left; width:60%; text-align: right;padding:3px'><?php echo _("Labels")?></div>
												<div style='float:right; width:18%; padding: 3px; text-align: right;'>
													<a style="cursor:pointer; text-align: right;" onclick="$('#tags').toggle()"><img src="../pixmaps/cross-circle-frame.png" alt="<?php echo _("Close"); ?>" title="<?php echo _("Close"); ?>" border="0" align='absmiddle'/></a>
												</div>
											</th>
										</tr>
										<?php 
										if (count($tags) < 1) 
										{ 
											?>
											<tr><td><?php echo _("No tags found.") ?></td></tr>
											<?php 
										} 
										else 
										{ 
											foreach ($tags as $tg) 
											{ 
												?>
												<tr>
													<td class="nobborder">
														<table class="transparent" cellpadding="4">
															<tr>
																<?php
																$style          = "border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;border:0px;";
																$style         .= "background-color: #".$tg->get_bgcolor().";";
																$style         .= "color: #".$tg->get_fgcolor().";";
																$style         .= ( $tg->get_bold() )   ? "font-weight: bold;"  : "font-weight: normal;";
																$style         .= ( $tg->get_italic() ) ? "font-style: italic;" : "font-style: none;";
																?>
																<td onmouseover="set_hand_cursor()" onmouseout="set_pointer_cursor()" onclick="document.fchecks.move_tag.value='<?php echo $tg->get_id() ?>';document.fchecks.submit();" style="<?php echo $style?>"><?php echo $tg->get_name()?></td>
															</tr>
														</table>
													</td>
												</tr>
												<?php 
											} 
											?>
											<tr>
												<td class="nobborder">
													<table class="transparent" cellpadding="2">
														<tr>
															<td class="nobborder"><a href="" onclick="document.fchecks.move_tag.value='0';document.fchecks.submit();return false"><?php echo _("Remove selected") ?></a></td>
														</tr>
													</table>
												</td>
											</tr>
											<?php 
										} 
										?>
									</table>
								</div>
							</div>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<?php
		// Timezone correction
		$tz = Util::get_timezone(); 
		?>
		<tr>
			<td class="nobborder" width="20" align="center"><input type="checkbox" name="allcheck" onclick="checkall()"></td>
			<td style="background-color:#9DD131;font-weight:bold">#</td>
			<td width="25%" style="background-color:#9DD131;font-weight:bold">
				<?php
					$alarm_url        = $_SERVER['SCRIPT_NAME']."?";
					$p_alarm          = $parameters;
					$p_alarm["order"] = "order=".ossim_db::get_order("sid_name", $order);
				?>
				<a href="<?php echo $alarm_url.implode("&", $p_alarm)?>"><?php echo gettext("Alarm"); ?> </a>
			</td>
				
			<td style="background-color:#9DD131;padding-left:3px;padding-right:3px;font-weight:bold">
				<?php $p_alarm["order"] = "order=".ossim_db::get_aorder("risk", str_replace(" DESC","",$order)); ?>
				<a href="<?php echo $alarm_url.implode("&", $p_alarm)?>"><?php echo gettext("Risk"); ?> </a>
			</td>
			
			<td style="background-color:#9DD131;font-weight:bold">
				<?php $p_alarm["order"] = "order=".ossim_db::get_order("sensor", $order); ?>
				<a href="<?php echo $alarm_url.implode("&", $p_alarm)?>"><?php echo gettext("Sensor"); ?> </a>
			</td>
        
			<td style="background-color:#9DD131;font-weight:bold"><?php echo gettext("First event")."<br/>".Util::timezone($tz); ?></td>
			
			<td style="background-color:#9DD131;font-weight:bold">
				<?php $p_alarm["order"] = "order=".ossim_db::get_order("a.timestamp", $order); ?>
				<a href="<?php echo $alarm_url.implode("&", $p_alarm)?>"><?php echo gettext("Last event")."<br>".Util::timezone($tz); ?></a>
			</td>
			
			<td style="background-color:#9DD131;font-weight:bold">
				<?php $p_alarm["order"] = "order=".ossim_db::get_order("a.src_ip", $order); ?>
				<a href="<?php echo $alarm_url.implode("&", $p_alarm)?>"><?php echo gettext("Source"); ?></a>
			</td>
			
			<td style="background-color:#9DD131;font-weight:bold">
				<?php $p_alarm["order"] = "order=".ossim_db::get_order("a.dst_ip", $order); ?>
				<a href="<?php echo $alarm_url.implode("&", $p_alarm)?>"><?php echo gettext("Destination"); ?></a>
			</td>
			
			<!-- <td style="background-color:#9DD131;font-weight:bold"> <?php echo gettext("Proto"); ?> </td> -->
			
			<td style="background-color:#9DD131;font-weight:bold">
				<?php $p_alarm["order"] = "order=".ossim_db::get_order("status", $order); ?>
				<a href="<?php echo $alarm_url.implode("&", $p_alarm)?>"><?php echo gettext("Status"); ?></a>
			</td>
			
			<td style="background-color:#9DD131;font-weight:bold"> <?php echo gettext("Action"); ?> </td>
		</tr>
		
		<form name="fchecks" action="alarms_check_delete.php" method="POST">
			<input type="hidden" name="hide_closed"     value="<?=$hide_closed?>"/>
			<input type="hidden" name="no_resolv"       value="<?=$no_resolv?>"/>
			<input type="hidden" name="only_close"      value=""/>
			<input type="hidden" name="move_tag"        value=""/>
			<input type="hidden" name="tag"             value="<?php echo $tag ?>"/>
			<input type="hidden" name="unique_id"       value="<?=$unique_id?>"/>
			<input type="hidden" name="date_from"       value="<?php echo $date_from ?>"/>
			<input type="hidden" name="date_to"         value="<?php echo $date_to ?>"/>
			<input type="hidden" name="order"           value="<?=$order?>"/>
			<input type="hidden" name="query"           value="<?=$query?>"/>
			<input type="hidden" name="autorefresh"     value="<?=$norefresh?>"/>
			<input type="hidden" name="directive_id"    value="<?=$directive_id?>"/>
			<input type="hidden" name="src_ip"          value="<?=$src_ip?>">
			<input type="hidden" name="dst_ip"          value="<?=$dst_ip?>"/>
			<input type="hidden" name="inf"             value="<?=$inf?>"/>
			<input type="hidden" name="sup"             value="<?=$sup?>"/>
			<input type="hidden" name="num_alarms_page" value="<?=$num_alarms_page?>"/>
			<input type="hidden" name="sensor_query"    value="<?=$sensor_query?>"/>
			<input type="hidden" name="num_events"      value="<?=$num_events?>"/>
			<input type="hidden" name="num_events_op"   value="<?=$num_events_op?>"/>
			<input type="hidden" name="ds_id"           value="<?=$ds_id?>"/>
			<input type="hidden" name="ds_name"         value="<?=$ds_name?>"/>
	  

		<?php
		$sound = 0;
		$time_start = time();
		if ($count > 0) 
		{
			$datemark = "";
			
			foreach($alarm_list as $alarm) 
			{
				/* hide closed alarmas */
				if (($alarm->get_status() == "closed") and ($hide_closed == 1)) 
					continue;
					
				$id         = $alarm->get_plugin_id();
				$sid        = $alarm->get_plugin_sid();
				$backlog_id = $alarm->get_backlog_id();
				$id_tag     = $alarm->get_id_tag();
				$csimilar   = $alarm->get_csimilar();
				$similar    = $alarm->get_similar();
				$sid_name   = $alarm->get_sid_name(); // Plugin_sid table just joined (Granada 27 mayo 2009)
				$alarm_name = Util::translate_alarm($conn, $sid_name, $alarm);
				$alarm_name_orig = $alarm_name;
				
				
				$src_ip = $alarm->get_src_ip();
				$dst_ip = $alarm->get_dst_ip();
				$src_port = $alarm->get_src_port();
				$dst_port = $alarm->get_dst_port();
				
				$src_port = Port::port2service($conn, $src_port);
				$dst_port = Port::port2service($conn, $dst_port);
				$sensors = $alarm->get_sensors();
				$risk = $alarm->get_risk();
				
        
				if ($plugin_id!="" && $plugin_sid!="") 
					$csimilar=0;  //Change similar when we search by data source
        
        
        
				$event_count_label = "";
				if ($backlog_id) 
				{
					$aid               = $alarm->get_event_id();
					#$summary = Alarm::get_total_events($conn, $backlog_id);
					#$event_count_label = $summary["total_count"] . " "._("events");
					$event_count       = Alarm::get_total_events($conn, $backlog_id);
					$event_count_label = $event_count." "._("events");
				}
		
				$date = Util::timestamp2date($alarm->get_timestamp());
				$timestamp_utc = Util::get_utc_unixtime($conn,$date);
				$sound = ($beep && $refresh_time_secs>0 && (gmdate("U")-$timestamp_utc<=$refresh_time_secs)) ? true : false;
				$date = gmdate("Y-m-d H:i:s",$timestamp_utc+(3600*$tz));
        
				if ($backlog_id && $id==1505 && $event_count > 0) 
				{
					$since = Util::timestamp2date($alarm->get_since());
					$since = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($conn,$since)+(3600*$tz));            
				}
				else 
					$since = $date;
        
				/* show alarms by days */
				$date_slices              = split(" ", $date);
				list($year, $month, $day) = split("-", $date_slices[0]);
				$date_unformated          = $year.$month.$day;
				$date_formatted           = Util::htmlentities(strftime("%A %d-%b-%Y", mktime(0, 0, 0, $month, $day, $year)));
				
				if ($datemark != $date_slices[0])
				{
					if ( Session::menu_perms("MenuIncidents", "ControlPanelAlarmsDelete") ){
                        $link_delete = " [<a href='' onclick='ondelete_day(\"delete_day=".$alarm->get_timestamp()."&inf=".($sup - $ROWS)."&sup=$sup&hide_closed=$hide_closed&unique_id=$unique_id\")' style='font-weight:bold' >".gettext("Delete")."</a>]";
					}
					
                    echo "
						<tr>
							<td style='border:0px;background-color:#d6dfeb'><input type='checkbox' onclick=\"checkall_date('".$date_unformated."')\"/></td>
							<td colspan='11' style='padding:5px;border-bottom:0px solid white;background-color:#B5C7DF'><strong>$date_formatted</strong>$link_delete<br/></td>         
						</tr>";
				}
		
				$datemark = $date_slices[0];
				?>
				<tr>
				
					<td class="nobborder">
						<input style="border:none" type="checkbox" name="check_<?php echo $backlog_id ?>_<?php echo $alarm->get_event_id() ?>" id="check_<?php echo $backlog_id ?>" class="alarm_check" datecheck="<?php echo $date_unformated ?>" value="1"/>
					</td>
					
					<td class="nobborder" nowrap='nowrap' id="plus<?php echo $inf + 1 ?>">
						<?php 
							if ($backlog_id && $id==1505 && $event_count > 0) 
							{ 
								?>
								<a href="" onclick="show_alarm('<?php echo $backlog_id ?>','<?php echo $inf + 1 ?>');return false;"><img align='absmiddle' src='../pixmaps/plus-small.png' border='0'/></a><?php echo ++$inf ?>
								<?php
							}
							else 
							{ 
								?>
								<img align='absmiddle' src='../pixmaps/plus-small-gray.png' border='0'><span style="color:gray"><?php echo ++$inf ?></span>
								<?php
							} 
						?>
					</td>
					
					<td class="nobborder" style="padding-left:5px">
						<?php
							
						if ($backlog_id && $id==1505) 
						{
							$events_link = "events.php?backlog_id=$backlog_id";
							if ($event_count > 0) 
								$alarm_name = "<a href='' class='trlnka' id='$id;$sid' onclick=\"show_alarm('" . $backlog_id . "','" . ($inf) . "');return false;\">$alarm_name</a>";
							else
								$alarm_name = "<a href='' class='trlnka' id='$id;$sid'  onclick='return false;'>$alarm_name</a>";
							
						} 
						else 
						{
							$events_link = $_SERVER["SCRIPT_NAME"];
							/*$alarm_link = Util::get_acid_pair_link($date, $alarm->get_src_ip() , $alarm->get_dst_ip());*/
							$alarm_link = Util::get_acid_single_event_link ($alarm->get_snort_sid(), $alarm->get_snort_cid());
							if ($show_all>=1)
								$alarm_name = "<a href='" . $alarm_link ."' class='trlnka' id='$id;$sid'>$alarm_name</a>";
							else
								$alarm_name = "<a href='" . $alarm_link . "&minimal_view=1&noback=1' class='greybox2 trlnka' id='$id;$sid'>$alarm_name</a>";
						}
						?>
					
						<table class="transparent">
							<tr>
							<?php if ($tags_html[$id_tag] != "") 
							{ 
								?>
								<td class="nobborder"><?php echo $tags_html[$id_tag]; ?></td>
								<?php 
							} 
							?>
								<td class="nobborder"><b><?php echo $alarm_name; ?></b>
									<span style='color: #AAAAAA;font-size:9px;'>
									<?php
									
									if ($backlog_id && $id==1505 && $event_count > 0)
										echo "<br />[" . $event_count_label;
									elseif($csimilar>1) 
										echo "<br />[";
																
									if($csimilar>1) 
									{
										echo ",&nbsp;". _("similar alarms: ");
										echo "<a href='javascript:;' class='alarminfo' style='text-decoration:none' similar='$similar'>".$csimilar."</a>";
									}

									if (($backlog_id && $id==1505 && $event_count > 0) || $csimilar>1) echo "]";
									?>
									</span>
								</td>
							</tr>
						</table>
					</td>
				
					<!-- risk -->
					<?php
				
					if ($risk > 7) 
					{
						echo "
						<td class='nobborder' style='text-align:center;background-color:red'>
							<strong><span style='color:white'>$risk</span></strong>
						</td>";
					} 
					elseif ($risk > 4) 
					{
						echo "
						<td class='nobborder' style='text-align:center;background-color:orange'>
							<strong><span style='color:black'>$risk</span></strong>
						</td>";
					} 
					elseif ($risk > 2)
					{
						echo "
						<td class='nobborder' style='text-align:center;background-color:green'>
							<strong><span style='color=:white'>$risk</span></strong>
						</td>";
					} 
					else 
					{
						echo "<td class='nobborder' style='text-align:center'>$risk</td>";
					}
					?>
					<!-- end risk -->


					<!-- sensor -->
					<td class="nobborder" style="text-align:center">
						<?php
						foreach($sensors as $sensor) 
						{
							?>
							<a href="../sensor/sensor_plugins.php?hmenu=Plugins&smenu=Plugins&sensor=<?php echo $sensor ?>"><?php echo ($no_resolv) ? $sensor : Host::ip2hostname($conn, $sensor) ?></a>
							<?php
						}
						if ( !count($sensors) ) 
							echo "&nbsp;";
						
						?>
					</td>
					<!-- end sensor -->


					<td style="padding-left:3px;padding-right:3px" class="center nobborder">
						<?php
						$acid_link = Util::get_acid_events_link($since, $date, "time_a");
						echo "<a href=\"$acid_link\"><span style='color:black'>$since</span></a>";
						?>
					</td>
					
					<td style="padding-left:3px;padding-right:3px" class="center nobborder">
						<?php
						$acid_link = Util::get_acid_events_link($since, $date, "time_d");
						echo "<a href=\"$acid_link\"><span style='color:black'>$date</span></a>";
						?>
					</td>
				
					<?php
							$src_link         = "../forensics/base_stat_ipaddr.php?clear_allcriteria=1&ip=$src_ip&hmenu=Forensics&smenu=Forensics";
							$dst_link         = "../forensics/base_stat_ipaddr.php?clear_allcriteria=1&ip=$dst_ip&hmenu=Forensics&smenu=Forensics";
							$src_name         = ($no_resolv) ? $src_ip : Host::ip2hostname($conn, $src_ip);
							$dst_name         = ($no_resolv) ? $dst_ip : Host::ip2hostname($conn, $dst_ip);
							$src_img          = Host_os::get_os_pixmap($conn, $src_ip);
							$dst_img 		  = Host_os::get_os_pixmap($conn, $dst_ip);
							$src_country 	  = strtolower($GeoLoc->get_country_code_by_addr($conn, $src_ip));
							$src_country_name = $GeoLoc->get_country_name_by_addr($conn, $src_ip);
							$src_country_img  = "<img src=\"/ossim/pixmaps/flags/" . $src_country . ".png\" title=\"" . $src_country_name . "\">";
							$dst_country      = strtolower($GeoLoc->get_country_code_by_addr($conn, $dst_ip));
							$dst_country_name = $GeoLoc->get_country_name_by_addr($conn, $dst_ip);
							$dst_country_img  = "<img src=\"/ossim/pixmaps/flags/" . $dst_country . ".png\" title=\"" . $dst_country_name . "\">";
							// Reputation info
							$event_info       = Alarm::get_event($conn,$alarm->get_event_id());
							$rep_src_icon     = Reputation::getrepimg($event_info["rep_prio_src"],$event_info["rep_rel_src"],$event_info["rep_act_src"]);
							$rep_src_bgcolor  = Reputation::getrepbgcolor($event_info["rep_prio_src"]);
							$rep_dst_icon     = Reputation::getrepimg($event_info["rep_prio_dst"],$event_info["rep_rel_dst"],$event_info["rep_act_dst"]);
							$rep_dst_bgcolor  = Reputation::getrepbgcolor($event_info["rep_prio_dst"]);
					?>
					
					<!-- src & dst hosts -->
					<td nowrap='nowrap' style="<?php echo $rep_src_bgcolor?>text-align:center;padding-left:3px;padding-right:3px" class="nobborder">
						<div id="<?php echo $src_ip; ?>;<?php echo $src_name; ?>" id2="<?php echo $src_ip; ?>;<?php echo $dst_ip; ?>" class="HostReportMenu">
							<?php
							echo $rep_src_icon;
							$homelan = (($match_cidr = Net::is_ip_in_cache_cidr($conn, $src_ip)) || in_array($src_ip, $hosts_ips)) ? " <a href='javascript:;' class='scriptinfo' style='text-decoration:none' ip='$src_ip'><img src=\"".Host::get_homelan_icon($src_ip,$icons,$match_cidr,$conn)."\" border=0></a>" : "";
							if ($src_country) 
								echo "<a href=\"$src_link\" alt=\"$src_ip\" title=\"$src_ip\">$src_name</a>:$src_port $src_img $src_country_img $homelan";
							else 
								echo "<a href=\"$src_link\" alt=\"$src_ip\" title=\"$src_ip\">$src_name</a>:$src_port $src_img $homelan";
							?>
						</div>
					</td>
					
					<td nowrap='nowrap'style=" <?php echo $rep_dst_bgcolor?>text-align:center;padding-left:3px;padding-right:3px" class="nobborder">
						<div id="<?php echo $dst_ip; ?>;<?php echo $dst_name; ?>"  id2="<?php echo $dst_ip; ?>;<?php echo $src_ip; ?>" class="HostReportMenu">
							<?php
							echo $rep_dst_icon;
							$homelan = (($match_cidr = Net::is_ip_in_cache_cidr($conn, $dst_ip)) || in_array($dst_ip, $hosts_ips)) ? " <a href='javascript:;' class='scriptinfo' style='text-decoration:none' ip='$dst_ip'><img src=\"".Host::get_homelan_icon($dst_ip,$icons,$match_cidr,$conn)."\" border=0></a>" : "";
							if ($dst_country) 
								echo "<a href=\"$dst_link\" alt=\"$dst_ip\" title=\"$dst_ip\">$dst_name</a>:$dst_port $dst_img $dst_country_img $homelan";
							 else 
								echo "<a href=\"$dst_link\" alt=\"$dst_ip\" title=\"$dst_ip\">$dst_name</a>:$dst_port $dst_img $homelan";
							?>
						</div>
					</td>
					<!-- end src & dst hosts -->
				
					<!--
					<td nowrap='nowrap' style="text-align:center;padding-left:3px;padding-right:3px" class="nobborder">
						<?php
							$proto = strtoupper(getprotobynumber($alarm->get_protocol()));
							echo "<span>$proto</span>";
						?>
					</td>
					-->
									
					<td nowrap='nowrap' bgcolor="<?php echo ($alarm->get_status() == "open") ? "#ECE1DC" : "#DEEBDB" ?>" style="text-align:center;color:#4C7F41;border:1px solid <?php echo ($alarm->get_status() == "open") ? "#E6D8D2" : "#D6E6D2" ?>">
						<?php
							$event_id = $alarm->get_event_id();
							if ( ($status = $alarm->get_status()) == "open" ) 
								echo "<span style='color:#923E3A'><strong>" . (($status != "") ? gettext($status) : "&nbsp;") . "</strong></span>";
							else 
								echo "<a title='" . gettext("Click here to open alarm") . " #$event_id' " . "href=\"" . $_SERVER['SCRIPT_NAME'] . "?open=$event_id" . "&sup=" . "$sup" . "&inf=" . ($sup - $ROWS) . "&hide_closed=$hide_closed&query=".urlencode($query)."&unique_id=$unique_id\"" . " style='color:#4C7F41'><strong>" . (($status != "") ? gettext($status) : "&nbsp;") . "</strong></a>";
							
						?>
					</td>

					<td nowrap='nowrap' class="nobborder" style='text-align:center'>
						<?php
							if ( ($status = $alarm->get_status()) == "open" ) 
								echo "<a title='" . gettext("Click here to close alarm") . " #$event_id' " . "href=\"" . $_SERVER['SCRIPT_NAME'] . "?close=$event_id" . "&sup=" . "$sup" . "&inf=" . ($sup - $ROWS) . "&hide_closed=$hide_closed&query=".urlencode($query)."&unique_id=$unique_id\"" . " onclick=\"if (!confirm('". Util::js_entities(_("Are you sure to close this alarm"))."?')) return false;\" style='color:#923E3A'><img src='../pixmaps/cross-circle-frame.png' border='0' alt='"._("Close alarm")."' title='"._("Close alarm")."'></a>";
							else 
								echo "<img src='../pixmaps/cross-circle-frame-gray.png' border='0' alt='"._("Alarm closed")."' title='"._("Alarm closed")."'>";
							
						
						// Calculate the right alarm_id
						// $tmp_aid: Alarm ID
						// $tmp_agid: Alarm Group ID

						$tmp_bid = $alarm->get_backlog_id();
						$tmp_eid = $alarm->get_event_id();
						
						//New ticket
                        if ( Session::menu_perms("MenuIncidents", "IncidentsOpen") )
						{
                            $alarm_name_orig = str_replace("<", "[", $alarm_name_orig);
                            $alarm_name_orig = str_replace(">", "]", $alarm_name_orig);
					
                            $new_ticket_url  = "../incidents/newincident.php?nohmenu=1";
                            $new_ticket_url .= "&ref=Alarm&title=".urlencode($alarm_name_orig)."&priority=$risk&"."src_ips=$src_ip&event_start=$since&event_end=$date&src_ports=$src_port&dst_ips=$dst_ip&dst_ports=$dst_port&backlog_id=$tmp_bid&event_id=$tmp_eid&alarm_gid=$tmp_agid";
						
                            ?>
						
                            <a class="greybox2" title="<?php echo _("New ticket for Alert ID")." ".$aid?>" href="<?php echo $new_ticket_url?>">
                                <img src="../pixmaps/script--pencil.png" alt="<?php echo _("New ticket for Alert ID")?>" title="<?php echo _("New ticket for Alert ID")?>" border="0"/>
                            </a> 
                            <?php
                        }
                        else
                        {
                            ?>                            
                            <span class='disabled'><img src="../pixmaps/script--pencil-gray.png" alt="<?php echo _("New ticket for Alert ID")?>" title="<?php echo _("New ticket for Alert ID")?>" border="0"/></span>
                            <?php
                        }
                        ?>
                    </td>
				</tr>

											
				<?php if ($sound) { ?>
					<audio controls="controls" style="display:none" autoplay="autoplay">
					<source src="../sounds/alarm.wav" type="audio/mpeg" />
					<embed style="display:none" src="../sounds/alarm.wav" />
					</audio>
				<?php } ?>

			  
				<tr>
					<td colspan='12' id="tr<?php echo $inf ?>"></td>
				</tr>
				<?php
			} 
	/* foreach alarm_list */
	?>
	</form>

	<tr>
		<td colspan="12" style="padding:10px;border-bottom:1px solid white">
		<?php
					
			if ($backup_inf >= $ROWS) 
			{
				echo "<a href=\"$first_link\">&lt;&lt;- ";
				printf(gettext("First") , $ROWS);
				echo "</a>";
				echo "&nbsp;<a href=\"$inf_link\">&lt;- ";
				printf(gettext("Prev %d") , $ROWS);
				echo "</a>";
			}

			if ($sup < $count) 
			{
				echo "&nbsp;&nbsp;(";
				printf(gettext("%d-%d of %d") , $backup_inf+1, $sup, $count);
				echo ")&nbsp;&nbsp;";
				echo "<a href=\"$sup_link\">";
				printf(gettext("Next %d") , $ROWS);
				echo " -&gt;</a>";
				echo "&nbsp;<a href=\"$last_link\">";
				printf(gettext("Last") , $ROWS);
				echo " -&gt;&gt;</a>";
			} 
			else 
			{
				echo "&nbsp;&nbsp;(";
				$aux_inf = ( $count == 0 ) ? 0 : $backup_inf+1;
				$aux_inf = ( $aux_inf > $count ) ? $count : $aux_inf;
							
				printf(gettext("%d-%d of %d") , $aux_inf, $count, $count);
				echo ")&nbsp;&nbsp;";
			}
			
			
			
		?>
		</td>
	</tr>
	<?php
	} 
	else
	{
		?>
		<tr><td colspan='12' style='height: 100px; font-weight: bold;' class='noborder center'><?php echo _("No alarms found")?></td></tr>	
		<?php
	}
	/* if alarm_list */
	
	?>
		<tr>
			<td class="nobborder" colspan="12">
				<div style='padding: 5px 0px 10px 10px;'>
                    <?php
                    if ( Session::menu_perms("MenuIncidents", "ControlPanelAlarmsDelete") )
                    {
                        $confirm_text =  Util::js_entities(_("Alarms should never be deleted unless they represent a false positive. Would you like to close the alarm instead of deleting it? Click cancel to close, or Accept to continue deleting."));
                        ?>
                        <input type="button" onclick="delete_all_alarms('<?php echo $unique_id?>');" value="<?php echo gettext("Delete ALL alarms"); ?>" class="button"/>&nbsp;
                        <input type="button" value="<?=_("Delete selected")?>" onclick="if (confirm('<?=$confirm_text?>')) bg_delete(); else { document.fchecks.only_close.value='1';document.fchecks.submit(); }" class="button"/>&nbsp;
                        <?php
                    }
                    ?>
					<input type="button" value="<?=_("Close selected")?>" onclick="document.fchecks.only_close.value='1';document.fchecks.submit();" class="button"/>
				</div>
			</td>
		</tr>
    </table>

	<table width="98%" border='0' cellspacing='1' cellpadding='0' align="center" class="noborder" style="background:transparent;">
		<tr>
			<td class="nobborder" align="left" style='padding: 10px 0px;'>
				<?php
				$time_load = time() - $time_start;

				echo "[ " . gettext("Page loaded in") . " $time_load " . gettext("seconds") . " ]";
				$db->close($conn);
				$GeoLoc->close();
				?>
			</td>
		
			<script type='text/javascript'>
				// GreyBox
				$(document).ready(function(){
					GB_TYPE = 'w';
					$("a.greybox2").click(function(){
						var t = this.title || $(this).text() || this.href;
						GB_show(t,this.href,490,'90%');
						return false;
					});
					
					load_contextmenu();
					
					$(".repinfo").simpletip({
						position: 'left',
						baseClass: 'idmtip',
						onBeforeShow: function() { 
							this.update(this.getParent().attr('txt'));
						}
					});

					$(".scriptinfo").simpletip({
						position: 'right',
						onBeforeShow: function() { 
							var ip = this.getParent().attr('ip');
							this.load('alarm_netlookup.php?ip=' + ip);
						}
					});
										
					$(".alarminfo").simpletip({
						position: 'right',
						onBeforeShow: function() {
							this.load('alarm_info.php?similar=' + this.getParent().attr('similar'));
						}
					});
					
					var hosts = [<?=preg_replace("/\,$/","",$hosts_str)?>];
					
					$("#src_ip").autocomplete(hosts, {
						minChars: 0,
						width: 225,
						matchContains: "word",
						autoFill: false,
						formatItem: function(row, i, max) {
							return row.txt;
						}
					}).result(function(event, item) {
						$("#src_ip").val(item.id);
					});
					
					$("#dst_ip").autocomplete(hosts, {
						minChars: 0,
						width: 225,
						matchContains: "word",
						autoFill: false,
						formatItem: function(row, i, max) {
							return row.txt;
						}
					}).result(function(event, item) {
						$("#dst_ip").val(item.id);
					});
					
					$("#ds_name").autocomplete('search_ds.php', {
						minChars: 0,
						width: 300,
						matchContains: "word",
						multiple: false,
						autoFill: false,
						formatItem: function(row, i, max, value) {
							return (value.split('###'))[1];
						},
						formatResult: function(data, value) {
							return (value.split('###'))[1];
						}
					}).result(function(event, item) {
						$("#ds_id").val((item[0].split('###'))[0]);
					});
					
					<?php if (GET('src_ip') != "" || GET('dst_ip') != "" || $date_from != "" || $query != "" || $sensor_query != "" || $directive_id != "" || $num_events > 0) { ?>
					tooglebtn();
					<?php } ?>
				});
			</script>
		</tr>
	</table>
</body>
</html>