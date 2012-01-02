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
/**
* Class and Function List:
* Function list:
* Classes list:
*/
require_once 'classes/Host.inc';
require_once 'classes/Host_os.inc';


$nets   = array();

if ( $param['assets']['type'] == 'net' )
    $nets[] = $param['assets']['data']['net'];
else
    $nets   = $param['assets']['data']['nets'];

$asset_counter = 0;
$height        = ( $param['assets']['data']['ip_cidr'] > 1 ) ? "height:215px;" : "";

foreach ($nets as $k => $net)
{
    $ips       = $net->get_ips();
    $cidrs     = explode(",", $ips);
        
    foreach ($cidrs as $cidr)
    {
        $asset_counter++;
        
        $table_style = ( $asset_counter > 1 ) ? "margin-top: 10px; width: 100%; border:none; $height" : "margin-top: 1px; width: 100%; border:none; $height";
                
        ?>
        <table cellpadding='0' cellspacing='2' style='<?php echo $table_style?>'>    
            <tr>
                <td valign="top">
                    <table height="100%">
                        <tr>
                            <td colspan="2" class="headerpr" height="18"><?php echo _("Inventory")?> <i>(<?php echo $cidr ?>)</i></td>
                        </tr>
                        <tr>
                            <td class="nobborder" valign="top">
                                <table align="center" class="noborder" width="100%">
                                    <tr>
                                        <th><?=gettext("Name")?></th>
                                        <th><?=gettext("CIDRs")?></th>
                                    </tr>
                                    <tr>
                                        <td><?php echo $net->get_name(); ?> <a href="javascript:;" onclick="return false" class="scriptinfo_net" data='<?php echo get_net_data($conn, $net)?>'>
                                            <img src="../pixmaps/information.png" align="top" border="0" title="<?=_("Show Info")?>" alt="<?=_("Show Info")?>"></a>
                                        </td>
                                        <td>
                                            <?php 
                                                $output_ips     = $net->get_ips();
                                                $output_cidrs   = explode(",", $output_ips);
                                                $cidrs_to_print = "";
                                                foreach($output_cidrs as $output_cidr)
                                                {
                                                    $cidrs_to_print .= ($output_cidr != $cidr) ? $output_cidr.", " : "<b>".$output_cidr."</b>, ";
                                                }
                                                $cidrs_to_print = preg_replace('/,\s$/','',$cidrs_to_print);
                                                echo ($cidrs_to_print);
                                            ?>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
                
            <tr>
                <td valign='top'>
                    <table height="100%">
                        <tr>
                            <td width="70%" valign="top">
                                <table>
                                    <?php
                                    $where     = "";
                                    $sql_where = array();
                                    
                                    $exp          = CIDR::expand_CIDR($cidr,"SHORT","IP");
                                    $host_s_range = $exp[0];
                                    $host_e_range = end($exp);
                                    $sql_where[]  = "INET_ATON(ip) >= INET_ATON('$host_s_range') AND INET_ATON(ip) <= INET_ATON('$host_e_range')";
                                
                                    $where  = implode(") OR (", $sql_where);
                                    $where  = "(".$where.")";
                                    $where  = "WHERE ".$where;
                                                                                
                                    $host_list     = Host::get_list($conn, $where);
                                    
                                    if ( count($host_list) > 0 ) 
                                    {
                                        ?>
                                        <tr>
                                            <td>
                                            <div style="height:124px;overflow:auto">
                                                <table>
                                                    <tr>
                                                        <th> <?php echo gettext("Name"); ?></th>
                                                        <th> <?php echo gettext("IP"); ?></th>
                                                    </tr>
                                                    <?php
                                                    $i = 1;
                                                    foreach ($host_list as $h) 
                                                    { 
                                                        $bgcolor = ($i%2==0) ? "#E1EFE0" : "#FFFFFF";
                                                        ?>
                                                        <tr>
                                                            <td bgcolor="<?=$bgcolor?>">
                                                                <a href="host_report.php?asset_type=host&asset_key=<?=$h->ip?>" class="HostReportMenu" id="<?php echo $h->ip; ?>;<?php echo $h->hostname; ?>">
                                                                    <?php echo $h->hostname." ".(Host_os::get_os_pixmap($conn, $h->ip)); ?>
                                                                </a>
                                                            </td>
                                                            <td bgcolor="<?=$bgcolor?>" style="width: 150px" >
                                                                <a href="host_report.php?asset_type=host&asset_key=<?=$h->ip?>" class="HostReportMenu" id="<?php echo $h->ip; ?>;<?php echo $h->hostname; ?>">
                                                                    <?php echo $h->ip ?>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                        <?php 
                                                        $i++; 
                                                    } 
                                                    ?>
                                                </table>
                                            </div>
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
            </table>
        <?php
    }
}
?>