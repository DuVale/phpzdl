<?
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

require_once('classes/Session.inc');
require_once('classes/Host.inc');
require_once('classes/Sensor.inc');

Session::logcheck("MenuEvents", "EventsVulnerabilities");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
  <title> <?php
echo gettext("Vulnmeter"); ?> </title>
<!--  <meta http-equiv="refresh" content="3"> -->
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/jquery.simpletip.js"></script>
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
  <script type="text/javascript">
    $(document).ready(function(){
        $("#fqdn_info").simpletip({
                        position: 'left',
                        offset: [-12, 15],
                        baseClass: 'ytooltip',
                        onBeforeShow: function() {
                                var txt = this.getParent().attr('txt');
                                this.update(txt);
                        }
        });
    });
  </script>
</head>
<body>
<?

$action    = POST('action');
$report_id = (GET('report_id')!="") ? GET('report_id') : POST('report_id');

ossim_valid($report_id, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("Report id"));
ossim_valid($action, OSS_NULLABLE, "insert", 'illegal:' . _("action"));

if (ossim_error()) {
    die(ossim_error());
}

$db = new ossim_db();
$dbconn = $db->connect();

$info_error = array();

if($action=="insert") {
    $sensors = array();
    $sensor_list = Sensor::get_list($dbconn);
    foreach($sensor_list as $sensor)
    $sensors[] = $sensor->get_name();
    
    $data = array();
    
    foreach ($_POST as $key => $value) {
        if(preg_match("/^ip(.+)/",$key,$found)) {
            ossim_valid(POST("$key"), OSS_IP_ADDR, 'illegal:' . _("ip"));
            $num = $found[1];
            if(POST("name$num")=="")    $hostname = POST("$key");
            else {
                $hostname = POST("name$num");
                ossim_valid($hostname, OSS_HOST_NAME, 'illegal:' . _("hostname"));
            }
            
            $fqdns = "";
            
            if(POST("fqdn$num")!="") { 
                $fqdns = POST("fqdn$num");
                ossim_valid($fqdns, OSS_FQDNS, OSS_NULLABLE, 'illegal:' . _("FQDN"));
            }
            
            $data[POST("$key")] = array( "hostname" => $hostname, "fqdns" => $fqdns );
            
            if (ossim_error()) {
                $info_error[] = ossim_get_error();
                ossim_clean_error();
            }
            else {
                Host::insert($dbconn, POST("$key"), $hostname, 2, 60, 60, NULL, 0, 0, NULL, $sensors, NULL, NULL, NULL, NULL, NULL, NULL, $fqdns);
            }
        }
    }
    
    if(count($info_error)==0) {
        ?>
        <script type="text/javascript">
            parent.GB_onclose();
        </script>
        <?php
    }
}
if( count($info_error)>0 ) {
    echo display_errors($info_error);
}

$ips = hosts_fqdns_to_insert($dbconn, $report_id, array("46180", "12053"));  // the third parameter are plugins to get the aliases

?>
<form action="add_hosts.php" method="post">
    <input type="hidden" name="action" value="insert" />
    <input type="hidden" name="report_id" value="<?php echo $report_id;?>" />
    <center>
    <table class="transparent" width="95%" align="center">
        <tr>
            <th height="22"><?php echo _("IP")?>          </th>
            <th height="22"><?php echo _("Hostname")?>    </th>
            <th height="22"><?php echo _("FQDN/Aliases")?>
                <a id="fqdn_info"  txt="<?=gettext("Comma-separated FQDN or aliases")?>">
                <img src="../pixmaps/help.png" width="16" border="0" align="absmiddle"/></a><br/>
            </th>
        </tr>
        <?php 
        $i=1;
        foreach($ips as $ip => $fqdn){ ?>
            <tr>
                <?php
                    if(count($data) == 0 || $data[$ip]!="") {
                        $checked = "checked=\"checked\"";
                    }
                    else {
                        $checked = "";
                    }
                
                ?>
                <td width="28%" style="text-align:left;" class="nobborder">
                    <input name="ip<?php echo $i;?>" value="<?php echo $ip;?>" type="checkbox" <?php echo $checked;?> /><?php echo $ip;?>
                </td>
                <td width="36%" style="text-align:center;" class="nobborder">
                   <input name="name<?php echo $i;?>" value="<?php echo ($data[$ip]["hostname"]);?>" style="width: 200px;" type="text" />
                </td>
                <td width="36%" style="text-align:center;" class="nobborder">
                    <input name="fqdn<?php echo $i;?>" value="<?php echo (($data[$ip]["fqdns"]!="") ? $data[$ip]["fqdns"] : $fqdn); ?>" style="width: 200px;" type="text" />
                </td>
            </tr>
            <?php
            $i++;
        } ?>
        <tr>
            <td colspan="3" class="nobborder" style="text-align:center;padding-top:10px;">
                <input type="submit" class="button" value="<?php echo _("Update")?>">
            </td>
        </tr>
        <tr>
            <td colspan="3" class="nobborder" height="20">
                &nbsp;
            </td>
        </tr>
    </table>
    </center>
</form>
</center>
<?

$dbconn->disconnect();
?>
</body>
</html>
<?php
function hosts_fqdns_to_insert($dbconn, $report_id, $plugins) {
    $in_assets = array();
    $ips = array();

    $result = $dbconn->Execute("SELECT distinct v.hostIP FROM vuln_nessus_results v,host h WHERE v.report_id='$report_id' AND v.hostIP NOT IN (SELECT ip FROM host)");

    while ( !$result->EOF ) {
        if(Session::hostAllowed($dbconn,$result->fields["hostIP"])) {
            $tmp =array();
            if(count($plugins)>0) {
                $resultf = $dbconn->Execute("SELECT distinct msg, scriptid
                                                FROM vuln_nessus_results v,host h 
                                                WHERE v.report_id='$report_id' AND v.hostIP LIKE '".$result->fields["hostIP"]."' AND v.scriptid IN ('".implode("','", $plugins)."')");

                while ( !$resultf->EOF ) {
                    if($resultf->fields["scriptid"] == "46180") {
                        /*
                            Plugin output:

                            - www.liquidity-analyzer.com             <---  FQDN

                                Info   Mark as false positive   i   	Family name: General
                        */
                        
                        $resultf->fields["msg"] = preg_replace("/\n/", "#", $resultf->fields["msg"]);
                        $resultf->fields["msg"] = preg_replace("/#\s*#/", "##", $resultf->fields["msg"]);
                        
                        $tokens = explode("##", $resultf->fields["msg"]);

                        $save_fqdn = false;
                        foreach ($tokens as $data) {

                            if($save_fqdn) {
                                $fqdns = explode("#", $data);
                                foreach ($fqdns as $fqdn) {
                                    $fqdn  = preg_replace("/^-/", "", $fqdn);
                                    $tmp[] = trim($fqdn);
                                }
                                $save_fqdn = false;
                            }
                            if(preg_match("/.*plugin output:.*/i",$data)) {  $save_fqdn = true;  }
                        }
                    }
                    else if($resultf->fields["scriptid"] == "12053") {
                        /*
                            Plugin output:

                            194.174.175.47 resolves as p-1-48-047.proxy.bdc-services.net.

                                Info   Mark as false positive   i   	Family name: General
                        */
                        $resultf->fields["msg"] = preg_replace("/\n/", "#", $resultf->fields["msg"]);
                        $resultf->fields["msg"] = preg_replace("/#\s*#/", "##", $resultf->fields["msg"]);
                        
                        $tokens = explode("##", $resultf->fields["msg"]);

                        $save_fqdn = false;
                        foreach ($tokens as $data) {

                            if($save_fqdn) {
                                $fqdns = explode("#", $data);
                                foreach ($fqdns as $fqdn) {
                                    if(preg_match("/resolves as (.*)/",$fqdn,$found)) {
                                        $found[1] = preg_replace("/\.$/", "", trim($found[1]));
                                        $tmp[]    = $found[1];
                                    }
                                }
                                $save_fqdn = false;
                            }
                            if(preg_match("/.*plugin output:.*/i",$data)) {  $save_fqdn = true;  }
                        }
                    }
                    $resultf->MoveNext();
                }
            }
            $ips[$result->fields["hostIP"]] = implode ("," , $tmp);
        }
        $result->MoveNext();
    }
    return $ips;
}

function display_errors($info_error) {
    $errors       = implode ("</div><div style='padding-top: 3px;'>", $info_error);
    $error_msg    = "<div>"._("We found the following errors:")."</div><div style='padding-left: 15px;'><div>$errors</div></div>";

    return "<div class='ossim_error'>$error_msg</div>";
}
?>