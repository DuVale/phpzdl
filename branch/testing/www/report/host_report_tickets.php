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

require_once('classes/Host.inc');
require_once('classes/Util.inc');


if( $host != 'any' )
{
	if ($date_from != '' && $date_to != '')
	{
		$date_from   = ( preg_match("/^\d+\-\d+\-\d+$/",$date_from) ) ?  $date_from." 00:00:00" : $date_from;
		$date_to     = ( preg_match("/^\d+\-\d+\-\d+$/",$date_to) )   ?  $date_to." 23:59:59"   : $date_to;
        
        $tzc = Util::get_tzc();

		$date_filter = "AND (convert_tz(date,'+00:00','$tzc') BETWEEN  '$date_from' AND '$date_to')";
	}
	
	$where = "AND status='open' $date_filter ORDER BY date DESC";
	
	if ( $param['assets']['type'] == 'net' || $param['assets']['type'] == 'net_group' )
	{
		require_once('classes/CIDR.inc');
		
		if ( preg_match("/,/",$host) )
		{
			$cidrs = explode(",", $host);
			foreach ($cidrs as $cidr)
			{
				$range                            = CIDR::expand_CIDR($cidr, "SHORT", "LONG");
				$assets[$range[0].",".$range[1]]  = $host;
			}
		}
		else
		{
			$range    = CIDR::expand_CIDR($host, "SHORT", "LONG");
			$assets  = array($range[0].",".$range[1] => $host);
		}
		
	}
	else
	{
		$host_ip2ulong  = Host::ip2ulong($host);
		$assets         = array($host_ip2ulong => $host);
	}
	
	$incident_list2 =  Incident::get_list_filtered($conn, $assets, $where);
	$tick_num       = count($incident_list2);
	
}
else
{
	$where          = "AND status='open' ORDER BY date DESC";
	$incident_list2 = Incident::search($conn, array("status"=>"Open"), "priority", "DESC", 1, 6);
	$tick_num       = count($incident_list2);
}


$i_date       = "-";
$i_maxprio    = 0;
$i_maxprio_id = 1;

if (  $asset_counter < 2 )
{
    ?>
	<script type="text/javascript">
        $("#pbar").progressBar(75);
        $("#progressText").html('<?php echo gettext("Loading")?> <strong><?php echo gettext("Tickets")?></strong>...');
        $('#tickets_num_<?php echo generate_id($host)?>').html('<a href="../incidents/index.php?status=Open" class="whitepn"><?php echo Util::number_format_locale((int)$tick_num,0)?></a>');       
    </script>
    <?php
}

ob_flush();
flush();
usleep(500000);

$table_style = ( $asset_counter > 1 ) ? "margin-top: 10px; width: 100%; height: 100%;" : "margin-top:1px; width: 100%; height: 100%;";

?>

<script type="text/javascript">
    $('#statusbar_incident_max_priority_<?php echo generate_id($host)?>').html('<?php echo preg_replace("/\n|\r/","",Incident::get_priority_in_html("-"))?>');
    $('#statusbar_incident_max_priority_<?php echo generate_id($host)?>').removeAttr("href");
    $('#statusbar_incident_max_priority_txt_<?php echo generate_id($host)?>').removeAttr("href");
</script>

<table align="center" style='<?php echo $table_style?>'>
	<tr>
		<td class="headerpr" height="20">
            <a style="color:black" href="../incidents/index.php?status=Open&with_text=<?php echo urlencode("$host")?>">
                <?php
                $tickets = (  count($param['assets']['data']['ip_cidr']) > 1 ) ? _("Tickets"). " <i>($host)</i>" : _("Tickets");
                echo $tickets;
                ?>
            </a>
        </td>
	</tr>
       
	<?php 
	if (count($incident_list2) < 1) 
	{ 
		$host_txt = ( $host == 'any') ? _("No Tickets found in the System") : _("No Tickets found for")." <i>".$host."</i>"; 
		?>
		<tr><td><?php echo $host_txt;?></td></tr>
		<?php 
	} 
	else 
	{ 
		?>
		<tr>
			<td class="nobborder" valign="top">
				<table class="noborder">
					<tr>
						<th><?php echo _("Ticket")?></th>
						<th><?php echo _("Title")?></th>
						<?php if ( $network == 9 ) { ?><th>IP</th><?php } ?>
						<th><?php echo _("Priority")?></th>
						<th><?php echo _("Status")?></th>
					</tr>
					
					<?php 
					$i = 0; 
					foreach ($incident_list2 as $incident) 
					{ 
						if ($i > 5) 
                            break;
						
						if ($i_date == "-") 
						{ 
							$i_date = $incident->get_date();
							?>
							<script type="text/javascript">
							    $('#tickets_date_<?php echo generate_id($host)?>').html('<?php echo $i_date?>');
                            </script>
							<?php
						}
						?>
                        
						<tr <?php if ($row++ % 2) echo 'bgcolor="#E1EFE0"'; ?> valign="center">
							<td>
								<a href="../incidents/incident.php?id=<?php echo $incident->get_id() ?>"<?php if ($greybox) echo " target='main'" ?>><?php echo $incident->get_ticket(); ?></a>
							</td>
							<td>
								<strong>
									<a href="../incidents/incident.php?id=<?php echo $incident->get_id() ?>"<?php if ($greybox) echo " target='main'" ?>><?php echo $incident->get_title(); ?></a>
								</strong>
								<?php
								if ($incident->get_ref() == "Vulnerability") 
                                {
									$vulnerability_list = $incident->get_vulnerabilities($conn);
									// Only use first index, there shouldn't be more
									if (!empty($vulnerability_list)) {
										echo " <font color='grey' size='1'>(" . $vulnerability_list[0]->get_ip() . ":" . $vulnerability_list[0]->get_port() . ")</font>";
									}
								}
								?>
							</td>
							<?php if ($network == 9) { ?><td><? echo $incident->get_src_ips()?></td><? } ?>
							<?php
							$priority = $incident->get_priority();
							
                            if ($priority > $i_maxprio) {
								$i_maxprio = $priority;
								$i_maxprio_id = $incident->get_id();
							}
							?>
							<td><?php echo Incident::get_priority_in_html($priority) ?></td>
							<td><?php
							Incident::colorize_status($incident->get_status()) ?></td>
						</tr>
						
						<?php 
							$i++; 
					} 
					?>
						
						<script type="text/javascript">
                            $('#statusbar_incident_max_priority_<?php echo generate_id($host)?>').html('<?php echo preg_replace("/\n|\r/","",Incident::get_priority_in_html($i_maxprio))?>');
                            $('#statusbar_incident_max_priority_<?php echo generate_id($host)?>').attr("href", "../incidents/incident.php?id=<?php echo $i_maxprio_id?>");
                            $('#statusbar_incident_max_priority_txt_<?php echo generate_id($host)?>').attr("href", "../incidents/incident.php?id=<?php echo $i_maxprio_id?>");
                        </script>
				</table>
			</td>
		</tr>
		<tr><td style="text-align:right;padding-right:20px"><a style="color:black" href="../incidents/index.php?status=Open&with_text=<?php echo ($host!="any") ? urlencode("$host") : ""?>"><strong><?php echo _("More")?> >></strong></a></td></tr>
		<?php
	} 
	?>
    <tr><td></td></tr>
</table>