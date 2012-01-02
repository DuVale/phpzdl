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
/**
* Class and Function List:
* Function list:
* Classes list:
*/
require_once ('classes/Session.inc');
require_once('ossim_db.inc');
require_once('classes/Webinterfaces.inc');
require_once 'ossim_conf.inc';
Session::logcheck("MenuMonitors", "MonitorsNetflows");

$self                = "/ossim/nfsen/main.php?tab=2";
$db_aux              = new ossim_db();
$conn_aux            = $db_aux->connect();
$webinterfaces_list  = Webinterfaces::get_list($conn_aux, "where status=1");
$ossim_conf          = $GLOBALS["CONF"];
$nfsen_in_frame     = ( $ossim_conf->get_conf("nfsen_in_frame", FALSE) == 1 )    ? "true" : "false";
$db_aux->close($conn_aux);

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <title> <?php echo gettext("OSSIM"); ?> </title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
        <meta http-equiv="Pragma" content="no-cache"/>
        <link rel="stylesheet" type="text/css" href="../style/style.css"/>
        <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
        <script type="text/javascript">
            
            function lastsessions() {
                $("#FlowProcessingForm").append("<input type='hidden' id='filter' name='filter' value='' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='filter_name' name='filter_name' value='none' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='DefaultFilter' name='DefaultFilter' value='-1' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='modeselect' name='modeselect' value='0' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='process' name='process' value='Process' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='listN' name='listN' value='3' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='topN' name='topN' value='0' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='stattype' name='stattype' value='1' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='statorder' name='statorder' value='0' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='aggr_srcselect' name='aggr_srcselect' value='0' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='aggr_srcnetbits' name='aggr_srcnetbits' value='24' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='aggr_dstselect' name='aggr_dstselect' value='0' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='aggr_dstnetbits' name='aggr_dstnetbits' value='24' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='limitwhat' name='limitwhat' value='0' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='limithow' name='limithow' value='0' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='limitsize' name='limitsize' value='0' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='limitscale' name='limitscale' value='0' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='output' name='output' value='extended' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='customfmt' name='customfmt' value='' />");

                send($('#interface').val(),$('#interface option:selected').text() );
                
                $("#filter").remove();
                $("#filter_name").remove();
                $("#DefaultFilter").remove();
                $("#modeselect").remove();
                $("#process").remove();
                $("#listN").remove();
                $("#topN").remove();
                $("#stattype").remove();
                $("#statorder").remove();
                $("#aggr_srcselect").remove();
                $("#aggr_srcnetbits").remove();
                $("#aggr_dstselect").remove();
                $("#aggr_dstnetbits").remove();
                $("#limitwhat").remove();
                $("#limithow").remove();
                $("#limitsize").remove();
                $("#limitscale").remove();
                $("#output").remove();
                $("#customfmt").remove();
                
            }
            
            function launch(val) {
                $("#FlowProcessingForm").append("<input type='hidden' id='filter' name='filter' value='' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='filter_name' name='filter_name' value='none' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='modeselect' name='modeselect' value='1' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='process' name='process' value='Process' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='listN' name='listN' value='0' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='topN' name='topN' value='0' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='stattype' name='stattype' value='"+val+"' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='statorder' name='statorder' value='0' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='aggr_proto' name='aggr_proto' value='checked' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='aggr_srcport' name='aggr_srcport' value='checked' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='aggr_srcip' name='aggr_srcip' value='checked' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='aggr_srcselect' name='aggr_srcselect' value='0' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='aggr_srcnetbits' name='aggr_srcnetbits' value='24' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='aggr_dstport' name='aggr_dstport' value='checked' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='aggr_dstip' name='aggr_dstip' value='checked' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='aggr_dstselect' name='aggr_dstselect' value='0' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='aggr_dstnetbits' name='aggr_dstnetbits' value='24' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='limitwhat' name='limitwhat' value='0' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='limithow' name='limithow' value='0' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='limitsize' name='limitsize' value='0' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='limitscale' name='limitscale' value='0' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='output' name='output' value='extended' />");
                $("#FlowProcessingForm").append("<input type='hidden' id='customfmt' name='customfmt' value='' />");
                
                send($('#interface').val(),$('#interface option:selected').text() );
                
                $("#wsize").remove();
                $("#filter").remove();
                $("#filter_name").remove();
                $("#modeselect").remove();
                $("#process").remove();
                $("#listN").remove();
                $("#topN").remove();
                $("#stattype").remove();
                $("#statorder").remove();
                $("#aggr_proto").remove();
                $("#aggr_srcport").remove();
                $("#aggr_srcip").remove();
                $("#aggr_srcselect").remove();
                $("#aggr_srcnetbits").remove();
                $("#aggr_dstport").remove();
                $("#aggr_dstip").remove();
                $("#aggr_dstselect").remove();
                $("#aggr_dstnetbits").remove();
                $("#limitwhat").remove();
                $("#limithow").remove();
                $("#limitsize").remove();
                $("#limitscale").remove();
                $("#output").remove();
                $("#customfmt").remove();
            }
            $(document).ready(function() {
                $('#interface').change(function() {
                    send($(this).val(),$('#interface option:selected').text() );
                });
                <?
                if (isset($_POST['ip'])){
                ?>
                send('<?=($_POST['ip'])?>', $('#interface option:selected').text());
                <?
                }
                ?>
            });
            function send(ip,name){
                var newremoteconsole
                var nfsen_in_frame = <?=($nfsen_in_frame)?>;
                $("#FlowProcessingForm").attr("action", "https://" + ip + $("#FlowProcessingForm").attr("laction"));
                if(nfsen_in_frame || ip == '<?=($_SERVER['SERVER_ADDR'])?>'){
                    $("#FlowProcessingForm").attr("target", "nfsen");
                }else{
                    $("#FlowProcessingForm").attr("target", ip);
                    var width = 1200;
                    var height = 720;
                    var left = (screen.width/2)-(width/2);
                    var top = (screen.height/2)-(height/2);
                    var strWindowFeatures = "menubar=no,location=no,resizable=yes,scrollbars=yes,status=yes, toolbar=no, personalbar=yes, chrome=yes, centerscreen=yes, top="+top+", left="+left+", height="+height+",width="+width;
                    newremoteconsole = window.open('about:blank',ip, strWindowFeatures);
                }
                
                if (ip != '<?=($_SERVER['SERVER_ADDR'])?>'){
                    $("#FlowProcessingForm").append("<input type='hidden' id='login' name='login' value='<?=($_SESSION["_remote_login"])?>' />");
                    $("#FlowProcessingForm").append("<input type='hidden' id='name' name='name' value='" + name + "' />");
                }else{
                    $("#FlowProcessingForm").append("<input type='hidden' id='process' name='process' value='Process' />");
                }
                <?
                if (isset($_POST))
                {
                    foreach($_POST as $key => $value)
                    {
                        if ($key="srcselector") continue;
                        if(is_array($value))
                        {
                            foreach($value as $valuearray)
                            {
                                ?>
                                $("#FlowProcessingForm").append("<input type='hidden' name='<?=($key)?>[]' value='<?=($valuearray)?>' />");
                                <?
                            }
                        }else{
                            ?>
                            $("#FlowProcessingForm").append("<input type='hidden' name='<?=($key)?>' value='<?=($value)?>' />");
                            <?
                        }
                    }
                }
                ?>
                if(!(nfsen_in_frame || ip == '<?=($_SERVER['SERVER_ADDR'])?>')){
                    newremoteconsole.focus();
                }
                $("#FlowProcessingForm").submit();
                if (ip != '<?=($_SERVER['SERVER_ADDR'])?>'){
                    $("#login").remove();
                }else{
                    $("#process").remove();
                }
                <?php
                if (isset($_POST))
                {
                    foreach($_POST as $key => $value)
                    {
                        if(is_array($value))
                        {
                            foreach($value as $valuearray)
                            {
                                ?>
                                if ($("#<?=($key)?>").length()){ $("#<?=($key)?>").remove(); }
                                <?
                            }
                        }else{
                            ?>
                            if ($("#<?=($key)?>").length()){ $("#<?=($key)?>").remove(); }
                            <?
                        }
                    }
                }
                ?>
                
            }
        </script>
    </head>
    <body>
        <table class='nobborder' style='width:100%'>
            <tr><td class='noborder' style='text-align:left;width: 40%'>
                <?=_("Traffic Console")?>:
                <SELECT NAME="interface" SIZE=1 id="interface">
                    <OPTION VALUE="<?=($_SERVER['SERVER_ADDR'])?>">Local</OPTION>
                    <?php
                        foreach($webinterfaces_list as $webinterface)
                        {
                            $selected = (isset($_POST['ip']) && $webinterface->get_ip() == $_POST['ip']) ? "selected='selected'" : "" ;
                            echo("<option value='".$webinterface->get_ip()."' ".$selected.">".$webinterface->get_name()." [".$webinterface->get_ip()."]"."</option>");
                        }
                    ?>
                </SELECT>
            </td><td class='noborder' style='text-align:right; width:60%'>
                [ <a href='javascript:lastsessions()'><?=_("List last 500 sessions")?></a> ]
                &nbsp;[ <a href='javascript:launch("2","<?=$type?>")'><?=_("Top 10 Src IPs")?></a> ]
                &nbsp;[ <a href='javascript:launch("3","<?=$type?>")'><?=_("Top 10 Dst IPs")?></a> ]
                &nbsp;[ <a href='javascript:launch("5","<?=$type?>")'><?=_("Top 10 Src Port")?></a> ]
                &nbsp;[ <a href='javascript:launch("6","<?=$type?>")'><?=_("Top 10 Dst Port")?></a> ]
                &nbsp;[ <a href='javascript:launch("13","<?=$type?>")'><?=_("Top 10 Proto")?></a> ]
            </td></tr>
        </table>
        <form action="<?php echo $self;?>" id="FlowProcessingForm" target="nfsen" method="POST" laction="<?php echo $self;?>"></form>
    </body>
</html>