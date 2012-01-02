<?php
/*****************************************************************************
*
*   Copyright (c) 2007-2011 AlienVault
*   All rights reserved.
*
****************************************************************************/
ini_set('memory_limit', '1024M');
set_time_limit(300);
require_once ('classes/Session.inc');
require_once ('classes/Reputation.inc');
require_once ('classes/Util.inc');

$conf     = $GLOBALS["CONF"];
$version  = $conf->get_conf("ossim_server_version", FALSE);
$prodemo  = ( preg_match("/pro|demo/i",$version) ) ? true : false;

$Reputation = new Reputation();

$type     = intval(GET("type"));

//$rep_file = trim(`grep "reputation" /etc/ossim/server/config.xml | perl -npe 's/.*\"(.*)\".*/$1/'`);

if ( $Reputation->existReputation() ) {

   ?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html lang="en">
	<head>
        <title> <?php echo gettext("OSSIM Framework"); ?> - <?php echo gettext("IP reputation"); ?> </title>
        <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1"/>
        <META http-equiv="Pragma" content="no-cache">
		<script language="javascript" type="text/javascript" src="../js/jqplot/jquery-1.4.2.min.js"></script>
		<!--[if IE]><script language="javascript" type="text/javascript" src="../js/jqplot/excanvas.js"></script><![endif]-->
		<link rel="stylesheet" type="text/css" href="../js/jqplot/jquery.jqplot.css" />
		<script language="javascript" type="text/javascript" src="../js/jqplot/jquery.jqplot.min.js"></script>
		<script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.pieRenderer.js"></script>
    </head>
	<style type="text/css">
		
		#chart .jqplot-point-label {
		  border: 1.5px solid #aaaaaa;
		  padding: 1px 3px;
		  background-color: #eeccdd;
		}
		.jqplot-data-label {
			font-size: 12px;
		}
		body,html {
		  font-family:arial;
		}
		.ne  { font-size:12px; color:black; font-weight:normal }
		.neb { font-size:12px; color:black; font-weight:bold }
		.gr  { font-size:12px; color:gray; font-weight:normal }
		.grb { font-size:12px; color:gray;font-weight:bold }
	</style>
    <?php
    list($ips,$cou,$order,$total) = $Reputation->get_data($type);    
    $data = array();
    $data1 = array();
    $order = array_splice($order,0,10);
	foreach($order as $type => $ocurrences) $data[] = "['$type [".Util::number_format_locale($ocurrences,0)."]',$ocurrences]";
	$data  = implode(",", $data);
    ?>	
	<script>
		$(document).ready(function(){
					
			$.jqplot.config.enablePlugins = true;
			
			s1 = [<?php echo $data; ?>];
			
			plot1 = $.jqplot('chart', [s1], {
				grid: {
					drawBorder: false, 
					drawGridlines: false,
					background: 'rgba(255,255,255,0)',
					shadow:false
				},
				<?php if ($colors!="") { ?>seriesColors: [ <?php echo $colors; ?> ], <?php } ?>
				axesDefaults: {
					
				},
				seriesDefaults:{
		            padding:10,
					renderer:$.jqplot.PieRenderer,
					rendererOptions: {
						diameter: '170',
						showDataLabels: true,
						dataLabels: "value",
						dataLabelFormatString: '%d'
					}								
				},
				
				legend: {
					show: true,
					rendererOptions: {
						numberCols: 2
					},
					location:'e'
				}
			});

			s2 = [<?php echo $data1; ?>];
			
			plot2 = $.jqplot('chart1', [s2], {
				grid: {
					drawBorder: false, 
					drawGridlines: false,
					background: 'rgba(255,255,255,0)',
					shadow:false
				},
				<?php if ($colors!="") { ?>seriesColors: [ <?php echo $colors; ?> ], <?php } ?>
				axesDefaults: {
					
				},
				seriesDefaults:{
		            padding:10,
					renderer:$.jqplot.PieRenderer,
					rendererOptions: {
						diameter: '170',
						showDataLabels: true,
						dataLabels: "value",
						dataLabelFormatString: '%d'
					}								
				},
				
				legend: {
					show: true,
					rendererOptions: {
						numberCols: 2
					},
					location:'e'
				}
			});

		});				
	</script>   
	<body style="overflow:hidden" scroll="no">
		<table border="0" cellpadding="0" cellspacing="0" width="100%">
		<tr>
		<td align="center" valign="top">
			<p style="margin:0px;text-align:center;font-family:arial;color:gray;size:14px;font-weight:bold"><?php echo _("General statistics")?></p><br/>
			<table border="0" cellpadding="0" cellspacing="0" width="100%">
			<tr><td class="ne"><?php echo _("Number of IPs in the database")?></td><td class="grb"><?php echo Util::number_format_locale($total,0)?></td></tr>
			<tr><td class="ne"><?php echo _("Latest update")?></td><td class="grb"><?php echo gmdate("Y-m-d H:i:s",filemtime($Reputation->rep_file))?></td></tr>
			</table>
			<br/>
		</td>		
		<td align="center" valign="top">
			<p style="margin:0px;text-align:center;font-family:arial;color:gray;size:14px;font-weight:bold"><?php echo _("Malicious IPs by Activity")?></p>
			<div id="chart" style="width:400px; height:220px"></div>
		</td>
		<td align="center" valign="top" width="400">
			<p style="margin:0px;text-align:center;font-family:arial;color:gray;size:14px;font-weight:bold"><?php echo _("Top 10 Countries")?></p>
			<br/>
			<table border="0" cellpadding="1" cellspacing="1" width="65%" align="center">
			<tr>
				<td class="neb"><?php echo _("Country")?></td>
				<td class="neb"><?php echo _("IPs #")?></td>
			</tr>
			<?php
			$cou = array_splice($cou,0,10);			
			foreach ($cou as $c => $value) { 
				$info = explode(";",$c);
				$flag = "";
				if ($info[1]!="") $flag = "<img src='../pixmaps/".($info[1]=="1x1" ? "" : "flags/") . strtolower($info[1]).".png' border='0' width='16' height='11' title='".$info[0]."'>&nbsp;";
			?>
			<tr>
				<td class="gr"><?php echo $flag . $info[0] ?></td>
				<td class="grb"><?php echo Util::number_format_locale($value,0); ?></td>
			</tr>
			<? 
			}
			?>
			</table>
		</td>
		</tr>
	</body>
	</html>
<?php
}
?>


