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
require_once ('classes/Security.inc');
require_once ('ossim_db.inc');
require_once ('classes/Alarm.inc');
require_once ('classes/Tags.inc');

/* connect to db */
$db   = new ossim_db();
$conn = $db->connect();


$hide_closed     = POST('hide_closed');
$no_resolv       = intval(POST('no_resolv'));
$only_close      = POST('only_close');
$move_tag        = POST('move_tag');
$tag             = POST('tag');
$param_unique_id = POST('unique_id');
$date_from       = POST('date_from');
$date_to         = POST('date_to');
$order           = POST('order');
$query           = POST('query');
$autorefresh     = POST('autorefresh');
$directive_id    = POST('directive_id');
$src_ip          = POST('src_ip');
$dst_ip          = POST('dst_ip');
$num_alarms_page = POST('num_alarms_page');
$inf             = POST('inf');
$sup             = POST('sup');
$sensor_query    = POST('sensor_query');
$num_events      = POST('num_events');
$num_events_op   = POST('num_events_op');
$ds_id           = POST('ds_id');
$ds_name         = POST('ds_name');
$background      = (POST('background') != "") ? 1 : 0;


$parameters['hide_closed']            = "hide_closed="    .$hide_closed;
$parameters['no_resolv']              = "no_resolv="      .$no_resolv;
$parameters['query']                  = "query="          .$query;
$parameters['directive_id']           = "directive_id="   .$directive_id;
$parameters['inf']                    = "inf="            .$inf;
$parameters['sup']                    = "sup="            .$sup;
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
$parameters['autorefresh']            = "autorefresh="    .$autorefresh;
$parameters['ds_id']                  = "ds_id="          .$ds_id;
$parameters['ds_name']         		  = "ds_name="        .urlencode($ds_name);
$parameters['bypassexpirationupdate'] = "bypassexpirationupdate=1";


ossim_valid($order,           OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, '.',           'illegal:' . _("Order"));
ossim_valid($query,           OSS_ALPHA, OSS_PUNC_EXT, OSS_SPACE, OSS_NULLABLE,             'illegal:' . _("Query"));
ossim_valid($autorefresh,     OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Autorefresh"));
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
ossim_valid($move_tag,        OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Tag"));
ossim_valid($num_events,      OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Num_events"));
ossim_valid($num_events_op,   OSS_ALPHA, OSS_NULLABLE,                                      'illegal:' . _("Num_events_op"));
ossim_valid($ds_id,           OSS_DIGIT, "-", OSS_NULLABLE,                                 'illegal:' . _("Datasource"));

if (ossim_error()) {
    die(ossim_error());
}
$prev_unique_id = $_SESSION['alarms_unique_id'];

// check required permissions
if (!$only_close && $move_tag == "" && !Session::menu_perms("MenuIncidents", "ControlPanelAlarmsDelete"))
	die(ossim_error("You don't have required permissions to delete Alarms"));

// check unique_id for alarms
if (check_uniqueid($prev_unique_id,$param_unique_id)) 
{
	foreach($_POST as $key => $value) 
	{
	    if (preg_match("/check_(\d+)_(\d+)/", $key, $found)) 
		{
	        if ($only_close) 
				Alarm::close($conn, $found[2]);
			elseif ($move_tag != "") 
			{
				if ($move_tag > 0)
					Tags::set_alarm_tag($conn,$found[1],$move_tag);
				else 
					Tags::del_alarm_tag($conn,$found[1]);
			}
	        else Alarm::delete_from_backlog($conn, $found[1], $found[2]);
	        //echo "<tr><td class='nobborder'>Alarm deleted: <font color='red'><b>" . $found[1] . "-" . $found[2] . "</b></font></td></tr>";
	    }
	}
	
	//header ("Location: alarm_console.php");
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html>
		<head>
			<title><?php echo _("Delete Selected Alarms")?></title>
		</head>
		<?php 
		if (!$background) 
		{ 
			$url = "alarm_console.php?".implode("&", $parameters);
			
			?>
				<body>
					<script type='text/javascript'>document.location.href='<?php echo $url?>'</script>
				</body>
			<?php 
		} 
		?>
	</html>
	<?php
} 

?>
