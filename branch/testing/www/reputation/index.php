<?php
/*****************************************************************************
*
*   Copyright (c) 2007-2011 AlienVault
*   All rights reserved.
*
****************************************************************************/
ini_set('memory_limit', '1024M');
set_time_limit(300);
require_once ('classes/Session.inc');
require_once ('ossim_conf.inc');
require_once ('classes/Reputation.inc');

Session::logcheck("MenuMonitors", "IPReputation");

$conf     = $GLOBALS["CONF"];
$version  = $conf->get_conf("ossim_server_version", FALSE);
$prodemo  = ( preg_match("/pro|demo/i",$version) ) ? true : false;

$Reputation = new Reputation();

$type     = intval(GET("type"));

if ( $Reputation->existReputation() ) {

    //$db     = new ossim_db();
    //$dbconn = $db->connect();
    foreach ($_SESSION as $k => $v) if (preg_match("/^_repinfodb/",$k)) unset($_SESSION[$k]);
    
   ?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html lang="en">
	<head>
        <title> <?php echo gettext("OSSIM Framework"); ?> - <?php echo gettext("IP reputation"); ?> </title>
        <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1"/>
        <META http-equiv="Pragma" content="no-cache">
		<link rel="stylesheet" type="text/css" href="../style/style.css" />
		<script language="javascript" type="text/javascript" src="../js/jqplot/jquery-1.4.2.min.js"></script>
        <script language="javascript" type="text/javascript">
        
        function get_map() {
            $("#loading").html('<img width="16" align="absmiddle" src="../vulnmeter/images/loading.gif">&nbsp;&nbsp;<?=_("Drawing IPs over the world map, please wait a few seconds...")?>');
            $("#tr_loading").show();
			$("#iframe").html('');
			var iframe = "<iframe src='IPsGoogleMap.php?type=<?php echo $type ?>&act=" + escape($('#afilter').val()) + "' width='100%' height='100%' scrolling='no' frameborder='0'></iframe>";
			$("#iframe").append(iframe);
		}
	
		$(document).ready(function () {
			get_map();
		});
		   
		function show_map() {
			$("#tr_loading").hide();
		}
        
        function update(txt) {
            if(txt!="") {
                $("#doutput").html("<strong><span>"+txt+"</span></strong>");
                $("#doutput").css({ background: "#7391AD" }); 
            }
            else {
                $("#doutput").html("&nbsp;");
                $("#doutput").css({ background: "#ffffff" });
            }
        }
        function hide_message(txt) {
            $("#doutput").html("&nbsp;");
            $("#doutput").css({ background: "#ffffff" });
        }
        </script>
    </head>
    <body style="height:100%">
    <?php
		include ("../hmenu.php"); 
    ?>
    
        <form action="index.php" style="margin:0px">
	    <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
	    <!-- MAP -->
        <?php
        list($ips,$cou,$order,$total) = $Reputation->get_data($type);
        ?>
	    <td class="noborder" style="padding:0px 0px 20px 0px;" valign="top">
            <table width="100%" class="transparent">
                <tr>
                    <td class="nobborder" height="25" style="text-align:left;padding:4px 0px 4px 4px;">
                        <div style="width:100%;">
                            <div style="float:left">
                                <?php echo _("Source:"); ?>&nbsp;&nbsp;<select name="type" onchange="this.form.submit()">
                                    <option value="0"<?php echo ($type==0) ? ' selected' : ''?>><?php echo _("SIEM Events");?></option>
                                    <option value="1"<?php echo ($type==1) ? ' selected' : ''?>><?php echo _("Reputation Data");?></option>
                                </select> &nbsp;&nbsp;
                                <?php
                                    $activities = array_keys($ips);
                                
                                    echo _("Filter by Activity:"); ?>&nbsp;&nbsp;<select id="afilter" onchange="get_map()">
                                    <option value="All"><?php echo _("All");?></option>
                                    <?php
                                    foreach ($activities as $activity) {
                                    ?>
                                        <option value="<?php echo $activity;?>"><?php echo $activity;?></option>

                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                            <div id="doutput" style="width:240px;text-align:center;float:right;margin-right:4px;background-color:#fafafa;color:#ffffff;padding:3px 0px;">
                                &nbsp;
                            </div>
                        </div>
                    </td>
                </tr>
                <tr id="tr_loading">
                    <td class="nobborder">
                        <div id="loading" style="text-align:center;height:16px;width:100%;background-color:#fafafa;">
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="nobborder" valign="top" style="padding:0px 4px 0px 4px;" >
                        <div style="width:100%;height:400px;clear:left;text-align:center;" id="iframe"></div>
                    </td>
                </tr>
            </table>
        </td>
	    </tr>

	    <!-- CHART -->
	    <tr><td class="noborder">
	    	<iframe src="pie.php?type=<?php echo $type ?>" frameborder="0" style="width:100%;height:250px;"></iframe>
	    </td></tr>
			    
		</table>
	    </form>
        <br /><br />
    </body>
    </html>
<?php

    //$db->close($dbconn);
}

?>
