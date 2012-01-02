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

require_once ('classes/Session.inc');
require_once ('classes/Host.inc');
require_once ('classes/Sensor.inc');
require_once ('ossim_db.inc');
require_once ('ossim_conf.inc');

//Export all hosts
if ( isset($_GET['get_data']) )
{
	$db   = new ossim_db();
	$conn = $db->connect();

	$csv       = array();
	$host_list = Host::get_list($conn, "", "");

	foreach($host_list as $host) 
	{
		$sensor_list = $host->get_sensors($conn);
		$sensors     = array();
		if ( is_array($sensor_list) )
		{
			foreach($sensor_list as $sensor) 
				$sensors[] = Sensor::get_sensor_ip($conn, $sensor->get_sensor_name());
				
			$str_sensors =  implode(",", $sensors);
		}	
		
		$descr = html_entity_decode($host->get_descr());
		
		if ( preg_match('/&#(\d{4,5});/', $descr) )
			$descr = mb_convert_encoding($descr, 'UTF-8', 'HTML-ENTITIES');
			
		
		$csv[] = '"'.$host->get_ip().'";"'.$host->get_hostname().'";"'.$host->get_fqdns().'";"'.$descr.'";"'.$host->get_asset().'";"'.$str_sensors.'";"'.$host->get_os($conn).'"';
	}
	
	$csv_data    		        = implode("\r\n", $csv); 
	$_SESSION['_csv_data_hosts'] = $csv_data;
	
	exit();
	
}
elseif ( isset($_GET['download_data']) )	
{	
	$output_name 		   = _("All_hosts") ."__" . gmdate("Y-m-d",time()) . ".csv";
	
	$csv_data = $_SESSION['_csv_data_hosts'];
	unset($_SESSION['_csv_data_hosts']);
	
	if ( !empty($csv_data) )
	{
		header("Cache-Control: must-revalidate");
		header("Pragma: must-revalidate");
		header("Content-type: text/csv");
		header("Content-disposition: attachment; filename=\"$output_name\";");
		header("Content-Transfer-Encoding: binary");
		header("Content-length: " . strlen($csv_data));
		
		echo $csv_data;
	}
	exit();
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <title><?php echo _("Export all hosts to CSV")?></title>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	
	<style type="text/css">
		a {cursor:pointer; font-weight: bold;}
		
		#container { 
			width: 90%;
			margin:auto;
			text-align:center;
			position: relative;
			height: 600px;
		}
		
		#loading {
			position: absolute; 
			width: 99%; 
			height: 99%; 
			margin: auto; 
			text-align: center;
			background: #FFFFFF;
			z-index: 10000;
		}
		
		#loading div{
			position: relative;
			top: 40%;
			margin:auto;
		}
		
		#loading div span{
			margin-left: 5px;
			font-weight: normal;
			font-size: 13px;	
		}
		
		
	</style>
	
	<script type='text/javascript'>
		
		function get_csv_data()
		{
			$.ajax({
				type: "GET",
				url: "export_hosts.php",
				data: "get_data=1",
				success: function(html){
							
					$("#export_csv").attr("action","export_hosts.php");
					$("#export_csv").html("<input type='hidden' name='download_data' value='1'/>");
                 	$("#export_csv").attr("target","downloads");
					$("#export_csv").submit();
					
					setTimeout('document.location.href = "host.php"', 1000);
				}
			});
		}
						
		$(document).ready(function() {
			setTimeout('get_csv_data()', 2000);	
		});
	</script>
	
</head>

<body>
	<?php include ("../hmenu.php"); ?>

	<div id='container'>
		<div id='loading'>
			<div>
				<img src='../pixmaps/loading3.gif' alt='<?php echo _("Exporting all hosts to CSV")?>'/>
				<span><?php echo _("Exporting all hosts to CSV")?>.</span>
				<span><?php echo _("Please, wait a few seconds")?>...</span>
			</div>
		</div>
	</div>
	
	<form name='export_csv' id='export_csv' action='' method='GET'></form>
	<iframe name='downloads' style='display:none'></iframe>
</body>

</html>