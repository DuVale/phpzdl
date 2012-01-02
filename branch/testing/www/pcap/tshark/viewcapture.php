<?php
// session_destroy();
// session_start();
// unset ( $_SESSION['TSHARK_file']);
// unset ( $_SESSION['TSHARK_filter']);
// unset ( $_SESSION['TSHARK_tshark']);
ini_set("max_execution_time","300"); 

require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
require_once ('classes/Scan.inc');
require_once ('classes/Util.inc');
require_once ("includes/tshark.inc");

Session::logcheck("MenuMonitors", "TrafficCapture");

$scan_name  = GET("scan_name");
$sensor_ip  = GET("sensor_ip");

$error_tshark=FALSE;
$output = `uname -a`;
if (!(preg_match('/64\sGNU\/Linux/',$output))){
    $error_tshark=TRUE;
}else{

    if (isset($scan_name) && isset($sensor_ip))
    {
        ossim_valid($scan_name, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_DOT, 'illegal:' . _("Capture name"));
        ossim_valid($sensor_ip, OSS_IP_ADDR, 'illegal:' . _("Sensor ip"));

        if (ossim_error()) {
            die(ossim_error());
        }

        $db     = new ossim_db();
        $dbconn = $db->connect();

        $scan_info = explode("_", $scan_name);
        $users = Session::get_users_to_assign($dbconn);

        $my_users = array();
        foreach( $users as $k => $v ) {  $my_users[$v->get_login()]=1;  }

        if($my_users[$scan_info[1]]!=1 && !Session::am_i_admin() )  return;
        $scan = new TrafficScan();
        $file = $scan->get_pcap_file_tshark($scan_name,$sensor_ip);
        if (preg_match('/Error\scode/',$file))
            die(_("Has not successfully downloaded the file: ").$file);

        unset($_SESSION['TSHARK_file']);
        unset($_SESSION['TSHARK_filter']);
        unset($_SESSION['TSHARK_tshark']);
    }else{
        $filter      = isset($_POST['filter']) ? checkfilter($_POST['filter']) : "" ;
        $file        = $_SESSION['TSHARK_file'] ;
    }

    if (!isset($_SESSION['TSHARK_tshark']) || !isset($_SESSION['TSHARK_tshark']) || $_SESSION['TSHARK_filter']!=$filter || $_SESSION['TSHARK_file']!=$file ){
        $tshark = new Tshark($file,$filter);
        $_SESSION['TSHARK_tshark'] = serialize($tshark);
        $_SESSION['TSHARK_filter'] = $filter;
        $_SESSION['TSHARK_file'] = $file;
    }else{
        $tshark = unserialize($_SESSION['TSHARK_tshark']);
    }

    $error=$tshark->get_error();

    if ($error != "")
        $error="<div class=error>".$error."</div>";
        
    $filter = $tshark->get_filter();
    $filter_show = $filter != "" ? $filter : "ip.src == 10.0.0.1" ;
}
?>
<!DOCTYPE html>
<html>
    <head>
        <script type="text/javascript" src="js/jquery.min.js"></script>
        <script type="text/javascript" src="js/jquery.sparkline.min.js"></script>
        <script type="text/javascript" src="js/jquery.tree.js"></script>
        <script type="text/javascript" src="js/greybox.js"></script>
        <link href="style/sharkvault.css" type="text/css" rel="stylesheet" />
        <link href="style/greybox.css" type="text/css" rel="stylesheet" />  
        <?php
            if (!$error_tshark)
            {
        ?>
            <script type="text/javascript">
                $(document).ready(function() {
                    var data = [<?php echo($tshark->get_data_for_graphs("sparkline")); ?>];
                    $('.inlinesparkline').sparkline(data, { lineColor: '#8CC221', fillColor: '#E2FFE0', width: '100%', height: '40px', minSpotColor : '', maxSpotColor : '', spotColor: '' });
                    
                    
                    
                    $('#psml').load('psml.php', function(){
                        var id=$('#psml')[0].firstChild.rows[0].id;
                        $("#pdml").load("pdml.php?id="+id, function() {
                            $('ul.packet').tree({default_expanded_paths_string : ''});
                        });
                        $("#bin").load("bin.php?id="+id);
                    });
                });
                $(function() {
                    $("#psml").click(function(e) {
                        document.getElementById("pdml").innerHTML="<img src='pixmaps/loading3.gif' /> Loading...";
                        document.getElementById("bin").innerHTML="<img src='pixmaps/loading3.gif' /> Loading...";
                        
                        var value=e.target.parentNode.id;
                        var valueclass=e.target.parentNode.className;
                        
                        $.each(e.target.parentNode.parentNode.rows, function(index, value) { 
                          nrow=((index)%2)+1
                          value.className = 'row'+nrow
                        });
                        
                        if (valueclass == 'row1' || valueclass == 'row1sel') {
                            e.target.parentNode.className = 'row1sel';
                        } else if (valueclass == 'row2' || valueclass == 'row2sel') {
                            e.target.parentNode.className = 'row2sel';
                        }
                        
                        
                        $('#pdml').load('pdml.php?id='+value, function() {
                            $('ul.packet').tree({default_expanded_paths_string : ''});
                        });
                        $('#bin').load('bin.php?id='+value);
                        return false;
                    })
                })

                
                GB_TYPE = '';
                
                function GB_load(url, h) {
                    var h = ( h != '' ) ? h : "";
                    GB_show("Loading graphs...",url, h,"840",15);
                   
                    return false;
                }
                
            </script>
        <?php
        }
        ?>
        <title>SharkVault</title>
    </head>

    <body style="text-align:center; margin:0px 0px 0px 0">
    <?php
        if ($error_tshark){
            echo("<div class='ossim_info'>"._("Available only in 64 bit installation")."</div></body>");
            die();
        }
    ?>
        <div id="padre" style="height:100%; width:100%;">
        <!-- <table class="noborder" style="text-align:center; margin:0; width:100%; heigth:100%">-->
        <?php
            echo($error);
            if ($tshark->critical_error)
                die();
        ?>
            <div class="head">
                <table style='width:100%;'><tr>
                    <td class='fileinfo'><?php echo($tshark->description); ?></td>
                    <td class='sparkline'><span class="inlinesparkline"></span></td>
                    <td class='toolbar'>
                        <form method='post' action='viewcapture.php' name='FilterForm'>
                            <span class='divider'>Filter:</span>

                            <input type='text' size="25" name="filter" class='ui-corner-all' id="filter-field" placeholder="<?php echo($filter_show); ?>" autocorrect="off" autocapitalize="off" value='<?php echo($filter); ?>'/>
                            
                            <a style="text-decoration:none;cursor: pointer;" onclick="FilterForm.submit()">
                                <span class="buttonlink">
                                    <img border="0" align="absmiddle" style="padding-bottom:2px;padding-right:8px" src="pixmaps/funnel--arrow.png">
                                    <?php echo _("Apply")?>
                                </span>
                            </a>
                            &nbsp;
                            <a style="text-decoration:none;cursor: pointer;" onclick="FilterForm.filter.value='';FilterForm.submit()">
                                <span class="buttonlink">
                                    <img border="0" align="absmiddle" style="padding-bottom:2px;padding-right:8px" src="pixmaps/delete.gif">
                                    <?php echo _("Clear")?>
                                </span>
                            </a>
                            
                            <span class='divider'> &nbsp;|</span>
                            <a style="text-decoration:none;cursor: pointer;" onclick="GB_load('statistics.php', '485')" >
                                <span class="buttonlink">
                                <img border="0" align="absmiddle" style="padding-bottom:2px;padding-right:8px" src="pixmaps/menu/dashboards.gif">
                                    <?php echo _("Graphs")?>
                                </span>
                            </a>
                        </form>
                    </td>
                </tr></table>
            </div>
            <div style="clear:both;"></div> 
            <?
            echo("<div style='text-align:left;'>");
                echo("<table id='head_psml' width=100% cellspacing=0 cellpadding=0 >");
                    $tshark->print_psml_head();
                echo("</table>");
            echo("</div>");

            echo("<div style='height:30%; width:100%; overflow:auto;'><div class='psml' id='psml' style='cursor: pointer;'><img src='pixmaps/loading3.gif' /> "._("Loading...")."</div></div>");
            echo("<div style='text-align:left; height:30%; width:100% !important; overflow:auto; border-bottom: 3px solid #D2D2D2; border-top: 3px solid #D2D2D2;'><div id='pdml' style='border 1px 0 1px 0 #F00'><img src='pixmaps/loading3.gif' /> "._("Loading...")."</div></div>");
            echo("<div style='text-align:left; height:29%; overflow:auto;'><div id='bin'><img src='pixmaps/loading3.gif' /> "._("Loading...")."</div></div>");
            ?>
        </div>
    </body>
</html>