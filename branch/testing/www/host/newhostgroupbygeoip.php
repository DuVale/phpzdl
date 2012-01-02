<?php
/*****************************************************************************
*
*    License:333
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
* - get_country($ccode, $cname)
* - get_av_hostgroups($countries_selected)
* Classes list:
*/

require_once ('classes/Session.inc');
Session::logcheck("MenuPolicy", "PolicyHosts");

require_once ('ossim_db.inc');
require_once ('ossim_conf.inc');
require_once ('classes/Host.inc');
require_once ('classes/Host_group.inc');
require_once ('classes/Util.inc');


function get_country($ccode, $cname)
{
    $flag = "";
   
    if( $ccode != "" && $ccode != "local" )
        $flag = '../pixmaps/flags/'.$ccode.'.png';
           
    return $flag;
}  
    
function get_av_hostgroups($conn)
{
    
    require_once ('classes/Host.inc');
    include_once ('classes/GeoLoc.inc');
    $GeoLoc = new GeoLoc("/usr/share/geoip/GeoIP.dat", GEOIP_STANDARD);
    
    $av_hostgroups = array();

    $host_list = Host::get_list($conn, '', '');
    foreach($host_list as $host) 
    {
        $ip       = $host->get_ip();
        $hostname = $host->get_hostname();
        
        $s_country      = strtolower($GeoLoc->get_country_code_by_addr($conn, $ip));
        $s_country_name = $GeoLoc->get_country_name_by_addr($conn, $ip);
        $geo_code       = get_country($s_country, $s_country_name);
                   
        if ( !empty($geo_code) )
        {
            $flag =  "<img src='".$geo_code."' border='0' align='top'/>";
            
            if ( empty($av_hostgroups[$s_country]) )
                $av_hostgroups[$s_country] = array("country_name" => $s_country_name, "flag" => $flag, "hosts" => array($host));
            else
                $av_hostgroups[$s_country]['hosts'][] = $host;
        }
    }
    $GeoLoc->close();
    return $av_hostgroups;
}

$db   = new ossim_db();
$conn = $db->connect();
    
if (POST('search') == 1 )
{     
    $av_hostgroups = get_av_hostgroups($conn);
          
    echo "OK###";
      
    if ( count($av_hostgroups) > 0 )
    {
        ?>
        <div id='container_msg'><div id='ajax_info'></div></div>
        
        <table id='t_avhg'>
            <thead>
                <tr><th colspan='3' style='padding: 8px 0px'><?php echo _("Host groups by IP Geolocation")?></th></tr>
                <tr>
                    <th class='th_chk'><input type='checkbox' name='select_all' id='all_chk' onclick="select_all('all_chk')"/></th>
                    <th class='th_hgroups'><?php echo _("Host groups")?></th>
                    <th class='th_hosts' colspan='3'><?php echo _("Hosts")?></th>
                </tr>
            </thead>
            <tbody>
            
            <?php
                
                $row = 0;
                foreach($av_hostgroups as $key => $data) 
                {
                    $chk_id     = 'chk_'.$key;
                    $chk_name   = 'chk_'.$data['country_name'];
                    $status_id  = 'st_'.$key;
                    $datahg_id  = 'datahg_'.$key;
                    $background = ( $row%2 == 0 ) ? 'background: #F2F2F2;' : 'background: #FFFFFF;';
                    ?>
                    <tr>
                        <td class='noborder center' valign='middle' style='<?php echo $background?>'>
                            <input type='checkbox' id='<?php echo $chk_id?>' name='<?php echo $chk_name?>' value='<?php echo $key?>'/>
                        </td>
                        <td class='noborder left' valign='middle' style='<?php echo $background?>'>
                            <span style='margin: 0px 3px 0px 5px;'><?php echo $data['flag']?></span>
                            <span><?php echo "AV_".$data['country_name']?></span>
                        </td>
                        <td class='noborder left' style='<?php echo $background?>' id='<?php echo $datahg_id?>'>
                            <?php
                            $num_hosts = 0;
                            foreach($data['hosts'] as $key => $host) 
                            {
                                if ( $num_hosts != 0 )
                                    echo ", ";
                                                                
                                if ( $host->get_ip() == $host->get_hostname() )
                                $hostname = $host->get_ip();
                            else
                                $hostname = $host->get_ip()." <span style='color:grey;'>[".$host->get_hostname()."]</span>";
                                
                                echo $hostname;
                                $num_hosts++;
                            }
                            ?>
                        </td>
                    </tr>
                    <?php
                    $row++;
                }
            ?>
                <tr>    
                    <td colspan='3' class='noborder' style='border-top: solid 1px #CBCBCB;' valign='middle'>
                        <div class='container_button'>
                            <div style='float: left;   margin-left: 5px; text-align: left;'>
                                <input type='button' class='button' onclick="document.location.href='hostgroup.php';"  value='<?php echo _("Back")?>'/>
                            </div>
                            <div style='float: right; margin-right: 5px; text-align: right;'>
                                <input type='button' name='created_selected' id='created_selected' class='button' onclick="add_selected();" value='<?php echo _("Update Selected")?>'/>
                            </div>
                        </div>
                    </td>
                </tr>  
           </tbody>
        </table>
        
        <div id='t_avhg_error'></div>
        <?php
    }
    else
    {
        ?>
            <div class='ossim_alert' style='margin-top: 50px'><?php echo _("There are not public IP addresses within the AlienVault inventory")?></div>
            <div class='container_button'><input type='button' class='button' onclick="document.location.href='hostgroup.php';" value='<?php echo _("Back")?>'/></div>
        <?php
    } 

}
elseif ( POST('create') == 1 && POST('ccode') != '' )
{
    $ccode = POST('ccode');
    
    ossim_valid($ccode, OSS_ALPHA, 'illegal:' . _("Country Code"));

    if (ossim_error()) 
    {
        echo "error###".ossim_get_error_clean();
        exit();
    }
    else
    {
        if ( isset($_SESSION['_av_hostgroups']) && !empty($_SESSION['_av_hostgroups']) )
            $av_hostgroups = $_SESSION['_av_hostgroups'];
        else
        {
            $av_hostgroups              = get_av_hostgroups($conn);
            $_SESSION['_av_hostgroups'] = $av_hostgroups;
        }
        
        if ( count($av_hostgroups[$ccode]['hosts']) > 0 )
        {
            $hosts   = array();
            $sensors = array();
            
            foreach ( $av_hostgroups[$ccode]['hosts'] as $k => $host )
            {
                $hosts[] = $host->get_ip();
                $s_aux   = $host->get_sensors($conn);
               
                if ( is_array($s_aux) && count($s_aux)>0 )
                {
                    foreach ($s_aux as $k => $hsr)
                        $sensors[$hsr->get_sensor_name()] = $hsr->get_sensor_name();
                }
            }
            
            include_once ('classes/GeoLoc.inc');
            $GeoLoc          = new GeoLoc("/usr/share/geoip/GeoIP.dat", GEOIP_STANDARD);
            
            $nagios          = 0 ;
            $rdd_profile     = "";
            $threshold_a     = 30;
            $threshold_c     = 30; 
            $descr           = "";
            $hgname          = "AV_".$av_hostgroups[$ccode]['country_name'];
            list($lat, $lon) = $GeoLoc->get_lat_lon_by_name_country($av_hostgroups[$ccode]['country_name']);
            
            $GeoLoc->close();
            
            Host_group::update($conn, $hgname, $threshold_c, $threshold_a, $rrd_profile, $sensors, $hosts, $descr, $lat, $lon); 
            Util::clean_json_cache_files("(policy|vulnmeter|hostgroup)"); 
            echo "OK###Host Group $hgname created successfully";            
        }
    }
}
elseif ( POST('delete_session_data') == 1 )
	unset($_SESSION['_av_hostgroups']);


$db->close($conn);

?>