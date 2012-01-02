<?
set_include_path('/usr/share/ossim/include');
require_once ('classes/Session.inc');
require_once ('classes/Util.inc');
require_once ('ossim_conf.inc');
require_once ('ossim_sql.inc');  

$job_id   = $argv[1];
$message  = $argv[2];

$conf = $GLOBALS["CONF"];

$levels = array("1" => "Serious:", "2" => "High:", "3" => "Medium:", "6" => "Low:", "7" => "Info:");

$db = new ossim_db();
$dbconn = $db->connect();

// select data for specified job_id

if (!$result = $dbconn->Execute(OssimQuery("SELECT vj.report_id, vns.name as profile, vj.meth_VSET as profile_id, vj.name, vj.username, vj.fk_name, vj.scan_SUBMIT, vj.scan_START, vj.scan_END, TIMESTAMPDIFF(MINUTE, vj.scan_START, vj.scan_END) as duration, vj.meth_TARGET
                            FROM vuln_jobs as vj, vuln_nessus_settings as vns WHERE vj.id=$job_id and vj.meth_VSET=vns.id"))) {
    echo $dbconn->ErrorMsg()."\n";
    $dbconn->disconnect();
}
else {
    $report_id = $result->fields["report_id"];
    $username  = $result->fields["username"];

    if ( intval($report_id)!=0 || $message!="" ) {
        $email_data = array ("subject" => _('Scan Job Notification: ').$result->fields["name"]);

        $width = 115;
        $body = '<html>
                    <head>
                        <title>'.$subject.'</title>
                        </head>
                        <body>'.
                        '<table width="100%" cellspacing="0" cellpadding="0" style="border:0px;">'.
                        '<tr><td colspan="2" style="text-decoration: underline;">'._('Email scan summary').'</td></tr>'.
                        '<tr><td colspan="2">&nbsp;</td></tr>'.
                        '<tr><td width="'.$width.'">'._('Scan Title:').'</td><td>'.$result->fields["name"].'</td></tr>'.
                        '<tr><td width="'.$width.'">'._('Profile:').'</td><td>'.$result->fields["profile"].'</td></tr>';
                        
        $body .=  '<tr><td width="'.$width.'">'._('Submit Date:').'</td><td>SCAN_SUBMIT</td></tr>'.
                        '<tr><td width="'.$width.'">'._('Start Date:').'</td><td>SCAN_START</td></tr>';
                        
                        
        $body .=  '<tr><td width="'.$width.'">'._('Duration:').'</td><td>'.$result->fields["duration"].' mins</td></tr>'.
                        $result->fields["meth_TARGET"] = preg_replace("/\n$/", "", $result->fields["meth_TARGET"]);
                        '<tr><td width="'.$width.'">'._('Targets:').'</td><td>'.str_replace("\n",", ", $result->fields["meth_TARGET"]).'</td></tr>'.
                        '<tr><td colspan="2">&nbsp;</td></tr>'.
                        '<tr><td width="'.$width.'">'._('Launched By:').'</td><td>'.(($result->fields["fk_name"]!="")? $result->fields["fk_name"]:_("Unknown")).'</td></tr>';
                        if(preg_match("/^\d+$/", $username)) {
                            $edata = Acl::get_entity_name_type($dbconn,$username);   
                            $visible_for = $edata[0]." [".$edata[1]."]"; 
                        }
                        else {
                            $visible_for = $username;
                        }
                        
                        $body.='<tr><td width="'.$width.'">'._('Job visible for:').'</td><td>'.(($visible_for!="")? $visible_for:_("Unknown")).'</td></tr>';
                        $body.='<tr><td colspan="2">&nbsp;</td></tr>';
                        if( intval($report_id)!= 0 ) {
                            $body.='<tr><td colspan="2" style="text-decoration: underline;">'._('Summary of Scanned Hosts').'</td></tr>';
                            $body.='<tr><td colspan="2">&nbsp;</td></tr>';
                        
                                                     
                            if (!$result_ip_name = $dbconn->Execute(OssimQuery("SELECT distinct t1.hostip as ip, t2.hostname as hostname FROM vuln_nessus_results t1 LEFT JOIN host t2 on t1.hostip = t2.ip
                                                     WHERE t1.report_id=$report_id"))) {
                                echo $dbconn->ErrorMsg()."\n";
                                $dbconn->disconnect();
                                return;
                            }
                                                     
                            $total = 0;
                            
                            while(list( $hostip, $hostname ) = $result_ip_name->fields) {
                                // read data from vuln_nessus_latest_results to generate stats
                                if (!$result_stats = $dbconn->Execute(OssimQuery("SELECT distinct t1.hostip as ip, t2.hostname as hostname FROM vuln_nessus_results t1 LEFT JOIN host t2 on t1.hostip = t2.ip
                                                         WHERE t1.report_id=$report_id"))) {
                                    echo $dbconn->ErrorMsg()."\n";
                                    $dbconn->disconnect();
                                    return;
                                }
                                                                    
                                $risk_stats = explode(";",$result_stats->fields["note"]);
                                
                                $body.='<tr><td width="'.$width.'">'._('Hostname:').'</td><td>'.(($hostname!="")? $hostname:_("Unknown")).'</td></tr>';
                                $body.='<tr><td width="'.$width.'">'._('Ip:').'</td><td>'.$hostip.'</td></tr>';
                                
                                if (!$result_risk = $dbconn->Execute(OssimQuery("SELECT count(risk) as count, risk FROM vuln_nessus_results
                                                                    WHERE report_id  in ($report_id) AND hostip='$hostip' AND falsepositive='N' GROUP BY risk"))) {
                                    echo $dbconn->ErrorMsg()."\n";
                                    $dbconn->disconnect();
                                    return;
                                }

                                $subtotal = 0;
                                
                                while(list( $count_risk, $risk ) = $result_risk->fields) {  
                                    if ($risk=="1")
                                        $diff = intval($count_risk-$risk_stats[0]);
                                    else if ($risk=="2")
                                        $diff = intval($count_risk-$risk_stats[1]);
                                    else if ($risk=="3")
                                        $diff = intval($count_risk-$risk_stats[2]);
                                    else if ($risk=="6")
                                        $diff = intval($count_risk-$risk_stats[3]);
                                    else if ($risk=="7")
                                        $diff = intval($count_risk-$risk_stats[4]);
                                    if ($diff==0)
                                        $body.='<tr><td width="'.$width.'">'._($levels[$risk]).'</td><td>'.$count_risk.' (=)</td></tr>';
                                    else
                                        $body.='<tr><td width="'.$width.'">'._($levels[$risk]).'</td><td>'.$count_risk.' ('.(($diff>0)? "+".$diff : $diff).')</td></tr>';
                                    $subtotal+=$count_risk;
                                    $result_risk->MoveNext();
                                }
                                
                                $total+=$subtotal;
                                
                                $body.='<tr><td width="'.$width.'">'._("Subtotal:").'</td><td>'.$subtotal.'</td></tr>';
                                $body.='<tr><td colspan="2">&nbsp;</td></tr>';
                                $result_ip_name->MoveNext();
                            }
                            $body.='<tr><td colspan="2">&nbsp;</td></tr>';
                            $body.='<tr><td width="'.$width.'">'._("Total:").'</td><td>'.$total.'</td></tr>';
                            $body.='<tr><td colspan="2">&nbsp;</td></tr>';
                            // show explanation
                            $body.='<tr><td colspan="2">'._("(+)(-)(=): Difference with previous detection for each host/vulnerability pair.").'</td></tr>';
                        
                        }
                        else {
                            $body.='<tr><td colspan="2"><strong>'._("The scan failed:").'</strong></td></tr>';
                            $body.='<tr><td colspan="2">'.$message.'</td></tr>';
                        }
                    $body.='</table>';
                    $body.='</body>';
                $body.='</html>';
                

                if( intval($report_id)!=0 ) {
                    // generate PDF
                    $scan_END = $result->fields["scan_END"];
                    $scan_END = str_replace("-","",$scan_END);
                    $scan_END = str_replace(":","",$scan_END);
                    $scan_END = str_replace(" ","",$scan_END);
                    
                    $file_path = "/usr/share/ossim/www/tmp/".$result->fields["name"]."_".$scan_END.".pdf";
                    $file_name = $result->fields["name"]."_".$scan_END.".pdf";
                    
                    exec ("/usr/bin/php /usr/share/ossim/scripts/vulnmeter/respdf.php '".$scan_END."' > ".$file_path);
                    
                    //echo "/usr/bin/php /usr/share/ossim/scripts/vulnmeter/respdf.php '".$scan_END."' > ".$file_path."\n";
                    
                    $email_data["attachment"]["path"]     = $file_path;
                    $email_data["attachment"]["filename"] = $file_name;
                }

        if (!preg_match("/^\d+$/",$username)) { //username is a user

            $user_data = Session::get_list($dbconn, "WHERE login='".$username."'");
            
            $email_data["body"] = get_timestamps ($dbconn, $username, $result->fields['scan_START'], $result->fields['scan_SUBMIT'], $body);
            sendEmail($conf, $email_data, $user_data[0]->get_email());
            
        }
        else { // username is a entity
            $entity_data = Acl::get_entity($dbconn,$username);
            if($entity_data["admin_user"]!="") { // exists pro admin
                $pro_admin_data = Session::get_list($dbconn, "WHERE login='".$entity_data["admin_user"]."'");
                $email_data["body"] = get_timestamps ($dbconn, $pro_admin_data[0]->get_login(), $result->fields['scan_START'], $result->fields['scan_SUBMIT'], $body);
                sendEmail($conf, $email_data, $pro_admin_data[0]->get_email());
            }
            else { // doesn't exit pro admin
                $users = Acl::get_users($dbconn);
                $user_list = $users[0];
                foreach ($user_list as $user) if(in_array($username,$user['entities'])) { // send an e-mail to each user
                    $email_data["body"] = get_timestamps ($dbconn, $user['login'], $result->fields['scan_START'], $result->fields['scan_SUBMIT'], $body);
                    sendEmail($conf, $email_data, $user['email']);
                }
            }
        }

        if(file_exists($file_path)) {
            unlink($file_path);
        }
    }
    $dbconn->disconnect();

}

function sendEmail ($conf, $email_data, $email) {

    $from       = $conf->get_conf("from");
    $fromName   = $conf->get_conf("from");
    $to         = $email;
    $toName     = $email;
    
    $subject    = $email_data["subject"];
    $body       = $email_data["body"];
    $attachment = $email_data["attachment"]["path"];
    $fileName   = $email_data["attachment"]["filename"];
    
    
    require_once('classes/PHPMailer.inc');
    require_once('classes/PHPMailerSMTP.inc');  // optional, gets called from within class.phpmailer.php if not already loaded

    $mail = new PHPMailer(true); // the true param means it will throw exceptions on errors, which we need to catch

    $mail->IsSMTP(); // telling the class to use SMTP
    try {
        if($conf->get_conf("use_ssl")=='yes'){ $mailHost='ssl://'; }else{ $mailHost=''; }
        $mailHost.=$conf->get_conf("smtp_server_address");
        $mail->Host       = $mailHost;// SMTP server
        $mail->SMTPDebug  = 1;                     // enables SMTP debug information (for testing)
        $mailSMTPUser=$conf->get_conf("smtp_user");
        if(!empty($mailSMTPUser)){
            $mailSMTPAuth=true;
            $mailUsername=$conf->get_conf("smtp_user");
            $mailPassword=$conf->get_conf("smtp_pass");
        }else{
            $mailSMTPAuth=false;
            $mailUsername='';
            $mailPassword='';
        }
        $mail->SMTPAuth   = $mailSMTPAuth;                  // enable SMTP authentication
        $mail->Port       = $conf->get_conf("smtp_port");                    // set the SMTP port for the GMAIL server (26)
        $mail->Username   = $mailUsername; // SMTP account username
        $mail->Password   = $mailPassword;        // SMTP account password
        $mail->AddAddress($to,$toName);
        $mail->SetFrom($from,$fromName);
        $mail->Subject = $subject;
        $mail->AltBody = _("To view the message, please use an HTML compatible email viewer!"); // optional - MsgHTML will create an alternate automatically
        $mail->MsgHTML($body);
        if(file_exists($attachment) && filesize($attachment) <= 5242880) {
            $mail->AddAttachment($attachment, $fileName);
        }
        $mail->Send();
        return true;
        }
    catch (phpmailerException $e) {
        echo $e->errorMessage(); //Pretty error messages from PHPMailer
    } catch (Exception $e) {
        echo $e->getMessage(); //Boring error messages from anything else!
    }
}

function get_timezone($tz="") {
    if (is_numeric($tz)) return $tz; // old numeric stored format
    $this_tz = new DateTimeZone($tz);
    $offset = $this_tz->getOffset(new DateTime("now", $this_tz));
    if ($offset!=0) $offset /= 3600;
    return $offset;
}

function get_timestamps ($dbconn, $login, $scan_START, $scan_SUBMIT, $body) {

    $user_timezone = $dbconn->GetOne("SELECT timezone FROM users WHERE login='".$login."'");
    
    $tz = get_timezone($user_timezone);
    
    if($tz!=0) {
        $scan_START  = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($dbconn, $scan_START)+(3600*$tz));
        $scan_SUBMIT = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($dbconn, $scan_SUBMIT)+(3600*$tz));
    }
    
    $body_part_with_timestamp = str_replace("SCAN_SUBMIT", $scan_SUBMIT, $body);
    $body_part_with_timestamp = str_replace("SCAN_START", $scan_START, $body_part_with_timestamp);
    
    return $body_part_with_timestamp;

}

?>
