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
set_time_limit(3600);
ob_implicit_flush();
function verifyCommunication() {
	GLOBAL $fp;
    if (connection_aborted() && $fp->is_running()) { $fp->kill(); }
}
register_shutdown_function(verifyCommunication);
//
require_once ('classes/Security.inc');
require_once ("classes/Session.inc");
Session::logcheck("MenuEvents", "ControlPanelSEM");
require_once ("classes/Host.inc");
require_once ("classes/Net.inc");
require_once ("classes/Util.inc");
require_once ("classes/Plugin_sid.inc");
require_once ('classes/Reputation.inc');
require_once ("process.inc");
require_once ('ossim_db.inc');

function dateDiff($startDate, $endDate)
{
    // Parse dates for conversion
    $startArry = date_parse($startDate);
    $endArry = date_parse($endDate);

    // Convert dates to Julian Days
    $start_date = gregoriantojd($startArry["month"], $startArry["day"], $startArry["year"]);
    $end_date = gregoriantojd($endArry["month"], $endArry["day"], $endArry["year"]);

    // Return difference
    return round(($end_date - $start_date), 0);
}
function has_results($num_lines) {
	foreach ($num_lines as $server=>$num) {
		if ($num > 0) return 1;
	}
	return 0;
}
function background_task($path_dir) {
	// Prepare background task
	$server_ip=trim(`grep framework_ip /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`);
	$https=trim(`grep framework_https /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`);
	$server='http'.(($https=="yes") ? "s" : "").'://'.$server_ip.'/ossim';
	$rnd = date('YmdHis').rand();
	$cookieFile= "$path_dir/cookie";
	$tmpFile= "$path_dir/bgt";
	file_put_contents($cookieFile,"#\n$server_ip\tFALSE\t/\tFALSE\t0\tPHPSESSID\t".session_id()."\n");
	$url = $server.'/sem/process.php?'.str_replace("exportEntireQuery","exportEntireQueryNow",$_SERVER["QUERY_STRING"]);
	$wget = "wget -q --no-check-certificate --cookies=on --keep-session-cookies --load-cookies='$cookieFile' '$url' -O -";
	exec("$wget > '$tmpFile' 2>&1 & echo $!");
}
function GetPluginCategoryID($catname, $db) {
    $idcat = 0;
    $temp_sql = "SELECT id FROM ossim.category WHERE name='".str_replace(" ","_",$catname)."'";
    $tmp_result = $db->Execute($temp_sql);
    if ($myrow = $tmp_result->fields) {
        $idcat = $myrow[0];
    }
    $tmp_result->free();
    return $idcat;
}
function GetPluginSubCategoryID($scatname, $idcat, $db) {
    $scatname = str_replace(" ","_",$scatname);
	$idscat = 0;
    $temp_sql = "SELECT id FROM ossim.subcategory WHERE cat_id=$idcat AND name='".str_replace(" ","_",$scatname)."'";
    $tmp_result = $db->Execute($temp_sql);
    if ($myrow = $tmp_result->fields) {
        $idscat = $myrow[0];
    }
    $tmp_result->free();
    return $idscat;
}

include ("geoip.inc");
$gi = geoip_open("/usr/share/geoip/GeoIP.dat", GEOIP_STANDARD);

$config = parse_ini_file("everything.ini");
$a      = str_replace("PLUS_SIGN","+",GET("query"));

//$export = (GET('txtexport') == "true") ? 1 : 0;
$export = GET('txtexport');

ossim_valid($export, OSS_LETTER, OSS_NULLABLE, 'illegal:' . _("txtexport"));
if (ossim_error()) {
    die(ossim_error());
}

$top    = GET('top');

if ($export=='stop') {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
    <link rel="stylesheet" href="../forensics/styles/ossim_style.css">
</head>
<body topmargin="0">
    <p style="text-align:center;margin:0px;font-weight:bold"><?php echo _("Process Stopped!") ?></p>
</body>
</html>
<?
exit;
}

$offset = GET("offset");
if (intval($offset) < 1) {
    $offset = 0;
}
$start      = GET("start");
$end        = GET("end");
$sort_order = GET("sort");
$uniqueid   = GET("uniqueid");
$uniqueid   = (GET("old_query")=="true") ? "NOINDEX" : $uniqueid;
$tzone      = intval(GET("tzone"));

$debug = 0;
$debug_log = GET("debug_log");
ossim_valid($debug_log, OSS_NULLABLE, OSS_DIGIT, OSS_ALPHA, OSS_PUNC, OSS_SCORE, OSS_SLASH, 'illegal:' . _("debug_log"));
ossim_valid($start, OSS_DIGIT, OSS_COLON, OSS_SCORE, OSS_SPACE, 'illegal:' . _("start date"));
ossim_valid($end, OSS_DIGIT, OSS_COLON, OSS_SCORE, OSS_SPACE, 'illegal:' . _("end date"));
ossim_valid($offset, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("offset"));
ossim_valid($top, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("top"));
ossim_valid($a, OSS_TEXT, OSS_NULLABLE, OSS_BRACKET, "\!\|\%|\*|\+|\;", 'illegal:' . _("a"));
ossim_valid($sort_order, OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("sort order"));
ossim_valid($uniqueid, OSS_ALPHA, OSS_DIGIT, OSS_NULLABLE, OSS_PUNC, 'illegal:' . _("uniqueid"));
if (ossim_error()) {
    die(ossim_error());
}

$db = new ossim_db();
$conn = $db->connect();

$start_query = $start;
$end_query = $end;

if ($tzone!=0) {
	$start = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($conn,$start)+(-3600*$tzone));
	$end = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($conn,$end)+(-3600*$tzone));
}	

$sensors = $hosts = $logger_servers = array(); $hostnames = array(); $sensornames = array();
list($sensors, $hosts, $icons) = Host::get_ips_and_hostname($conn);
//$networks = "";
$_nets = Net::get_all($conn);
$_nets_ips = $_host_ips = $_host = array(); $netnames = array();
foreach ($_nets as $_net) { $_nets_ips[] = $_net->get_ips(); $netnames[$_net->get_name()] = $_net->get_ips(); }
foreach ($hosts as $ip=>$name) { $hostnames[$name] = $ip; }
foreach ($sensors as $ip=>$name) { $sensornames[$name] = $ip; }
//$networks = implode(",",$_nets_ips);
$hosts_ips = array_keys($hosts);

if ($a != "" && !preg_match("/\=/",$a)) { // Search in data field
	$a = "data='".$a."'";
}

// Chanage filter=val1|val2 to filter=val1|filter=val2
//if (preg_match("/ AND /",$a)) {
	$aa = explode(" AND ",$a);
	$a = "";
	foreach ($aa as $aa1) {
		$aa1 = preg_replace("/(.*?)=(.*)(\|)(.*)/","\\1=\\2\\3\\1=\\4",$aa1);
		$a .= ($a=="") ? $aa1 : " AND $aa1";
	}
//}

// Patch "sensor=A OR sensor=B"
$a = preg_replace("/SPACESCAPEORSPACESCAPE([a-zA-Z\_]+)\=/"," or \\1=",$a);

// Filter batch injection
$a = str_replace(";","",$a);
//error_log("I: $a\n",3,"/tmp/fetch");

$atoms = explode("|",preg_replace("/ (and|or) /i","|",$a));
$source_type = ""; $category_id = ""; $subcategory_id = "";
foreach ($atoms as $atom) {
    $atom = trim($atom);
	$atom = str_replace("src_ip=","src=",$atom);
	$atom = str_replace("dst_ip=","dst=",$atom);
	// Product type tranforms
	if (preg_match("/product_type(\!?\=)(.+)/", $atom, $matches)) {
	    $op = $matches[1];
	    $source_type = $matches[2];
	    if ($category_id!="")
	    	$a = str_replace("product_type".$op.$source_type,"taxonomy".$op."'".$source_type."-$category_id-$subcategory_id'",preg_replace("/taxonomy(\!?\=)'-\d+-\d+'/","",$a));
	    else
			$a = str_replace("product_type".$op.$source_type,"taxonomy".$op."'".$source_type."-0-0'",$a);
	}
	// Taxonomy tranforms
	if (preg_match("/(category|event_category)(\!?\=)(.+)/", $atom, $matches)) {
	    $cat = str_replace("SPACESCAPE"," ",$matches[3]);
	    $subcat = "";
	    if (preg_match("/([^\-]+)\-(.+)/",$cat,$catfound)) {
	    	$cat = $catfound[1];
	    	$subcat = $catfound[2];
	    }
	    $category_id = GetPluginCategoryID($cat,$conn);
	    $subcategory_id = GetPluginSubCategoryID($subcat,$category_id,$conn);
	    if ($source_type!="")
	    	$a = str_replace($matches[1].$matches[2].$matches[3],"taxonomy".$matches[2]."'$source_type-$category_id-$subcategory_id'",preg_replace("/taxonomy(\!?\=)'(.*?)-0-0'/","",$a));
	    else
	    	$a = str_replace($matches[1].$matches[2].$matches[3],"taxonomy".$matches[2]."'-$category_id-$subcategory_id'",$a);
	}
	// Data source tranforms
	if (preg_match("/(plugin|datasource)(\!?\=)(.+)/", $atom, $matches)) {
	    $plugin_name = str_replace('\\\\','\\',str_replace('\\"','"',$matches[3]));	    
	    $query = "select id from plugin where name like '" . $plugin_name . "%' order by id";
	    if (!$rs = & $conn->Execute($query)) {
	        print $conn->ErrorMsg();
	        exit();
	    }
	    if ($plugin_id = $rs->fields["id"] != "") {
	        $plugin_id = $rs->fields["id"];
	    } else {
	        $plugin_id = $matches[3];
	    }
	    $a = str_replace($matches[1].$matches[2].$matches[3],"plugin_id".$matches[2]."'".$plugin_id."'",$a);
	}
	// Sensor tranforms
	if (preg_match("/sensor(\!?\=)(\S+)/", $atom, $matches)) {
	    $sensor_name = str_replace('\\\\','\\',str_replace('\\"','"',$matches[2]));
	    $sensor_name = str_replace("SPACESCAPE"," ",str_replace("'","",$sensor_name));
	    $query = "select ip from sensor where name like '" . $sensor_name . "'";
	    if (!$rs = & $conn->Execute($query)) {
	        print $conn->ErrorMsg();
	        exit();
	    }
	    if ($rs->fields["ip"] != "") {
	        $sensor_ip = $rs->fields["ip"];
	    } else {
	        $sensor_ip = $matches[2];
	    }
	    $a = str_replace("sensor".$matches[1].$matches[2],"sensor".$matches[1].$sensor_ip,$a);
	}
	// Src OR Dst tranforms
	if (preg_match("/(src|dst)(\!?\=)(\S+)/", $atom, $matches)) {
	    $field = $matches[1];
		$op = $matches[2];
	    $name = $matches[3];
	    // Is a net or host?
	    if ($netnames[$name] != "") {
	    	$resolv = $netnames[$name];
	    	$field .= "_net";
	    } else {
	    	$resolv = ($sensornames[$name]!="") ? $sensornames[$name] : (($hostnames[$name]!="") ? $hostnames[$name] : $name);
	    	$field .= "_ip";
	    }
		$a = str_replace($matches[1].$matches[2].$matches[3],$field.$op.$resolv,$a);
	}
	// Src AND Dst tranforms
	if (preg_match("/(ip|ip_src_or_dst)(\!?\=)(\S+)/", $atom, $matches)) {
	    $field = $matches[1];
		$op = "=";
	    $name = $matches[3];
	    // Is a net or cidr?
	    if ($netnames[$name] != "" || preg_match("/\d+\/\d+/",$name)) {
	        require_once("classes/CIDR.inc");
	        $cidr = (preg_match("/\d+\/\d+/",$name)) ? $name : $netnames[$name]; 
	        $ip_range = CIDR::expand_CIDR($cidr, "FULL", "IP");
    	    $resolv = implode("|$field=",$ip_range);
	    } else {
    	    $resolv = ($sensornames[$name]!="") ? $sensornames[$name] : (($hostnames[$name]!="") ? $hostnames[$name] : $name);
        }
        $a = str_replace($matches[1].$matches[2].$matches[3],$field.$op.$resolv,$a);
	}
	//error_log("T: $a\n",3,"/tmp/fetch");
}
$a = preg_replace("/^\s*(and|or)\s*/i","",$a);
// Do not use indexer with != queries
if (preg_match("/\!\=/",$a)) $uniqueid = "NOINDEX";
//error_log("Q: $a\n",3,"/tmp/fetch");

$_SESSION["forensic_query"] = $a;
$_SESSION["forensic_start"] = $start;
$_SESSION["forensic_end"] = $end;

$user = $_SESSION["_user"];

if($export=='exportEntireQuery') {
	$outdir = $config["searches_dir"].$user."_"."$start"."_"."$end"."_"."$sort_order"."_".base64_encode($a);
	if(strlen($outdir) > 255) {
		$outdir = substr($outdir,0,255);
	}
	if (!is_dir($outdir)) mkdir($outdir);
	background_task($outdir);
	unset($export); // continues normal execution
}

$save = $_SESSION;
session_write_close();
$_SESSION = $save;
if($export=='exportEntireQueryNow') {
    $top = (intval($config["max_export_events"])>0) ? $config["max_export_events"] : 250000;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="">
<head>
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
<link rel="stylesheet" href="../forensics/styles/ossim_style.css">

<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
<script type="text/javascript" src="../js/jquery.progressbar.min.js"></script>
<style type="text/css">
.level11  {  background:url(../pixmaps/statusbar/level11.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level10  {  background:url(../pixmaps/statusbar/level10.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level9  {  background:url(../pixmaps/statusbar/level9.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level8  {  background:url(../pixmaps/statusbar/level8.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level7  {  background:url(../pixmaps/statusbar/level7.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level6  {  background:url(../pixmaps/statusbar/level6.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level5  {  background:url(../pixmaps/statusbar/level5.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level4  {  background:url(../pixmaps/statusbar/level4.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level3  {  background:url(../pixmaps/statusbar/level3.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level2  {  background:url(../pixmaps/statusbar/level2.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level1  {  background:url(../pixmaps/statusbar/level1.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level0  {  background:url(../pixmaps/statusbar/level0.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.tag_cloud { padding: 3px; text-decoration: none; }
.tag_cloud:link  { color: #17457C; }
.tag_cloud:visited { color: #17457C; }
.tag_cloud:hover { color: #ffffff; background: #17457C; }
.tag_cloud:active { color: #ffffff; background: #ACFC65; }
a {
	font-size:10px;
}
</style>
</head>
<body>
<?php

$db->close($conn);
$time1 = microtime(true);
$cmd = process($a, $start, $end, $offset, $sort_order, "logs", $uniqueid, $top, 1);

//$status = exec($cmd, $result);
$result = array();
//error_log ("\nCMD: $cmd $user\n",3,"/tmp/fetch");

if($debug_log!=""){
	$handle = fopen($debug_log, "a+");
	fputs($handle,"============================== PROCESS.php ".date("Y-m-d H:i:s")." ==============================\n");
	fputs($handle,"PROCESS.php: $cmd '$user' '".$debug_log."'\n");
	fclose($handle);
}

$logger_servers_keys = array();
$fp = new Process('/usr/share/ossim/www/sem');

// LOCAL OR REMOTE fetch
if (is_array($_SESSION['logger_servers']) && (count($_SESSION['logger_servers']) > 1 || (count($_SESSION['logger_servers']) == 1 && reset($_SESSION['logger_servers']) != "127.0.0.1"))) {
	$from_remote = 1;
	$cmd = str_replace("perl fetchall.pl","sudo ./fetchremote.pl",$cmd);
	$servers_string = "";
	$num_servers = 0;
	?><div id="loading" style="position:absolute;top:0;left:30%"><table class="noborder" style="background-color:white"><?php
	foreach ($_SESSION['logger_servers'] as $key=>$val) {
		$servers_string .= ($servers_string != "") ? ",".$val : $val;
		$logger_servers[$val] = $key;
		if ($val != "127.0.0.1") $logger_servers_keys[$val] = `sudo ./fetchremote_publickey.pl $val`;
		$num_servers++;
		?>
		<tr>
			<td><span class="progressBar" id="pbar_<?php echo $key ?>"></span></td>
			<td valign="top" class="nobborder" id="progressText_<?php echo $key ?>" style="text-align:left;padding-left:5px"><?=gettext("Loading data. Please, wait a few seconds...")?></td>
			<script type="text/javascript">
				$("#pbar_<?php echo $key ?>").progressBar();
				$("#pbar_<?php echo $key ?>").progressBar(1);
			</script>
		</tr>
		<?php
	}
	?>	<tr>
			<td colspan="2" style="text-align:center;padding-top:5px"><input type="button" onclick="parent.KillProcess()" class="button" value="<?php echo _("Stop") ?>"></input></td>
		</tr>
		</table>
	</div><script type="text/javascript">parent.resize_iframe();</script><?php
	//$fp = popen("$cmd '$user' $servers_string 2>>/dev/null", "r");
	$fp->popen("$cmd '$user' $servers_string");
	//error_log("$cmd '$user' $servers_string 2>>/dev/null\n",3,"/tmp/fetch");
} else {
	?>
	<div id="loading" style="position:absolute;top:0;left:30%">
		<table class="noborder" style="background-color:white">
			<tr>
				<td class="nobborder" style="text-align:center">
					<span class="progressBar" id="pbar_local"></span>
				</td>
				<td class="nobborder" id="progressText_local" style="text-align:center;padding-left:5px"><?=gettext("Loading data. Please, wait a few seconds...")?></td>
				<td><input type="button" onclick="parent.KillProcess()" class="button" value="<?php echo _("Stop") ?>"></input></td>
			</tr>
		</table>
	</div>
	<script type="text/javascript">
		$("#pbar_local").progressBar();
		$("#pbar_local").progressBar(1);
	</script>
	<?php
	if (is_array($_SESSION['logger_servers'])) foreach ($_SESSION['logger_servers'] as $key=>$val) {
		$logger_servers[$val] = $key;
	}
	$from_remote = 0;
	$num_servers = 1;
	//$fp = popen("$cmd '$user' '".$_GET['debug_log']."' 2>>/tmp/popen", "r");
	$fp->popen("$cmd '$user' '".$_GET['debug_log']."'");	
	//error_log("$cmd '$user' '".$_GET['debug_log']."' 2>>/dev/null\n",3,"/tmp/fetch");
}
$perc = array();
$ndays = dateDiff($start,$end);
if ($ndays < 1) $ndays = 1;
$inc = 100/$ndays;
$num_lines = array(); // Number of lines for each logger server
$current_server = ($from_remote) ? "" : "local";
$server_bcolor = $server_fcolor = array();
$cont = 0;
$has_next_page = 0;
//while (!feof($fp)) {
while (!$fp->feof()) {
    //$line = trim(fgets($fp));
    $line = trim($fp->fgets());
    if (connection_aborted()) { $fp->kill(); continue; }
	// Remote connect message
    /*
    if (preg_match("/^Connecting (.+)/",$line,$found)) {
    	$current_server = ($logger_servers[$found[1]] != "") ? $logger_servers[$found[1]] : $found[1];
    	$server_bcolor[$current_server] = $_SESSION['logger_colors'][$current_server]['bcolor'];
    	$server_fcolor[$current_server] = $_SESSION['logger_colors'][$current_server]['fcolor'];
    	$cont++;
    }
    */
	// Searching message
    if (preg_match("/^Searching (\d\d\d\d)(\d\d)(\d\d) in (\d+\.\d+\.\d+\.\d+)/i",$line,$found) ||
    	preg_match("/^Searching (\d\d\d\d)\/(\d\d)\/(\d\d) in (\d+\.\d+\.\d+\.\d+)/i",$line,$found) ) {
    	ob_flush();
		flush();
		$sdate = date("d F Y",strtotime($found[2].$found[3].$found[4]));
		$current_server = ($logger_servers[$found[5]] != "") ? $logger_servers[$found[5]] : $found[5];
		if (!$from_remote) $current_server = "local";
		if ($perc[$current_server] == "") { $perc[$current_server] = 1; }
		$from_str = ($from_remote) ? " from <b>".$current_server."</b>" : ""; 
    	?><script type="text/javascript">$("#pbar_<?php echo $current_server ?>").progressBar(<?php echo floor($perc[$current_server]) ?>);$("#progressText_<?php echo $current_server ?>").html('Searching <b>events</b> in <?php echo $sdate?><?php echo $from_str ?>...');</script><?php
    	$perc[$current_server] += $inc;
    	if ($perc[$current_server] >= 100 || $num_lines[$current_server] >= $top) {
    		?><script type="text/javascript">$("#pbar_<?php echo $current_server ?>").progressBar(100);$("#progressText_<?php echo $current_server ?>").html('All done <?php echo $from_str ?>...');</script><?php
    		$perc[$current_server] = 100;
    	}
    // Event line
    } elseif (preg_match("/entry id='([^']+)'\s+fdate='([^']+)'\s+date='([^']+)'/",$line,$found)) {
    	$fields = explode(";",$line);
    	// added 127.0.0.1 if not exists
    	if (is_numeric($fields[count($fields)-1])) $fields[] = "127.0.0.1";
    	//
    	$current_server = ($logger_servers[trim($fields[count($fields)-1])] != "") ? $logger_servers[trim($fields[count($fields)-1])] : trim($fields[count($fields)-1]);
    	$event_date = preg_replace("/\s|\-/","",$found[2]);
    	$num_lines[$current_server]++;
    	if ($num_lines[$current_server] <= $top) {
    		$result[$line] = $event_date;
    	} else {
    		$has_next_page = 1;
    	}
    }
	if ($num_lines[$current_server] >= $top) {
    	?><script type="text/javascript">$("#pbar_<?php echo $current_server ?>").progressBar(100);$("#progressText_<?php echo $current_server ?>").html('All done <?php echo $from_str ?>...');</script><?php
    	$perc[$current_server] = 100;
    }
}

// Order only if remote fetch
if ($from_remote) {
	arsort($result);
}

?><script type="text/javascript">$("#loading").hide();</script><?php
//fclose($fp);
$fp->pclose();
$time2 = microtime(true);
$totaltime = round($time2 - $time1, 2);
$tz=(GET("tzone")!="") ? $tzone : Util::get_timezone(); 
$txtzone = Util::timezone($tz);
?>
<div id="processcontent" style="display:none">
<?php if (has_results($num_lines)) { ?>
<table width="100%" class="noborder" style="background-color:transparent;">
	<tr>
		<td width="20%" class="nobborder" nowrap><img src="../pixmaps/arrow_green.gif" align="absmiddle"><?php print _("Time Range").": <b>$start_query <-> $end_query</b> $txtzone" ?></td>
		<td class="center nobborder">
			<?php if ($from_remote) { ?>
			<?php echo _("Showing ")."<b>".($offset+1)."</b> - <b>".($offset+$top)."</b>"._(" <b>first</b> events")._(" for <b>each server</b>")." (<b>".(($offset*$num_servers)+1)."</b> - <b>".(($offset*$num_servers)+count($result))."</b> total)" ?>.&nbsp;
			<?php } else { ?>
			<?php echo _("Showing ")."<b>".($offset+1)."</b> - <b>".($offset+count($result))."</b>"._(" events") ?>.&nbsp;
			<?php } ?>
			<?php if ($offset > 0) { ?>
			<a href="javascript:DecreaseOffset(<?php echo GET('top')?>);"><?php echo ($from_remote) ? "<< "._("Fetch previous ") : "<< "._("Previous ")?><?php echo "<b>".GET('top')."</b>" ?></a>
			<?php } ?>
			<?php if ($has_next_page) { //if($num_lines > $offset + 50){
			    echo ($offset != 0) ?  "&nbsp;<b>|</b>&nbsp;" : "";
			?>
			<a href="javascript:IncreaseOffset(<?php echo GET('top')?>);"><?php echo ($from_remote) ? _("Fetch next ") : _("Next ")?><?php echo "<b>".GET('top')."</b> >>" ?></a>
			<?php } ?>
		</td>
		<td width="20%" class="nobborder" style="text-align:right;" nowrap><?php echo ($uniqueid=="NOINDEX" ? _("Raw query") : _("Indexed query"))." "._("parsing time").": <b>$totaltime</b> "._("seconds") ?></td>
	</tr>
</table>

<table class='transparent' style='border: 1px solid rgb(170, 170, 170);border-radius: 0px; -moz-border-radius: 0px; -webkit-border-radius: 0px;' width='100%' cellpadding='5' cellspacing='0'>
	<tr height="35">
		<td class='plfieldhdr' style='border-right: 1px solid rgb(170, 170, 170);border-bottom: 1px solid rgb(170, 170, 170); background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 50% 50%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: rgb(34, 34, 34); font-size: 12px; font-weight: bold;'><?php echo _("ID") ?></td>
		<?php if ($from_remote) { ?>
		<td class='plfieldhdr' style='padding-left:3px;padding-right:3px;border-right: 1px solid rgb(170, 170, 170);border-bottom: 1px solid rgb(170, 170, 170); background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 50% 50%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: rgb(34, 34, 34); font-size: 12px; font-weight: bold;'><?php echo _("Server") ?></td>
		<?php } ?>
		<td class='plfieldhdr' style='border-right: 1px solid rgb(170, 170, 170);border-bottom: 1px solid rgb(170, 170, 170); background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 50% 50%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: rgb(34, 34, 34); font-size: 12px; font-weight: bold;' nowrap>
			<a href="javascript:DateAsc()"><img src="../forensics/images/order_sign_a.gif" border="0"></a><?php print " " . _("Date") . " $txtzone " ?>
			<a href="javascript:DateDesc()"><img src="../forensics/images/order_sign_d.gif" border="0"></a>
		</td>
		<td class='plfieldhdr' style='border-right: 1px solid rgb(170, 170, 170);border-bottom: 1px solid rgb(170, 170, 170); background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 50% 50%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: rgb(34, 34, 34); font-size: 12px; font-weight: bold;'><?php echo _("Type") ?></td>
		<td class='plfieldhdr' style='border-right: 1px solid rgb(170, 170, 170);border-bottom: 1px solid rgb(170, 170, 170); background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 50% 50%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: rgb(34, 34, 34); font-size: 12px; font-weight: bold;'><?php echo _("Sensor") ?></td>
		<td class='plfieldhdr' style='border-right: 1px solid rgb(170, 170, 170);border-bottom: 1px solid rgb(170, 170, 170); background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 50% 50%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: rgb(34, 34, 34); font-size: 12px; font-weight: bold;'><?php echo _("Source") ?></td>
		<td class='plfieldhdr' style='border-right: 1px solid rgb(170, 170, 170);border-bottom: 1px solid rgb(170, 170, 170); background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 50% 50%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: rgb(34, 34, 34); font-size: 12px; font-weight: bold;'><?php echo _("Dest") ?></td>
		<td class='plfieldhdr' style='border-right: 1px solid rgb(170, 170, 170);border-bottom: 1px solid rgb(170, 170, 170); background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 50% 50%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: rgb(34, 34, 34); font-size: 12px; font-weight: bold;'><?php echo _("Data") ?></td>
		<td class='plfieldhdr' style='border-bottom: 1px solid rgb(170, 170, 170); background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 50% 50%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: rgb(34, 34, 34); font-size: 12px; font-weight: bold;'><?php echo _("Signature") ?></td>
	</tr>
<?php

// Output file TXT
if (isset($export) && $export != "noExport") {
	if (is_dir($config["searches_dir"])) {
		// dir
		$outdir = $config["searches_dir"].$user."_"."$start"."_"."$end"."_"."$sort_order"."_".base64_encode($a);
		if (!is_dir($outdir)) mkdir($outdir);
		$outfilename = $outdir."/results.txt";
		// file
		if ($offset > 0 && file_exists($outfilename)) {
			$outfile = fopen($outfilename,"a");
			$loglist = fopen($outdir."/loglist.txt","a");
		}
		else {
			$outfile = fopen($outfilename,"w"); fclose($outfile); $outfile = fopen($outfilename,"w");
			$loglist = fopen($outdir."/loglist.txt","w");
		}
		$logarr = array();
	}
}

// RESULTS Main Loop
$color_words = array(
    'warning',
    'error',
    'failure',
    'break',
    'critical',
    'alert'
);
$inc_counter = 1 + $offset;
$total_counter = 1 + $offset*$num_servers;
$cont = array(); // Counter for each logger server
$colort = 1;
$alt = 0;
$htmlResult=true;
$i = 0; // to show event details

$conn = $db->connect();
foreach($result as $res=>$event_date) {
    //entry id='2' fdate='2008-09-19 09:29:17' date='1221816557' plugin_id='4004' sensor='192.168.1.99' src_ip='192.168.1.119' dst_ip='192.168.1.119' src_port='0' dst_port='0' data='Sep 19 02:29:17 ossim sshd[2638]: (pam_unix) session opened for user root by root(uid=0)'
	if (preg_match("/entry id='([^']+)'\s+fdate='([^']+)'\s+date='([^']+)'\s+plugin_id='([^']+)'\s+sensor='([^']+)'\s+src_ip='([^']+)'\s+dst_ip='([^']+)'\s+src_port='([^']+)'\s+dst_port='([^']+)'\s+tzone='([^']+)'+\s+(datalen='\d+'\s+)?data='(.*)'/", $res, $matches)) {
		$lf = explode(";", $res);
		// added 127.0.0.1 if not exists
    	if (is_numeric($lf[count($lf)-1])) $lf[] = "127.0.0.1";
    	//
        $logfile = $lf[count($lf)-3];
        $current_server = urlencode($lf[count($lf)-1]);
        $current_server_ip = $current_server;
        $current_server = $logger_servers[$current_server];
		if ($cont[$current_server] == "") $cont[$current_server] = 1;
		if ($cont[$current_server] > $num_lines[$current_server] || $cont[$current_server] > $top*$num_servers){
	        $htmlResult = false;
	    } else {
	    	$htmlResult = ($export=='exportEntireQueryNow') ? false : true;
	    }
	    
	    $res = str_replace("<", "", $res);
	    $res = str_replace(">", "", $res);

		# Clean data => matches[12] may contains sig, plugin_sid, userdatas, reputation idm...
		$plugin_sid = "";
        $userdata = array();
		if (preg_match("/' plugin_sid='(\d+)/",$matches[12],$fnd)) $plugin_sid = $fnd[1];
        
		// Reputation
		$rep_dst_icon = $rep_src_icon = $rep_prio_src = $rep_prio_dst = $rep_rel_src = $rep_rel_dst = $rep_act_src = $rep_act_dst = "";
		$idm_user_src = $idm_user_dst = $idm_dom_src = $idm_dom_dst = $idm_host_src = $idm_host_dst = $idm_mac_src = $idm_mac_dst = "";
		$rep_dst_bgcolor = ($colort%2==0) ? "#F2F2F2" : "transparent";
		$rep_src_bgcolor = ($colort%2==0) ? "#F2F2F2" : "transparent";
		//echo "Reputation Matches: ".$matches[12]."<br>";
		if (preg_match("/' rep_prio_src='(\d+)/",$matches[12],$fnd)) { $rep_prio_src = $fnd[1]; }
		if (preg_match("/' rep_prio_dst='(\d+)/",$matches[12],$fnd)) { $rep_prio_dst = $fnd[1]; }
		if (preg_match("/' rep_rel_src='(\d+)/",$matches[12],$fnd)) { $rep_rel_src = $fnd[1]; }
		if (preg_match("/' rep_rel_dst='(\d+)/",$matches[12],$fnd)) { $rep_rel_dst = $fnd[1]; }
		if (preg_match("/' rep_act_src='(\d+)/",$matches[12],$fnd)) { $rep_act_src = $fnd[1]; }
		if (preg_match("/' rep_act_dst='(\d+)/",$matches[12],$fnd)) { $rep_act_dst = $fnd[1]; }
		if (preg_match("/' idm_user_src='([^']+)/",$matches[12],$fnd)) { $idm_user_src = $fnd[1]; }
		if (preg_match("/' idm_user_dst='([^']+)/",$matches[12],$fnd)) { $idm_user_dst = $fnd[1]; }
		if (preg_match("/' idm_dom_src='([^']+)/",$matches[12],$fnd)) { $idm_dom_src = $fnd[1]; }
		if (preg_match("/' idm_dom_dst='([^']+)/",$matches[12],$fnd)) { $idm_dom_dst = $fnd[1]; }
		if (preg_match("/' idm_host_src='([^']+)/",$matches[12],$fnd)) { $idm_host_src = $fnd[1]; }
		if (preg_match("/' idm_host_dst='([^']+)/",$matches[12],$fnd)) { $idm_host_dst = $fnd[1]; }
		if (preg_match("/' idm_mac_src='([^']+)/",$matches[12],$fnd)) { $idm_mac_src = $fnd[1]; }
		if (preg_match("/' idm_mac_dst='([^']+)/",$matches[12],$fnd)) { $idm_mac_dst = $fnd[1]; }
		$rep_src_icon     = Reputation::getrepimg($rep_prio_src,$rep_rel_src,$rep_act_src);
		$rep_src_bgcolor  = Reputation::getrepbgcolor($rep_prio_src, 0);
		$rep_dst_icon     = Reputation::getrepimg($rep_prio_dst,$rep_rel_dst,$rep_act_dst);
		$rep_dst_bgcolor  = Reputation::getrepbgcolor($rep_prio_dst, 0);
		
        $matches_user_data = $matches[12];
        
        $matches_user_data = preg_replace('/\\\\/', '\\', $matches_user_data);
        $matches_user_data = str_replace("\'" , "##_##" , $matches_user_data);
        if (preg_match_all("/ userdata(\d)='(.*?)'/", $matches_user_data."'",$all)) {
            unset($all[0]);
            foreach($all[1] as $k => $v) {
                $userdata[$v] = str_replace("##_##", "'",$all[2][$k]);
            }
        }
		$matches[12] = preg_replace("/' plugin_sid=.*/","",$matches[12]);
		$signature = "";
		if (preg_match("/' sig='(.*)('?)/",$matches[12],$found)) {
			$signature = $found[1];
			$matches[12] = preg_replace("/' sig=.*/","",$matches[12]);
		}

        # decode if data is stored in utf-8
        $matches[12] = mb_convert_encoding($matches[12],"HTML-ENTITIES","UTF-8");
        $data = $matches[12];
        
        $demo = 0;
        # special case "demo event"
        if ($data == "demo event" && $plugin_sid!="") {
        	$demo = 1;
        	$plugin_sid_name = Plugin_sid::get_name_by_idsid($conn,$matches[4],$plugin_sid);
        	if ($plugin_sid_name!="") {
        		$data = $plugin_sid_name;
        		$matches[12] = $plugin_sid_name;
        	}
        }
        
        #$data = $matches[12];
        #$matches[12] = base64_decode($matches[12],true);
        #if ($matches[12]==FALSE) $matches[12] = $data;
                            
        if($htmlResult){
        	if ($_SESSION["_plugins"][$matches[4]]!="") {
        		$plugin = $_SESSION["_plugins"][$matches[4]];
        	} else {
	            $query = "select name from plugin where id = " . intval($matches[4]);
	            if (!$rs = & $conn->Execute($query)) {
	                print $conn->ErrorMsg();
	            }
		        if (!$rs->EOF) $plugin = Util::htmlentities($rs->fields["name"]);            
		        if ($plugin == "") $plugin = intval($matches[4]);
		        $_SESSION["_plugins"][$matches[4]] = $plugin;
		    }
        }
        if($htmlResult){
            $red = 0;
            $color = "black";
        }
        // para coger
        $date = $matches[2];
        $event_date = $matches[2];
        $tzone = intval($matches[10]);
        $txtzone = Util::timezone($tzone);
        $event_date_uut = Util::get_utc_unixtime($conn,$event_date);

        // Special case: old events
        $ctime = explode("/",$logfile); $storehour = $ctime[count($ctime)-3]; // hours
        $event_time = strtotime($ctime[count($ctime)-6]."-".$ctime[count($ctime)-5]."-".$ctime[count($ctime)-4]." ".$ctime[count($ctime)-3].":00:00");
        //$warning = ($storehour-$eventhour != 0) ? "<a href='javascript:;' style='text-decoration:none' txt='"._("Date may not be normalized")."' class='scriptinfotxt'><img src='../pixmaps/warning.png' align='absmiddle' border='0' style='margin-left:3px;margin-right:3px'></a>" : "";
        $warning = (abs($event_time - strtotime($event_date)) > $config['delay_seconds_todate']) ? "<a href='javascript:;' style='text-decoration:none' txt='"._("Date may not be normalized")."' class='scriptinfotxt'><img src='../pixmaps/warning.png' align='absmiddle' border='0' style='margin-left:3px;margin-right:3px'></a>" : "";
        
        // Event date timezone
		if ($tzone!=0) $event_date = gmdate("Y-m-d H:i:s",$event_date_uut+(3600*$tzone));
        
        // Apply user timezone
		if ($tz!=0) $date = gmdate("Y-m-d H:i:s",$event_date_uut+(3600*$tz));
	
		//echo "$date - $event_date - $tzone - $tz<br>";
		
        // fin para coger
        if($htmlResult){
            $sensor = $matches[5];
            $src_ip = $matches[6];
            $country = strtolower(geoip_country_code_by_addr($gi, $src_ip));
            $country_name = geoip_country_name_by_addr($gi, $src_ip);
            if ($country) {
                $country_img_src = " <img src=\"/ossim/pixmaps/flags/" . $country . ".png\" alt=\"$country_name\" title=\"$country_name\">";
            } else {
                $country_img_src = "";
            }
                    $dst_ip = $matches[7];
                    $country = strtolower(geoip_country_code_by_addr($gi, $dst_ip));
            $country_name = geoip_country_name_by_addr($gi, $dst_ip);
            if ($country) {
                $country_img_dst = " <img src=\"/ossim/pixmaps/flags/" . $country . ".png\" alt=\"$country_name\" title=\"$country_name\">";
            } else {
                $country_img_dst = "";
            }

                    $homelan_src = (($match_cidr = Net::is_ip_in_cache_cidr($conn, $src_ip)) || in_array($src_ip, $hosts_ips)) ? " <a href='javascript:;' class='scriptinfo' style='text-decoration:none' ip='$src_ip'><img src=\"".Host::get_homelan_icon($src_ip,$icons,$match_cidr,$conn)."\" border=0></a>" : "";
                    $homelan_dst = (($match_cidr = Net::is_ip_in_cache_cidr($conn, $dst_ip)) || in_array($dst_ip, $hosts_ips)) ? " <a href='javascript:;' class='scriptinfo' style='text-decoration:none' ip='$dst_ip'><img src=\"".Host::get_homelan_icon($dst_ip,$icons,$match_cidr,$conn)."\" border=0></a>" : "";

            $src_port = $matches[8];
            $dst_port = $matches[9];
                    // resolv hostname
                    $sensor_name = ($sensors[$sensor]!="") ? $sensors[$sensor] : $sensor;
                    $src_ip_name = ($sensors[$src_ip]!="") ? $sensors[$src_ip] : (($hosts[$src_ip]!="") ? $hosts[$src_ip] : $src_ip);
                    $dst_ip_name = ($sensors[$dst_ip]!="") ? $sensors[$dst_ip] : (($hosts[$dst_ip]!="") ? $hosts[$dst_ip] : $dst_ip);

                    $src_div = "<div id=\"$src_ip;$src_ip_name\" class=\"HostReportMenu\" style=\"display:inline\">";
                    $dst_div = "<div id=\"$dst_ip;$dst_ip_name\" class=\"HostReportMenu\" style=\"display:inline\">";
                    
            // Event info tooltip
            //$event_info = "<a href='javascript:;' style='text-decoration:none' class='eventinfo' txt='".wordwrap(Util::htmlentities($matches[12],ENT_QUOTES),170," ",true)."'>$total_counter</a>";
            $event_info = "$total_counter";
                    
            // Solera DeepSee API
            $solera = "";
            if ($_SESSION["_solera"]) {
                $solera = "<a href=\"javascript:;\" onclick=\"solera_deepsee('$start','$end','$src_ip','$src_port','$dst_ip','$dst_port','tcp')\"><img src='../pixmaps/solera.png' border='0' align='absmiddle'></a>";
            }
            
            $niplugin = str_replace("<", "[", $plugin);
            $niplugin = str_replace(">", "]", $niplugin);
            
            $link_incident = "";
            if ( Session::menu_perms("MenuIncidents", "IncidentsOpen") )
                $link_incident = "<a class='greybox' title='"._('New Alarm ticket')."' href=\"../incidents/newincident.php?nohmenu=1&" . "ref=Alarm&" . "title=" . urlencode($niplugin . " Event") . "&" . "priority=1&" . "src_ips=$src_ip&" . "event_start=$date&" . "event_end=$date&" . "src_ports=$src_port&" . "dst_ips=$dst_ip&" . "dst_ports=$dst_port" . "&hmenu=Tickets&smenu=Tickets\">" . "<img src='../pixmaps/script--pencil.png' width='12' alt='"._('New Alarm ticket')."' border='0' align='absmiddle'/></a>";
          
            $line = "<tr ".(($htmlResult) ? "onclick=\"show_details('details$i');\" class=\"green_tr\"" : "").(($colort%2==0) ? " style=\"background-color: #F2F2F2\"" : "").">
                                    
            <td class='nobborder' style='border-right:1px solid #FFFFFF;text-align:center;' nowrap='nowrap'>" . $warning . $link_incident. " $event_info $solera</td>";
            
            if ($from_remote) {
            	$line .= "<td class='nobborder' style='border-right:1px solid #FFFFFF;text-align:center;' nowrap><table class='transparent' align='center'><tr><td class='nobborder' style='padding-left:5px;padding-right:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;border:0px;background-color:#".$_SESSION['logger_colors'][$current_server]['bcolor'].";color:#".$_SESSION['logger_colors'][$current_server]['fcolor']."'>$current_server</td></tr></table></td>";
            }
            
            // compare real date with timezone corrected date
			if ($event_date==$matches[2] || $event_date==$date) {
            	$line .= "<td class='nobborder' style='border-right:1px solid #FFFFFF;text-align:center;padding-left:5px;padding-right:5px;' nowrap>" . Util::htmlentities($date) . "</td>";
			} else {
            	$line .= "<td class='nobborder' style='border-right:1px solid #FFFFFF;text-align:center;padding-left:5px;padding-right:5px;' nowrap> <a href='javascript:;' txt='" ._("Event date").": ". Util::htmlentities("<b>".$event_date."</b><br>"._("Timezone").": <b>$txtzone</b>") . "' class='scriptinfotxt' style='text-decoration:none'>" . Util::htmlentities($date) . "</a></td>";
			}
			
       		$line.= "<td class='nobborder' style='border-right:1px solid #FFFFFF;padding-left:5px;padding-right:5px;text-align:center;'><a href=\"#\" onclick=\"javascript:SetSearch('<b>plugin</b>=' + this.innerHTML)\"\">$plugin</a></td>";
            $line.="<td class='nobborder' style='border-right:1px solid #FFFFFF;padding-left:5px;padding-right:5px;text-align:center;'>";
            $line.= "<a href=\"#\" alt=\"$sensor\" title=\"$sensor\" onclick=\"javascript:SetSearch('<b>sensor</b>=$sensor');return false\"\">" . Util::htmlentities($sensor_name) . "</a></td><td class='nobborder' style='background-color:$rep_src_bgcolor;border-right:1px solid #FFFFFF;text-align:center;padding-left:5px;padding-right:5px;' nowrap>$rep_src_icon$src_div";
            $line.= "<a href=\"#\" alt=\"$src_ip\" title=\"$src_ip\" onclick=\"javascript:SetSearch('<b>src</b>=$src_ip_name');return false\"\">" . Util::htmlentities($src_ip_name) . "</a></div>:";
            $line.= "<a href=\"#\" onclick=\"javascript:SetSearch('<b>src_port</b>=$matches[8]');return false\">" . Util::htmlentities($matches[8]) . "</a>$country_img_src $homelan_src</td><td class='nobborder' style='background-color:$rep_dst_bgcolor;border-right:1px solid #FFFFFF;text-align:center;padding-left:5px;padding-right:5px;' nowrap>$rep_dst_icon$dst_div";
            $line.= "<a href=\"#\" alt=\"$dst_ip\" title=\"$dst_ip\" onclick=\"javascript:SetSearch('<b>dst</b>=$dst_ip_name');return false\"\">" . Util::htmlentities($dst_ip_name) . "</a></div>:";
            $line.= "<a href=\"#\" onclick=\"javascript:SetSearch('<b>dst_port</b>=$matches[9]');return false\">" . Util::htmlentities($matches[9]) . "</a>$country_img_dst $homelan_dst</td>";
            if ($alt) {
                $color = "grey";
                $alt = 0;
            } else {
                $color = "blue";
                $alt = 1;
            }
            $verified = - 1;
            $data = $matches[12];
            if ($signature != '') {
                $sig_dec = base64_decode($signature);
                $pk = ($logger_servers_keys[$current_server_ip]!="") ? $logger_servers_keys[$current_server_ip] : file_get_contents(str_replace("file://","",$config["pubkey"]));
                $pub_key = openssl_pkey_get_public($pk); // openssl_pkey_get_public openssl_get_publickey
                $verified = openssl_verify($data, $sig_dec, $pub_key);
                //error_log("$current_server_ip = $data\n$signature\n$pk\n$verified\n\n", 3, "/tmp/validate");
            }
            $encoded_data = base64_encode($data);
            $data = "<td class='nobborder' style='border-right:1px solid #FFFFFF;padding-left:5px;padding-right:5px;'>";
        }
        // para coger
		$data_out  = $matches[12];
        $hm        = 50;
        
        if(strlen($matches[12])>$hm) {
            $data .= "<table class='transparent' width='100%'><tr><td class='nobborder'><div style='overflow:hidden;height:20px;width:100%'>".$matches[12]."</div></td><td class='nobborder' width='20'><a href=\"javascript:;\">[...]</a></td></tr></table>";
        } else {
        	$data .= $matches[12];
        }
         
        $moredata  = "";
        // fin para coger
        // change ,\s* or #\s* adding blank space to force html break line
        // para coger
        $matches[12] = preg_replace("/([\,\#])([^\d;])\s*/", "\\1 \\2", $matches[12]);
        // fin para coger
        if($htmlResult){
                //$matches[12] = wordwrap($matches[12], 60, " ", true);
                $matches[12] = preg_replace("/(;) (&#\d+;)/",";\\1\\2",$matches[12]);
                $matches[12] = preg_replace("/(&) (#\d+;)/","\\1\\2",$matches[12]);
                $matches[12] = preg_replace("/(&#) (\d+;)/","\\1\\2",$matches[12]);
                $matches[12] = preg_replace("/(&#\d+) (\d+;)/","\\1\\2",$matches[12]);
                $matches[12] = preg_replace("/(&#\d+) (;)/","\\1\\2",$matches[12]);
                $matches[12] = preg_replace("/(&#\d+;) (&)/","\\1\\2",$matches[12]);
                $words = preg_split("/[\=\|\s\t:;,\"\']+/", $matches[12]);
                $pspace=" ";
                foreach($words as $piece) {
                    // Chinese html-entities chars
                    if (preg_match("/(.*)(&#\d+)$/",$piece,$fnd)) {
                    	$clean_piece = $fnd[1].$fnd[2].";";
                    	$space = "";
                    } else {
                    	$clean_piece = (($pspace=="") ? " " : "").$piece;
                    }                    
                    $clean_piece = Util::htmlentities($clean_piece);
                    $red = 0;
                    foreach($color_words as $word) {
                        if (stripos($clean_piece, $word)) {
                            $red = 1;
                            break;
                        }
                    }
                    if (preg_match("/(&gt;)|(&lt;)/",$clean_piece)) {
                    	$onclick = (preg_match("/(&gt;)|(&lt;)/",$clean_piece)) ? ";" : "SetSearch('<b>data</b>=" . $clean_piece . "')";
                    	$cursor = "not-allowed";
                    } else {
                    	$onclick = (preg_match("/(&gt;)|(&lt;)/",$clean_piece)) ? ";" : "SetSearch('<b>data</b>=" . trim($clean_piece) . "')";
                    	$cursor = "pointer";
                    }
                    if ($demo) {
	                    $moredata .= $clean_piece . $space;
                    } else {
	                    if ($red) {
	                        $moredata.= "<span style=\"color:red\" onmouseover=\"this.style.color = 'green';this.style.cursor='$cursor';\" onmouseout=\"this.style.color = 'red';this.style.cursor = document.getElementById('cursor').value;\" onclick=\"javascript:" . $onclick . "\">" . $clean_piece . "$space</span>";
	                    } else {
	                        $moredata.= "<span style=\"color:$color\" onmouseover=\"this.style.color = 'green';this.style.cursor='$cursor';\" onmouseout=\"this.style.color = '$color';this.style.cursor = document.getElementById('cursor').value;\" onclick=\"javascript:" . $onclick . "\">" . $clean_piece . "$space</span>";
	                    }
	                }
	                $pspace = $space;
                }
                if ($verified >= 0) {
                    if ($verified == 1) {
                        $moredata.= '<img src="' . $config["verified_graph"] . '" height=15 width=15 title="Valid" />';
                    } else if ($verified == 0) {
                        $moredata.= '<img src="' . $config["failed_graph"] . '" height=15 width=15 title="Wrong" />';
                    } else {
                        $moredata.= '<img src="' . $config["error_graph"] . '" height=15 width=15 title="Error" />';
                        $moredata.= openssl_error_string();
                    }
                }
        }
        if($htmlResult){
        	if ($debug) $data.= '<br><pre>'.htmlentities($res).'</pre>';
            //if(strlen($matches[12])>$hm)
            //    $data .= "&nbsp;&nbsp;<a href=\"javascript:;\">[...]</a>";
            $data.= '</td><td class="nobborder" style="text-align:center;padding-left:5px;padding-right:5px;" nowrap><a href="javascript:;" class="thickbox" rel="AjaxGroup" onclick="validate_signature(\''.$encoded_data.'\',\''.$start.'\',\''.$end.'\',\''.$logfile.'\',\''.$signature.'\',\''.$current_server_ip.'\');return false" style="font-family:arial;color:gray"><img src="../pixmaps/lock-small.png" align="absmiddle" border=0><i>'._("Validate").'</i></a>';
            $data.= "</td>";
            $line.= $data;
        }
        $line .= "</tr>";
        // para coger
        $inc_counter++;
        // fin para coger

		if (is_dir($config["searches_dir"]) && isset($export) && $export != "noExport") {
			fputs($outfile,"$inc_counter,$date,$plugin,".Util::htmlentities($matches[5]).",".Util::htmlentities($matches[6]).":".Util::htmlentities($matches[8]).",".Util::htmlentities($matches[7]).":".Util::htmlentities($matches[9]).",$data_out\n");
			$logarr[urldecode($logfile)]++;
		}
		
		$cont[$current_server]++;
	    if($htmlResult){
	        print $line;
	        $colort++;
	        $total_counter++;
            ?>
            <tr id="details<?php echo $i; ?>" style="display:none">
            <td class="nobborder">&nbsp;</td>
            <?php
            $product_type = "-";
            $sid_name     = "";
            $category     = "-";
            $sub_category = "";
            $query1 = "SELECT ps.name,p.source_type,c.name as cname,sc.name as scname FROM plugin_sid ps LEFT JOIN category c ON c.id=ps.category_id LEFT JOIN subcategory sc ON sc.id=ps.subcategory_id AND sc.cat_id=ps.category_id, plugin p WHERE p.id=ps.plugin_id AND p.name LIKE '$plugin' AND ps.sid = $plugin_sid";
            if (!$rs = & $conn->Execute($query1)) {
                print $conn->ErrorMsg();
            }
	        if (!$rs->EOF) {
	           $product_type = $rs->fields["source_type"];    
		       $sid_name     = $rs->fields["name"];
	           $category     = $rs->fields["cname"];  if ($category=="")     $category     = "-";      
		       $sub_category = $rs->fields["scname"]; if ($sub_category=="") $sub_category = "-";
		    }
            ?>
            <td class="nobborder left" colspan="<?php echo ($from_remote) ? "7": "6" ?>">
                <table width="100%" style="border: 1px solid rgb(170, 170, 170); -moz-border-radius: 0px 0px 0px 0px; background: url('../pixmaps/fondo_hdr2.png') repeat-x scroll 0% 0% transparent;">
                    <tr style="padding:2px">
                        <td width="20%" class="header" style="background-position: center bottom !important;"><?php echo _("Event type"); ?></td>
                        <td width="80%" class="header" style="background-position: center bottom !important;"><?php echo _("Event Detail"); ?></td>
                    </tr>
                    <tr><td class="nobborder">
                    <?php
                    if( strstr($sid_name, $plugin)!="" || preg_match("/ossec.*/",$sid_name) ) {
                        echo Util::htmlentities($sid_name);
                    }
                    else {
                        echo Util::htmlentities($plugin)." / ".Util::htmlentities($sid_name);
                    }
                     ?></td>
                        <td class="left nobborder"><?php echo $moredata; ?></td>
                    </tr>
                    <?php
                    if ($product_type != "-" || $category != "-") {
                    ?>
                        <tr>
                            <td colspan="2" class="nobborder">
                                <table width="100%" cellpadding="0" cellspacing="1">
                                    <TR>
									   <TD class="header"><?php echo gettext("Product Type") ?></TD>
									   <TD class="header"><?php echo gettext("Category") ?></TD>
									   <TD class="header"><?php echo gettext("Sub-Category") ?></TD>
									</TR>
									<TR>
									  <TD class="center nobborder"><?php echo Util::htmlentities($product_type) ?></TD>
									  <TD class="center nobborder"><?php echo Util::htmlentities($category) ?></TD>
									  <TD class="center nobborder"><?php echo Util::htmlentities(str_replace("_"," ",$sub_category)) ?></TD>
									</TR>
                                </table>
                            </td>
                        </tr>
                    <?php
                    }
                    ?>                    
                    <?php
                    if (count($userdata)>0) {
                    ?>
                        <tr>
                            <td colspan="2" class="nobborder">
                                <table width="100%" cellpadding="0" cellspacing="1">
                                    <tr>
                                        <td class="header"><?php echo _("userdata1")?></td><td class="header"><?php echo _("userdata2")?></td>
                                        <td class="header"><?php echo _("userdata3")?></td><td class="header"><?php echo _("userdata4")?></td>
                                        <td class="header"><?php echo _("userdata5")?></td><td class="header"><?php echo _("userdata6")?></td>
                                        <td class="header"><?php echo _("userdata7")?></td><td class="header"><?php echo _("userdata8")?></td><td class="header"><?php echo _("userdata9")?></td>
                                    </tr>
                                    <tr>
                                    <?php 
                                    for ($iu=1;$iu<=9;$iu++) {
                                        if($userdata[$iu]!="") {
                                        ?>
                                        <td class="nobborder" style="text-align:center;"><?php echo Util::htmlentities($userdata[$iu]); ?></td>
                                        <?php
                                        }
                                        else{
                                        ?>
                                        <td class="center nobborder"><?php echo "<span style='color:gray'><i>"._("empty")."</i></span>" ?></td>
                                        <?php
                                        }
                                    }
                                    ?>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    <?php
                    }
                    ?>
					<?php
                    if ($rep_src_icon != "" || $rep_dst_icon != "") {
                    ?>
                        <tr>
                            <td colspan="2" class="nobborder">
                                <table width="100%" cellpadding="0" cellspacing="1">
                                    <TR><TD rowspan="2" class="header2" width="80" ALIGN=CENTER><?php echo _("Reputation") ?></TD>
									   <TD class="header"><?php echo gettext("Source Address") ?></TD>
									   <TD class="header"><?php echo gettext("Priority") ?></TD>
									   <TD class="header"><?php echo gettext("Reliability") ?></TD>
									   <TD class="header"><?php echo gettext("Activity") ?></TD>
									   <td></td>
									   <TD class="header"><?php echo gettext("Destination Address") ?></TD>
									   <TD class="header"><?php echo gettext("Priority") ?></TD>
									   <TD class="header"><?php echo gettext("Reliability") ?></TD>
									   <TD class="header"><?php echo gettext("Activity") ?></TD>
									</TR>
									<TR>
									  <TD class="plfield" style="background-color:<?php echo $rep_src_bgcolor ?>" nowrap><?php echo $ret_src_icon . Util::htmlentities($src_ip_name) ?></TD>
									  <TD class="plfield" style="background-color:<?php echo $rep_src_bgcolor ?>" nowrap><?php echo ($rep_prio_src != "") ? $rep_prio_src : "<span style='color:gray'><i>"._("empty")."</i></span>"; ?></TD>
									  <TD class="plfield" style="background-color:<?php echo $rep_src_bgcolor ?>" nowrap><?php echo ($rep_rel_src != "") ? $rep_rel_src : "<span style='color:gray'><i>"._("empty")."</i></span>"; ?></TD>
									  <TD class="plfield" style="background-color:<?php echo $rep_src_bgcolor ?>" nowrap><?php echo ($rep_act_src != "") ? $rep_act_src : "<span style='color:gray'><i>"._("empty")."</i></span>"; ?></TD>
									  <td></td>
									  <TD class="plfield" style="background-color:<?php echo $rep_dst_bgcolor ?>" nowrap><?php echo $ret_dst_icon . Util::htmlentities($dst_ip_name) ?></TD>
									  <TD class="plfield" style="background-color:<?php echo $rep_dst_bgcolor ?>" nowrap><?php echo ($rep_prio_dst != "") ? $rep_prio_dst : "<span style='color:gray'><i>"._("empty")."</i></span>"; ?></TD>
									  <TD class="plfield" style="background-color:<?php echo $rep_dst_bgcolor ?>" nowrap><?php echo ($rep_rel_dst != "") ? $rep_rel_dst : "<span style='color:gray'><i>"._("empty")."</i></span>"; ?></TD>
									  <TD class="plfield" style="background-color:<?php echo $rep_dst_bgcolor ?>" nowrap><?php echo ($rep_act_dst != "") ? $rep_act_dst : "<span style='color:gray'><i>"._("empty")."</i></span>"; ?></TD>
									</TR>
                                </table>
                            </td>
                        </tr>
                    <?php
                    }
                    ?>
					<?php
                    if ($idm_user_src != "" || $idm_user_dst != "" || $idm_dom_src != "" || $idm_dom_dst != "" || $idm_host_src != "" || $idm_host_dst != "" || $idm_mac_src != "" || $idm_mac_dst != "") {
                    ?>
                        <tr>
                            <td colspan="2" class="nobborder">
                                <table width="100%" cellpadding="0" cellspacing="1">
                                    <TR><TD rowspan="2" class="header2" width="80" ALIGN=CENTER><?php echo _("IDM") ?></TD>
									   <TD class="header"><?php echo gettext("Src Username") ?></TD>
									   <TD class="header"><?php echo gettext("Src Domain") ?></TD>
									   <TD class="header"><?php echo gettext("Src Hostname") ?></TD>
									   <TD class="header"><?php echo gettext("Src MAC") ?></TD>
									   <td></td>
									   <TD class="header"><?php echo gettext("Dst Username") ?></TD>
									   <TD class="header"><?php echo gettext("Dst Domain") ?></TD>
									   <TD class="header"><?php echo gettext("Dst Hostname") ?></TD>
									   <TD class="header"><?php echo gettext("Dst MAC") ?></TD>
									</TR>
									<TR>
									  <TD class="plfield" nowrap><?php echo ((!empty($idm_user_src)) ? $idm_user_src : "<span style='color:gray'><i>"._("empty")."</i></span>") ?></TD>
									  <TD class="plfield" nowrap><?php echo ((!empty($idm_dom_src)) ? $idm_dom_src : "<span style='color:gray'><i>"._("empty")."</i></span>" ) ?></TD>
									  <TD class="plfield" nowrap><?php echo ((!empty($idm_host_src)) ? $idm_host_src : "<span style='color:gray'><i>"._("empty")."</i></span>") ?></TD>
									  <TD class="plfield" nowrap><?php echo ((!empty($idm_mac_src)) ? $idm_mac_src : "<span style='color:gray'><i>"._("empty")."</i></span>") ?></TD>
									  <td></td>
									  <TD class="plfield" nowrap><?php echo ((!empty($idm_user_dst)) ? $idm_user_dst : "<span style='color:gray'><i>"._("empty")."</i></span>") ?></TD>
									  <TD class="plfield" nowrap><?php echo ((!empty($idm_dom_dst)) ? $idm_dom_dst : "<span style='color:gray'><i>"._("empty")."</i></span>") ?></TD>
									  <TD class="plfield" nowrap><?php echo ((!empty($idm_host_dst)) ? $idm_host_dst : "<span style='color:gray'><i>"._("empty")."</i></span>") ?></TD>
									  <TD class="plfield" nowrap><?php echo ((!empty($idm_mac_dst)) ? $idm_mac_dst : "<span style='color:gray'><i>"._("empty")."</i></span>") ?></TD>
									</TR>
                                </table>
                            </td>
                        </tr>
                    <?php
                    }
                    ?>
                </table>
            </td>
            <td class="nobborder">&nbsp;</td>
            </tr>
            <?php
	    }
    } else {
    	if ($debug) echo "<tr><td class='nobborder' colspan='9'>WARNING: NOT MATCHING EVENT</td></tr>";
    }
    $i++;
}
print "</table>";

if (is_dir($config["searches_dir"]) && isset($export) && $export != "noExport") {
	fclose ($outfile);
	$logs = "";
	foreach ($logarr as $key=>$val) {
		$logs .= $key."\n";
	}
	fputs($loglist,$logs);
	fclose ($loglist);
}

} // FROM: if (has_results()) {

if (!has_results($num_lines)) {
    echo '<table align="center" width="100%" style="background:transparent"><tr>
    	<td class="noborder" style="color:red;font-size:14px">'._("No Data Found Matching Your Criteria").'</td>
		<td width="150px" class="nobborder" style="text-align:right;" nowrap>'.($uniqueid=="NOINDEX" ? _("Raw query") : _("Indexed query"))." "._("parsing time").': <b>'.$totaltime.'</b> '._("seconds").'</td>
		</tr>
		</table>';
} else {
?>
<center>
<?php if ($from_remote) { ?>
<?php echo _("Showing ")."<b>".($offset+1)."</b> - <b>".($offset+$top)."</b>"._(" <b>first</b> events")._(" for <b>each server</b>")." (<b>".(($offset*$num_servers)+1)."</b> - <b>".(($offset*$num_servers)+count($result))."</b> total)" ?>.&nbsp;
<?php } else { ?>
<?php echo _("Showing ")."<b>".($offset+1)."</b> - <b>".($offset+count($result))."</b>"._(" events") ?>.&nbsp;
<?php } ?>
<?php if ($offset > 0) { ?>
<a href="javascript:DecreaseOffset(<?php echo GET('top')?>);" style="color:black"><?php echo ($from_remote) ? "<< "._("Fetch the previous ") : "<< "._("Previous ")?><?php echo "<b>".GET('top')."</b>" ?></a>
<?php } ?>
<?php if ($has_next_page) { //if($num_lines > $offset + 50){
    echo ($offset != 0) ?  "&nbsp;<b>|</b>&nbsp;" : "";
?>
<a href="javascript:IncreaseOffset(<?php echo GET('top')?>);" style="color:black"><?php echo ($from_remote) ? _("Fetch the next ") : _("Next ")?><?php echo "<b>".GET('top')."</b> >>" ?></a>
<?php } ?>
</center>
<br>
<?php } 
$db->close($conn);
?>
</div>
</body>
<script type="text/javascript">$("#pbar").progressBar(100);parent.SetFromIframe($("#processcontent").html(),"<?php echo $a ?>","<?php echo $start ?>","<?php echo $end ?>","<?php echo $sort_order ?>")</script>
