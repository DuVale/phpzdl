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
ini_set("max_execution_time","720");

if ($argv[1] != "") {
	$path_class = '/usr/share/ossim/include/:/usr/share/ossim/www/sem';
	ini_set('include_path', $path_class);
}
require_once ('classes/Session.inc');
if ($argv[1] == "") Session::logcheck("MenuEvents", "ControlPanelSEM");
require_once ('ossim_db.inc');
require_once ('classes/Util.inc');
require_once ('classes/Security.inc');
#require_once ('../graphs/charts.php');
require_once ('forensics_stats.inc');
$db = new ossim_db();
$conn = $db->connect();
$monthnames = array(
"Jan" => "01",
"Feb" => "02",
"Mar" => "03",
"Apr" => "04",
"May" => "05",
"Jun" => "06",
"Jul" => "07",
"Aug" => "08",
"Sep" => "09",
"Oct" => "10",
"Nov" => "11",
"Dec" => "12"
);

$only_json = 0;
if ($argv[1] != "") {
	$gt = $argv[1];
	$cat = $argv[2];
	$allowed_sensors = $argv[3];
	$tz = $argv[4];
	$only_json = 1;
} else {
	$gt = $_SESSION["graph_type"];
	$cat = $_SESSION["cat"];
	$allowed_sensors = implode("|",$_SESSION["_allowed_sensors"]);
	$tz = Util::get_timezone();
}
$tzc = Util::get_tzc($tz);
$range = "";
$remote_data = array();

//Target all years by default
if ($gt == "") $gt = "all";

// REMOTE GRAPH MERGE
if ($_GET['ips'] != "") {
	$ip_to_name = array();
	foreach ($_SESSION['logger_servers'] as $name=>$ip) {
		$ip_to_name[$ip] = $name;
	}
	$ip_list = $_GET['ips'];
	ossim_valid($ip_list, OSS_DIGIT, OSS_PUNC, 'illegal:' . _("ip_list"));
	if (ossim_error()) {
	    die(ossim_error());
	}
	$cmd = "sudo /usr/share/ossim/www/sem/fetchremote_graph.pl '$gt' '$cat' '$allowed_sensors' $ip_list '$tz'";
	//echo $cmd;exit;
	$aux = explode("\n",`$cmd`);
	if (count($aux) > 2) {
		echo "<br><br><center><font style='font-family:arial;font-size:12px'>"._("An <b>error</b> has occured fetching remote data. Please <b>check Log configuration</b> in remote machines.")."</font></center>";
		?><script type="text/javascript">parent.document.getElementById('testLoading2').style.display='none';</script><?php
		exit;
	}
	$string = trim($aux[0]);
	$remote_data_aux = json_decode($string);
	$remote_data_aux2 = (array) $remote_data_aux;
	foreach ($remote_data_aux2 as $key=>$val) {
		$remote_data[$key] = (array) $val;
	}
	//print_r($remote_data);
// LOCAL GRAPH DATA
} else {
	// Range with 'last'
	if ($gt == "last_year") {
		$date_from = gmdate("Y-m-d", gmdate("U") - ((24 * 60 * 60) * 365));
		$date_to = gmdate("Y-m-d");
		$range = "month";
	} elseif ($gt == "last_month") {
		$date_from = gmdate("Y-m-d", gmdate("U") - ((24 * 60 * 60) * 31));
		$date_to = gmdate("Y-m-d");
		$range = "day";
	} elseif ($gt == "last_week") {
		$date_from = gmdate("Y-m-d", gmdate("U") - ((24 * 60 * 60) * 7));
		$date_to = gmdate("Y-m-d");
		$range = "day";
	// Specific date range: extract from $cat
	} elseif ($gt == "year") {
		$date_from = $cat."-01-01";
		$date_to = $cat."-12-31";
		$range = "month";
	} elseif ($gt == "month") {
		$tmp = explode(",", $cat);
	    $t_year = str_replace(" ", "", $tmp[1]);
	    $t_month = str_replace(" ", "", $tmp[0]);
		$date_from = $t_year."-".$monthnames[$t_month]."-01";
		$date_to = $t_year."-".$monthnames[$t_month]."-31";
		$range = "day";
	} elseif ($gt == "day") {
		if (preg_match("/(...) (\d+)\, (\d+)/",$cat,$found)) {
			$t_day = (preg_match("/^\d$/",$found[2])) ? "0".$found[2] : $found[2];
			$t_month = $monthnames[$found[1]];
			$t_year = $found[3];
			$date_from = $t_year."-".$t_month."-".$t_day." 00:00:00";
			$date_to = $t_year."-".$t_month."-".$t_day." 23:59:59";
		} else {
			$date_from = date("Y-m-d")." 00:00:00";
			$date_to = date("Y-m-d")." 23:59:59";
		}
		$range = "hour";
	} elseif ($gt == "all") { // 5 years back
		$date_from = gmdate("Y-m-d", gmdate("U") - ((24 * 60 * 60) * 235 * 5));;
		$date_to = date("Y")."-12-31";
		$range = "year";
	}
	$chart['link_data'] = array(
	    'url' => "javascript:parent.graph_by_date( _col_, _row_, _value_, _category_, _series_, '" . $t_year . "', '" . $t_month . "', '".$t_day."')",
	    'target' => "javascript"
	);
	
	$data = get_range_csv($date_from,$date_to,$range,$tz,$allowed_sensors);
	
	/* OLD VERSION CODE (Saved for checking generalV meaning...)
	 
	$general = array();
	$generalV = array();
	$i = 0;
	$j = 0;
	
	$general[$j][$i++] = "NULL";
	
	
	$generalV = $general;
	foreach ($generalV as $k=>$v) {
		foreach ($v as $k1=>$v1) {
			if ($v1>0) { $generalV[$k][$k1] = Util::number_format_locale($v1,0);}
		}
	}
	
	$chart['chart_data'] = $data;
	$chart['chart_value_text'] = $generalV;
	*/
}

// IF CALLED BY PROMPT ONLY PRINT DATA (For remote logger graph merge)
if ($only_json) {
	$json = array('chart_data' => $data);
	echo json_encode($json);
	exit;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<HTML>
<HEAD>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<META HTTP-EQUIV="pragma" CONTENT="no-cache">
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
<TITLE><?=_("Forensics Console")?> : <?=_("Query Results")?></TITLE>
<style type="text/css">
BODY {
    color: #111111;
    font-family: arial, helvetica, sans-serif;
    font-size: 12px;
    margin: 0px;
    padding: 0px;
}
a { color: #555555; text-decoration:none; }
a:hover { text-decoration: underline; }
.tickLabel { font-size:11px; font-weight:bold; color:#111111; }
.tooltipLabel { font-size:11px; color:#111111; }
</style>
<script src="../js/jquery-1.3.2.min.js" type="text/javascript"></script>
<!--[if IE]><script language="javascript" type="text/javascript" src="../js/excanvas.pack.js"></script><![endif]-->
<script src="../js/jquery.flot.pack.js" type="text/javascript"></script>
<script src="../js/jquery.flot.stack.js" type="text/javascript"></script>
<script type="text/javascript">
	var monthnames = new Array;
	monthnames['Jan'] = "01";
	monthnames['Feb'] = "02";
	monthnames['Mar'] = "03";
	monthnames['Apr'] = "04";
	monthnames['May'] = "05";
	monthnames['Jun'] = "06";
	monthnames['Jul'] = "07";
	monthnames['Ago'] = "08";
	monthnames['Sep'] = "09";
	monthnames['Oct'] = "10";
	monthnames['Nov'] = "11";
	monthnames['Dec'] = "12";
<?  flush(); sleep(1);
    $row = ($gt=="year" || $gt=="last_year") ? 2 : (($gt=="month" || $gt=="last_month" || $gt=="last_week") ? 3 : ($gt=="day" ? 4 : 1));
    $salto = ($gt=="month" || $gt=="last_month") ? 4 : (($gt=="day") ? 2 : 1);
    $with = ($gt=="month") ? 1 : (($gt=="day") ? 0 : 0);
?>
	<?php if ($_GET['ips'] != "") { ?>
	var links = []; <? $flag = 0; foreach ($remote_data as $ip=>$arr) { if($flag) continue; $flag = 1; $i = 0; foreach ($arr['chart_data'] as $key => $val) { echo "    links[".$i."] = '$key';\n"; $i++; } } ?>
	<?php } else { ?>
	var links = []; <?php $i = 0; foreach ($data as $key => $val) { echo "    links[".$i."] = '$key';\n"; $i++; } ?>
	<?php } ?>

	function get_tick_date(str) {
		var year_str = "";
        var month_str = "";
        var day_str = "";
		// Case showing years
		if (str.match(/^\d\d\d\d/)) {
			var aux = str.split(" ");
			year_str = aux[0];
		// Case showing months
		} else if (str.match(/^...\, \d\d\d\d/)) {
			var aux = str.split(", ");
			year_str = aux[1];
			month_str = monthnames[aux[0]];
		// Case showing days
		} else if (str.match(/... \d\d\, \d\d\d\d/)) {
			var aux = str.split(", ");
			var aux2 = aux[0].split(" ");
			year_str = aux[1];
			month_str = monthnames[aux2[0]];
			day_str = aux2[1];
		// Case showing hours
		} else if (str.match(/\d\d\/\d\d\/\d\d\d\d at/)) {
			var aux = str.split(" at");
			var aux2 = aux[0].split("/");
			year_str = aux2[2];
			month_str = aux2[1];
			day_str = aux2[0];
		}
		return {year : year_str, month : month_str, day : day_str};
	}
    function showTooltip(x, y, row, col, contents) {
		links[row] = links[row].replace(/\(h\)( GMT)*.*/,"(h) GMT <?php echo $tzc ?>");

        // Get year/month/day from label text for links
		var tick_date = get_tick_date(links[row]);
		
		if (x + 130 > $(document).width()) x = x - 100;
		$('<div id="tooltip" class="tooltipLabel" onclick="parent.graph_by_date( \''+col+'\', <?=$row?>, 0, \''+links[row]+'\', \'\', \''+tick_date.year+'\',\''+tick_date.month+'\', \''+tick_date.day+'\')">'+links[row]+': <a href="javascript:parent.graph_by_date( \''+col+'\', <?=$row?>, 0, \''+links[row]+'\', \'\', \''+tick_date.year+'\', \''+tick_date.month+'\', \''+tick_date.day+'\')" style="font-size:10px;">' + contents + '</a></div>').css( {
            position: 'absolute',
            display: 'none',
            top: y - 10,
            left: x + 5,
            border: '1px solid #ADDF53',
            padding: '5px 7px 5px 7px',
            'background-color': '#CFEF95',
            opacity: 0.80
        }).appendTo("body").fadeIn(200);
    }
    function formatNmb(nNmb){
        var sRes = ""; 
        for (var j, i = nNmb.length - 1, j = 0; i >= 0; i--, j++)
            sRes = nNmb.charAt(i) + ((j > 0) && (j % 3 == 0)? ",": "") + sRes;
        return sRes;
    }
    
    $(document).ready(function(){
    	parent.document.getElementById('testLoading2').style.display='none'
		<?php if ($_GET['ips'] != "") { ?>
		var options = {
	        series: {stack: 0,
	                 lines: {show: false, steps: false },
	                 bars: {show: true, barWidth: 0.9, align: 'center'}
	                 },       
	        xaxis: { tickDecimals:0, ticks: [<? $flag = 0; foreach ($remote_data as $ip=>$arr) { if($flag) continue; $flag = 1; $i = 0; foreach ($arr['chart_data'] as $key=>$val) { if ($i > 0) echo ","; if ($i % $salto == $with) { ?>[<?php echo $i ?>,"<?php echo preg_replace("/(\d\d)\/\d\d\/\d\d\d\d/","\\1",$key) ?>"]<?php } else { ?>[<?php echo $i ?>,""]<?php } ?><?php $i++; } }?>] },
	        grid: { color: "#8E8E8E", labelMargin:3, borderWidth:2, backgroundColor: "#EDEDED", tickColor: "#D2D2D2", hoverable:true, clickable:true}, shadowSize:1
	    };
		var data = [
            <?php foreach ($remote_data as $ip=>$arr) { ?>
            {color: "#<?php echo $_SESSION['logger_colors'][$ip_to_name[$ip]]['bcolor']; ?>", label: '<?php echo $ip_to_name[$ip] ?>', data: [<? $i = 0; foreach ($arr['chart_data'] as $key=>$val) { if ($i > 0) echo ","; ?>[<?=$i?>,<?=$val?>]<? $i++; } ?>]},
            <?php } ?>
        ];
		<?php } else { ?>
        var options = {
            bars: {
                show: true,
                barWidth: 0.9, // in units of the x axis
	            fill: true,
                fillColor: null,
                align: "center" // or "center"
            },
			points: { show:false, radius: 2 },
            legend: { show: false },
            yaxis: { autoscale:true },
            xaxis: { tickDecimals:0, ticks: [<?php $i = 0; foreach ($data as $key=>$val) { if ($i > 0) echo ","; if ($i % $salto == $with) { ?>[<?php echo $i ?>,"<?php echo preg_replace("/(\d\d)\/\d\d\/\d\d\d\d/","\\1",$key) ?>"]<?php } else { ?>[<?php echo $i ?>,""]<?php } ?><?php $i++; } ?>] },
            grid: { color: "#8E8E8E", labelMargin:3, borderWidth:2, backgroundColor: "#EDEDED", tickColor: "#D2D2D2", hoverable:true, clickable:true}, shadowSize:1 
        };
        var data = [{
            color: "rgb(173,223,83)",
            label: "Events",
            lines: { show: false, fill: true},
            data: [<?php $i = 0; foreach ($data as $key=>$val) { if ($i > 0) echo ","; ?>[<?php echo $i ?>,<?php echo $val ?>]<? $i++; } ?>]
        }];
        <?php } ?>
        var plotarea = $("#plotareaglobal");
        plotarea.css("height", 150);
        plotarea.css("width", (window.innerWidth || document.body.clientWidth)-40);
        
        plotarea.toggle();
        $.plot( plotarea , data, options );
        var previousPoint = null;
        $("#plotareaglobal").bind("plothover", function (event, pos, item) {
            if (item) {
                if (previousPoint != item.datapoint) {
                    previousPoint = item.datapoint;
                    $("#tooltip").remove();
                    //console.log(item)
                    var x = item.datapoint[0].toFixed(0), y = item.datapoint[1].toFixed(0);
                    if (item.datapoint[2].toFixed(0)>=0) y = y - item.datapoint[2].toFixed(0);
                    showTooltip(item.pageX, item.pageY, x, y, formatNmb(y+'')+' '+item.series.label);
                }
            }
            else {
                $("#tooltip").remove();
                previousPoint = null;
            }
        });
		$("#plotareaglobal").bind("plotclick", function (event, pos, item) {
			if (item) {
				var x = item.datapoint[0].toFixed(0), y = item.datapoint[1].toFixed(0);
				var tick_date = get_tick_date(links[x]);
				parent.graph_by_date(y, <?=$row?>, 0, links[x], '', tick_date.year, tick_date.month, tick_date.day);
            }
		});
    });
</script>
</HEAD>
<BODY>
<div id="plotareaglobal" style="text-align:center;margin:5px 0px 0px 20px;padding:0px;display:none;"></div>
</body>
</html>