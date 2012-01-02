<?php
/*****************************************************************************
*
*    License:
*
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

list($x, $y, $xticks, $xlabels, $tr, $interval) = Status::range_graphic('custom', $date_range);

$height      = ( count($param['assets']['data']['ip_cidr']) > 1 ) ? "height: auto;" : "height: 100%;";
$table_style = ( $asset_counter > 1 ) ? "margin-top: 10px; width: 100%; $height" : "margin-top:1px; width: 100%; $height";

// Key to retrieve data
$key = ( $host == '' ) ? 'any' : $host;

list($unique_events,$plots,$sim_numevents) = $sim_data[$key];
list($sim_ports,$sim_ipsrc,$sim_ipdst)     = $clouds_data[$key];

$_SESSION['host_report'] = $key;


?>

<table align="center" style='<?php echo $table_style?>'>
	<tr>
		<td class="headerpr" height="20">
            <?php 
                $security_events = (  count($param['assets']['data']['ip_cidr']) > 1 ) ? _("Security Events"). " <i>($host)</i>" : _("Security Events");
                $se_url          = "../forensics/base_qry_main.php?num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d&ip=".urlencode($host)."&date_range=All";
            ?>
            <a style="color:black" href="<?php echo $se_url?>"><?php echo $security_events; ?></a>
        </td>
	</tr>
	
    <?php
	// GRAPH
    $unique_id = generate_id($host);
	$id_graph  = "plotareag_".$unique_id;
    $graph     = '<div id="'.$id_graph.'" class="plot" style="margin: auto;"></div>';
	$sim_gplot = Status::get_SIM_Plot($host, $host, $date_from, $date_to);
   	$plot      = plot_graphic($id_graph, 60, 800, $x, $sim_gplot[0], $xticks, $xlabels, false, "131,137,175", $host, $interval);
		
	?>
	
    <tr>
		<td style="text-align:center">
			
			<div style='text-align: center; margin: auto; padding-bottom: 5px;'><?php echo $graph.$plot ?></div>
			<div><b><?php echo $sim_numevents?></b> <?php echo gettext("Unique Security Events in <b>selected range</b>");?></div>
		</td>
	</tr>
	
	<tr>
		<td>
			<table>
				<tr>
                   	<td><iframe frameborder="0" width="310" height="300" src="../graphs/draw_swf_graph.php?source_graph=host_report.php&host=<?php echo $key?>&width=270&height=270"></iframe></td>
					
					<td valign="middle">
						<table>
                            <tr>
                                <th><?php echo ("Most Common Event: Last Week")?></th>
                                <th><?php echo _("Total #"); ?></th>
                                <?php 
                                if ( $param['assets']['type'] == 'net' || $param['assets']['type'] == 'net_group' ) 
                                { 
                                    ?>
                                    <th><?php echo _("IP src"); ?></th>
                                    <th><?php echo _("IP dst"); ?></th>
                                    <?php 
                                } 
                                ?>
                                <th><?php echo _("Sensor #"); ?></th>
                                <th><?php echo _("Src/Dst Addr."); ?></th>
                                <th style='width: 450px'><?php echo _("Graph")?></th>
                            </tr>
						
                            <?php 
                            if (count($unique_events) < 1) 
                            { 
                                $num_fields = ( $param['assets']['type'] == 'net' || $param['assets']['type'] == 'net_group' ) ? 7 : 5;
                                $host_txt   = ( $param['assets']['type'] == 'any') ? _("No Unique Events found in the System") : _("No Unique Events found for")."<i>".$host."</i>"; 
                                ?>
                                <tr><td height='30px' colspan='<?php echo $num_fields?>'><?php echo $host_txt?></td></tr>
                                <?php
                            } 
                            else 
                            { 
                                $i = 0;
                                foreach ($unique_events as $ev) 
                                {
                                    if ($i >= 6) 
                                        continue;
                                    $color = (($i+1)%2==0) ? "#E1EFE0" : "#FFFFFF";
                                    
                                    // GRAPH
                                                                            
                                    $id_graph_uv = "plotarea".$i."_".generate_id($host);
                                    $graph       = '<div id="'.$id_graph_uv.'" class="plot" style="margin:auto;"></div>';
                                    $yy          = $plots[$i][0];
                                                                    
                                    $plot        = plot_graphic($id_graph_uv, 37, 350, $x, $yy, $xticks, $xlabels, false, "131,137,175", $host, $interval);
                                    $tmp_rowid   = "#1-(" . $ev['sid'] . "-" . $ev['cid'] . ")";
                                    ?>
                                        
                                    <tr>
                                        <td bgcolor="<?php echo $color?>"><a href="../forensics/base_qry_alert.php?submit=<?php echo rawurlencode($tmp_rowid)?>" style="text-align:left;color: #17457c;font-size:10px"><strong><?php echo $ev['sig_name']?></strong></a></td>
                                        <td bgcolor="<?php echo $color?>"><?php echo Util::number_format_locale($ev['sig_cnt'],0)?></td>
                                        <?php 
                                        if ( $param['assets']['type'] == 'net' || $param['assets']['type'] == 'net_group' )
                                        { 
                                            ?>
                                            <td bgcolor="<?php echo $color?>"><?php echo long2ip($ev['ip_s'])?></td>
                                            <td bgcolor="<?php echo $color?>"><?php echo long2ip($ev['ip_d'])?></td>
                                            <?php 
                                        } 
                                        ?>
                                        <td bgcolor="<?php echo $color?>"><?php echo $ev['num_sensors']?></td>
                                        <td bgcolor="<?php echo $color?>"><?php echo $ev['ip_src']?>/<?php echo $ev['ip_dst']?></td>
                                        <td class='center' style='width: 450px'><?php echo $graph.$plot?></td>
                                    </tr>
                                    <?php 
                                    $i++; 
                                }  
                            
                            } 
                            ?>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

<!--
<? if (count($sim_events) < 1) { ?>
<tr><td>No Security Events Found for <i><?=$host?></i></td></tr>
<? } else { ?>
<tr>
	<td class="nobborder">
		<table class="noborder" width="100%">
			<tr>
				<th>Event</th>
				<th>Date</th>
				<th>Source</th>
				<th>Destination</th>
				<th>Asst</th>
				<th>Prio</th>
				<th>Rel</th>
				<th>Risk</th>
				<th>L4-proto</th>
			</tr>
		<?php
		
		$i = 0;
		foreach ($sim_events as $sim_event) 
		{ 
			if ($i >= 5) 
				continue;
                
			$color         = ($i%2==0) ? "#F2F2F2" : "#FFFFFF";
			$current_sip32 = $sim_event['sip'];
			$current_sip   = baseLong2IP($current_sip32);
			$current_dip32 = $sim_event['dip'];
			$current_dip   = baseLong2IP($current_dip32);
			
			$current_oasset_s = $sim_event['oasset_s'];
			$current_oasset_d = $sim_event['oasset_d'];
			$current_oprio = $sim_event['prio'];
			$current_oreli = $sim_event['rel'];
			$current_oriskc = $sim_event['risk_c'];
			$current_oriska = $sim_event['risk_a'];
			$proto = IPProto2str($sim_event['proto']);

			if ($current_sip32 != "") 
			{
				$country = strtolower(geoip_country_code_by_addr($gi, $current_sip));
				$country_name = geoip_country_name_by_addr($gi, $current_sip);
				if ($country) {
					$country_img = " <img src=\"/ossim/pixmaps/flags/" . $country . ".png\" alt=\"$country_name\" title=\"$country_name\">";
				} else {
					$country_img = "";
				}
				$ip_aux = ($sensors[$current_sip] != "") ? $sensors[$current_sip] : (($hosts[$current_sip] != "") ? $hosts[$current_sip] : $current_sip);
				$ip_src = '<A HREF="base_stat_ipaddr.php?ip=' . $current_sip . '&amp;netmask=32">' . $ip_aux . '</A><FONT SIZE="-1">' . $current_sport . '</FONT>' . $country_img;
			} 
			else 
			{
				/* if no IP address was found check if this is a spp_portscan message
				* and try to extract a source IP
				* - contrib: Michael Bell <michael.bell@web.de>
				*/
				if (stristr($current_sig_txt, "portscan")) {
					$line = split(" ", $current_sig_txt);
					foreach($line as $ps_element) {
						if (ereg("[0-9]*\.[0-9]*\.[0-9]*\.[0-9]", $ps_element)) {
							$ps_element = ereg_replace(":", "", $ps_element);
							$ip_src = "<A HREF=\"base_stat_ipaddr.php?ip=" . $ps_element . "&amp;netmask=32\">" . $ps_element . "</A>";
						}
					}
				} else $ip_src = '<A HREF="' . $BASE_urlpath . '/help/base_app_faq.php#1">' . _UNKNOWN . '</A>';
			}
			if ($current_dip32 != "") 
			{
				$country = strtolower(geoip_country_code_by_addr($gi, $current_dip));
				$country_name = geoip_country_name_by_addr($gi, $current_dip);
				if ($country) {
					$country_img = " <img src=\"/ossim/pixmaps/flags/" . $country . ".png\" alt=\"$country_name\" title=\"$country_name\">";
				} else {
					$country_img = "";
				}
				$ip_aux = ($sensors[$current_dip] != "") ? $sensors[$current_dip] : (($hosts[$current_dip] != "") ? $hosts[$current_dip] : $current_dip);
				$ip_dst = '<A HREF="base_stat_ipaddr.php?ip=' . $current_dip . '&amp;netmask32">' . $ip_aux . '</A><FONT SIZE="-1">' . $current_dport . '</FONT>' . $country_img;
			} else $ip_dst = '<A HREF="' . $BASE_urlpath . '/help/base_app_faq.php#1">' . _UNKNOWN . '</A>';
			
			// Assets
			$asst = "<img src=\"../forensics/bar2.php?value=" . $current_oasset_s . "&value2=" . $current_oasset_d . "&max=5\" border='0' align='absmiddle'>&nbsp;";
			
			// Prio
			$prio = "<img src=\"../forensics/bar2.php?value=" . $current_oprio . "&max=5\" border='0' align='absmiddle'>&nbsp;";
			
			// Rel
			$rel = "<img src=\"../forensics/bar2.php?value=" . $current_oreli . "&max=9\" border='0' align='absmiddle'>&nbsp;";
			
			// Risk
			$sim_risk = "<img src=\"../forensics/bar2.php?value=" . $current_oriskc . "&value2=" . $current_oriska . "&max=9&range=1\" border='0' align='absmiddle'>&nbsp;";
						?>
			<tr>
				<td class="nobborder" style="background-color:<?=$color?>;border-right:1px solid white"><?=$sim_event['sig_name']?></td>
				<td bgcolor="<?=$color?>" style="border-right:1px solid white"><?=$sim_event['timestamp']?></td>
				<td bgcolor="<?=$color?>"><?=$ip_src?></td>
				<td bgcolor="<?=$color?>"><?=$ip_dst?></td>
				<td class="nobborder"><?=$asst?></td>
				<td class="nobborder"><?=$prio?></td>
				<td class="nobborder"><?=$rel?></td>
				<td class="nobborder"><?=$sim_risk?></td>
				<td class="nobborder" align="center"><?=$proto?></td>
			</tr>
			<? $i++; } ?>
			</table>
		</td>
	</tr>
	<? } ?>
-->
	<?
	
	
	// CLOUDS
	
	// Default font sizes
	$min_font_size = 12;
	$max_font_size = 35;
	?>
	
	<tr>
		<td>
			<table height="100%" id='clouds_table'>
				<tr>
					<td width="33%" valign="top">
						<table height="100%">
							<tr><th height="30"><?php echo _("Security Event Destination Ports")?></th></tr>
							<?php 
                           
							if (count($sim_ports) < 1) 
							{ 
								?>
								<tr><td style="background:#F2F2F2; border: solid 1px #CBCBCB !important;"><?php echo _("No ports found")?></td></tr>
								<?php 
							} 
							else 
							{ 
								$minimum_count = min(array_values($sim_ports));
								$maximum_count = max(array_values($sim_ports));
								$spread = $maximum_count - $minimum_count;
								if ($spread == 0) 
									$spread = 1;
								
								?>
							<tr>
								<td style="background:#F2F2F2; border: solid 1px #CBCBCB !important">
								<?php 
									$draw_ports = false;
                                    foreach ($sim_ports as $port=>$port_val) 
									{ 
										if ($port == 0) 
                                            continue; 
                                        
                                        $draw_ports = true;
                                        
										$size = $min_font_size + ($port_val - $minimum_count) * ($max_font_size - $min_font_size) / $spread;
										?>
										<a href="../forensics/base_qry_main.php?tcp_port%5B0%5D%5B0%5D=+&tcp_port%5B0%5D%5B1%5D=layer4_dport&tcp_port%5B0%5D%5B2%5D=%3D&tcp_port%5B0%5D%5B3%5D=<?php echo $port?>&tcp_port%5B0%5D%5B4%5D=+&tcp_port%5B0%5D%5B5%5D=+&tcp_flags%5B0%5D=+&layer4=TCP&num_result_rows=-1&current_view=-1&submit=QUERYDBP&sort_order=sig_a&clear_allcriteria=1&clear_criteria=time" style="font-size:<?php echo$size?>px" class="tag_cloud"><?php echo$port?></a>&nbsp;
										<?php 
									}
                                    
                                    if ( $draw_ports == false ) 
                                        echo _("No ports found");
                                    
                                ?>                                 
								</td>
							</tr>
							<? } ?>
						</table>
					</td>
					
					<td width="33%" valign="top">
						<table height="100%">
							<tr><th height="30"><?php echo _("Security Event Sources")?></th></tr>
							<?php 
							if (count($sim_ipsrc) < 1) 
							{ 
								?>
								<tr><td style="background:#F2F2F2; border: solid 1px #CBCBCB !important;"><?php echo _("No Security Event Sources found")?></td></tr>
								<?php
							} 
							else 
							{ 
								$minimum_count = min(array_values($sim_ipsrc));
								$maximum_count = max(array_values($sim_ipsrc));
								$spread = $maximum_count - $minimum_count;
								if ($spread == 0) 
									$spread = 1;
							
							?>
							<tr>
								<td style="background:#F2F2F2; border: solid 1px #CBCBCB !important">
								<?php 
									foreach ($sim_ipsrc as $ip=>$ip_val) 
									{ 
										if ($ip == $host) continue; 
										$size = $min_font_size + ($ip_val - $minimum_count) * ($max_font_size - $min_font_size) / $spread;?>
										<a href="../forensics/base_qry_main.php?num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d&ip=<?php echo urlencode($ip)?>&date_range=All" style="font-size:<?php echo $size?>px" class="tag_cloud"><?php echo $ip?></a>&nbsp;&nbsp;
										<?php 
									} 
								?>
								</td>
							</tr>
							<?php 
							} 
							?>
						</table>
					</td>
					
					<td width="33%" valign="top">
						<table height="100%">
							<tr><th height="30"><?php echo _("Security Event Destinations")?></th></tr>
							<?php 
								if (count($sim_ipdst) < 1) 
								{ 
									?>
									<tr><td style="background:#F2F2F2; border: solid 1px #CBCBCB !important;"><?php echo _("No destinations found")?></td></tr>
									<?php 
								} 
								else 
								{ 
									$minimum_count = min(array_values($sim_ipdst));
									$maximum_count = max(array_values($sim_ipdst));
									$spread        = $maximum_count - $minimum_count;
									
									if ($spread == 0) 
										$spread = 1;
									?>
								<tr>
									<td style="background:#F2F2F2; border: solid 1px #CBCBCB !important">
									<?php 
									foreach ($sim_ipdst as $ip=>$ip_val) 
									{ 
										if ($ip == $host) 
											continue; 
										
										$size = $min_font_size + ($ip_val - $minimum_count) * ($max_font_size - $min_font_size) / $spread;?>
										
										<a href="../forensics/base_qry_main.php?num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d&ip=<?php echo urlencode($ip)?>&date_range=All" style="font-size:<?php echo $size?>px" class="tag_cloud"><?php echo $ip?></a>&nbsp;&nbsp;
										<?php 
									} 
									?>
									</td>
								</tr>
								<?php
								} 
								?>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	
	<tr>
		<td>
			<table>
				<tr>
					<td style="text-align:right;padding-right:20px">
						<a style="color:black" href="../forensics/base_qry_main.php?num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d&ip=<?php echo (  $param['assets']['type'] != "any") ? urlencode($host) : ""?>&date_range=All&clear_allcriteria=1&sort_order=time_d"><strong><?php echo gettext("More")?> >></strong></a>
					</td>
				</tr>
			</table>
		</td>
	</tr>

</table>
