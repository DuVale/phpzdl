<?php
require_once ('classes/Security.inc');
require_once ("classes/Session.inc");
require_once ('ossim_db.inc');
require_once ('ossim_conf.inc');

Session::logcheck("MenuEvents", "EventsForensics");
$rtype = GET('rtype');
ossim_valid($rtype, OSS_DIGIT, 'illegal:' . _("Report type"));

if (ossim_error()) {
    die(_("Invalid report type"));
}

$addr_type = intval(GET('addr_type'));

$type = array("33" => "Events",
              "38" => "Sensors",
              "36" => "Unique_Events",
              "46" => "Unique_Plugins",
              "40" => "Unique_Addresses",
              "42" => "Source_Port",
              "44" => "Destination_Port",
              "37" => "Unique_IP_links",
              "48" => "Unique_Country_Events");

$user      = $_SESSION["_user"];
$path_conf = $GLOBALS["CONF"];

/* database connect */
$db = new ossim_db();
$conn = $db->connect();
//$conn = $db->custom_connect('localhost',$path_conf->get_conf("ossim_user"),$path_conf->get_conf("ossim_pass"));

$output_name = $type[$rtype]."_" . $user . "_" . date("Y-m-d",time()) . ".csv";

$csv_header = "";
$csv_body   = "";


if($type[$rtype]=="Events") {
	$csv_header .= "Signature;Date;Source Address;Dest. Address; Asset Source; Asset Destination;Prio;Rel;Risk Source;Risk Destination;L4-protocol\n";
    $sql = "SELECT dataV1, dataV2, dataV3, dataV5, dataV7, dataV8, dataV9, dataV10, dataV11
            FROM datawarehouse.report_data WHERE id_report_data_type=$rtype and user='$user'";
}
else if($type[$rtype]=="Sensors") {
    $csv_header .= "Sensor;Name;Total Events;Unique Events;Src. Addr; Dest. Addr;First;Last\n";
    $sql = "SELECT dataV1, dataV3, dataV4, dataV5, dataV6, dataI1, dataI2, dataI3
            FROM datawarehouse.report_data WHERE id_report_data_type=$rtype and user='$user'";
}
else if($type[$rtype]=="Unique_Events") {
   $csv_header .= "Signature;Total #;Sensor #;Src. Addr;Dst. Addr;First;Last\n";
   $sql = "SELECT dataV1, dataV2, dataI1, dataI2, dataI3, dataV3, dataV4
            FROM datawarehouse.report_data WHERE id_report_data_type=$rtype and user='$user'";
}
else if($type[$rtype]=="Unique_Plugins") {
   $csv_header .= "Plugin;Events;Sensor #;Last Event;Source Address;Dest. Address;Date\n";
   $sql = "SELECT dataV1, dataI1, dataI2, dataV2, dataV3, dataV5, dataV7
            FROM datawarehouse.report_data WHERE id_report_data_type=$rtype and user='$user'";
}
else if($type[$rtype]=="Unique_Addresses") {
    
	if ( $addr_type == 1 )
       $csv_header .= "Src IP address;Sensor #;Total #;Unique Events;Dest. Addr.\n";
    else
       $csv_header .= "Dst IP address;Sensor #;Total #;Unique Events;Src. Addr.\n";

    $sql = "SELECT dataV1, dataI2, dataI3, dataV3, dataV4
            FROM datawarehouse.report_data WHERE id_report_data_type=$rtype and user='$user'";
}
else if($type[$rtype]=="Source_Port" || $type[$rtype]=="Destination_Port") {
    $csv_header .= "Port;Sensor;Occurrences;Unique Events;Src. Addr.;Dest. Addr;First;Last\n";
    $sql = "SELECT dataV1, dataI2, dataI3, dataV2, dataV3, dataV4, dataV5, dataV6
            FROM datawarehouse.report_data WHERE id_report_data_type=$rtype and user='$user'";
}
else if($type[$rtype]=="Unique_IP_links") {
    $csv_header .= "Source IP;Destination IP;Protocol;Unique Dst Ports;Unique Events;Total Events\n";
    $sql = "SELECT dataV1, dataV3, dataV5, dataI1, dataI2, dataI3
            FROM datawarehouse.report_data WHERE id_report_data_type=$rtype and user='$user'";
}
else if($type[$rtype]=="Unique_Country_Events") {
   $csv_header .= "Country;# of events;# of IPs;Event\n";
   $sql = "SELECT dataV1, dataI1, dataI2, dataI3
           FROM datawarehouse.report_data WHERE id_report_data_type=$rtype and user='$user'";
}



$result     = $conn->Execute($sql);
while ( !$result->EOF ) 
{
    if($type[$rtype]=="Events") 
	{
        list ($dataV1, $dataV2, $dataV3, $dataV5, $dataV7, $dataV8, $dataV9, $dataV10, $dataV11) = $result->fields;

        $m = array();
        $assets = 0;
        $assetd = 0;
        $prio   = 0;
        $rel    = 0;
        $risks  = 0;
        $riskd  = 0;

        preg_match('/value=(\d+)&value2=(\d+)/', $dataV7, $m);
        $assets = $m[1];
        $assetd = $m[2];
        
        preg_match('/value=(\d+)/', $dataV8, $m);
        $prio = $m[1];
        
        preg_match('/value=(\d+)/', $dataV9, $m);
        $rel = $m[1];
        
        preg_match('/value=(\d+)&value2=(\d+)/', $dataV10, $m);
        $risks = $m[1];
        $riskd = $m[2];

        $csv_body .= "$dataV1;$dataV2;$dataV3;$dataV5;$assets;$assetd;$prio;$rel;$risks;$riskd;$dataV11\n";
    }
    if($type[$rtype]=="Sensors") {
        list ($dataV1, $dataV3, $dataV4, $dataV5, $dataV6, $dataI1, $dataI2, $dataI3) = $result->fields;
        $csv_body .= "$dataI1;$dataV1;$dataI2;$dataI3;$dataV3;$dataV4;$dataV5;$dataV6\n";
    }
    else if ($type[$rtype]=="Unique_Events"){
        list ($dataV1, $dataV2, $dataI1, $dataI2, $dataI3, $dataV3, $dataV4) = $result->fields;
        $csv_body .= "$dataV1;$dataV2;$dataI1;$dataI2;$dataI3;$dataV3;$dataV4\n";
    }
    else if($type[$rtype]=="Unique_Plugins") {
        list ($dataV1, $dataI1, $dataI2, $dataV2, $dataV3, $dataV5, $dataV7) = $result->fields;
        $csv_body .= "$dataV1;$dataI1;$dataI2;$dataV2;$dataV3;$dataV5;$dataV7\n";
    }
    else if($type[$rtype]=="Unique_Addresses") {
        list ($dataV1, $dataI2, $dataI3, $dataV3, $dataV4) = $result->fields;
        $csv_body .= "$dataV1;$dataI2;$dataI3;$dataV3;$dataV4\n";
    }
    else if($type[$rtype]=="Source_Port" || $type[$rtype]=="Destination_Port") {
        list ($dataV1, $dataI2, $dataI3, $dataV2, $dataV3, $dataV4, $dataV5, $dataV6) = $result->fields;
        $csv_body .= "$dataV1;$dataI2;$dataI3;$dataV2;$dataV3;$dataV4;$dataV5;$dataV6\n";
    }
    else if($type[$rtype]=="Unique_IP_links") {
        list ($dataV1, $dataV3, $dataV5, $dataI1, $dataI2, $dataI3) = $result->fields;
        $csv_body .= "$dataV1;$dataV3;$dataV5;$dataI1;$dataI2;$dataI3\n";
    }
    else if($type[$rtype]=="Unique_Country_Events") {
        list ($dataV1, $dataI1, $dataI2, $dataI3) = $result->fields;
       $csv_body .= "$dataV1;$dataI1;$dataI2;$dataI3\n";
    }
    $result->MoveNext();
}

if ( !empty($csv_body) )
{
	$csv_body = $csv_header.$csv_body; 
	header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: public");
	header("Content-Disposition: attachment; filename=\"$output_name\";");
	header('Content-Type: application/force-download');
	header("Content-Transfer-Encoding: binary");
	header("Content-length: " . strlen($csv_body));
	
	echo $csv_body;
}
else
{
	echo ossim_error(_("Error: Failed to download file"));
}	


?>