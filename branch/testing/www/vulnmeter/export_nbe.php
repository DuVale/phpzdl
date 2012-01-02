<?php
require_once ('classes/Security.inc');
require_once ("classes/Session.inc");

$job_id = GET("job_id");
ossim_valid($job_id, OSS_DIGIT, 'illegal:' . _("job id"));

if (ossim_error()) {
    die(ossim_error());
}

$db = new ossim_db();
$dbconn = $db->connect();

$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE); 

// check username

$user = "";
$user_name_filter = "";

if(!Session::am_i_admin()) {
    if(!preg_match("/pro|demo/i",$version)){
        $user = Session::get_session_user();
    }
    else {
        $entities_and_users = array();
        $entities_and_users = Acl::get_user_entities();
        $entities_and_users[] = Session::get_session_user(); // add current user
        $users_pro_admin = Acl::get_my_users($dbconn, Session::get_session_user());
        foreach ($users_pro_admin as $us) {
            $entities_and_users[] = $us["login"];
        }
        $user = implode("', '",$entities_and_users); 
    }
}

if($user!="") $user_name_filter = "and username in ('$user')";

$sql = "select * from vuln_jobs where id=$job_id $user_name_filter";

if (!$rs = & $dbconn->Execute($sql)) {
    print _('error reading vuln_jobs information').' '.$conn->ErrorMsg() . '<BR>';
    exit;
}

$name       = $rs->fields["name"];
$report_id  = $rs->fields["report_id"];
$scan_start = $rs->fields["scan_START"];
$scan_end   = $rs->fields["scan_END"];

if($name!="") {

    $oids  = array();
    $risks = array( "1" => "Serious", "2" => "Security Hole", "3" => "Security Warning", "6" => "Security Note", "7" => "Log Message" );
    
    $dest = $GLOBALS["CONF"]->db_conf["nessus_rpt_path"]."/tmp/nessus_s".$report_id.".nbe";
    
    if(file_exists($dest)) {
        unlink($dest);
    }
    
    // write data into file
    
    $fh = fopen($dest, "w");
    
    if($fh==false) {
        echo _("Unable to create file")."<br />";
    
    }
    fputs ($fh, "timestamps|||scan_start|".date("D M d H:i:s Y", strtotime($scan_start))."|\n");
    
    $sql = "SELECT * from vuln_nessus_results WHERE report_id = ".$rs->fields["report_id"]." ORDER BY hostIP DESC";
    
    if (!$rs = & $dbconn->Execute($sql)) {
        print _('error reading vuln_nessus_results information').' '.$dbconn->ErrorMsg() . '<BR>';
        exit;
    }
    
    $hostIP = "";
    while (!$rs->EOF) {
    
        // get oid
        
        if($oids[$rs->fields["scriptid"]] == "") {
            $oid = $dbconn->GetOne("SELECT oid FROM vuln_nessus_plugins WHERE id=".$rs->fields["scriptid"]);
            
            if( $oid == "" )
                $oid = $rs->fields["scriptid"];
            
            $oids[$rs->fields["scriptid"]] = $oid; // save to cache
        }
        else {
            $oid = $oids[$rs->fields["scriptid"]];
        }
    
        // to display host_start y host_end for each host
        
        if ($rs->fields["hostIP"]!=$hostIP) {
            fputs ($fh, "timestamps||".$rs->fields["hostIP"]."|host_start|".date("D M d H:i:s Y", strtotime($scan_start))."|\n");
            if( $hostIP !="" ) {
                fputs ($fh, "timestamps||".$hostIP."|host_end|".date("D M d H:i:s Y", strtotime($scan_end))."|\n");
            }
            $hostIP = $rs->fields["hostIP"];
        }
        
        preg_match("/(\d+\.\d+\.\d+)\.\d+/", $rs->fields["hostIP"], $found);
        
        
        $rs->fields["msg"] = preg_replace ( "/Risk [fF]actor\s*:\s*(\\n)*Serious((\\n)+| \/ |$)/", "", $rs->fields["msg"]);
        $rs->fields["msg"] = preg_replace ( "/Risk [fF]actor\s*:\s*(\\n)*Critical((\\n)+| \/ |$)/", "", $rs->fields["msg"]);
        $rs->fields["msg"] = preg_replace ( "/Risk [fF]actor\s*:\s*(\\n)*High((\\n)+| \/ |$)/", "", $rs->fields["msg"]);
        $rs->fields["msg"] = preg_replace ( "/Risk [fF]actor\s*:\s*(\\n)*Medium((\\n)+| \/ |$)/", "", $rs->fields["msg"]);
        $rs->fields["msg"] = preg_replace ( "/Risk [fF]actor\s*:\s*(\\n)*Medium\/Low((\\n)+| \/ |$)/", "", $rs->fields["msg"]);
        $rs->fields["msg"] = preg_replace ( "/Risk [fF]actor\s*:\s*(\\n)*Low\/Medium((\\n)+| \/ |$)/", "", $rs->fields["msg"]);
        $rs->fields["msg"] = preg_replace ( "/Risk [fF]actor\s*:\s*(\\n)*Low((\\n)+| \/ |$)/", "", $rs->fields["msg"]);
        $rs->fields["msg"] = preg_replace ( "/Risk [fF]actor\s*:\s*(\\n)*Info((\\n)+| \/ |$)/", "", $rs->fields["msg"]);
        $rs->fields["msg"] = preg_replace ( "/Risk [fF]actor\s*:\s*(\\n)*[nN]one to High((\\n)+|(\s)+| \/ |$)/", "", $rs->fields["msg"]);
        $rs->fields["msg"] = preg_replace ( "/Risk [fF]actor\s*:\s*(\\n)*[nN]one((\\n)+| \/ |$)/", "", $rs->fields["msg"]);
        $rs->fields["msg"] = preg_replace ( "/Risk [fF]actor\s*:\s*(\\n)*Passed((\\n)+| \/ |$)/", "", $rs->fields["msg"]);
        $rs->fields["msg"] = preg_replace ( "/Risk [fF]actor\s*:\s*(\\n)*Unknown((\\n)+| \/ |$)/", "", $rs->fields["msg"]);
        $rs->fields["msg"] = preg_replace ( "/Risk [fF]actor\s*:\s*(\\n)*Failed((\\n)+| \/ |$)/", "", $rs->fields["msg"]);
        
        $line  = "results|".$found[1]."|".$rs->fields["hostIP"]."|".$rs->fields["service"]."|".$oid."|".$risks[$rs->fields["risk"]]."|".preg_replace ( "/\n/" , '\n' , $rs->fields["msg"] ); 
        fputs ($fh, $line."\n");
        
        $last_ip = $rs->fields["hostIP"]; // last ip
        $rs->MoveNext();
    }
    fputs ($fh, "timestamps||".$last_ip."|host_end|".date("D M d H:i:s Y", strtotime($scan_end))."|\n");
    fputs ($fh, "timestamps|||scan_end|".date("D M d H:i:s Y", strtotime($scan_end))."|\n");
    
    fclose ($fh);
    
    // download .nbe
     
    $file_name = "results_".$name;
    
    $file_name = preg_replace("/:|\\|\'|\"|\s+|\t|\-/", "_", $file_name);

    header("Content-type: application/unknown");
    header('Content-Disposition: attachment; filename='.$file_name.'.nbe');
   
    readfile($dest);
    
    if(file_exists($dest)) {
        unlink($dest);
    }
}
else {
    echo _("You don't have permission to see these results");
}

$dbconn->disconnect();
?>