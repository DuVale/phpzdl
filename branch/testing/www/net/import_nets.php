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
*    -  print_form($msg_errors)
*    -  print_results($res)
*    -  is_allowed_format ($format)
*    -  csv_net_allowed($net, $sensors_list)
*    -  csv_host_allowed($ip)
*    -  import_assets_csv($filename, $ignore_invalid_characters)
* Classes list:
*/

require_once ('classes/Session.inc');
require_once ('classes/CIDR.inc');
require_once ('classes/Net.inc');
require_once ('classes/Net_scan.inc');
require_once ('ossim_conf.inc');
require_once ('classes/Sensor.inc');
require_once ('classes/Util.inc');

require_once ('classes/Host.inc');
require_once ('ossim_db.inc');

Session::logcheck("MenuPolicy", "PolicyHosts");

//Functions 

function print_form($msg_errors=''){
		
	if (is_array ($msg_errors) && !empty ($msg_errors) )
			echo "<div class='ossim_error'>"._("We found the following errors:")."<div style='padding-left: 25px;'>".implode("<div class='error_sep'", $msg_errors)."</div></div>";
	?>
	
	<form name='form_csv' id='form_csv' method='post' enctype='multipart/form-data'>			
		
		<table class='transparent' id='form_container_tit'>
			<tr><td class='headerpr'><?php echo _("Import Networks from CSV")?></td></tr>
		</table>
		
		<table align='center' id='form_container'>
			<tr>
				<td class='nobborder'>
					<div class='file_csv'>
						<input name='file_csv' id='file_csv' type='file' size='35'/>
						<span style='margin-left: 5px;'>
							<input name='iic' id='iic' type='checkbox'/><span style='margin-left: 2px;'><?php echo _("Ignore invalid characters")?> *</span>
						</span>
					</div>
				</td>
			</tr>
			<tr>
				<td class='nobborder'>
					<span style='font-weight: bold;'><?php echo _("Format allowed")?>:</span><br/>
					<div id='format'><?php echo _('"Netname"**;"CIDRs(CIDR1,CIDR2,... )"**;"Description";"Asset value";"Sensors(Sensor1,Sensor2,...)"**')?></div>
				</td>
			</tr>
			<tr>
				<td class='nobborder'>
					<div style='padding-top: 10px'>
						<span style='font-weight: bold; font-style: normal'><?php echo _("Example") ?>:</span><br/>
						<div id='example'>"Net_1";"192.168.10.0/24,192.168.9.0/24";"<?php echo _("Short description of net");?>";"2";"192.168.10.2,192.168.10.3"</div>
					</div>
				</td>
			</tr>
			<tr>
				<td class='nobborder'>
					<div style='padding-top: 30px'>(*)&nbsp;&nbsp;&nbsp;&nbsp; <?php echo _("Characters allowed: A-Z, a-z, 0-9, ., :, _ and -");?></div>
					<div style='padding: 5px 0px'>(**)&nbsp;&nbsp; <?php echo _("Values marked with (*) are mandatory");?>.</div>
				</td>
			</tr>
			<tr>
				<td class='nobborder'><div id='send'><input type='submit' value='<?php echo _("Import") ?>' name='submit'class='button'></div></td>
			</tr>
		</table>
	</form>
	
	<?php		
}

function print_results($res){
	
	$num_errors = count($res['line_errors']);
	?>
	
	<table class='transparent' id='result_container_tit'>
		<tr><td class='headerpr' colspan='2'><?php echo _("Import Results");?></td></tr>
	</table>
	
	<table align='center' id='result_container'>
		<tr>
			<td class='nobborder'>
				<table class='transparent' style='width: 400px;'>
					<tr>
						<td class='line nobborder'>
							<span class='label' valign='absmiddle'><?php echo _("Read Assests lines")?>:</span>
						</td>
						<td class='nobborder result'><?php echo $res['read_line']?></td>
					</tr>
					<tr>
						<td class='line nobborder' valign='absmiddle'>
							<span class='label ok'><?php echo _("Correct Assests lines")?>:</span>
						</td>
						<td class='nobborder result ok'><?php echo $res['read_line']-$num_errors?></td>
					</tr>
					<tr>
						<td class='line nobborder' valign='absmiddle'>
							<span class='label error'><?php echo _("Wrong Assests lines")?>:</span>
							<?php
							
							if ( $num_errors > 0 )
							{ 
								echo "<a class='td_hide' id='show_error' onclick=\"javascript: show_errors();\">["._("View errors")."]</a>";
							}
							?>		
						</td>
						<td class='nobborder result error'><?php echo $num_errors ?></td>
					</tr>
				</table>
			</td>
		</tr>
		
		
		<?php
		
		if ( $num_errors > 0 )
		{
			?>
			<tr>
				<td class='nobborder' id='errors_csv'>
					<table id='list_errors'>
						<tr>
							<th><?php echo _("Line")?></th>
							<th><?php echo _("Description")?></th>
						</tr>
					<?php
						$index = 0;
						foreach ($res['line_errors'] as $k => $v) 
						{
							$txt_errors = null;
							
							foreach ($v as $j => $error)
							{
								$txt_errors .=" <table class='transparent'>
													<tr>
														<td class='left noborder' style='width: 70px;'>"._($error[0]).": </td>
														<td class='left error noborder'>"._($error[1])."</td>
													</tr>
												</table>"; 
							}
							$color = ($index % 2 == 0 ) ? '#FFFFFF' : '#F2F2F2';
							?>
							<tr style='background: <?php echo $color?>'>
								<td class='nobborder line_error' valign='absmiddle'><?php echo $k?></td>
								<td class='nobborder line_desc'>
									<div><?php echo $txt_errors ?></div>
								</td>		
							</tr>
							<?php
							$index++;
						}
						?>
					</table>					
				</td>
			</tr>
			<?php
		} 
		?>
		
	</table>
	<?php
	
}	

function is_allowed_format ($type_uf){
	$types = '/force-download|octet-stream|text|csv|plain|spreadsheet|excel|comma-separated-values/';
	
	if (preg_match ($types, $type_uf, $match) == false)
		return false;
	else
		return true;
}


function csv_get_allowed_sensors($conn)
{
	$allowed_sensors = array();
	
	if ( Session::am_i_admin() )
	{
		$aux_sensor_list = Sensor::get_list($conn);
		
		if ( is_array($aux_sensor_list) && count($aux_sensor_list) > 0 )
		{
			foreach ( $aux_sensor_list as $k => $v)
				$allowed_sensors[] = $v->get_ip();
		}
	}
	else
	{
		if ( isset($_SESSION['_allowed_sensors']) )
			$allowed_sensors = $_SESSION['_allowed_sensors'];
	}
	
	return $allowed_sensors;
}

function csv_net_allowed($my_net, $sensors_list)
{
	if ( Session::am_i_admin() )
		return true;
	else
	{
		$allowed_nets = array();
		if ( isset($_SESSION['_allowed_nets']) )
			$allowed_nets = $_SESSION['_allowed_nets'];
		else
			return false;
			
		if( empty($allowed_nets) )	
		{
			if ( empty($sensors_list) )
				return false;
		}
		else
		{
			$cidrs = explode(",", $my_net);
			
			foreach($cidrs as $cidr)
			{
				if ( !in_array($cidr, $allowed_nets) )
					return false;
			}
		}
				
		return true;
	}
}

function clean_iic($string)
{
	$str  = Util::removeaccents($string);
	$size = strlen($str);
	
	$val_str = OSS_NET_NAME;
	$res     = null;
	
	for($i=0; $i<$size; $i++)
	{
		if ( !preg_match("/[^$val_str]/", $str[$i]) )
			$res .= $str[$i];
	}
		
	return $res;
}


function import_assets_csv($filename, $iic){
	
	require_once('classes/Util.inc');
	$response= array();
	$db   = new ossim_db();
	$conn = $db->connect();
		
		
	if (($content = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) == false )
	{
		$response ['file_errors'] = "Failed to read file";
		$response ['status'] = false;
		return $response;
	}
	else
	{
		foreach ($content as $k => $v)
			$data[] = explode('";"', $v);
	}
	
	$cont = 0;
	
	ini_set('max_execution_time', 180);
	ids_valid($data);	
	
	if (count($data) <= 0)
	{
		$response ['file_errors'] = _("Incompatible file format");
		$response ['status'] = false;
		return $response;
	}
	
	$my_allowed_sensors = csv_get_allowed_sensors($conn);
	
	if ( empty($my_allowed_sensors) )
	{
		$response ['file_errors'] = _("You need at least one sensor assigned");
		$response ['status'] = false;
		return $response;
	}
	
	
	foreach ($data as $k => $v)
	{
		$response ['status'] = true;
		$response ['read_line'] = $cont;
		$cont++;
		
		
		if (count($v) != 5)
		{
			$response ['line_errors'][$cont][] = array("Line", _("Format not allowed"));
			$response ['status'] = false;
		}
		
		$param = array();
		
		foreach ($v as $i => $field)
		{
			$parameter = trim($field);
			$pattern = '/^\"|\"$|^\'|\'$/';
			$param[] = preg_replace($pattern, '', $parameter);
		}
		
		
		//Netname
		if ( !empty($iic) )
			$param[0] = clean_iic($param[0]);
			
		if ( !ossim_valid($param[0], OSS_NOECHARS, OSS_NET_NAME, 'illegal:' . _("Netname")) )
		{
			$response ['line_errors'][$cont][] = array("Net", ossim_get_error_clean());
			$response ['status'] = false;
		}
					
		
		//CIDRs
		$$param[1] = preg_replace("/[\n\r\t]+/", "", $param[1]);
		if ( !ossim_valid($param[1], OSS_IP_CIDR, 'illegal:' . _("CIDR")) )
		{
			$response ['line_errors'][$cont][] = array("Hostname", ossim_get_error_clean());
			$response ['status'] = false;
			ossim_clean_error();
		}
		
				
		//Description
		
		if ( !ossim_valid($param[2], OSS_NULLABLE, OSS_AT, OSS_TEXT, 'illegal:' . _("Description")) )
		{
			$response ['line_errors'][$cont][] = array("Description", ossim_get_error_clean());
			$response ['status'] = false;
			ossim_clean_error();
		}
		
		//Asset
		if ( $param[3] == '' )
			$param[3] = 2;
		else
		{
			if ( !ossim_valid($param[3], OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("Asset value")) )
			{
				$response ['line_errors'][$cont][] = array("Asset", ossim_get_error_clean());
				$response ['status'] = false;
				ossim_clean_error();
			}
		}
					
				
		//Sensors
		$sensors      = array();
		$sensors_list = array();
		
		if ( !empty ($param[4]) )
		{
			$list         = explode(",", $param[4]);
									
			$sensors_list = array_intersect($list, $my_allowed_sensors);
						
			if ( !empty($sensors_list) )
			{
				foreach ($sensors_list as $sensor)
					$sensors[] = Sensor::get_sensor_name($conn, $sensor);
			}
			else
			{
				$response ['line_errors'][$cont][] = array("Sensors", _("You need at least one allowed sensor"));
				$response ['status'] = false;
			}
		}
		else
		{
			$response ['line_errors'][$cont][] = array("Sensors", _("Column Sensors is empty"));
			$response ['status'] = false;
		}
		
		
		//Check if a net is allowed
		
		if ( csv_net_allowed($param[1], $sensors_list) == false )
		{
			$response ['line_errors'][$cont][] = array("Net", _("Net")." ".$param[0]." "._("not allowed. You don't have permission to import this net"));
			$response ['status'] = false;
		}	
		
		
		if ( $response ['status'] == true )
		{
			//Parameters
			$net_name    = $param[0];
			$cidrs       = $param[1];
			$asset       = $param[3];
			$threshold_c = 30;
            $threshold_a = 30;
			$rrd_profile = "";
			$alert       = 0;
			$persistence = 0;
			$icon        = "";			
			
			if ( mb_detect_encoding($param[2]." ",'UTF-8,ISO-8859-1') == 'UTF-8')
				$descr = mb_convert_encoding($param[2],'HTML-ENTITIES', 'UTF-8');
			else
				$descr = $param[2];
					
						
			if ( Net::get_ips_by_name($conn,$net_name, false) == '' ) 
				Net::insert($conn, $net_name, $cidrs, $asset, $threshold_c, $threshold_a, $rrd_profile, $alert, $persistence, $sensors, $descr, $icon);
			else
			{
				Net::update($conn, $net_name, $cidrs, $asset, $threshold_c, $threshold_a, $rrd_profile, $alert, $persistence, $sensors, $descr, $icon);
				Net_scan::delete($conn, $net_name, 3001);
    			Net_scan::delete($conn, $net_name, 2007);
			}
    	}
		
	}
	
	$response ['read_line'] = $cont;
	return $response; 
	
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <title><?php echo _("Import Nets from CSV")?></title>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	
	<style type="text/css">
		a {cursor:pointer; font-weight: bold;}
		#container{ width: 90%; text-align: center; margin: 5px auto 0px auto; padding: 10px;}
		#form_container {width:100%;}
		#form_container_tit {width:100%; margin-top: 10px;}
		#form_container td { padding-left: 15px;}
		
		#result_container_tit {width:100%; margin-top: 30px;}
		#result_container     {width:100%;}
		#result_container .line { height: 25px; padding-left: 15px; text-align: left; width: 70%;}
		.file_csv { padding: 15px 0px 15px 15px; width: 70%; margin: auto; text-align: center;}
		#send{ padding: 20px 0px 20px 0px; text-align: center; margin:auto;}
		#format, #example { padding: 5px 0px 0px 20px; font-style: italic;}
				
		.error { color: #C92323;}
		.error_sep { font-weight: bold; padding-top: 3px; text-align: left;}
		.ok { font-weight: bold; color: #179D53;}
		.label {font-weight: bold; text-align: left; font-size: 13px;}
		.result {font-weight: bold; text-align: right; font-size: 14px; padding-right: 20px;}
		.ossim_error { text-align: left; width: auto;}
		
		#list_errors { font-weight: bold; font-size: 12px; width:80%; margin: 0px auto 15px auto;}
		#list_errors tr{ padding-bottom: 4px;}
		#list_errors th{ padding: 3px;}
		.line_error {padding: 2px 3px; text-align: center;}
		.line_desc div { padding-left: 5px;}
		#errors_csv { display: none;}
		.p5 { padding: 5px 0px;}
	</style>
	
	<script type='text/javascript'>
		function  show_errors()
		{
			if ( $("#show_error").attr("class") == 'td_hide')
			{
				$('#errors_csv').show();
				$("#show_error").text("[<?php echo _("Hide errors")?>]")
				$('#show_error').removeClass();
				$('#show_error').addClass("td_show");
			}
			else
			{
				$('#errors_csv').hide();
				$("#show_error").text("[<?php echo _("Show errors")?>]")
				$('#show_error').removeClass();
				$('#show_error').addClass("td_hide");
			}
			
		}
	</script>
	
</head>

<body>
	<?php include ("../hmenu.php"); ?>

	<div id='container'>
	
	<?php
	
	$path         = "../tmp/";
	$current_user = md5(Session::get_session_user());
	$file_csv     = $path.$current_user."_assest_import.csv";
	$msg_errors   = '';
				
	if ( isset($_POST['submit']) && !empty($_POST['submit']) )
	{
		if ( !empty ($_FILES['file_csv']['name']) )
		{
			if ($_FILES['file_csv']['"error'] > 0 )
			{
				$error         = true;
				$msg_errors[]  = _("Unable to upload file. Return Code: ").$_FILES["file_csv"]["error"];
			}
			else
			{
				if ( !is_allowed_format ($_FILES['file_csv']['type']) )
				{
					$error         = true;
					$msg_errors[]  = _("File type \"".$_FILES['file_csv']['type']."\" not allowed");
				}
								
				
				if ( @move_uploaded_file($_FILES["file_csv"]["tmp_name"], $file_csv ) == false  && !$error)
				{
					$error        = true;
					$msg_errors[] = ( empty ($msg_errors) ) ? _("Unable to upload file") : $msg_errors;
				}
			}
									
			if ($error == false)
				$res = import_assets_csv ($file_csv, $_POST['iic']); 
			
			@unlink($file_csv);
		}
		else
			$msg_errors[]  = _("Filename is empty");
	}
	
	
	if ( isset ($res['status']) && !empty($res['file_errors']) )
		$msg_errors[]  = $res['file_errors'];
	
	
	print_form($msg_errors);
	
		
	if ( isset ($res['status']) && empty($res['file_errors']) )
	{
		Util::clean_json_cache_files("(policy|vulnmeter|hostgroup)");
		print_results($res);
	}
		

	?>
	
	</div>
	
</body>
</html>

