<?php
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

require_once 'classes/Session.inc';
require_once 'classes/Av_center.inc';
require_once 'ossim_db.inc';


//Only admin can access
Avc_utilities::check_access('', '/ossim/session/login.php');

$db       = new ossim_db();
$conn     = $db->connect();

$where = "WHERE uuid='".$uuid."'";
$current_local_data = Av_center::get_current_local_data($conn, $where);
$st                 = Av_center::avc_collector_status($uuid);

$status_data = $st['output'];

/*
echo "<pre>";
    print_r($status_data);
echo "</pre>";
*/
    
$yes_ok = array("yes" => "Yes", "no" => "No");


$hostname         = $current_local_data['data']['hostname'];
$admin_ip         = $current_local_data['data']['admin_ip'];
$admin_interfaces = $current_local_data['data']['interface'];
$profiles         = $current_local_data['data']['profile'];
$version          = $current_local_data['data']['version'];
$language         = $current_local_data['data']['language'];

//Proxy Configuration
$proxy_conf = $current_local_data['data']['update_proxy'];

if ( $proxy_conf == 'manual')
{
    $proxy_url  = $current_local_data['data']['update_proxy_dns'];
    $proxy_user = $current_local_data['data']['update_proxy_user'];
    $proxy_pass = $current_local_data['data']['update_proxy_pass'];
    $proxy_port = $current_local_data['data']['update_proxy_port'];
}
else
{
    $proxy_url  = '';
    $proxy_user = '';
    $proxy_pass = '';
    $proxy_port = '';
}



$snmp_enable      = $yes_ok[$current_local_data['data']['snmpd']];
$snmp_trap_enable = $yes_ok[$current_local_data['data']['snmptrap']];
$firewall         = $yes_ok[$current_local_data['data']['firewall_active']];
    
if ( $status_data['System Status']['status'] != 'UP' )
{
    $status           = "<span class='red'>"._("Down")."</span>";
}
else
{
    $status           = "<span class='green'>"._("UP")."</span>";
    
    $system_time        = $status_data['System Status']['system_time'];
    $system_uptime      = $status_data['System Status']['system_uptime'];
    $running_processes  = $status_data['System Status']['running_proc'];
    $load_average       = $status_data['System Status']['loadaverage'];
    $current_sessions   = $status_data['System Status']['current_sessions'];
    
    //CPU Usange
    $cpu_info         = $status_data['CPU'];
    $cpu_proc         = $status_data['CPU Info'];
    
          
    //Real memory
    $rmt = $status_data['System Status']['memtotal'];
    $rmu = $status_data['System Status']['memused'];
    $rmf = $status_data['System Status']['memfree'];
    $rmp = $status_data['System Status']['percent_memused'];
           
    //Virtual memory
    $vmt = $status_data['System Status']['virtualmem'];
    $vmu = $status_data['System Status']['virtualmemused'];
    $vmf = $status_data['System Status']['virtualmemfree'];
    $vmp = $status_data['System Status']['percent_virtualmemused'];
    
    //Disk Usage
    $mounted_disks = explode(";", $status_data['Disk usage']['mounted']);
    
    //Network
    $nt = Av_center::avc_collector_network($uuid);
    $network_data = $nt['output'];
}


//Software
    
$sf = Av_center::avc_collector_software($uuid);
$software_data = $sf['output']['Software'];
    
$packages_installed        = ( is_numeric($software_data['packages_installed']) )        ? $software_data['packages_installed']        : _("Unknown");
$packages_installed_failed = ( is_numeric($software_data['packages_installed_failed']) ) ? $software_data['packages_installed_failed'] : _("Unknown");
$packages_pending_updates  = ( is_numeric($software_data['packages_pending_updates']) )  ? $software_data['packages_pending_updates']  : _("Unknown");
$packages_pending_purge    = ( is_numeric($software_data['packages_pending_purge']) )    ? $software_data['packages_pending_purge']    : _("Unknown");
$latest_update             = ( !empty($software_data['latest_update']) )                 ? $software_data['latest_update']             : _("Unknown");


/*****************************************************************
*****               Valor no calculado todavía              ******
******************************************************************/
$access_repositories       = "Ok";


?>


<table id='t_home'>
    <tr>
        <td class='sep_panel_2'>
            <div class='panel wp2 p_left'>
                <div class='panel_header'>
                    <div class='phl_action'></div>
                    <div class='phc_action'><span><?php echo _("SYSTEM STATUS")?></span></div>
                    <div class='phr_action'></div>
                </div>
                <div class='panel_body'>
                    <?php 
                    if ( $status_data['System Status']['status'] == 'UP' )
                        include 'system_status.php';
                    else
                    {
                        echo Avc_utilities::show_tooltip('error', _("Information not available"), "margin: 150px auto 0px auto;text-align:center;");
                    }
                    ?>
                </div>
                <div class='panel_footer'></div>
            <div>
        </td>
        <td class='sep_panel_2'>
            <div class='panel wp2 p_right'>
                <div class='panel_header'>
                    <div class='phl_action'></div>
                    <div class='phc_action'><span><?php echo _("ALIENVAULT STATUS")?></span></div>
                    <div class='phr_action'></div>
                </div>
                <div class='panel_body'>
                    <?php 
                    if ( $status_data['System Status']['status'] == 'UP' )
                        include 'alienvault_status.php';
                    else
                    {
                        echo Avc_utilities::show_tooltip('error', _("Information not available"), "margin: 150px auto 0px auto;text-align:center;");
                    }
                    ?>
                </div>
                <div class='panel_footer'></div>
            <div>
        </td>
    </tr>
    
    <tr>
        <td class='sep_panel_2'>
            <div class='panel wp2 p_left'>
                <div class='panel_header'>
                    <div class='phl_action'></div>
                    <div class='phc_action'><span><?php echo _("SOFTWARE")?></span></div>
                    <div class='phr_action'></div>
                </div>
                <div class='panel_body'>
                    <?php 
                    //if ( $status_data['System Status']['status'] != 'UP' )
                    if ( 1 )
                        include 'software.php';
                    else
                    {
                        echo Avc_utilities::show_tooltip('error', _("Information not available"), "margin: 150px auto 0px auto;text-align:center;");
                    }
                    ?>
                </div>
                <div class='panel_footer'></div>
            <div>
        </td>
        <td class='sep_panel_2'>
            <div class='panel wp2 p_right'>
                <div class='panel_header'>
                    <div class='phl_action'></div>
                    <div class='phc_action'><span><?php echo _("NETWORK")?></span></div>
                    <div class='phr_action'></div>
                </div>
                <div class='panel_body'>
                    <?php 
                    //if ( $status_data['System Status']['status'] == 'UP' )
                    
                    if ( 1 )
                        include 'network.php';
                    else
                    {
                        echo Avc_utilities::show_tooltip('error', _("Information not available"), "margin: 150px auto 0px auto;text-align:center;");
                    }
                    ?>
                </div>
                <div class='panel_footer'></div>
            <div>
        </td>
    </tr>
</table>  
