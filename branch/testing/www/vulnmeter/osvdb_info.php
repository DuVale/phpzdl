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

require_once('ossim_conf.inc');
require_once ('classes/Session.inc');
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
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
    <style type="text/css">
    .gtd {
        background-color: #f2f2f2;
    }
  </style>
</head>
<body>
<?

$scriptid = $_GET["scriptid"];

ossim_valid($scriptid, OSS_DIGIT, 'illegal:' . _("scriptid"));
if (ossim_error()) {
    die(_("Invalid Parameter scriptid"));
}

$path_conf = $GLOBALS["CONF"];

$db = new ossim_db();
$dbconn = $db->connect();
$dbconn->Execute('use osvdb');

// test if table exits
$old=1;
$query = "show tables";
$result = $dbconn->Execute($query);
while ( !$result->EOF ) {
    if ($result->fields[0]=="ext_references") $old=0;
    $result->MoveNext(); 
}
if ($old) {
    echo "<table width=\"100%\" class=\"noborder\" style=\"background:transparent;\">";
    echo "<tr><td style=\"text-align:center;padding-top:10px;\" class=\"nobborder\">"._("OSVDB ext_references table not found. Please update.")."</td></tr>";
    echo "</table></body></html>";
    exit(0);
}
//

$cve_references = array();
$bugtraq_references = array();

$dbconn->Execute('use ossim');
$query = "select cve_id, bugtraq_id from vuln_nessus_plugins
            where id=$scriptid";
//echo $query;
$result = $dbconn->Execute($query);

$cve_references = explode(", ",str_replace("CVE-", "",$result->fields['cve_id']));
$bugtraq_references = explode(", ",$result->fields['bugtraq_id']);

$cve_data = "";
foreach ($cve_references as $cve) {
    $cve_data = $cve_data."'".$cve."',";
}
$cve_data = substr($cve_data,0,strlen($cve_data)-1);

$bugtraq_data = "";
foreach ($bugtraq_references as $bug) {
    $bugtraq_data = $bugtraq_data."'".$bug."',";
}
$bugtraq_data = substr($bugtraq_data,0,strlen($bugtraq_data)-1); 

//var_dump($cve_references);
//var_dump($bugtraq_references);

if ($bugtraq_data == "")  $bugtraq_data = "0";
if ($cve_data == "")  $cve_data = "0";

$query = "select distinct(vulnerability_id) from osvdb.ext_references where (ext_reference_type_id = 3 and value in ($cve_data)) or (ext_reference_type_id = 5 and value in ($bugtraq_data))";
//print $query;
$result = $dbconn->Execute($query);

$vulns_ids = "";
while ( !$result->EOF ) {
    $vulns_ids = $vulns_ids."'".$result->fields['vulnerability_id']."',";
    $result->MoveNext(); 
}

$vulns_ids = substr($vulns_ids,0,strlen($vulns_ids)-1); 

if ($vulns_ids=="") $vulns_ids = "0";

         
$query = "SELECT ov.id as vulnerability_id, ov.osvdb_id, ov.description, ov.t_description, ov.short_description, ov.solution, ov.disclosure_date, ov.exploit_publish_date, ov.solution_date,
                 oc.access_vector, oc.access_complexity, oc.authentication, oc.confidentiality_impact, oc.integrity_impact, oc.availability_impact, oc.calculated_cvss_base_score, oc.source,
                 oc.created_at, oc.updated_at
                    FROM osvdb.vulnerabilities AS ov, osvdb.cvss_metrics AS oc
                    WHERE ov.id in($vulns_ids) AND ov.id = oc.vulnerability_id ORDER BY oc.updated_at ASC";
          
//print $query;
$result = $dbconn->Execute($query);

$desc = ($result->fields['short_description']!="") ? $result->fields['short_description'] : (($result->fields['description']!="") ? $result->fields['description'] : $result->fields['t_description']);

$vulnerabilities = array();

while ( !$result->EOF ) {
    if(!empty($vulnerabilities_id[$result->fields['vulnerability_id']])) {
        $result->MoveNext();
        continue;
    }
    $vulnerabilities_id[$result->fields['vulnerability_id']] = 1;
    ?>
    <table width='100%'>
        <tr>
            <th style="text-align:right;" width="100"><?php echo _("OSVdb ID"); ?></th>
            <td style="text-align:left;padding-left:3px;" width="700" class="nobborder"> 
                <?php
                    echo $result->fields['osvdb_id'];
                    echo ("<a style='margin-left: 20px;' href='http://osvdb.org/show/osvdb/".$result->fields['osvdb_id']."' />http://osvdb.org/show/osvdb/".$result->fields['osvdb_id']."</a>"); ?>
             </td> 
        </tr>
        <?php
        $disclosure_date      = (($result->fields["disclosure_date"]!="" && !preg_match("/1970-01-01/",$result->fields["disclosure_date"])) ? preg_replace("/ \d+:\d+:\d+/", "", $result->fields["disclosure_date"]) : "");
        $exploit_publish_date = (($result->fields["exploit_publish_date"]!="" && !preg_match("/1970-01-01/",$result->fields["exploit_publish_date"])) ?  preg_replace("/ \d+:\d+:\d+/", "", $result->fields["exploit_publish_date"]) : "");
        $solution_date        = (($result->fields["solution_date"]!="" && !preg_match("/1970-01-01/",$result->fields["solution_date"])) ?  preg_replace("/ \d+:\d+:\d+/", "", $result->fields["solution_date"]) : "");
        
        if( !empty($disclosure_date) || !empty($exploit_publish_date) || !empty($solution_date) ) {
        ?>
            <tr>
                <th style="text-align:right;" width="100"><?php echo _("Timeline"); ?></th>

                <td style="text-align:left;"  width="700" class="nobborder">
                    <table class="nobborder">
                        <tr>
                            <?php
                            if(!empty($disclosure_date)) { ?>
                                <th width="150"> <?php echo _("Disclosure Date");?> </th>
                            <?php
                            }
                            if(!empty($exploit_publish_date)) { ?>
                                <th width="150"> <?php echo _("Exploit Publish Date");?> </th>
                            <?php
                            }
                            if(!empty($solution_date)) { ?>
                                <th width="150"> <?php echo _("Vendor Solution Date");?> </th>
                            <?php
                            }
                            ?>
                        </tr>
                        <tr>
                            <?php
                            if(!empty($disclosure_date)) { ?>
                                <td width="150" class="nobborder" style="text-align:center"> <?php echo $disclosure_date; ?> </td>
                            <?php
                            }
                            if(!empty($exploit_publish_date)) { ?>
                                <td width="150" class="nobborder" style="text-align:center"> <?php echo $exploit_publish_date; ?> </td>
                            <?php
                            }
                            if(!empty($solution_date)) { ?>
                                <td width="150" class="nobborder" style="text-align:center"> <?php echo $solution_date; ?> </td>
                            <?php
                            }
                            ?>
                        </tr>
                    </table>
                </td>
            </tr>
        <?php
        }
        ?>
        <tr>
            <?php
            // color for access vector
            $access_vector     = array("LOCAL"              => "#008D15",
                                       "NETWORK"            => "#FFA500",
                                       "REMOTE"             => "#FF0000");
            // color for access complexity
            $access_complexity = array("HIGH"               => "#008D15",
                                       "MEDIUM"             => "#FFA500",
                                       "LOW"                => "#FF0000");
            // color for authentcation
            $authentication    = array("MULTIPLE INSTANCES" => "#008D15",
                                       "SINGLE INSTANCES"   => "#FFA500",
                                       "NONE"               => "#FF0000");
            
            // color for Confidentiality, Integrity, Availability
            $cia_color         = array("NONE"               =>  "#008D15",
                                       "PARTIAL"            => "#FFA500",
                                       "COMPLETE"           => "#FF0000");
            ?>
            <th style="text-align:right;"><?php echo _("CVSSv2 Score"); ?></th>
            <td valign="top" class="nobborder">
                <table class="transparent">
                    <tr>
                        <td colspan="6" style="text-align:center" class="nobborder"><?php
                            echo "<strong>"._("CVSSv2 Base Score = ").preg_replace("/^(\d+)$/","$1.0",$result->fields['calculated_cvss_base_score'])."</strong><br />";
                            if( !preg_match("/http/", $result->fields['source']) ) {
                                echo _("Source: ")."<span style='font-size:12px;'>".$result->fields['source']."</span> | ";
                            }
                            else {
                                echo _("Source: ")."<a href='".$result->fields['source']."'><span style='font-size:12px;'>".$result->fields['source']."</span></a> | ";
                            }
                            echo _("Generated: ").preg_replace("/\s\d{1,2}:\d{1,2}:\d{1,2}/", "", $result->fields['created_at']);
                            if( preg_replace("/\s\d{1,2}:\d{1,2}:\d{1,2}/", "", $result->fields['created_at']) != preg_replace("/\s\d{1,2}:\d{1,2}:\d{1,2}/", "", $result->fields['updated_at']) ) {
                                echo " | "._("Updated: ").preg_replace("/\s\d{1,2}:\d{1,2}:\d{1,2}/", "", $result->fields['updated_at']);
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo _("Access Vector"); ?></th><th><?php echo _("Access Complexity");?></th><th><?php echo _("Authentication");?></th>
                        <th><?php echo _("Confidentiality"); ?></th><th><?php echo _("Integrity");?></th><th><?php echo _("Availability");?></th>
                    </tr>
                    <tr>
                        <td style="text-align:center;" class="nobborder">
                            <span style="font-weight:bold;color:<?php echo $access_vector[$result->fields['access_vector']];?>">
                                <?php echo ucfirst(strtolower($result->fields['access_vector']));?>
                            </span>
                        </td>
                        <td style="text-align:center;" class="nobborder">
                            <span style="font-weight:bold;color:<?php echo $access_complexity[$result->fields['access_complexity']];?>">
                                <?php echo ucfirst(strtolower($result->fields['access_complexity']));?>
                            </span>
                        </td>
                        <td style="text-align:center;" class="nobborder">
                            <span style="font-weight:bold;color:<?php echo $authentication[$result->fields['authentication']];?>">
                                <?php echo ucfirst(strtolower($result->fields['authentication']));?>
                            </span>
                        </td>
                        <td style="text-align:center;" class="nobborder">
                            <span style="font-weight:bold;color:<?php echo $cia_color[$result->fields['confidentiality_impact']];?>">
                                <?php echo ucfirst(strtolower($result->fields['confidentiality_impact']));?>
                            </span>
                        </td>
                        <td style="text-align:center;" class="nobborder">
                            <span style="font-weight:bold;color:<?php echo $cia_color[$result->fields['integrity_impact']];?>">
                                <?php echo ucfirst(strtolower($result->fields['integrity_impact']));?>
                            </span>
                        </td>
                        <td style="text-align:center;" class="nobborder">
                            <span style="font-weight:bold;color:<?php echo $cia_color[$result->fields['availability_impact']];?>">
                                <?php echo ucfirst(strtolower($result->fields['availability_impact']));?>
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <th style="text-align:right;" width="100"> <?php echo _("Description"); ?></th>
            <td style="text-align:left;padding-left:3px;" width="700" class="nobborder">
            <?php
            echo (($desc!="") ? $desc :  _("Not Available"));
            ?>
            </td>
        </tr>
        <tr>
            <th style="text-align:right;" width="100"><?php echo _("Solution"); ?></th>
            <td style="text-align:left;padding-left:3px;" class="nobborder" width="700">
            <?php
            echo (($result->fields['solution']!="") ? $result->fields['solution'] :  _("Not Available"));
            ?>
            </td>
        </tr>
        <tr>
            <th style="text-align:right;" width="100"> <?php echo _("Classification"); ?>
            </th><td class="nobborder" style="text-align:left;">
                    <table width="100%" class="noborder">
                    <?php
                    $sql_cl = "SELECT ct.name as ctname, c.longname
                                FROM osvdb.classification_types AS ct, osvdb.classifications AS c, osvdb.classification_items AS ci
                                WHERE ci.vulnerability_id = '".$result->fields['vulnerability_id']."' AND ci.classification_id = c.id AND c.classification_type_id = ct.id";
        
                    $result_cl = $dbconn->Execute($sql_cl);
                    if($result_cl->EOF){
                    ?>
                        <tr>
                            <td style="text-align:left;" class='noborder'><?php echo $result->fields['title'] ?></td>
                        </tr>
                    <?php
                    }
                    
                    while ( !$result_cl->EOF ) {?>
                        <tr><td style="text-align:left;" class='noborder'><strong><?php echo ($result_cl->fields['ctname']); ?>:</strong><?php echo (" ".$result_cl->fields['longname']);?></td></tr>
                        <?php
                        $result_cl->MoveNext();
                    }
                    ?>
                    </table>
                </td>
        </tr>
       <?php
        // get references from ext_references
        $references = array();
        $sql_re = "SELECT ert.name, er.value
                    FROM osvdb.ext_references AS er, osvdb.ext_reference_types AS ert
                    WHERE er.vulnerability_id = ".$result->fields['vulnerability_id']." AND er.ext_reference_type_id = ert.id";
        //print_r($sql_re);

        $result_re = $dbconn->Execute($sql_re);

        while ( !$result_re->EOF ) {
            if( preg_match("/http/", $result_re->fields["value"] )) {
                $result_re->fields["value"] = "<blockquote style='margin:0px 0px 0px 20px;'><a href='".$result_re->fields["value"]."'>".$result_re->fields["value"]."</a></blockquote>";
            }
            else {
                $result_re->fields["value"] = $result_re->fields["value"].", ";
            }
            if($references[$result_re->fields["name"]]=="")
                $references[$result_re->fields["name"]] = $result_re->fields["value"];
                
            else {
                $references[$result_re->fields["name"]] = $references[$result_re->fields["name"]].$result_re->fields["value"];
            }
            $result_re->MoveNext();
        }
        //print_r($references);
        if(count($references) > 0) {
        ?>
            <tr>
                <th style="text-align:right;background-position: top;" width="100"> <?php echo _("References"); ?></th>
                <td class="nobborder" style="text-align:left;padding-left:3px;">
                <?php
                    foreach ($references as $key => $value) {
                        $value = preg_replace("/,\s$/", "<br />", $value);
                        echo "<strong>".$key.":</strong> ";
                        echo $value;
                    }
                ?>
                </td>
            </tr>
        <?php
        }
        
        // get objects
        $objects = array();
        $sql_ob  = "SELECT oven.name as vendor_name, op.name as product_name, over.name as version, oat.name as affect_type
                    FROM osvdb.object_links AS ol, osvdb.object_vendors AS oven, osvdb.object_products AS op, osvdb.object_versions AS over, osvdb.object_correlations AS oc, osvdb.object_affect_types AS oat
                    WHERE ol.vulnerability_id = ".$result->fields['vulnerability_id']." AND ol.object_affect_type_id=oat.id AND ol.object_correlation_id=oc.id AND oc.object_vendor_id=oven.id AND
                    oc.object_product_id=op.id AND oc.object_version_id=over.id";
        $result_ob = $dbconn->Execute($sql_ob);
        while ( !$result_ob->EOF ) {
                if(empty($objects[$result_ob->fields["vendor_name"]][$result_ob->fields["product_name"]])) {
                    $objects[$result_ob->fields["vendor_name"]][$result_ob->fields["product_name"]] = $result_ob->fields["version"]." <span style='font-size:9px;color:#7C7C7C'>(".$result_ob->fields["affect_type"].")</span>";
                }
                else {
                    $objects[$result_ob->fields["vendor_name"]][$result_ob->fields["product_name"]] = $objects[$result_ob->fields["vendor_name"]][$result_ob->fields["product_name"]].", ".$result_ob->fields["version"]." <span style='font-size:9px;color:#7C7C7C'>(".$result_ob->fields["affect_type"].")</span>";
                }
            $result_ob->MoveNext();
        }
        if(!empty($objects)) {?>
            <tr>
                <th style="text-align:right;" width="100"> <?php echo _("Products"); ?></th>
                <td class="nobborder" style="text-align:left;padding-left:3px;">
            <?php
            foreach ($objects as $vendor_name => $product_data) {
                echo "<strong>".$vendor_name."</strong><br />";
                foreach($product_data as $product_name => $product_version) {
                    echo $product_name.": ".$product_version;
                }
                echo "<br />";
            }
            ?>
            </tr>
            <?php
        }
        $result->MoveNext();?>
        </table>
    <br />
    <br />
<?php
}

if ($vulns_ids=="0") { ?>
    <table width="100%" class="noborder" style="background:transparent;">
    <tr><td style="text-align:center;padding-top:10px;" class="nobborder"><?php echo _("No data available"); ?></td></tr>
    </table>
    <?php
    }
?>

</body>
</html>