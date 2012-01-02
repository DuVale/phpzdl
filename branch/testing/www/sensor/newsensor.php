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
require_once ('classes/Security.inc');
require_once ('ossim_db.inc');
require_once ('classes/Sensor.inc');
require_once ('classes/Util.inc');

Session::logcheck("MenuConfiguration", "PolicySensors");

$error = false;

$sname       = POST('sname');
$ip          = POST('ip');
$priority    = POST('priority');
$descr	     = POST('descr');
$port	     = POST('port');
$tzone       = POST('tzone');

$validate = array (
	"sname"     => array("validation"=>"OSS_HOST_NAME",                       "e_message" => 'illegal:' . _("Sensor name")),
	"ip"        => array("validation"=>"OSS_IP_ADDR", 		  				  "e_message" => 'illegal:' . _("Ip")),
	"priority"  => array("validation"=>"OSS_DIGIT",           				  "e_message" => 'illegal:' . _("Priority")),
	"port"      => array("validation"=>"OSS_PORT",                            "e_message" => 'illegal:' . _("Port number")),
	"tzone"     => array("validation"=>"OSS_DIGIT, OSS_SCORE, OSS_DOT, '\+'", "e_message" => 'illegal:' . _("Timezone")),
	"descr"     => array("validation"=>"OSS_NULLABLE, OSS_AT, OSS_TEXT",      "e_message" => 'illegal:' . _("Description")));
	
if ( GET('ajax_validation') == true )
{
	$validation_errors = validate_form_fields('GET', $validate);
	if ( $validation_errors == 1 )
		echo 1;
	else if ( empty($validation_errors) )
		echo 0;
	else
		echo $validation_errors[0];
		
	exit();
}
else
{
	$validation_errors = validate_form_fields('POST', $validate);
	
	if ( ( $validation_errors == 1 ) ||  (is_array($validation_errors) && !empty($validation_errors))  )
	{
		$error = true;
				
		$message_error = array();
		
		if ( is_array($validation_errors) && !empty($validation_errors) )
			$message_error = array_merge($message_error, $validation_errors);
		else
		{
			if ($validation_errors == 1)
				$message_error [] = _("Invalid send method");
		}
						
	}	
	
	if ( POST('ajax_validation_all') == true )
	{
		if ( is_array($message_error) && !empty($message_error) )
			echo utf8_encode(implode( "<br/>", $message_error));
		else
			echo 0;
		
		exit();
	}
}

if ( $error == true )
{
	$_SESSION['_sensor']['sname']    = $sname;
	$_SESSION['_sensor']['ip']       = $ip;
	$_SESSION['_sensor']['descr']    = $descr;
	$_SESSION['_sensor']['priority'] = $priority;
	$_SESSION['_sensor']['tzone']    = $tzone;
}

?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link type="text/css" rel="stylesheet" href="../style/style.css"/>
</head>

<body>

<?php
if (POST('withoutmenu') != "1")
{
    include ("../hmenu.php");
    $get_param = "ip=$ip&name=".urlencode($sname);
}
else
    $get_param = "ip=$ip&name=".urlencode($sname)."&withoutmenu=1";

if (POST('insert'))
{
	if ( $error == true)
	{
		$txt_error = "<div>"._("We Found the following errors").":</div><div style='padding:10px;'>".implode( "<br/>", $message_error)."</div>";			
		Util::print_error($txt_error);	
		Util::make_form("POST", "newsensorform.php?".$get_param);
		die();
	}
		
    $db = new ossim_db();
    $conn = $db->connect();
		
   	Sensor::insert($conn, $sname, $ip, $priority, $port, $tzone, $descr);
    Sensor::set_properties($conn, $ip, 0, 1, 1, 0);
    Sensor::insert_vuln_nessus_servers($conn, $ip, $name, 0);
    Sensor::update_vuln_nessus_servers($conn, $ip, $sname, "ossim", "ossim", "9390", "5", 1);
    
    // Change ossim.config nessus_host variable if needed
    require_once ('ossim_conf.inc');
	$conf        = $GLOBALS["CONF"];
	$nessus_host = $conf->get_conf("nessus_host", FALSE);
    $all_sensors = Sensor::get_all_ips($conn);
	if ($nessus_host=="" || !isset($all_sensors[$nessus_host]))
	{
		require_once 'classes/Config.inc';
		$upt = new Config();
		$upt->update("nessus_host" , $ip);
	}

	$db->close($conn);
    Util::clean_json_cache_files("sensors");
}

if ( isset($_SESSION['_sensor']) )
    unset($_SESSION['_sensor']);

if ( $_SESSION["menu_sopc"]=="SIEM Components" && POST('withoutmenu') != "1" ) 
{ 
	?>
	<p><?php echo gettext("Sensor successfully inserted"); ?></p>
	<script type='text/javascript'>setTimeout("document.location.href='sensor.php'",1000)</script>
	<? 
}
else 
{
	?>
	<script type='text/javascript'>document.location.href="modifysensorform.php?<?php echo $get_param; ?>&update=1"</script>
	<?php
}
?>
    </body>
</html>