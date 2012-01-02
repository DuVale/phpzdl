<?php
require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
require_once 'classes/Util.inc';
require_once 'sensor_filter.php';
Session::logcheck("MenuControlPanel", "ControlPanelExecutive");

require_once 'ossim_db.inc';
$db = new ossim_db();
$conn = $db->connect();
session_write_close();

$ips = $urls = "";
$values = "";
$colors = '"#E9967A","#9BC3CF"';

$sensor_where = make_sensor_filter($conn,"a");
$range =  604800; // Week
$forensic_link = "forensics/base_qry_main.php?clear_allcriteria=1&time_range=week&time[0][0]=+&time[0][1]=>%3D&time[0][2]=".gmdate("m",$timetz-$range)."&time[0][3]=".gmdate("d",$timetz-$range)."&time[0][4]=".gmdate("Y",$timetz-$range)."&time[0][5]=&time[0][6]=&time[0][7]=&time[0][8]=+&time[0][9]=+&submit=Query+DB&num_result_rows=-1&time_cnt=1&sort_order=time_d&hmenu=Forensics&smenu=Forensics";
$forensic_ulink = "forensics/base_stat_alerts.php?clear_allcriteria=1&time_range=week&sort_order=occur_d&time[0][0]=+&time[0][1]=>%3D&time[0][2]=".gmdate("m",$timetz-$range)."&time[0][3]=".gmdate("d",$timetz-$range)."&time[0][4]=".gmdate("Y",$timetz-$range)."&time[0][5]=&time[0][6]=&time[0][7]=&time[0][8]=+&time[0][9]=+&hmenu=Forensics&smenu=Forensics";
$nodata_text = _("No IPs found");

switch(GET("type")) {       

    // Antivirus - Last Week
	case "virus":
		//$taxonomy = "AND (c.cat_id=12 AND c.id in (97))";
		$taxonomy = make_where($conn,array("Malware" => array("Spyware","Adware","Fake_Antivirus","KeyLogger","Trojan","Virus","Worm","Generic","Backdoor","Virus_Detected"), "Antivirus" => array("Virus_Detected")));
		$sqlgraph = "select count(a.sid) as num_events,inet_ntoa(a.ip_src) as name from snort.acid_event a,ossim.plugin_sid p LEFT JOIN ossim.subcategory c ON c.cat_id=p.category_id AND c.id=p.subcategory_id WHERE p.plugin_id=a.plugin_id AND p.sid=a.plugin_sid AND a.timestamp BETWEEN '".gmdate("Y-m-d H:i:s",gmdate("U")-$range)."' AND '".gmdate("Y-m-d H:i:s")."' $sensor_where $taxonomy group by a.ip_src order by num_events desc limit 10";
		//print_r($sqlgraph);
		if (!$rg = & $conn->Execute($sqlgraph)) {
		    print $conn->ErrorMsg();
		} else {
			$i=1;
		    while (!$rg->EOF) {
		        $values .= "[".$rg->fields["num_events"].",$i],"; $i++;
		        $ips .= "'".$rg->fields["name"]."',";
				//Ticket #2552: Not linkable graphic.
                //$urls .= "'../top.php?hmenu=".md5("Analysis")."&smenu=".md5("Forensics")."&url=".urlencode($forensic_link."&category%5B0%5D=12&category%5B1%5D=97&ip_addr[0][0]=+&ip_addr[0][1]=ip_src&ip_addr[0][2]=%3D&ip_addr[0][3]=".$rg->fields["name"]."&ip_addr[0][8]=+&ip_addr[0][9]=+&ip_addr_cnt=1")."',";
		        $rg->MoveNext();
		    }
		}
		$colors = '"#F08080"';
		break;
        

    // Top 5 promiscuous hosts - Last Week
	case "promiscuous":
		$sqlgraph = "select count(distinct(ip_dst)) as num_events,INET_NTOA(ip_src) as name from snort.acid_event a WHERE timestamp BETWEEN '".gmdate("Y-m-d H:i:s",gmdate("U")-$range)."' AND '".gmdate("Y-m-d H:i:s")."' $sensor_where group by ip_src having ip_src<>0 order by num_events desc limit 10";
		
		//print_r($sqlgraph);
		if (!$rg = & $conn->Execute($sqlgraph)) {
		    print $conn->ErrorMsg();
		} else {
			$i=1;
		    while (!$rg->EOF) {
		        $values .= "[".$rg->fields["num_events"].",$i],"; $i++;
		        $ips .= "'".$rg->fields["name"]."',";
                $urls .= "'../top.php?hmenu=".md5("Analysis")."&smenu=".md5("Forensics")."&url=".urlencode($forensic_link."&ip_addr[0][0]=+&ip_addr[0][1]=ip_src&ip_addr[0][2]=%3D&ip_addr[0][3]=".$rg->fields["name"]."&ip_addr[0][8]=+&ip_addr[0][9]=+&ip_addr_cnt=1")."',";
		        $rg->MoveNext();
		    }
		}
		$colors = '"#8EC336"';
		break;

    // Top 5 hosts with multiple events - Last Week
	case "unique":
		$sqlgraph = "select count(distinct plugin_id,plugin_sid) as num_events,INET_NTOA(ip_src) as name from snort.acid_event a WHERE timestamp BETWEEN '".gmdate("Y-m-d H:i:s",gmdate("U")-$range)."' AND '".gmdate("Y-m-d H:i:s")."' $sensor_where group by ip_src having ip_src<>0 order by num_events desc limit 10";
		
		//print_r($sqlgraph);
		if (!$rg = & $conn->Execute($sqlgraph)) {
		    print $conn->ErrorMsg();
		} else {
			$i=1;
		    while (!$rg->EOF) {
		        $values .= "[".$rg->fields["num_events"].",$i],"; $i++;
		        $ips .= "'".$rg->fields["name"]."',";
                $urls .= "'../top.php?hmenu=".md5("Analysis")."&smenu=".md5("Forensics")."&url=".urlencode($forensic_ulink."&ip_addr[0][0]=+&ip_addr[0][1]=ip_src&ip_addr[0][2]=%3D&ip_addr[0][3]=".$rg->fields["name"]."&ip_addr[0][8]=+&ip_addr[0][9]=+&ip_addr_cnt=1")."',";
		        $rg->MoveNext();
		    }
		}
		$colors = '"#80BEF0"';
		break;

	// Honeypot - Top 10 Source Assets
	case "honeypot_src":
		//$taxonomy = "AND (c.cat_id=12 AND c.id in (97))";
		$nodata_text .= _(" for <i>Honeypot</i>");
		$sqlgraph = "select count(a.sid) as num_events,inet_ntoa(a.ip_src) as name from snort.acid_event a,ossim.plugin_sid p WHERE p.plugin_id=a.plugin_id AND p.sid=a.plugin_sid AND p.category_id=19 AND a.timestamp BETWEEN '".gmdate("Y-m-d H:i:s",gmdate("U")-$range)."' AND '".gmdate("Y-m-d H:i:s")."' $sensor_where group by a.ip_src order by num_events desc limit 10";
		//print_r($sqlgraph);
		if (!$rg = & $conn->Execute($sqlgraph)) {
		    print $conn->ErrorMsg();
		} else {
			$i=1;
		    while (!$rg->EOF) {
		        $values .= "[".$rg->fields["num_events"].",$i],"; $i++;
		        $ips .= "'".$rg->fields["name"]."',";
                $urls .= "'../top.php?hmenu=".md5("Analysis")."&smenu=".md5("Forensics")."&url=".urlencode($forensic_link."&category%5B0%5D=19&ip_addr[0][0]=+&ip_addr[0][1]=ip_src&ip_addr[0][2]=%3D&ip_addr[0][3]=".$rg->fields["name"]."&ip_addr[0][8]=+&ip_addr[0][9]=+&ip_addr_cnt=1")."',";
		        $rg->MoveNext();
		    }
		}
		$colors = '"#F08080"';
		break;
        
	default:
		// ['Sony',7], ['Samsumg',13.3], ['LG',14.7], ['Vizio',5.2], ['Insignia', 1.2]
		$values = "0";
		$ips = "'"._("No IPs found")."'";
}
$values = preg_replace("/,$/","",$values);
$ips = preg_replace("/,$/","",$ips);
if ($values=="") {
	$values = "0";
	$ips = "'"._("No IPs found")."'";
}
$db->close($conn);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
<head>
	  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	  <title>Bar Charts</title>
	  <!--[if IE]><script language="javascript" type="text/javascript" src="../js/jqplot/excanvas.js"></script><![endif]-->
	  <link rel="stylesheet" type="text/css" href="../js/jqplot/jquery.jqplot.css" />
		
	  <!-- BEGIN: load jquery -->
	  <script language="javascript" type="text/javascript" src="../js/jqplot/jquery-1.4.2.min.js"></script>
	  <!-- END: load jquery -->
	  
	  <!-- BEGIN: load jqplot -->
	  <script language="javascript" type="text/javascript" src="../js/jqplot/jquery.jqplot.min.js"></script>
	  <script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.barRenderer.js"></script>
	  <script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.categoryAxisRenderer.min.js"></script>
	  <script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.pointLabels.min.js"></script>
	  <script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.canvasTextRenderer.js"></script>
	  <script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.canvasAxisTickRenderer.js"></script>  

	 
  <!-- END: load jqplot -->

	<style type="text/css">
		
		#chart .jqplot-point-label {
		  border: 1.5px solid #aaaaaa;
		  padding: 1px 3px;
		  background-color: #eeccdd;
		}

	</style>
<?php if ($value != "0") { ?>
	<script class="code" type="text/javascript">
	
		var links = [<?=$urls?>];

		function myClickHandler(ev, gridpos, datapos, neighbor, plot) {
            //mouseX = ev.pageX; mouseY = ev.pageY;
            url = links[neighbor.pointIndex];
            if (typeof(url)!='undefined' && url!='') top.frames['topmenu'].location.href = url;
        }
		var isShowing = -1;
		function myMoveHandler(ev, gridpos, datapos, neighbor, plot) {
			if (neighbor == null) {
	            $('#myToolTip').hide().empty();
	            isShowing = -1;
	        }
	        if (neighbor != null) {
	        	if (neighbor.pointIndex!=isShowing) {
	            	$('#myToolTip').html(neighbor.data[0]).css({left:gridpos.x+60, top:gridpos.y-5}).show();
	            	isShowing = neighbor.pointIndex
	            }
	        }
        }
        
		$(document).ready(function(){
		
			$.jqplot.config.enablePlugins = true;		
			$.jqplot.eventListenerHooks.push(['jqplotClick', myClickHandler]); 
			$.jqplot.eventListenerHooks.push(['jqplotMouseMove', myMoveHandler]);
			
			line1 = [<?=$values?>];
			
			plot1 = $.jqplot('chart', [line1], {
			    legend:{show:false},
			    seriesDefaults:{
			        renderer:$.jqplot.BarRenderer, 
			        rendererOptions:{barDirection:'horizontal', barPadding:2, barMargin:2}, 
			        shadowAngle:135
			    },
				series:[
			        { pointLabels:{ show: false }, shadow: false, renderer:$.jqplot.BarRenderer }
			    ],			        
			    <? if ($colors!="") { ?>seriesColors: [ <?=$colors?> ], <? } ?>                            
			    grid: { background: '#F5F5F5', shadow: false },
			    axes:{
			        yaxis:{
			        	renderer:$.jqplot.CategoryAxisRenderer,
			        	ticks:[<?=$ips?>]
			        }, 
			        xaxis:{min:0, tickOptions:{formatString:'%d'}}
			    }
			});
            
            $('#chart').append('<div id="myToolTip"></div>');

		});
		
	</script>
<?php } ?>
    
  </head>
	<body style="overflow:hidden" scroll="no">
		<?php if ($values != "0") { ?>
		<div id="chart" style="width:100%; height: 250px;"></div>
		<?php } else { ?>
		<table class="transparent" align="center"><tr><td class="center nobborder" style="font-family:arial;font-size:12px;background-color:white;padding-top:40px"><?php echo $nodata_text ?></td></tr></table>
		<?php } ?>
	</body>
</html>
