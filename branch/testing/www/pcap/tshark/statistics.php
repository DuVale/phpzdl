<?php
session_start();
// session_destroy();
// session_start();
require_once("includes/tshark.inc");
?>
<script src="js/jquery.min.js" type="text/javascript"></script>
<script src="js/raphael/raphael.js" charset="utf-8"></script>
<script src="js/raphael/popup.js" type="text/javascript"></script>
<script src="js/raphael/drawgrid.js" type="text/javascript"></script>
<script type="text/javascript">
clearselbutton=function(){
    $.each($("buttonlink").context.activeElement.childNodes[0].children, function(index, value) { 
      value.className = 'buttonlink';
    });
};
</script>


<link href="style/sharkvault.css" type="text/css" rel="stylesheet" />
<?php

$tshark = unserialize($_SESSION['TSHARK_tshark']);
$filter = $tshark->get_filter();
?>

<div id="buttom" style='text-align:center; padding-top:25px; margin-left:10px; margin-right:10px;'>
<a class="buttonlink" style="text-decoration:none" onclick="clearselbutton();statistics1('holder')">
    <span>
        <img border="0" align="absmiddle" style="padding-bottom:2px;padding-right:8px" src="pixmaps/theme/route.png">
        <?php echo _("All Trafic")?>
    </span>
</a>
&nbsp;
<a class="buttonlink" style="text-decoration:none" onclick="clearselbutton();statisticsproto('holder')">
    <span>
        <img border="0" align="absmiddle" style="padding-bottom:2px;padding-right:8px" src="pixmaps/theme/ports.png">
        <?php echo _("Protocol Trafic")?>
    </span>
</a>
<?php
if (($filter!="") && !is_null($filter))
{
?>
&nbsp;
<a class="buttonlink" style="text-decoration:none" onclick="clearselbutton();statistics1f('holder')">
    <span>
        <img border="0" align="absmiddle" style="padding-bottom:2px;padding-right:8px" src="pixmaps/theme/net.png">
        <?php echo _("All Trafic Vs Filter Trafic")?>
    </span>
</a>
&nbsp;
<a class="buttonlink" style="text-decoration:none" onclick="clearselbutton();statisticsprotofilter('holder')">
    <span>
        <img border="0" align="absmiddle" style="padding-bottom:2px;padding-right:8px" src="pixmaps/theme/net_group.png">
        <?php echo _("Protocol Filter Trafic")?>
    </span>
</a>
<?php 
}
?>
</div>
<div id="holder"><img src='pixmaps/loading3.gif' /> <?php echo _("Loading...")?></div>

<?php

$tshark->get_data_for_graphs("All");
$tshark->get_data_for_graphs("Protocols");
if (($filter!="") && !is_null($filter))
{
    $tshark->get_data_for_graphs("AllFilter");
    $tshark->get_data_for_graphs("ProtocolsFilter");
}
?>
<script type="text/javascript">
    statistics1('holder');
</script>