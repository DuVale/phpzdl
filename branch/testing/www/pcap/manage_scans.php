<?php
/*****************************************************************************
*
*   Copyright (c) 2007-2011 AlienVault
*   All rights reserved.
*
****************************************************************************/
ini_set("max_execution_time","300"); 

require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
require_once ('classes/Sensor.inc');
require_once ('classes/Scan.inc');

Session::logcheck("MenuMonitors", "TrafficCapture");

$info_error = array();

$jtimeout = 3000;

$db     = new ossim_db();
$dbconn = $db->connect();

$scan = new TrafficScan();

$sensors_status = $scan->get_status();

if(!$sensors_status)  $sensors_status  = array();

$message_info = "";

// Parameters to delete scan

$op           = GET("op");
$scan_name    = GET("scan_name");
$sensor_ip    = GET("sensor_ip");

// Others parameters

$soptions     = intval(GET("soptions"));

ossim_valid($op, OSS_NULLABLE, 'delete', 'illegal:' . _("Option"));
ossim_valid($scan_name, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_DOT, 'illegal:' . _("Capture name"));
ossim_valid($sensor_ip, OSS_IP_ADDR, OSS_NULLABLE, 'illegal:' . _("Sensor ip"));

if(GET("command") == _("Launch capture")) {

    // Parameters to launch scan

    $timeout          = $parameters['timeout'] = GET("timeout");
    $cap_size         = intval(GET("cap_size"));
    
    if($cap_size < 100 || $cap_size > 8000) {
        $cap_size = 4000;
    }
    
    $raw_filter       = GET("raw_filter");
    
    $sensor_data      = GET("sensor");
    
    if( !preg_match("/#/",$sensor_data) ) {
        $tmp              = explode("-",$sensor_data);
        $sensor_ip        = $parameters['sensor_ip']        = $tmp[0];
        $sensor_interface = $parameters['sensor_interface'] = $tmp[1];
    }
    else {
        $sensor_interface = $parameters['sensor_interface'] = "";
    }
    
    if(!Session::sensorAllowed($sensor_ip)) $sensor_ip = $sensor_interface = "";
    
    $src  = GET("src");
    $dst  = GET("dst");

	// clean ANY
	$src  = trim(preg_replace("/ANY/i","",$src));
	$dst  = trim(preg_replace("/ANY/i","",$dst));

    $validate  = array (
        "timeout"          => array("validation" => "OSS_DIGIT"                     , "e_message" => 'illegal:' . _("Timeout")),
        "raw_filter"       => array("validation" => "OSS_ALPHA , '\.\|\&\=\<\>\!\^'", "e_message" => 'illegal:' . _("Raw Filter")),
        "sensor_ip"        => array("validation" => "OSS_IP_ADDR"                   , "e_message" => 'illegal:' . _("Sensor")),
        "sensor_interface" => array("validation" => "OSS_ALPHA, OSS_PUNC"           , "e_message" => 'illegal:' . _("Interface"))
    );

    foreach ($parameters as $k => $v )
    {
        eval("ossim_valid(\$v, ".$validate[$k]['validation'].", '".$validate[$k]['e_message']."');");

        if ( ossim_error() )
        {
            $info_error[] = ossim_get_error();
            ossim_clean_error();
        }
    }


    // sources
    
    ossim_valid($src, OSS_NULLABLE, OSS_DIGIT, OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_PUNC, OSS_NL, '\.\,\/', 'illegal:' . _("Source"));
    if( ossim_error() )  
    {
        $info_error[] = ossim_get_error();
        ossim_clean_error();
    }

    if($src!="") {
        $all_sources = explode("\n", $src);
        $tsources     = array(); // sources for tshark
        foreach($all_sources as $source) 
        {
            $source      = trim($source);
            $source_type = null;
            
            if ( ossim_error() == false )
            {
                if(!preg_match("/\//",$source)) 
                {
                    if(!preg_match('/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/', $source))  {  $source = Host::hostname2ip($dbconn, $source, true);  } // resolve to ip
                    ossim_valid($source, OSS_IP_ADDR, 'illegal:' . _("Source ip"));
                    $source_type = 'host';
                }
                else 
                {
                    ossim_valid($source, OSS_IP_CIDR, 'illegal:' . _("Source cidr"));
                    $source_type = 'net';
                }
            }
            
            if( ossim_error() )  
            {
                $info_error[] = ossim_get_error();
                ossim_clean_error();
            }
            else
            {
                if ( $source_type == 'host' )
                {
                    if( Session::hostAllowed($dbconn, $source) ) 
                        $tsources[] = $source;
                }
                elseif ( $source_type == 'net' )
                {
                    if(Session::netAllowed($dbconn, $source))   
                        $tsources[] = $source;
                }
            }
                
            
        }
    }
    else {
        $tsources = array();
    
    }
    
    ossim_valid($dst, OSS_NULLABLE, OSS_DIGIT, OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_PUNC, OSS_NL, '\.\,\/', 'illegal:' . _("Destination"));
    
    if( ossim_error() )  
    {
        $info_error[] = ossim_get_error();
        ossim_clean_error();
    }

    // destinations

    if($dst!="") {
        $all_destinations  = explode("\n", $dst);
        $tdestinations     = array(); // sources for tshark
        foreach($all_destinations as $destination) 
        {
            $destination      = trim($destination);
            $destination_type = null;
            
            if ( ossim_error() == false )
            {
                if(!preg_match("/\//",$destination)) 
                {
                    if(!preg_match('/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/', $destination))  {  $destination = Host::hostname2ip($dbconn, $destination, true);  } // resolve to ip
                    ossim_valid($destination, OSS_IP_ADDR, 'illegal:' . _("Destination ip"));
                    $destination_type = 'host';
                }
                else 
                {
                    ossim_valid($destination, OSS_IP_CIDR, 'illegal:' . _("Destination cidr"));
                    $destination_type = 'net';
                }
            }
            
            if( ossim_error() )  
            {
                $info_error[] = ossim_get_error();
                ossim_clean_error();
            }
            else
            {
                if ( $destination_type == 'host' )
                {
                    if( Session::hostAllowed($dbconn, $destination) ) 
                        $tdestinations[] = $destination;
                }
                elseif ( $destination_type == 'net' )
                {
                    if(Session::netAllowed($dbconn, $destination))   
                        $tdestinations[] = $destination;
                }
            }
                
            
        }
    }
    else {
        $tdestinations = array();
    }
    // launch scan
    
    $info_sensor = $sensors_status[Sensor::get_sensor_name($dbconn, $sensor_ip)];

    if($sensor_ip!="" && $sensor_interface!="" && intval($timeout)>0 && count($info_error)==0 && ($info_sensor[0]==0 || $info_sensor[0]==-1)) {
        $rlaunch_scan = $scan->launch_scan($tsources, $tdestinations, $sensor_ip, $sensor_interface, $timeout, $cap_size, $raw_filter);
        
        if($rlaunch_scan["status"] === true) {
            $message_info="<div class='ossim_success'>"._("Launching capture... wait a few seconds")."</div>";
        }
        else {
            $message_info="<div class='ossim_error'>".$rlaunch_scan["message"]."</div>";
        }
        $jtimeout = 4000;
    }
    else if($info_sensor[0]!= -1 && ($info_sensor[0]== 1 || $info_sensor[0]== 2)){
        $message_info="<div class='ossim_alert'>"._("The sensor is busy")."</div>";
    }
}

// delete scan
if($op=="delete" && $scan_name!="" && $sensor_ip!="") {

    $message_info="<div class='ossim_success'>"._("Deleting capture... wait a few seconds")."</div>";
        
    $scan_info = explode("_", $scan_name);
    $users = Session::get_users_to_assign($dbconn);
    
    $my_users = array();
    foreach( $users as $k => $v ) {  $my_users[$v->get_login()]=1;  }
    
    if( $my_users[$scan_info[1]]==1 || Session::am_i_admin() )  $scan->delete_scan($scan_name,$sensor_ip);
}

// stop capture
if($op=="stop" && $sensor_ip!="") {
 
    if( Session::sensorAllowed($sensor_ip) ) {
        $scan->stop_capture($sensor_ip);
    }
    $db->close($dbconn);
    exit;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> - Traffic capture </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<META http-equiv="Pragma" content="no-cache">
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>

</head>
<body>
<?php
include ("../hmenu.php");

if ( count($info_error)>0 )
		echo display_errors($info_error);
if ( $message_info !="") 
        echo $message_info;
?>
</body>
</html>
<?php
$db->close($dbconn);

?><script type="text/javascript">
    //<![CDATA[
<?php
$params = "&src=".urlencode($src)."&dst=".urlencode($dst)."&timeout=".$timeout."&cap_size=".$cap_size."&raw_filter=".$raw_filter."&sensor_ip=".urlencode($sensor_ip)."&sensor_interface=".urlencode($sensor_interface);

if( count($info_error)>0 ) { $params .="&soptions=1"; }

?>
    setTimeout("document.location.href='index.php?hmenu=Network&smenu=Traffic+capture<?php echo $params;?>'", <?php echo $jtimeout;?>);

    //]]>
</script><?

function display_errors($info_error)
{
	$errors       = implode ("</div><div style='padding-top: 3px;'>", $info_error);
	$error_msg    = "<div>"._("We found the following errors:")."</div><div style='padding-left: 15px;'><div>$errors</div></div>";
							
	return "<div class='ossim_error'>$error_msg</div>";
}
?>
