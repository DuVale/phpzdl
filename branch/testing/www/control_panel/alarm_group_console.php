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
include ("classes/AlarmGroups.inc");

require_once 'classes/Session.inc';
require_once 'classes/Security.inc';
require_once 'classes/Util.inc';
require_once ('classes/Tags.inc');

Session::logcheck("MenuIncidents", "ControlPanelAlarms");
$unique_id                    = uniqid("alrm_");
$prev_unique_id               = $_SESSION['alarms_unique_id'];
$_SESSION['alarms_unique_id'] = $unique_id;

function build_url($action, $extra) {
	global $date_from, $date_to, $show_options, $src_ip, $dst_ip, $num_alarms_page, $hide_closed, $autorefresh, $refresh_time, $inf, $sup;
	
	if (empty($action)) {
		$action = "none";
	}
	
	$options = "";
	
	if (!empty($date_from)) {
		$options = $options . "&date_from=" . $date_from;
	}
	
	if (!empty($date_to))           $options = $options . "&date_to=" . $date_to;
	if (!empty($show_options))      $options = $options . "&show_options=" . $show_options;
	if (!empty($autorefresh))       $options = $options . "&autorefresh=1";
	if (!empty($refresh_time))      $options = $options . "&refresh_time=" . $refresh_time;
	if (!empty($src_ip))            $options = $options . "&src_ip=" . $src_ip;
	if (!empty($dst_ip))            $options = $options . "&dsp_ip=" . $dsp_ip;
	if (!empty($num_alarms_page))   $options = $options . "&num_alarms_page=" . $num_alarms_page;
	if (!empty($hide_closed))       $options = $options . "&hide_closed=on";
	
	if ($action != "change_page") 
	{
		if (!empty($inf)) $options = $options . "&inf=" . $inf;
		if (!empty($sup)) $options = $options . "&sup=" . $sup;
	}
	
	$url = $_SERVER["SCRIPT_NAME"] . "?action=" . $action . $extra . $options . "&bypassexpirationupdate=1&group_type=".POST('group_type');
	
	return $url;
}

/*
echo "<pre>";
	print_r($_POST);
echo "</pre>";
*/

require_once ('ossim_db.inc');
$db   = new ossim_db();
$conn = $db->connect();

/* GET VARIABLES FROM URL */
//$ROWS = 100;
$ROWS = 10;
$db   = new ossim_db();
$conn = $db->connect();
list($sensors, $hosts, $icons) = Host::get_ips_and_hostname($conn,true);

$delete 		    = POST('delete');
$delete_group 	    = POST('delete_group');
$close 			    = POST('close');
$delete_day 	    = POST('delete_day');
$order 			    = POST('order');
$src_ip			    = POST('src_ip');
$dst_ip 		    = POST('dst_ip');
$backup_inf 	    = $inf = POST('inf');
$sup 			    = POST('sup');
$hide_closed 	    = (POST('hide_closed')!="" || POST('unique_id')!="") ? POST('hide_closed') : GET('hide_closed');
$hide_closed 	    = ( $hide_closed == "1" ) ? 1 : 0;
$date_from 			= POST('date_from');
$date_to 			= POST('date_to');
$num_alarms_page 	= POST('num_alarms_page');
$disp 				= POST('disp');  // Telefonica disponibilidad hack
$group 				= POST('group'); // Alarm group for change descr
$new_descr 			= POST('descr');
$action 			= POST('action');
$show_options 		= POST('show_options');

$autorefresh        = "";
$refresh_time       = "";

if ( isset($_POST['search']) )
{
    unset($_SESSION['_grouped_alarm_autorefresh']);
    if ( isset($_POST['autorefresh']) )
    {
        $autorefresh  = ( POST('autorefresh') != '1' ) ? 0 : 1;
        $refresh_time = POST('refresh_time');
        $_SESSION['_grouped_alarm_autorefresh'] = $refresh_time;
    }
}
else
{
    if ( $_SESSION['_grouped_alarm_autorefresh'] != '' )
    {
        $autorefresh  = 1;
        $refresh_time = $_SESSION['_grouped_alarm_autorefresh'];
    }
}


$alarm 				= POST('alarm');
$param_unique_id 	= POST('unique_id');
$group_type 		= POST('group_type') ? POST('group_type') : "name";
$query 				= (POST('query') != "") ? POST('query') : "";
$directive_id 		= POST('directive_id');
$sensor_query 		= POST('sensor_query');
$num_events 		= POST('num_events');
$num_events_op 		= POST('num_events_op');
$no_resolv 			= intval(POST('no_resolv'));
$tag 				= POST('tag');

ossim_valid($param_unique_id, OSS_ALPHA, OSS_SCORE, OSS_NULLABLE,               'illegal:' . _("Unique id"));
ossim_valid($disp, OSS_DIGIT, OSS_NULLABLE, 						             'illegal:' . _("Disp"));
ossim_valid($order, OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE,               'illegal:' . _("Order"));
ossim_valid($delete, OSS_DIGIT, OSS_NULLABLE, 						             'illegal:' . _("Delete"));
ossim_valid($delete_group, OSS_DIGIT, OSS_NULLABLE, 				             'illegal:' . _("Delete group"));
ossim_valid($close, OSS_DIGIT, OSS_NULLABLE, 						             'illegal:' . _("Close"));
ossim_valid($open, OSS_DIGIT, OSS_NULLABLE, 						             'illegal:' . _("Open"));
ossim_valid($delete_day, OSS_ALPHA, OSS_NULLABLE,					             'illegal:' . _("Delete_day"));
ossim_valid($src_ip, OSS_IP_ADDRCIDR, OSS_NULLABLE, 				             'illegal:' . _("Src_ip"));
ossim_valid($dst_ip, OSS_IP_ADDRCIDR, OSS_NULLABLE, 				             'illegal:' . _("Dst_ip"));
ossim_valid($inf, OSS_DIGIT, OSS_NULLABLE,						                 'illegal:' . _("Inf"));
ossim_valid($sup, OSS_DIGIT, OSS_NULLABLE, 							             'illegal:' . _("Order"));
ossim_valid($hide_closed, OSS_DIGIT, OSS_NULLABLE, 					             'illegal:' . _("Hide_closed"));
ossim_valid($autorefresh, OSS_DIGIT, OSS_NULLABLE, 					             'illegal:' . _("Autorefresh"));
ossim_valid($date_from, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 		             'illegal:' . _("From date"));
ossim_valid($date_to, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 			             'illegal:' . _("To date"));
ossim_valid($num_alarms_page, OSS_DIGIT, OSS_NULLABLE, 				             'illegal:' . _("Field number of alarms per page"));
ossim_valid($new_descr, OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, OSS_PUNC, 'illegal:' . _("Descr"));
ossim_valid($show_options, OSS_DIGIT, OSS_NULLABLE, 							 'illegal:' . _("Show_options"));
ossim_valid($refresh_time, OSS_DIGIT, OSS_NULLABLE, 							 'illegal:' . _("Refresh_time"));
ossim_valid($alarm, OSS_DIGIT, OSS_PUNC, OSS_NULLABLE,							 'illegal:' . _("Alarm"));
ossim_valid($sensor_query, OSS_IP_ADDR, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 		 'illegal:' . _("Sensor_query"));
ossim_valid($query, OSS_ALPHA, OSS_PUNC_EXT, OSS_SPACE, OSS_NULLABLE, 			 'illegal:' . _("Query"));
ossim_valid($directive_id, OSS_DIGIT, OSS_NULLABLE, 							 'illegal:' . _("Directive_id"));
ossim_valid($num_events, OSS_DIGIT, OSS_NULLABLE, 								 'illegal:' . _("Num_events"));
ossim_valid($num_events_op, OSS_ALPHA, OSS_NULLABLE, 							 'illegal:' . _("Num_events_op"));
ossim_valid($no_resolv, OSS_DIGIT, OSS_NULLABLE, 								 'illegal:' . _("No_resolv"));
ossim_valid($tag, OSS_DIGIT, OSS_NULLABLE, 										 'illegal:' . _("Tag"));
ossim_valid($action, OSS_ALPHA, OSS_NULLABLE, OSS_PUNC,                          'illegal:' . _("Action"));

if (ossim_error()) {
    die(ossim_error());
}

$tags      = Tags::get_list($conn);
$tags_html = Tags::get_list_html($conn);

if (empty($order)) 
	$order = " timestamp DESC";
	
if ((!empty($src_ip)) && (!empty($dst_ip))) 
    $where = "WHERE inet_ntoa(src_ip) = '$src_ip' OR inet_ntoa(dst_ip) = '$dst_ip'";
elseif (!empty($src_ip)) 
    $where = "WHERE inet_ntoa(src_ip) = '$src_ip'";
elseif (!empty($dst_ip)) 
    $where = "WHERE inet_ntoa(dst_ip) = '$dst_ip'";
else 
    $where = '';

if ( $num_alarms_page )
    $ROWS = $num_alarms_page;

if (empty($inf) || $inf < 1) 
	$inf = 0;
	
if (!$sup) 
	$sup = $ROWS;
	
	
if (empty($show_options) || ($show_options < 1 || $show_options > 4)) {
    $show_options = 1;
}
if (empty($refresh_time) || ($refresh_time != 30000 && $refresh_time != 60000 && $refresh_time != 180000 && $refresh_time != 600000)) {
    $refresh_time = 60000;
}
//Options
$selected1 = $selected2 = $selected3 = $selected4 = "";
if ($show_options == 1) $selected1 = 'selected="selected"';
if ($show_options == 2) $selected2 = 'selected="selected"';
if ($show_options == 3) $selected3 = 'selected="selected"';
if ($show_options == 4) $selected4 = 'selected="selected"';

$hide_check = ( $hide_closed ) ? 'checked="checked"' : "";

$refresh_sel1 = $refresh_sel2 = $refresh_sel3 = $refresh_sel4 = "";
if ($refresh_time == 30000)  $refresh_sel1 = 'selected="selected"';
if ($refresh_time == 60000)  $refresh_sel2 = 'selected="selected"';
if ($refresh_time == 180000) $refresh_sel3 = 'selected="selected"';
if ($refresh_time == 600000) $refresh_sel4 = 'selected="selected"';

if (POST('take') != "") {
	if (!ossim_valid(POST('take'), OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SQL, 'illegal:' . _("take"))) exit;
	if (check_uniqueid($prev_unique_id,$param_unique_id)) AlarmGroups::take_group ($conn, POST('take'), $_SESSION["_user"]);
	else die(ossim_error("Can't do this action for security reasons."));
}

if (POST('release') != "") {
	if (!ossim_valid(POST('release'), OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SQL, 'illegal:' . _("release"))) exit;
	if (check_uniqueid($prev_unique_id,$param_unique_id)) AlarmGroups::release_group ($conn, POST('release'));
	else die(ossim_error("Can't do this action for security reasons."));
}

if ( $group != "" ) {
	if (!ossim_valid($new_descr,  OSS_NULLABLE, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SQL, 'illegal:' . _("descr"))) exit;
	if (!ossim_valid($group, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SQL, 'illegal:' . _("group"))) exit;
	AlarmGroups::change_descr ($conn, $new_descr, $group);
}

if (POST('close_group') != "") {
	if (!ossim_valid(POST('close_group'), OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SQL, 'illegal:' . _("close_group"))) exit;
	$group_ids = split(',', POST('close_group'));
    if (check_uniqueid($prev_unique_id,$param_unique_id)) {
	foreach($group_ids as $group_id) AlarmGroups::change_status ($conn, $group_id, "closed");
    } else die(ossim_error("Can't do this action for security reasons."));
}

if (POST('open_group') != "") {
	if (!ossim_valid(POST('open_group'), OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SQL, 'illegal:' . _("open_group"))) exit;
	if (check_uniqueid($prev_unique_id,$param_unique_id)) AlarmGroups::change_status ($conn, POST('open_group'), "open");
	else die(ossim_error("Can't do this action for security reasons."));
}

if (POST('delete_group') != "") {
	if (!ossim_valid(POST('delete_group'), OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SQL, 'illegal:' . _("delete_group"))) exit;
	$group_ids = split(',', POST('delete_group'));
    if (check_uniqueid($prev_unique_id,$param_unique_id)) {
	foreach($group_ids as $group_id) AlarmGroups::delete_group ($conn, $group_id, $_SESSION["_user"]);
    } else die(ossim_error("Can't do this action for security reasons."));
}

if (POST('action') == "open_alarm") {
	if (check_uniqueid($prev_unique_id,$param_unique_id)) Alarm::open($conn, POST('alarm'));
	else die(ossim_error("Can't do this action for security reasons."));
}

if (POST('action') == "close_alarm") {
    if (check_uniqueid($prev_unique_id,$param_unique_id)) Alarm::close($conn, POST('alarm'));
    else die(ossim_error("Can't do this action for security reasons."));
}

if (POST('action') == "delete_alarm") {
    if (check_uniqueid($prev_unique_id,$param_unique_id)) Alarm::delete($conn, POST('alarm'));
    else die(ossim_error("Can't do this action for security reasons."));
}

//Autocompleted
foreach ($sensors as $s_ip=>$s_name) {
	if ($s_name!=$s_ip) $sensors_str .= '{ txt:"'.$s_ip.' ['.$s_name.']", id: "'.$s_ip.'" },';
    else $sensors_str .= '{ txt:"'.$s_ip.'", id: "'.$s_ip.'" },';
}

foreach ($hosts as $h_ip=>$h_name) {
	if ($h_name!=$h_ip) $hosts_str .= '{ txt:"'.$h_ip.' ['.$h_name.']", id: "'.$h_ip.'" },';
    else $hosts_str .= '{ txt:"'.$h_ip.'", id: "'.$h_ip.'" },';
}

$db_groups = AlarmGroups::get_dbgroups($conn);

list($alarm_group, $count) = AlarmGroups::get_grouped_alarms($conn, $group_type, $show_options, $hide_closed, $date_from, $date_to, $src_ip, $dst_ip, $sensor_query, $query, $directive_id, $num_events, $num_events_op, $tag, "LIMIT $inf,$ROWS");
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo _("Control Panel")?> </title>
	<meta http-equiv="Pragma" content="no-cache"/>
	<!--  <link rel="StyleSheet" href="dtree.css" type="text/css" />-->
	<link rel="stylesheet" href="../style/style.css"/>
	<link rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
	<link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
	<link rel="stylesheet" type="text/css" href="../style/datepicker.css"/>
	<link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css">

	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
	<script type="text/javascript" src="../js/greybox.js"></script>
	<script src="../js/jquery.simpletip.js" type="text/javascript"></script>
	<script src="../js/datepicker.js" type="text/javascript"></script>
	<script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>
	<?php include ("../host_report_menu.php") ?>
	<script type="text/javascript">
		var open = false;
                
        var st_alarms = new Array();
        <?php 
        foreach ($alarm_group as $group) 
        { 
            echo "st_alarms['".$group['group_id']."'] = 0;\n";
        }
        ?>
        	  
		function toggle_group (group_id,ip_src,ip_dst,time,from,similar) {
			document.getElementById(group_id+from).innerHTML = "<div style='padding: 10px 0px'><img src='../pixmaps/loading3.gif'/><span style='margin-left: 5px;'><?php echo _("Loading alarms")?>...</span></div>";;
			
            var hide_closed = ( st_alarms[group_id] == 1 ) ? "0" : "<?php echo $hide_closed?>";
            
            $.ajax({
				type: "GET",
				url: "alarm_group_response.php?from="+from+"&group_id="+group_id+"&unique_id=<?php echo $unique_id ?>&name="+group_id+"&ip_src=<?php echo $src_ip ?>&ip_dst=<?php echo $dst_ip ?>&timestamp="+time+"&hide_closed=<?php echo $hide_closed ?>&sensor_query=<?php echo $sensor_query ?>&date_from=<?php echo $date_from ?>&date_to=<?php echo $date_to ?>&no_resolv=<?php echo $no_resolv ?>&similar="+similar,
				data: "",
				success: function(msg){
					//alert (msg);
					document.getElementById(group_id+from).innerHTML = msg;

					plus = "plus"+group_id;
					document.getElementById(plus).innerHTML = "<a href='' onclick=\"untoggle_group('"+group_id+"','"+ip_src+"','"+ip_dst+"','"+time+"','"+similar+"');return false\"><img align='absmiddle' src='../pixmaps/minus-small.png' border='0'></a>";
					$(".auto_display").each(function(){
						var alarm_data = $(this).attr("id").replace("eventplus", "");
						var tmp = alarm_data.split("-")
						var backlog_id = tmp[0];
						var event_id   = tmp[1];
						toggle_alarm(backlog_id, event_id);
					});
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
					greybox2();
                    if (typeof(load_contextmenu) != "undefined") {  
                        load_contextmenu();
                    }
                }
			});
		}
		
		function untoggle_group (group_id,ip_src,ip_dst,time,similar) {
			plus = "plus"+group_id;
			document.getElementById(plus).innerHTML = "<a href=\"javascript:toggle_group('"+group_id+"','"+ip_src+"','"+ip_dst+"','"+time+"','','"+similar+"');\"><strong><img src='../pixmaps/plus-small.png' border=0></strong></a>";
			document.getElementById(group_id).innerHTML = "";
		}
	
		function opencloseAll () {
			if (!open) 
			{
				<?php foreach ($alarm_group as $group) { ?>
				toggle_group('<?=$group['group_id']?>','<?=$group['ip_src']?>','<?=$group['ip_dst']?>','<?=$group['date']?>','','<? echo ($group_type == "similar") ? "1" : "" ?>');
				<?php } ?>
				open = true;
				document.getElementById('expandcollapse').src='../pixmaps/minus.png';
			} 
			else 
			{
				<?php foreach ($alarm_group as $group) { ?>
				untoggle_group('<?=$group['group_id']?>','<?=$group['ip_src']?>','<?=$group['ip_dst']?>','<?=$group['date']?>','<? echo ($group_type == "similar") ? "1" : "" ?>');
				<?php } ?>
				open = false;
				document.getElementById('expandcollapse').src='../pixmaps/plus.png';
			}
		}
  
		function toggle_alarm (backlog_id,event_id) {
			var td_id = "eventbox"+backlog_id+"-"+event_id;
			var plus = "eventplus"+backlog_id+"-"+event_id;
			document.getElementById(td_id).innerHTML = "<img src='../pixmaps/loading.gif' width='16'>";
			$.ajax({
				type: "GET",
				url: "events_ajax.php?backlog_id="+backlog_id,
				data: "",
				success: function(msg){
					//alert (msg);
					document.getElementById(td_id).innerHTML = msg;
					document.getElementById(plus).innerHTML = "<a href='' onclick=\"untoggle_alarm('"+backlog_id+"','"+event_id+"');return false\"><img src='../pixmaps/minus-small.png' border='0' alt='plus'></img></a>";
					GB_TYPE = 'w';
					$("a.greybox").click(function(){
						var t = this.title || $(this).text() || this.href;
						GB_show(t,this.href,450,'90%');
						return false;
					});
					load_contextmenu();
				}
			});
		}
		
		function untoggle_alarm (backlog_id,event_id) {
			var td_id = "eventbox"+backlog_id+"-"+event_id;
			var plus = "eventplus"+backlog_id+"-"+event_id;
			document.getElementById(td_id).innerHTML = "";
			document.getElementById(plus).innerHTML = "<a href='' onclick=\"toggle_alarm('"+backlog_id+"','"+event_id+"');return false\"><img src='../pixmaps/plus-small.png' border='0' alt='plus'></img></a>";
		}
  
		function change_descr(objname)
		{
			var descr;
			descr = document.getElementsByName(objname); 
			descr = descr[0];
			document.getElementById('group').value = objname.replace("input","");
			document.getElementById('descr').value = descr.value;
			form_submit();
			//location.href= "alarm_group_console.php?group_type=<?php echo $group_type ?>&group=" + objname.replace("input","") + "&descr=" + descr.value;
		}

		function send_descr(obj ,e) 
		{
			var key;

			if (window.event)
			{
				key = window.event.keyCode;
			}
			else if (e)
			{
				key = e.which;
			}
			else
			{
				return;
			}
			
			if (key == 13) 
			{
				change_descr(obj.name);
			}
		}

		function open_group(group_id,ip_src,ip_dst,time,similar) {
			// GROUPS
			$('#lock_'+group_id).html("<img src='../pixmaps/loading.gif' width='16'>");
			document.getElementById("plus"+group_id).innerHTML = "<a href=\"javascript:toggle_group('"+group_id+"','"+ip_src+"','"+ip_dst+"','"+time+"','','"+similar+"');\"><strong><img src='../pixmaps/plus-small.png' border='0'/></strong></a>";
			document.getElementById(group_id).innerHTML = "";
            
			$.ajax({
				type: "GET",
				url: "alarm_group_response.php?only_open=1&group1="+group_id,
				data: "",
				success: function(msg){
					document.getElementById('lock_'+group_id).innerHTML = "<a href='' onclick=\"close_group('"+group_id+"','"+ip_src+"','"+ip_dst+"','"+time+"','"+similar+"');return false\"><img src='../pixmaps/lock-unlock.png' alt='<?php echo _("Open, click to close group") ?>' title='<?php echo _("Open, click to close group") ?>' border=0></a>";
                    st_alarms[group_id] = 0;
                    $(".repinfo").simpletip({
						position: 'left',
						baseClass: 'idmtip',
						onBeforeShow: function() { 
							this.update(this.getParent().attr('txt'));
						}
					});
                }
			});
		}
		
		function close_group(group_id,ip_src,ip_dst,time,similar) {
			// GROUPS
			$('#lock_'+group_id).html("<img src='../pixmaps/loading.gif' width='16'>");
			document.getElementById("plus"+group_id).innerHTML = "<a href=\"javascript:toggle_group('"+group_id+"','"+ip_src+"','"+ip_dst+"','"+time+"','','"+similar+"');\"><strong><img src='../pixmaps/plus-small.png' border='0'/></strong></a>";
			document.getElementById(group_id).innerHTML = "";
			            
            $.ajax({
				type: "GET",
				url: "alarm_group_response.php?only_close=1&group1="+group_id,
				data: "",
				success: function(msg){
					document.getElementById('lock_'+group_id).innerHTML = "<a href='' onclick=\"open_group('"+group_id+"','"+ip_src+"','"+ip_dst+"','"+time+"','"+similar+"');return false\"><img src='../pixmaps/lock.png' alt='<?php echo _("Closed, click to open group") ?>' title='<?php echo _("Closed, click to open group") ?>' border=0></a>";
                                        
                    st_alarms[group_id] = 1;
					$(".repinfo").simpletip({
						position: 'left',
						baseClass: 'idmtip',
						onBeforeShow: function() { 
							this.update(this.getParent().attr('txt'));
						}
					});                    
                }
			});
		}
	
		function close_groups() {
			
			// ALARMS
			var params = "";
			$(".alarm_check").each(function()
			{
				if ($(this).attr('checked') == true) {
					params += "&"+$(this).attr('name')+"=1";
				}
			});
			// GROUPS
			var selected_group = "";
			var group = document.getElementsByName("group");	
			var index = 0;

			for(var i = 0; i < group.length; i++)
			{
				if( group[i].checked )
				{
					selected_group += "&group"+(index+1)+"="+group[i].value;
					index++;
				}
			}

			if (selected_group.length == 0 && params == "")
			{
				alert("<?php echo Util::js_entities(_("Please, select the groups or any alarm to close"));?>");
				return;
			}
			
			$('#delete_data').html('<?php echo _("Closing grouped alarms ...") ?>');
			$('#info_delete').show();
			
			if (params != "")
			{
				$.ajax({
					type: "POST",
					url: "alarms_check_delete.php",
					data: "background=1&only_close=1&unique_id=<?php echo $unique_id ?>"+params,
					success: function(msg){
						
						if (selected_group != "") {
							$.ajax({
								type: "GET",
								url: "alarm_group_response.php?only_close="+index+selected_group,
								data: "",
								success: function(msg){
									$('#delete_data').html('<?php echo _("Reloading data ...") ?>');
									form_submit();
								}
							});
						}
						form_submit();
					}
				});
			} 
			else 
			{
				$.ajax({
					type: "GET",
					url: "alarm_group_response.php?only_close="+index+selected_group,
					data: "",
					success: function(msg){
						$('#delete_data').html('<?php echo _("Reloading data ...") ?>');
						form_submit();
					}
				});
			}
			
		}
	
		function checkall () {
			$("input[type=checkbox]").each(function() {
				if (this.id.match(/^check_/) && this.disabled == false) {
					this.checked = (this.checked) ? false : true;
				}
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
			} else {
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
			} else {
				$y2 = date("Y"); $m2 = date("m"); $d2 = date("d");
			}
			?>
			var datefrom = new Date(<?php echo $y ?>,<?php echo $m-1 ?>,<?php echo $d ?>);
			var dateto = new Date(<?php echo $y2 ?>,<?php echo $m2-1 ?>,<?php echo $d2 ?>);
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
		
		<?php 
        if ( Session::menu_perms("MenuIncidents", "ControlPanelAlarmsDelete") )
        {
            ?>
            function bg_delete() {
                
                // ALARMS
                var params = "";
                $(".alarm_check").each(function()
                {
                    if ($(this).attr('checked') == true) {
                        params += "&"+$(this).attr('name')+"=1";
                    }
                });
                
                // GROUPS
                var selected_group = "";
                var group = document.getElementsByName("group");	
                var index = 0;

                for(var i = 0; i < group.length; i++)
                {
                    if( group[i].checked )
                    {
                        selected_group += "&group"+(index+1)+"="+group[i].value;
                        index++;
                    }
                }
                
                if (selected_group == "" && params == "")
                {
                    alert("<?php echo Util::js_entities(_("Please, select the groups or any alarm to close"));?>");
                    return;
                }
                
                $('#info_delete').show();
                
                if (params != "") {
                    $.ajax({
                        type: "POST",
                        url: "alarms_check_delete.php",
                        data: "background=1&unique_id=<?php echo $unique_id ?>"+params,
                        success: function(msg){
                            
                            if (selected_group != "") 
                            {
                                $.ajax({
                                    type: "GET",
                                    url: "alarm_group_response.php?only_delete="+index+selected_group,
                                    data: "",
                                    success: function(msg){
                                        $('#delete_data').html('<?php echo _("Reloading data ...") ?>');
                                        form_submit();
                                    }
                                });
                            }
                            
                            form_submit();
                        }
                    });
                } 
                else 
                {
                    $.ajax({
                        type: "GET",
                        url: "alarm_group_response.php?only_delete="+index+selected_group,
                        data: "",
                        success: function(msg){
                            $('#delete_data').html('<?php echo _("Reloading data ...") ?>');					
                            form_submit();
                        }
                    });
                }
            }
            
            function delete_all_groups()
            {
                if ( $('#no_groups').length >= 1)
                {
                    return;
                }
                                                              
                if(confirm('<?php echo  Util::js_entities(_("Alarms should never be deleted unless they represent a false positive. Do you want to Continue?"))?>')) 
                {
                    
                    var query_string = $('#queryform').serialize()+"&delete_all=1";
                        query_string = query_string.replace("&src_ip", "&ip_src");
                        query_string = query_string.replace("&dst_ip", "&ip_dst");
                        
                    $.ajax({
                        type: "GET",
                        url: "alarm_group_response.php?"+query_string,
                        beforeSend: function( xhr ) {
                            $('#info_delete').show();
                        },
                        success: function(msg){
                            $('#delete_data').html('<?php echo _("Reloading data ...") ?>');
                            form_submit();
                        }
                    });
                }
            }
            
            
            <?php
        }
        ?>

		function form_submit() {
			document.filters.submit();
		}

		function set_hand_cursor() {
			document.body.style.cursor = 'pointer';
		}
		
		function set_pointer_cursor() {
			document.body.style.cursor = 'default';
		}
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
		.label_ip_s {padding-bottom: 3px; height: 20px;}
		
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

//print_r($alarm_group);
//$count = count($alarm_group);
$tree_count = 0;
if (GET('withoutmenu') != "1") 
	include ("../hmenu.php");
/* Filter & Action Console */
?>
<form name="filters" id="queryform" method="POST">
	<input type="hidden" name="unique_id" id="unique_id" value="<?php echo $unique_id ?>"/>
	<input type="hidden" name="date_from" id="date_from"  value="<?php echo $date_from ?>"/>
	<input type="hidden" name="date_to" id="date_to" value="<?php echo $date_to ?>"/>
	<input type="hidden" name="group" id="group" value=""/>
	<input type="hidden" name="release" id="release" value=""/>
	<input type="hidden" name="take" id="take" value=""/>
	<input type="hidden" name="action" id="action" value=""/>
	<input type="hidden" name="descr" id="descr" value=""/>
	<input type="hidden" name="inf" id="inf" value=""/>
	<input type="hidden" name="sup" id="sup" value=""/>
	<input type="hidden" name="alarm" id="alarm" value=""/>
	<input type="hidden" name="tag" id="tag" value="<?php echo $tag ?>"/>


<!-- Start Filter Box -->

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
		<th width="55%"><?php echo _("Filter") ?></th>
		<th><?php echo _("Options") ?></th>
		<th><?php echo _("Actions") ?></th>
	</tr>
	
	<?php $underlined = ($date_from != "" && $date_to != "") ? ";text-decoration:underline" : ""; ?>
	<tr>
		<td class="nobborder">
			<table class="transparent" style='width: 100%'>
				<tr>
					<td class='label_filter_l'><strong><?php echo _("Sensor")?></strong>:</td>
				    <td class='noborder left' nowrap='nowrap'>
						<select name="sensor_query" id='sensor_query'>
							<option value=""></option>
							<?php 
							foreach ($sensors as $sensor_ip=>$sensor_name) 
							{ 
								$selected = ( $sensor_query == $sensor_ip ) ? "selected='selected'" : "";
								?>
								<option value="<?php echo $sensor_ip ?>" <?php echo $selected?>><?php echo $sensor_name ?> (<?php echo $sensor_ip ?>)</option>
								<?php 
							} 
							?>
						</select>
				    </td>
					<td class='noborder left'>&nbsp;</td>
				</tr>
				<tr>
					<td class='label_filter_l width20'><strong><?php echo _("Alarm name")?></strong>: </td>
				    <td class='label_filter_l pl4' nowrap='nowrap'><input type="text" class='inpw_200' name="query" value="<?php echo $query ?>"/></td>
					<td class='noborder left'><span style='font-weight: bold;'><?php echo _("Directive ID")?></span>: <input type="text" class='inpw_200' name="directive_id" value="<?=$directive_id?>"></td>
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
							<strong><?php echo _("Num. alarm groups per page") ?></strong>: <input type="text" size='3' name="num_alarms_page" value="<?php echo $ROWS ?>"/>
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
		

		<td style="text-align: left;border-bottom:0px solid white">
			<table style='width:100%' class='noborder'>
				<tr>
					<td class='noborder'>
						<table style='width:100%' class='noborder'>
							<tr>
								<td class='noborder left'><strong><?php echo _("Show") ?>:</strong></td>
								<td class='noborder left'>
									<select name="show_options">
										<option value="1" <?php echo $selected1 ?>><?php echo _("All Groups") ?></option>
										<option value="2" <?php echo $selected2 ?>><?php echo _("My Groups") ?></option>
										<option value="3" <?php echo $selected3 ?>><?php echo _("Groups Without Owner") ?></option>
										<option value="4" <?php echo $selected4 ?>><?php echo _("My Groups & Without Owner") ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<td class='noborder left'>&nbsp;</td>
								<td class='noborder left'>
									<input style="border:none" name="no_resolv" type="checkbox" onclick="document.filters.submit()" value="1" <?php if ($no_resolv) echo " checked='checked' " ?> /><?php echo gettext("Do not resolv ip names"); ?><br/>
									<input type="checkbox" name="hide_closed" value="1" onclick="document.filters.submit()" <?php echo $hide_check ?> /><?php echo gettext("Hide closed alarms") ?><br/>
									<input type="checkbox" name="autorefresh" onclick="javascript:document.filters.refresh_time.disabled=!document.filters.refresh_time.disabled;" <?php echo ($autorefresh) ? "checked='checked'" : "" ?> value='1'/><?php echo gettext("Autorefresh") ?>&nbsp;
									<select name="refresh_time" <?php echo (!$autorefresh) ? "disabled='disabled'" : "" ?>>
										<option value="30000" <?php echo $refresh_sel1 ?>><?php echo _("30 sec") ?></option>
										<option value="60000" <?php echo $refresh_sel2 ?>><?php echo _("1 min") ?></option>
										<option value="180000" <?php echo $refresh_sel3 ?>><?php echo _("3 min")?></option>
										<option value="600000" <?php echo $refresh_sel4 ?>><?php echo _("10 min") ?></option>
									</select>
									&nbsp;<a href="" onclick="form_submit();return false">[<?php echo _("Refresh") ?>]</a>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
		
		<td style="text-align: center;border-bottom:0px solid white" nowrap='nowrap'>
			<table class="noborder" width='100%'>
				<tr>
					<td class="nobborder center" style="padding-bottom:5px">
						<input type="button" onclick="close_groups()" value="<?php echo _("Close Selected") ?>" class="lbutton" style="width:70%;" />
					</td>
				</tr>
                <?php
                if ( Session::menu_perms("MenuIncidents", "ControlPanelAlarmsDelete") )
                {
                    $confirm_text =  Util::js_entities(_("Alarms should never be deleted unless they represent a false positive. Would you like to close the alarm instead of deleting it? Click cancel to close, or Accept to continue deleting."));
                    ?>
                    
                    <tr>	
                        <td class="nobborder center">
                            <input type="button" value="<?=_("Delete selected")?>" onclick="if (confirm('<?php echo $confirm_text?>')) bg_delete(); else close_groups()" class="lbutton" style="width:70%;" />
                        </td>
                    </tr>
                    <?php 
                }
                ?>
				<tr>
					<td class="nobborder">
						<table class="transparent" width='100%'>
							<?php 
							if ( count($tags) < 1 ) 
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
									<?php if ($tag != "") 
									{ 
									?>
										<table class="transparent">
											<tr>
												<td class="nobborder"><?php echo $tags_html[$tag] ?></td>
												<td class="nobborder"><a href="" onclick="document.filters.tag.value='';document.filters.submit();return false"><?php echo _("Remove filter")?></a></td>
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
																<tr><td onmouseover="set_hand_cursor()" onmouseout="set_pointer_cursor()" onclick="document.filters.tag.value='<?php echo $tg->get_id() ?>';document.filters.submit()" style="border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;border:0px;background-color:<?php echo '#'.$tg->get_bgcolor()?>;color:<?php echo '#'.$tg->get_fgcolor()?>;font-weight:<?php echo ($tg->get_bold()) ? "bold" : "normal" ?>;font-style:<?php echo ($tg->get_italic()) ? "italic" : "none" ?>"><?php echo $tg->get_name()?></td></tr></table>
															</td>
															<td class="nobborder">
															<?php 
															if ($tag == $tg->get_id()) 
															{ 
																?>
																<a href="" onclick="document.filters.tag.value='';document.filters.submit();return false"><img src="../pixmaps/cross-small.png" border="0" alt="<?php echo _("Remove filter") ?>" title="<?php echo _("Remove filter") ?>"></img></a>
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
		<td colspan="4" style="padding:5px; background: #F2F2F2" class='noborder'>
			<input type="submit" class="button" name='search' value="<?php echo _("Search") ?>"/>
			<div id="loading_div" style="display:inline"></div>
		</td>
	</tr>
</table>


<!-- End Filter Box -->

<br/>

<table cellpadding='0' cellspacing='1' width='98%' align="center">
	<tr>
		<td colspan="8" class="nobborder" style="text-align:center">
			<table class="noborder" align="center" width="100%">
				<tr>
					<td width="200" class="nobborder">
						<table class="transparent">
							<tr>
								<td class="nobborder" nowrap='nowrap'><input type="button" onclick="document.location.href='alarm_console.php?hide_closed=1'" value="<?=_("Ungrouped")?>" class="button"><a href="alarm_console.php?hide_closed=1"></td>
								<td class="nobborder" nowrap='nowrap'><input type="button" value="<?=_("Grouped")?>" class="buttonon" disabled='disabled'> by:</td>
								<td class="nobborder">
									<select name="group_type" onchange="document.filters.submit()">
										<option value="all" <?php if ($group_type == "all")           echo "selected='selected'" ?>><?php echo _("Alarm name, Src/Dst, Date")?></option>
										<option value="namedate" <?php if ($group_type == "namedate") echo "selected='selected'" ?>><?php echo _("Alarm name, Date")?></option>
										<option value="name" <?php if ($group_type == "name")         echo "selected='selected'" ?>><?php echo _("Alarm name")?></option>
                                        <option value="similar" <?php if ($group_type == "similar")   echo "selected='selected'" ?>><?php echo _("Similar alarms")?></option>
									</select>
								</td>
							</tr>
						</table>
					</td>
					
					<td class='nobborder' style='text-align:center'>
                        <?php
                        if ( $inf >= $ROWS) 
                        { 
                            ?>
                            <a href="" onclick="document.getElementById('action').value='change_page';document.getElementById('inf').value=0;document.getElementById('sup').value=<?php echo $ROWS ?>;form_submit();return false">&lt;&lt;- <?php echo _("First") ?>&nbsp;</a>
                            <a href="" onclick="document.getElementById('action').value='change_page';document.getElementById('inf').value=<?php echo ($inf - $ROWS) ?>;document.getElementById('sup').value=<?php echo ($sup - $ROWS) ?>;form_submit();return false">&lt;-<?php printf(gettext("Prev %d") , $ROWS) ?></a>
                            <?php 
                        } 
                        
                        if ($sup < $count) 
                        { 
                            ?>
                            &nbsp;&nbsp;(<?php printf(gettext("%d-%d of %d") , $inf+1, $sup, $count) ?>)&nbsp;&nbsp;
                            <a href="" onclick="document.getElementById('action').value='change_page';document.getElementById('inf').value=<?php echo ($inf + $ROWS) ?>;document.getElementById('sup').value=<?php echo ($sup + $ROWS) ?>;form_submit();return false"><?php printf(gettext("Next %d") , $ROWS) ?> -&gt;</a>
                            <a href="" onclick="document.getElementById('action').value='change_page';document.getElementById('inf').value=<?php echo ($count - $ROWS) ?>;document.getElementById('sup').value=<?php echo $count ?>;form_submit();return false">&nbsp;<?php echo _("Last") ?> -&gt;&gt;</a>
                            <?php 
                        } 
                        else
                        {
                            $aux_inf = ( $count == 0 ) ? 0 : $inf+1;
                            $aux_inf = ( $aux_inf > $count ) ? $count : $aux_inf;
                            echo "&nbsp;&nbsp;(";
                            printf(gettext("%d-%d of %d") , $aux_inf, $count, $count);
                            echo ")&nbsp;&nbsp;";
                        }
						?>
					</td>
					<td width="250" class="nobborder right">
						&nbsp;
					</td>
				</tr>
			</table>
		</td>
	</tr>
		
	
	<tr>
		<td width='20' class='nobborder' style='text-align:center'>
			<input type='checkbox' name='allcheck' onclick='checkall()'/>
		</td>
		<td class='nobborder' style='text-align: center; padding:0px' width='20'>
			<a href='javascript: opencloseAll();'>
					<img src='../pixmaps/plus.png' id='expandcollapse' border='0' alt='<?=_("Expand/Collapse ALL")?>' title='<?=_("Expand/Collapse ALL")?>'>
				</a>
		</td>
		<td style='text-align: left;padding-left:10px; background-color:#9DD131;font-weight:bold'><?=gettext("Group")?></td>
		<td width='10%' style='text-align: center; background-color:#9DD131;font-weight:bold'>    <?=gettext("Owner")?></td>
		<td width='90' style='text-align: center; background-color:#9DD131;font-weight:bold'>     <?=gettext("Highest Risk")?></td>
		<td width='20%' style='text-align: center; background-color:#9DD131;font-weight:bold'>    <?=gettext("Description")?></td>
		<td style='text-align: center; background-color:#9DD131;font-weight:bold' width='7%'>     <?=gettext("Status")?></td>
		<td width='7%' style='text-decoration: none; background-color:#9DD131;font-weight:bold'>  <?=gettext("Action")?></td>
	</tr>
	
	<?php
      	
	if ( count($alarm_group) == 0 )
	{
		?>
		<tr><td colspan='8' style='height: 100px; font-weight: bold;' class='noborder center' id='no_groups'><?php echo _("No groups found")?></td></tr>	
		<?php
	}
	else
	{
		// Timezone correction (in query)
		//$tz = Util::get_timezone();
             
		foreach($alarm_group as $group) 
		{
			//$group['date']       = ($group['date'] != "") ? gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($conn,$group['date'])+(3600*$tz)) : "";
			$group_id            = $group['group_id'];
			$_SESSION[$group_id] = $group['name'];
			$ocurrences          = $group['group_count'];
			//if($group_type=="similar" && $ocurrences>1) { $ocurrences = $ocurrences-1; }
			
			$max_risk = $group['max_risk'];
			$id_tag   = $group['id_tag'];
			
			if ($group['date'] != $lastday) 
			{
				$lastday                  = $group['date'];
				list($year, $month, $day) = split("-", $group['date']);
				$date                     = Util::htmlentities(strftime("%A %d-%b-%Y", mktime(0, 0, 0, $month, $day, $year)));
				$show_day                 = ($group_type == "name" || $group_type == "similar") ? 0 : 1;
			} 
			else 
				$show_day = 0;
		
			$descr   = $db_groups[$group_id]['descr'];
			
            //$status  = ($db_groups[$group_id]['status'] != "") ? $db_groups[$group_id]['status'] : "open";
            
            //Get group status dynamically
            
             
            
            if( $group_type == "similar" )
                $st_name = $group_id;
            else
                $st_name = $group['name'];
                    
            if ( $group_type == "name" || $group_type == "similar" )
            {
                $st_df = $date_from;
                $st_dt = $date_to;
            }
            else
            {
                $timestamp = preg_replace("/\s\d\d\:\d\d\:\d\d$/","", $group['date']);
                                                
                $st_df = $timestamp." 00:00:00";
                $st_dt = $timestamp;
            }     
            
            $status = AlarmGroups::get_group_status ($conn, $sensor_query, $src_ip, $dst_ip, $st_df, $st_dt, $st_name);
                                             
			$incident_link = "<img border='0' src='../pixmaps/script--pencil-gray.png'/>";
			$background    = '#DFDFDF;';
			$group_box     = "";
			$owner_take     = 0;
			$av_description = "readonly='true'";
			
			$ocurrence_text = ( $ocurrences > 1 ) ? strtolower(gettext("Alarms")) : strtolower(gettext("Alarm"));
		   
			$current_page = "document.getElementById('action').value='change_page';document.getElementById('inf').value=$inf;document.getElementById('sup').value=$sup";
			//$owner = ($db_groups[$group_id]['owner'] == $_SESSION["_user"]) ? "<a href='alarm_group_console.php?group_type=$group_type&release=$group_id&inf=$inf&sup=$sup&unique_id=$unique_id'>"._("Release")."</a>" : "<a href='alarm_group_console.php?group_type=$group_type&take=$group_id&inf=$inf&sup=$sup&unique_id=$unique_id'>"._("Take")."</a>";
			$owner = ($db_groups[$group_id]['owner'] == $_SESSION["_user"]) ? "<a href='' onclick=\"$current_page;document.getElementById('release').value='$group_id';form_submit();return false\">"._("Release")."</a>" : "<a href='' onclick=\"$current_page;document.getElementById('take').value='$group_id';form_submit();return false\">"._("Take")."</a>";
			
			if ($db_groups[$group_id]['owner'] != "")
			{
				if ($db_groups[$group_id]['owner'] == $_SESSION["_user"]) 
				{
					$owner_take = 1;
					$background = '#A7D7DF;';
					if ($status == 'open') {
						//$owner = "<a href='alarm_group_console.php?group_type=$group_type&release=$group_id&inf=$inf&sup=$sup&unique_id=$unique_id'>"._("Release")."</a>";
						$owner = "<a href='' onclick=\"$current_page;document.getElementById('release').value='$group_id';form_submit();return false\">"._("Release")."</a>";
					}
					
                    $group_box      = "<input type='checkbox' id='check_" . $group_id . "' name='group' value='" . $group_id . "' >";
					
                    //Create a new ticket for Group ID
                    if ( Session::menu_perms("MenuIncidents", "IncidentsOpen") )
                        $incident_link  = '<a class="greybox2" title=\''._("New ticket for Group ID") . " " . $group_id . '\' href=\'../incidents/newincident.php?nohmenu=1&' . "ref=Alarm&" . "title=" . urlencode(Util::signaturefilter($group['name'])) . "&" . "priority=$s_risk&" . "src_ips=$src_ip&" . "event_start=$since&" . "event_end=$date&" . "src_ports=$src_port&" . "dst_ips=$dst_ip&" . "dst_ports=$dst_port" . '\'>' . '<img border="0" src="../pixmaps/script--pencil.png" alt="'._("New ticket for Group ID").'"/>' . '</a>';
					else
                        $incident_link  = "<span class='disabled'><img src='../pixmaps/script--pencil.png' alt='"._("New ticket for Group ID")."' title='"._("New ticket for Group ID")."' border='0'/></span>";
                    
                    $av_description = "";
				} 
				else 
				{
					$owner_take  = 0;
					$background  = '#FEE599;';
					$description = "<input type='text' name='input" . $group_id . "' title='" . $descr . "' " . $av_description . " style='text-decoration: none; border: 0px; background: #FEE599' size='20' value='" . $descr . "' />";
					$group_box   = "<input type='checkbox' disabled = 'true' name='group' value='" . $group_id . "' >";
				}
			}
		
			$delete_link = ($status == "open" && $owner_take) ? "<a title='" . gettext("Close") . "' href='' onclick=\"close_group('".$group_id."','".$group['ip_src']."','".$group['ip_dst']."','".$group['date']."', '');return false\"><img border=0 src='../pixmaps/cross-circle-frame.png'/>" . "</a>" : "<img border=0 src='../pixmaps/cross-circle-frame-gray.png'/>";
			if ($status == 'open') 
			{
				if ($owner_take) $close_link = "<a href='' onclick=\"close_group('".$group_id."','".$group['ip_src']."','".$group['ip_dst']."','".$group['date']."', '');return false\"><img src='../pixmaps/lock-unlock.png' alt='"._("Open, click to close group")."' title='"._("Open, click to close group")."' border=0></a>";
				else $close_link = "<img src='../pixmaps/lock-unlock.png' alt='"._("Open, take this group then click to close")."' title='"._("Open, take this group then click to close")."' border='0'>";
			} 
			else 
			{
				if ($owner_take) $close_link = "<a href='' onclick=\"open_group('".$group_id."','".$group['ip_src']."','".$group['ip_dst']."','".$group['date']."', '');return false\"><img src='../pixmaps/lock.png' alt='"._("Closed, click to open group")."' title='"._("Closed, click to open group")."' border=0></a>";
				else $close_link = "<img src='../pixmaps/lock.png' alt='"._("Closed, take this group then click to open")."' title='"._("Closed, take this group then click to open")."' border='0'>";
				$group_box = "<input type='checkbox' disabled = 'true' name='group' value='" . $group_id . "' >";
			}
			
			if ($show_day) 
			{ 
				?>
				<tr>
					<td colspan='8' class="nobborder" style="text-align:center;padding:5px;background-color:#B5C7DF"><strong><?=$date?></strong></td>
				</tr>
				<?php 
			} 
			?>
			
			<tr>
				<td class="nobborder" width="20"><input type='checkbox' id='check_<?=$group_id?>' name='group' value='<?=$group_id?>_<?=$group['ip_src']?>_<?=$group['ip_dst']?>_<?=$group['date']?>' <?if (!$owner_take) echo "disabled"?>></td>
				<td class="nobborder" width="20" id="plus<?=$group['group_id']?>"><a href="javascript:toggle_group('<?=$group['group_id']?>','<?=$group['ip_src']?>','<?=$group['ip_dst']?>','<? echo ($group_type == "name" || $group_type == "similar") ? "" : $group['date'] ?>','','<? echo ($group_type == "similar") ? "1" : "" ?>');"><strong><img src='../pixmaps/plus-small.png' border='0'/></strong></a></td>
				<th style='text-align: left; border-width: 0px; background: <?=$background?>'>
					<table class="transparent">
						<tr>
							<?php if ($tags_html[$id_tag] != "") { ?><td class="nobborder"><?php echo $tags_html[$id_tag]; ?></td><?php } ?>
							<td class="nobborder"><?=Util::signaturefilter($group['name'])?>&nbsp;&nbsp;<span style='font-size:xx-small; text-color: #AAAAAA;'>(<?=$ocurrences?> <?=$ocurrence_text?>)</span></td>
						</tr>
					</table>
				</th>
				<th width='10%' style='text-align: center; border-width: 0px; background: <?=$background?>'><?=$owner?></th>
				<th style='text-align: center; border-width: 0px; background: <?=$background?>'>
					<table class="transparent" align="center">
						<tr>
						<?php
						if ($max_risk > 7) 
						{
							echo "
							<td class='nobborder' style='text-align:center;background-color:red;padding:5px'>
							  <strong>
								  <font color=\"white\">$max_risk</font>
							  </strong>
							</td>
							";
						} 
						elseif ($max_risk > 4) 
						{
							echo "
							<td class='nobborder' style='text-align:center;background-color:orange;padding:5px'>
							  <strong>
								  <font color=\"black\">$max_risk</font>
							  </strong>
							</td>
							";
						} 
						elseif ($max_risk > 2) 
						{
							echo "
							<td class='nobborder' style='text-align:center;background-color:green;padding:5px'>
							  <strong>
								  <font color=\"white\">$max_risk</font>
							  </strong>
							</td>
							";
						} 
						else 
						{
							echo "
							<td class='nobborder' style='text-align:center;padding:5px'>$max_risk</td>
							";
						} 
						?>
						</tr>
					</table>
				</th>
				
				<th width='20%' style='text-align: center; border-width: 0px; background: <?=$background?>;padding:3px'>
					<table class='noborder' style='background:$background' align="center">
						<tr>
							<td class='nobborder'><input type='text' name='input<?=$group_id?>' title='<?=$descr?>' <?=$av_description?> style='text-decoration: none; border: 0px; background: #FFFFFF' size='20' value='<?=$descr?>' onkeypress='send_descr(this, event);' /></td>
							<td class='nobborder'><?php if ($owner_take) { ?><a href="javascript:change_descr('input<?=$group_id?>')"><img valign='middle' border='0' src='../pixmaps/disk-black.png' /></a><?php } ?></td>
						</tr>
					</table>
				</th>
				
				<th style='text-align: center; border-width: 0px; background: <?=$background?>' id='lock_<?php echo $group_id ?>' width='7%'><?=$close_link?></th>
				
				<td width='7%' style='text-decoration: none;'><?=$delete_link?> <?=$incident_link?></td>
			</tr>
		
			<tr>
				<td colspan="8" id="<?=$group['group_id']?>" class="nobborder" style="text-align:center"></td>
			</tr>
			<?php 
		} 
    }
	?>
	
	<!-- Pagination -->
	<tr>
		<td colspan="8" class="nobborder" style="text-align:center">
			<table class="noborder" align="center">
				<tr>
					<td class='nobborder' style='text-align:center'>
					<?php
					if ( $inf >= $ROWS) 
                    { 
                        ?>
                        <a href="" onclick="document.getElementById('action').value='change_page';document.getElementById('inf').value=0;document.getElementById('sup').value=<?php echo $ROWS ?>;form_submit();return false">&lt;&lt;- <?php echo _("First") ?>&nbsp;</a>
                        <a href="" onclick="document.getElementById('action').value='change_page';document.getElementById('inf').value=<?php echo ($inf - $ROWS) ?>;document.getElementById('sup').value=<?php echo ($sup - $ROWS) ?>;form_submit();return false">&lt;-<?php printf(gettext("Prev %d") , $ROWS) ?></a>
                        <?php 
                    } 
                    
                    if ($sup < $count) 
                    { 
                        ?>
                        &nbsp;&nbsp;(<?php printf(gettext("%d-%d of %d") , $inf+1, $sup, $count) ?>)&nbsp;&nbsp;
                        <a href="" onclick="document.getElementById('action').value='change_page';document.getElementById('inf').value=<?php echo ($inf + $ROWS) ?>;document.getElementById('sup').value=<?php echo ($sup + $ROWS) ?>;form_submit();return false"><?php printf(gettext("Next %d") , $ROWS) ?> -&gt;</a>
                        <a href="" onclick="document.getElementById('action').value='change_page';document.getElementById('inf').value=<?php echo ($count - $ROWS) ?>;document.getElementById('sup').value=<?php echo $count ?>;form_submit();return false">&nbsp;<?php echo _("Last") ?> -&gt;&gt;</a>
                        <?php 
                    } 
                    else
                    {
                        
                        $aux_inf = ( $count == 0 ) ? 0 : $inf+1;
                        $aux_inf = ( $aux_inf > $count ) ? $count : $aux_inf;
                        echo "&nbsp;&nbsp;(";
                        printf(gettext("%d-%d of %d") , $aux_inf, $count, $count);
                        echo ")&nbsp;&nbsp;";
                    }
					?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
    
    <tr>
        <td class="nobborder" colspan="8">
            <div style='padding: 5px 0px 10px 10px;'>
                <?php
                if ( Session::menu_perms("MenuIncidents", "ControlPanelAlarmsDelete") )
                {
                    $confirm_text =  Util::js_entities(_("Alarms should never be deleted unless they represent a false positive. Would you like to close the alarm instead of deleting it? Click cancel to close, or Accept to continue deleting."));
                    ?>
                    <input type="button" onclick="delete_all_groups();" value="<?php echo gettext("Delete ALL groups"); ?>" class="button"/>&nbsp;
                    <input type="button" value="<?php echo _("Delete selected")?>" onclick="if (confirm('<?php echo $confirm_text?>')) bg_delete(); else close_groups()" class="button"/>&nbsp;
                    <?php
                }
                ?>
                <input type="button" value="<?php echo _("Close selected")?>" onclick="close_groups()" class="button"/>
            </div>
        </td>
    </tr>
    
</table>

</form>

<script type="text/javascript">
    function greybox2() {
        $("a.greybox2").click(function(){
            var t = this.title || $(this).text() || this.href;
            GB_show(t,this.href,450,'90%');
            return false;
        });
    }
    
    $(document).ready(function(){
        GB_TYPE = 'w';
        greybox2();
        $("a.greybox").click(function(){
            var t = this.title || $(this).text() || this.href;
            GB_show(t,this.href,150,'40%');
            return false;
        });
        
        var hosts = [<?=preg_replace("/\,$/","",$hosts_str)?>];
        $("#src_ip").autocomplete(hosts, {
            minChars: 0,
            width: 225,
            matchContains: "word",
            autoFill: true,
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
            autoFill: true,
            formatItem: function(row, i, max) {
                return row.txt;
            }
        }).result(function(event, item) {
            $("#dst_ip").val(item.id);
        });
        <?php if ($date_from != "" || $query != "" || $sensor_query != "" || $directive_id != "" || $num_events > 0) { ?>
        tooglebtn();
        <?php } ?>
        <?php if ($autorefresh) { ?>
        setTimeout("form_submit()",<?php echo $refresh_time ?>);
        <?php } ?>
    });
</script>
</body>
</html>
