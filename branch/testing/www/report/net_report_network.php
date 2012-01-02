<?php

$height      = ( count($param['assets']['data']['ip_cidr']) > 1 ) ? "height: 215px;" : "height: 100%;";
$table_style = ( $asset_counter > 1 ) ? "margin-top: 10px; width: 100%; $height" : "margin-top:1px; width: 100%; $height";

$ntop_links  = Sensor::get_net_sensor_link($conn, $asset);
$ntop_link   = $ntop_links["ntop"];

?>

<table style='<?php echo $table_style?>'>
	<tr>
        <?php $network_traffic = (  $param['assets']['data']['ip_cidr'] > 1 ) ? _("Network Traffic"). " <i>($asset)</i>" : _("Network Traffic"); ?>
        <td class="headerpr" height="20"><?php echo $network_traffic?></td>
    </tr>

	<tr>
		<td id='<?php echo "graph_".generate_id($asset)."_1"?>'><img src="../pixmaps/ntop_graph_thumb_gray.gif"></td>
	</tr>
	
    <tr>
		<td><img src="/<?php echo $ntop_links["sensor_ntop"];?>/graph.gif"> <a href="<?php echo $ntop_link?>/plugins/rrdPlugin?action=list&key=interfaces/eth0&title=interface%20eth0&hmenu=Network&smenu=Profiles" target="main"><?=gettext("Traffic Detail")?></a></td>
	</tr>
	
    <tr>
		<td style="text-align:right;padding-right:20px"><a style="color:black" href="../ntop/index.php?opc=services&sensor=<?php echo $ntop_links["sensor_ntop"];?>&hmenu=Network&smenu=Profiles" target="main"><b><?=_("More")?> >></b></a></td>
	</tr>
</table>