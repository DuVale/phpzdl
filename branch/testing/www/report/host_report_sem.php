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
require_once ("geoip.inc");
$gi     = geoip_open("/usr/share/geoip/GeoIP.dat", GEOIP_STANDARD);
$config = parse_ini_file("../sem/everything.ini");

$color_words = array(
    'warning',
    'error',
    'failure',
    'break',
    'critical',
    'alert'
);

$lnk        = "";
$logger_url = "../sem/index.php";

if ( $host != "" ) 
{
	$lnk        = "ip=$host";
    $logger_url = "../sem/index.php?hmenu=SEM&smenu=SEM&query=".urlencode($lnk);
}


    
$table_style = ( $asset_counter > 1 ) ? "margin-top: 10px; width: 100%;" : "margin-top:1px; width: 100%;";
?>

<table style='<?php echo $table_style?>'>    
    <tr>
		<td class="headerpr">
            <?php $raw_logs = (  count($param['assets']['data']['ip_cidr']) > 1 ) ? _("Raw Logs"). " ($host)" : _("Raw Logs"); ?>
            <a style="color:black" href="<?php echo $logger_url?>">
            <?php echo $raw_logs; ?>
            </a>
        </td>
	</tr>
    
	<?php 
    // Key to retrieve data
	$key = ( $host == '' ) ? 'any' : $host;
	
    list($sem_events_week,$sem_foundrows_week,$sem_date,$sem_wplot_y,$sem_wplot_x) = $sem_events_data[$key];
        
	if (count($sem_events_week) > 0) 
	{ 
		// GRAPH
		list($x, $y, $xticks, $xlabels, $tr, $interval) = Status::range_graphic("week");
		
        $unique_id = generate_id($host);
        $id_graph  = "plotareasem_".$unique_id;
        
        $graph   = '<div id="'.$id_graph.'" class="plot" style="margin:auto;"></div>';
				
        $yaxis = array();
        foreach ($xticks as $k=>$v) {
			$yaxis[$xlabels[$k]] = ( intval($sem_wplot_y[$xlabels[$k]]) > 0 ) ? $sem_wplot_y[$xlabels[$k]] : 0;
		}
                           
		$plot = plot_graphic($id_graph, 60, 800, $sem_wplot_x, $yaxis, $xticks, $xlabels, false, "239, 214, 209", '', $interval);
        
		?>
		<tr>
			<td style="text-align:center">
				<table align="center">
					<tr>
                        <td><?php echo $graph.$plot?></td>              
                    </tr>
				</table>
			</td>
		</tr>
	
		<tr>
			<td>
				<table border='0' width='100%' cellpadding='2'>
					<tr>
						<?php if ($from_remote) { ?><th><?php echo _("Server") ?></th><?php } ?>
						<th><?php echo _("Date")?></th>
						<th><?php echo _("Event type")?></th>
						<th><?php echo _("Sensor")?></th>
						<th><?php echo _("Source")?></th>
						<th><?php echo _("Dest")?></th>
						<th><?php echo _("Raw Logs")?></th>
					</tr>
					<?php
					$cont = 0;
					foreach($sem_events_week as $res) 
					{
						//if ($cont < 5) 
						//{
							$bgcolor = (($cont)%2==0) ? "#EFE0E0" : "#FFFFFF";
							$res     = str_replace("<", "", $res);
							$res     = str_replace(">", "", $res);
							//entry id='2' fdate='2008-09-19 09:29:17' date='1221816557' plugin_id='4004' sensor='192.168.1.99' src_ip='192.168.1.119' dst_ip='192.168.1.119' src_port='0' dst_port='0' data='Sep 19 02:29:17 ossim sshd[2638]: (pam_unix) session opened for user root by root(uid=0)'
							//echo $res."\n\n";

							if (preg_match("/entry id='([^']+)'\s+fdate='([^']+)'\s+date='([^']+)'\s+plugin_id='([^']+)'\s+sensor='([^']+)'\s+src_ip='([^']+)'\s+dst_ip='([^']+)'\s+src_port='([^']+)'\s+dst_port='([^']+)'\s+tzone='([^']+)'+\s+(datalen='\d+'\s+)?data='(.*)'/", $res, $matches))
							{
								$lf        = explode(";", $res);
								// added 127.0.0.1 if not exists
						    	if (is_numeric($lf[count($lf)-1])) $lf[] = "127.0.0.1";
						    	//
						        $logfile             = $lf[count($lf)-3];
						        $current_server      = urlencode($lf[count($lf)-1]);
						        $current_server_ip   = $current_server;
						        $current_server      = $ip_to_name[$current_server];
                                
						        if ($current_server_ip=="127.0.0.1" && $current_server=="") 
                                    $current_server="local";
						        
								# Clean data => matches[12] may contains sig and/or plugin_sid
								$plugin_sid = "";
								if (preg_match("/' plugin_sid='(\d+)/",$matches[12],$fnd)) $plugin_sid = $fnd[1];
								$matches[12] = preg_replace("/' plugin_sid=.*/","",$matches[12]);
								$signature = "";
								if (preg_match("/' sig='(.*)('?)/",$matches[12],$found)) {
									$signature = $found[1];
									$matches[12] = preg_replace("/' sig=.*/","",$matches[12]);
								}
						
						        # decode if data is stored in base64
						        $data = $matches[12];

								$query     = "select name from plugin where id = " . intval($matches[4]);
								
								if (!$rs = & $conn->Execute($query)) {
									print $conn->ErrorMsg();
									exit();
								}
								
								$plugin = htmlspecialchars($rs->fields["name"]);
								
								if ($plugin == "") {
									$plugin = intval($matches[4]);
								}
								
								$red     = 0;
								$color   = "black";
								$date    = $matches[2];
								$sensor  = $matches[5];
								$src_ip  = $matches[6];
								$dst_ip  = $matches[7];
								$country = strtolower(geoip_country_code_by_addr($gi, $src_ip));
								$country_name = geoip_country_name_by_addr($gi, $src_ip);
								
								$country_img_src = (  $country ) ? " <img src=\"/ossim/pixmaps/flags/" . $country . ".png\" alt=\"$country_name\" title=\"$country_name\">" : "";
								 
								
								$dst_ip       = $matches[7];
								$country      = strtolower(geoip_country_code_by_addr($gi, $dst_ip));
								$country_name = geoip_country_name_by_addr($gi, $dst_ip);
								
								$country_img_dst = ( $country ) ? " <img src=\"/ossim/pixmaps/flags/" . $country . ".png\" alt=\"$country_name\" title=\"$country_name\">" : "";
								
								$src_port = $matches[8];
								$dst_port = $matches[9];
								$target = ($greybox) ? "target='main'" : "";
								
								$niplugin = str_replace("<", "[", $plugin);
								$niplugin = str_replace(">", "]", $niplugin);
								
                             	$line = "<tr>";
								
                                if ($from_remote) {
					            	$line .= "<td class='nobborder' style='border-right:1px solid #FFFFFF;text-align:center;' nowrap><table class='transparent' align='center'><tr><td class='nobborder' style='padding-left:5px;padding-right:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;border:0px;background-color:#".$logger_colors[$current_server]['bcolor'].";color:#".$logger_colors[$current_server]['fcolor']."'>$current_server</td></tr></table></td>";
					            }
								
                                $line .= "<td nowrap='nowrap' style='background: $bgcolor; padding:0px 2px;'>" . htmlspecialchars($matches[2]) . "</td>
											<td style='background: $bgcolor; padding:0px 2px;'><font color=\"$color\">$plugin</font></td>
											<td style='background: $bgcolor; padding:0px 2px;'>";
								$line.= "<font color=\"$color\">" . htmlspecialchars($matches[5]) . "</font></td><td style='background: $bgcolor; padding:0px 2px;'>";
								$line.= "<font color=\"$color\">" . htmlspecialchars($matches[6]) . ":</font>";
								$line.= "<font color=\"$color\">" . htmlspecialchars($matches[8]) . "</font></td><td style='background: $bgcolor; padding:0px 2px;'>";
								$line.= "<font color=\"$color\">" . htmlspecialchars($matches[7]) . ":</font>";
								$line.= "<font color=\"$color\">" . htmlspecialchars($matches[9]) . "</font></td>";
								
								if ($alt) 
								{
									$color = "grey";
									$alt = 0;
								} 
								else 
								{
									$color = "blue";
									$alt = 1;
								}
								
								$verified = - 1;
								/*
								if ($signature != '') {
									$sig_dec = base64_decode($signature);
									$verified = 0;
									$pub_key = openssl_pkey_get_public($config["pubkey"]);
									$verified = openssl_verify($data, $sig_dec, $pub_key);
								}
								*/
								
								$encoded_data = base64_encode($data);
                                
                                $raw_data = Util::wordwrap (Util::htmlentities($matches[12]), 110, "<br/>", true);
                                                              
                                $data     = "<td style='background: $bgcolor; padding:0px 2px; text-align:left'>".$raw_data.'</td>';
								
								if ($verified >= 0) 
								{
									if ($verified == 1) {
										$data.= '<img src="' . $config["verified_graph"] . '" height=15 width=15 alt="V" />';
									} else if ($verified == 0) {
										$data.= '<img src="' . $config["failed_graph"] . '" height=15 width=15 alt="F" />';
									} else {
										$data.= '<img src="' . $config["error_graph"] . '" height=15 width=15 alt="E" />';
										$data.= openssl_error_string();
									}
								}
								//$data.= '<a href="validate.php?log=' . $encoded_data . "&start=$start&end=$end&logfile=$logfile" . '" class="thickbox" rel="AjaxGroup" target="_blank"> <small>(Validate signature)</small></a>';
								$data.= "</td>";
								$line.= $data;
								$cont++;
							}
							
							print $line;
						//}
					}
					?>
				</table>
			</td>
		</tr>
	
		<tr>
			<td>
				<table>
					<tr>
						<td style="text-align:left"><b><?php echo $sem_foundrows_week?></b> <?=gettext("Raw Logs")?> <?=_("in")?> <b><?=_("week range")?></b></td>
						<td style="text-align:right;padding-right:20px"><a style="color:black" href="<?php echo $logger_url?>"><strong><?php echo _("More")?> >></strong></a></td>
					</tr>
				</table>
			</td>
		</tr>
		<?php
		} 
		else 
		{ 
			$host_txt = ( $host == 'any') ? _("No Raw Logs found in the System") : _("No Raw Logs found for")."<i>".$host."</i>"; 
			?>
			<tr><td valign='middle'><?php echo $host_txt;?></td></tr>
			
			<tr>
				<td>
					<table>
						<tr>
							<td style="text-align:right;padding-right:20px">
                               	<a style="color:black" href="<?php echo $logger_url?>"><strong><?php echo gettext("More")?> >></strong></a>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<?php
		} 
	?>
</table>
