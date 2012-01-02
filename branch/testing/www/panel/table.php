<?php
ob_implicit_flush();
require_once ('ossim_db.inc');
require_once ('classes/Util.inc');
require_once ('classes/Security.inc');
require_once ('classes/Session.inc');
require_once 'sensor_filter.php';
Session::logcheck("MenuControlPanel", "ControlPanelExecutive");

$type = GET('type');
$range = (GET('range') != "") ? GET('range') : 604800; // Week by default
ossim_valid($type, OSS_ALPHA, OSS_SCORE, OSS_DIGIT, 'illegal:' . _("Type"));
ossim_valid($range, OSS_DIGIT, 'illegal:' . _("Range"));
if (ossim_error()) {
    die(ossim_error());
}

// Default values (customize them into each type)
$nodata_text = _("No events found");
$text_column = _("Event");
$value_column = _("Count");
$link = "javascript:return false;";

$db = new ossim_db();
$conn = $db->connect();
$conn2 = $db->snort_connect();

$sensor_where = "";
$sensor_where_ossim = "";
if (Session::allowedSensors() != "") {
	$user_sensors = explode(",",Session::allowedSensors());
	$snortsensors = GetSnortSensorSids($conn2);
	$sids = array();
	foreach ($user_sensors as $user_sensor) {
		//echo "Sids de $user_sensor ".$snortsensors[$user_sensor][0]."<br>";
		if (count($snortsensors[$user_sensor]) > 0)
			foreach ($snortsensors[$user_sensor] as $sid) if ($sid != "")
				$sids[] = $sid;
	}
	if (count($sids) > 0) {
		$sensor_where = " AND a.sid in (".implode(",",$sids).")";
		$sensor_where_ossim = " AND a.snort_sid in (".implode(",",$sids).")";
	}
	else {
		$sensor_where = " AND a.sid in (0)"; // Vacio
		$sensor_where_ossim = " AND a.snort_sid in (0)"; // Vacio
	}
}
session_write_close();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Dashboard Table Panel</title>
<link rel="stylesheet" type="text/css" href="../style/style.css"/>
<script language="javascript" type="text/javascript" src="../js/jqplot/jquery-1.4.2.min.js"></script>
<script type="text/javascript">
	$(document).ready(function(){
		$('#loading').hide();
		$('#content').show();
	});
</script>
</head>
<body style="overflow:hidden" scroll="no">
<div id="loading"><br><br><br>
<table class="transparent" align="center" style="height:100%">
	<tr>
		<td class="nobborder"><img src="../pixmaps/loading3.gif"></td>
		<td class="nobborder"><?php echo _("Loading data...") ?></td>
	</tr>
</table>
</div>
<?php
// Honeypot Events List
if ($type == "honeypot_events") {
	$text_column = _("Event");
	$value_column = _("Count");
	$nodata_text .= _(" for <i>Honeypot</i>");
	$link = "../forensics/base_qry_main.php?clear_allcriteria=1&time_range=week&time[0][0]=+&time[0][1]=>%3D&time[0][2]=".gmdate("m",$timetz-$range)."&time[0][3]=".gmdate("d",$timetz-$range)."&time[0][4]=".gmdate("Y",$timetz-$range)."&time[0][5]=&time[0][6]=&time[0][7]=&time[0][8]=+&time[0][9]=+&submit=Query+DB&sig_type=1&sig%5B0%5D=%3D&sig%5B1%5D=QQQ&sort_order=time_d&hmenu=Forensics&smenu=Forensics";
	$query = "select count(*) as val,p.name,p.plugin_id,p.sid FROM snort.acid_event a,ossim.plugin_sid p WHERE p.plugin_id=a.plugin_id AND p.sid=a.plugin_sid AND p.category_id=19 AND a.timestamp BETWEEN '".gmdate("Y-m-d H:i:s",gmdate("U")-$range)."' AND '".gmdate("Y-m-d H:i:s")."' $sensor_where group by p.name order by val desc limit 10";
}

if (!$rs = & $conn->Execute($query)) {
    print $conn->ErrorMsg();
    exit();
}
$data = array();
while (!$rs->EOF) {
	$data[] = array(
		"text" => $rs->fields['name'],
		"value" => $rs->fields['val'],
		"link" => str_replace("QQQ",$rs->fields["plugin_id"]."%3B".$rs->fields["sid"],$link)
	);
	$rs->MoveNext();
}
//
$db->close($conn);
$db->close($conn2);
?>
<div id="content" style="display:none;height:100%">
<table width="100%" height="100%" cellpadding=3 cellspacing=0 style="border:0px">
	<?php if (count($data) > 0) { ?>
	<tr>
		<th><?php echo $text_column ?></th>
		<th><?php echo $value_column ?></th>
	</tr>
	<?php foreach ($data as $row) { $color = ($i++%2 == 0) ? "transparent" : "white"; ?>
	<tr>
		<td style="text-align:left;background-color:<?php echo $color ?>"><a href="<?php echo $row['link'] ?>" target="main"><?php echo $row['text'] ?></a></td>
		<td style="background-color:<?php echo $color ?>"><b><?php echo $row['value'] ?></b></td>
	</tr>
	<?php } ?>
	<?php } else { ?>
	<tr><td class="center nobborder" style="font-family:arial;font-size:12px;background-color:white;padding-top:40px"><?php echo $nodata_text ?></td></tr>
	<?php } ?>
</table>
</div>
</body>
</html>
