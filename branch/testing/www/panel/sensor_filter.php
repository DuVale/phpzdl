<?php
require_once ('classes/Util.inc');

// Timezone correction
$tz = Util::get_timezone();
$timetz = gmdate("U")+(3600*$tz); // time to generate dates with timezone correction

// Sensor sid allowed (snort bbdd)
function GetSnortSensorSids($conn2) {
	$query = "SELECT * FROM snort.sensor";
	if (!$rs = & $conn2->Execute($query)) {
		print $conn2->ErrorMsg();
		exit();
	}
	while (!$rs->EOF) {
		$sname = ($rs->fields['sensor']!="") ? $rs->fields['sensor'] : preg_replace("/-.*/","",preg_replace("/.*\]\s*/","",$rs->fields['hostname']));
		$ret[$sname][] = $rs->fields['sid'];
		$rs->MoveNext();
	}
	return $ret;
}
// Sensor allowed filter
function make_sensor_filter($conn,$alias="acid_event") {
	$sensor_where = "";
	if (Session::allowedSensors() != "") {
		$user_sensors = explode(",",Session::allowedSensors());
		$snortsensors = GetSnortSensorSids($conn);
		$sids = array();
		foreach ($user_sensors as $user_sensor) {
			//echo "Sids de $user_sensor ".$snortsensors[$user_sensor][0]."<br>";
			if (count($snortsensors[$user_sensor]) > 0)
				foreach ($snortsensors[$user_sensor] as $sid) if ($sid != "")
					$sids[] = $sid;
		}
		$sensor_where = (count($sids)>0) ? " AND $alias.sid in (".implode(",",$sids).")" : " AND $alias.sid in (0)"; // Vacio
	}
	return $sensor_where;
}
// Asset filter
function make_asset_filter($conn,$alias="acid_event") {
	// Assets filter
	$ips = "";	
	if (Session::allowedNets() != "") {
		require_once("classes/CIDR.inc");
		$assets = explode(",",Session::allowedNets());
		foreach ($assets as $cidr) {
			$asst = CIDR::expand_CIDR($cidr,"SHORT","LONG");
			if ($asst[0]!=$asst[1])
				$ips .= " OR (($alias.ip_src>=".$asst[0]." AND $alias.ip_src<=".$asst[1].") OR ($alias.ip_dst>=".$asst[0]." AND $alias.ip_dst<=".$asst[1]."))";
			else
				$ips .= " OR ($alias.ip_src=".$asst[0]." OR $alias.ip_dst=".$asst[0].")";
		}
		$ips = " AND (".preg_replace("/^ OR /","",$ips).")";
	}
	return $ips;
}
// Taxonomy filter
function make_where ($conn,$arr) {
	include_once("../report/plugin_filters.php");
	$w = "";
	foreach ($arr as $cat => $scs) {
		$id = GetPluginCategoryID($cat,$conn);
		$w .= "(c.cat_id=$id"; 
		$ids = array();
		foreach ($scs as $scat) {
			$ids[] = GetPluginSubCategoryID($scat,$id,$conn);
		}
		if (count($ids)>0) $w .= " AND c.id in (".implode(",",$ids).")";
		$w .= ") OR ";
	}
	return ($w!="") ? "AND (".preg_replace("/ OR $/","",$w).")" : "";
}
// SID filter from snort.sensor
function make_sid_filter($conn,$ip) {
	$sids = array();
	if (preg_match("/\d+\/\d+/",$ip)) {
		require_once("classes/CIDR.inc");
		$aux = CIDR::expand_CIDR($ip,"SHORT","LONG");
		if ($aux[0]=="I" && $aux[1]=="P") $aux[0]=$aux[1]=0;
		$query = "SELECT sid FROM snort.sensor WHERE inet_aton(sensor)>=".$aux[0]." AND inet_aton(sensor)<=".$aux[1];
	} else {
		$query = "SELECT sid FROM snort.sensor WHERE hostname like '%$ip%' OR sensor='$ip'";
	}
	//print_r($query);
	if (!$rs = & $conn->Execute($query)) {
		print $conn->ErrorMsg();
		exit();
	}
	while (!$rs->EOF) {
		$sids[] = $rs->fields['sid'];
		$rs->MoveNext();
	}
	return implode(",",$sids);
}
?>