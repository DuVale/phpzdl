<?
set_include_path('/usr/share/ossim/include');
require_once ('classes/Alarm.inc');
require_once ('ossim_conf.inc');
require_once ('ossim_db.inc');
$db   = new ossim_db();
$conn = $db->connect();

$conf = $GLOBALS["CONF"];
$days = $conf->get_conf("alarms_lifetime", FALSE);
if ($days < 3) {
	exit;
}
$date_to = date("Y-m-d", time() - $days * 24 * 60 * 60);

$outdir = "/var/lib/ossim/backup_alarm";
if (!is_dir($outdir)) {
	mkdir($outdir);
}

// event table
$sql = "SELECT * FROM event WHERE id in (SELECT backlog_event.event_id as id FROM alarm, backlog_event WHERE alarm.backlog_id = backlog_event.backlog_id AND alarm.timestamp <= '$date_to')";
if (!$rs = & $conn->Execute($sql)) {
	print $conn->ErrorMsg();
} else {
	while (!$rs->EOF) {
		$current_date = preg_replace("/ \d\d\:\d\d\:\d\d/","",$rs->fields['timestamp']);
		$f = fopen("$outdir/alarm_restore_".$current_date.".sql", "a");
		fputs($f, "INSERT IGNORE INTO `event` (`id`, `timestamp`, `tzone`, `sensor`, `interface`, `type`, `plugin_id`, `plugin_sid`, `protocol`, `src_ip`, `dst_ip`, `src_port`, `dst_port`, `event_condition`, `value`, `time_interval`, `absolute`, `priority`, `reliability`, `asset_src`, `asset_dst`, `risk_a`, `risk_c`, `alarm`, `snort_sid`, `snort_cid`, `filename`, `username`, `password`, `userdata1`, `userdata2`, `userdata3`, `userdata4`, `userdata5`, `userdata6`, `userdata7`, `userdata8`, `userdata9`, `rulename`, `uuid`) VALUES (".$rs->fields['id'].", '".$rs->fields['timestamp']."', ".$rs->fields['tzone'].", '".$rs->fields['sensor']."', '".$rs->fields['interface']."', ".$rs->fields['type'].", ".$rs->fields['plugin_id'].", ".$rs->fields['plugin_sid'].", ".$rs->fields['protocol'].", ".$rs->fields['src_ip'].", ".$rs->fields['dst_ip'].", ".$rs->fields['src_port'].", ".$rs->fields['dst_port'].", ".$rs->fields['event_condition'].", '".$rs->fields['value']."', ".$rs->fields['time_interval'].", ".(($rs->fields['absolute'] != "") ? $rs->fields['absolute'] : "NULL").", ".$rs->fields['priority'].", ".$rs->fields['reliability'].", ".$rs->fields['asset_src'].", ".$rs->fields['asset_dst'].", ".$rs->fields['risk_a'].", ".$rs->fields['risk_c'].", ".$rs->fields['alarm'].", ".$rs->fields['snort_sid'].", ".$rs->fields['snort_cid'].", '".$rs->fields['filename']."', '".$rs->fields['username']."', '".$rs->fields['password']."', '".$rs->fields['userdata1']."', '".$rs->fields['userdata2']."', '".$rs->fields['userdata3']."', '".$rs->fields['userdata4']."', '".$rs->fields['userdata5']."', '".$rs->fields['userdata6']."', '".$rs->fields['userdata7']."', '".$rs->fields['userdata8']."', '".$rs->fields['userdata9']."', ".(($rs->fields['rulename'] != "") ? $rs->fields['rulename'] : "NULL").", '".$rs->fields['uuid']."');\n");
		fclose($f);
		$rs->MoveNext();
	}
}
$sql_del = "DELETE FROM event WHERE id in (SELECT backlog_event.event_id as id FROM alarm, backlog_event WHERE alarm.backlog_id = backlog_event.backlog_id AND alarm.timestamp <= '$date_to')";
if (!$rs = & $conn->Execute($sql_del)) {
	print $conn->ErrorMsg();
}

// backlog tables
$sql = "SELECT be.*,b.* FROM backlog_event be, backlog b, alarm a WHERE be.backlog_id = a.backlog_id AND be.event_id = a.event_id AND b.id = a.backlog_id AND a.timestamp <= '$date_to'";
if (!$rs = & $conn->Execute($sql)) {
	print $conn->ErrorMsg();
} else {
	while (!$rs->EOF) {
		$current_date = preg_replace("/ \d\d\:\d\d\:\d\d/","",$rs->fields['timestamp']);
		$f = fopen("$outdir/alarm_restore_".$current_date.".sql", "a");
		fputs($f, "INSERT IGNORE INTO backlog_event (backlog_id, event_id, time_out, occurrence, rule_level, matched, uuid, uuid_event) VALUES (".$rs->fields['backlog_id'].",".$rs->fields['event_id'].",".$rs->fields['time_out'].",".$rs->fields['occurrence'].",".$rs->fields['rule_level'].",".$rs->fields['matched'].",'".$rs->fields['uuid']."','".$rs->fields['uuid_event']."');\n");
		fputs($f, "INSERT IGNORE INTO `backlog` (`id`, `directive_id`, `timestamp`, `matched`, `uuid`) VALUES (".$rs->fields['id'].", ".$rs->fields['directive_id'].", '".$rs->fields['timestamp']."', ".$rs->fields['matched'].", '".$rs->fields['uuid']."');\n");
		fclose($f);
		$rs->MoveNext();
	}
}
$sql_del = "DELETE FROM backlog WHERE id in (SELECT backlog_id as id FROM alarm WHERE timestamp <= '$date_to')";
if (!$rs = & $conn->Execute($sql_del)) {
	print $conn->ErrorMsg();
}
//$sql_del = "DELETE FROM backlog_event WHERE CONCAT(backlog_id,',',event_id) in (SELECT CONCAT(backlog_id,',',event_id) FROM alarm WHERE timestamp <= '$date_to')";
// Optimization
$sql_del = "DELETE backlog_event.* FROM backlog_event, alarm WHERE alarm.timestamp <= '$date_to' AND backlog_event.backlog_id = alarm.backlog_id AND backlog_event.event_id = alarm.event_id";
if (!$rs = & $conn->Execute($sql_del)) {
	print $conn->ErrorMsg();
}

$conn->disconnect();
?>