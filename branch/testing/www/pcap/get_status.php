<?php
/***************************************************************************
*
*   Copyright (c) 2007-2011 AlienVault
*   All rights reserved.
*
****************************************************************************/
require_once ('classes/Security.inc');
require_once ('classes/Session.inc');
require_once ('classes/Scan.inc');

$ips = POST('ips');
//if(GET('ips')!="") { $ips = GET('ips'); }

$ips_array = explode("#", $ips);

$output = array();

foreach($ips_array as $ip) {
    ossim_valid($id, OSS_NULLABLE, OSS_NULOSS_IP_ADDR, 'illegal:' . _("Report Id"));

    if (ossim_error()) {
        die(ossim_error());
    }
    
    if( Session::sensorAllowed($ip) ) {
        $scan     = new TrafficScan();
        $result   = $scan->get_scan_status($ip);
        $output[] = md5($ip)."|".$result["status"]."|".$result["packets"]."|".$result["total_packets"]."|".$result["packet_percentage"]."|".
                          $result["elapsed_time"]."|".$result["total_time"]."|".$result["time_percentage"]."|".$result["errno"]."\n";
    }
}
echo implode("\n",$output);

?>