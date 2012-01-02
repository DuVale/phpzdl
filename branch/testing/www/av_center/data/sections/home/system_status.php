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
$path = '/usr/share/ossim/www/av_center';
set_include_path(get_include_path() . PATH_SEPARATOR . $path);
?>

<table class='st_status'>
    <tr>
        <td>
            <table class='st_data'>
                <tr>
                    <td valign='top'>
                        <table class='noborder'>
                            <tr>
                                <td class='_label'><?php echo _("Hostname")?>:</td>
                                <td class='_data'><?php echo $hostname. "[".$admin_ip."]"?></td>
                            </tr>
                            <tr>
                                <td class='_label'><?php echo _("Time on system")?>:</td>
                                <td class='_data'><?php echo $system_time;?></td>
                            </tr>
                            <tr>
                                <td class='_label'><?php echo _("System uptime")?>:</td>
                                <td class='_data'><?php echo $system_uptime;?></td>
                            </tr>
                            <tr>
                                <td class='_label'><?php echo _("Load Average")?>:</td>
                                <td class='_data' id='la_data'><?php echo $load_average;?></td>
                            </tr>
                            <tr>
                                <td class='_label'><?php echo _("Current Sessions")?>:</td>
                                <td class='_data' id='cs_data'><?php echo $current_sessions;?></td>
                            </tr>
                            <tr>
                                <td class='_label'><?php echo _("Running processes")?>:</td>
                                <td class='_data' id='rc_data'><?php echo $running_processes;?></td>
                            </tr>
                        </table>
                    </td>
                           
                    <td valign='top'>
                        <?php
                        /*******************************************************************
                            No esta terminado, se debe por un acumulado del uso de disco
                        ********************************************************************/
                        $cont = 0;
                        foreach ($mounted_disks as $path)
                        {
                            $d_info     = $status_data['Disk usage;'.$path]['usage'];
                            $disk_info  = explode(";",  $d_info);
                            $mounted_in = array_pop($disk_info);
                            
                            $user_ds = str_replace("%", "", $disk_info[3]);
                            $free_ds = 100 - $user_ds;
                           
                            
                            $data = "[['"._("Free")."', ".$free_ds."],['"._("Used")."',".$user_ds."]]";
                            $id   =  "pie_".$cont;
                                                          
                            ?>
                            
                            <div id='<?php echo $id?>' style='width: 200px; height: 130px; margin: auto;'></div>
                            
                            <script type='text/javascript'>
                                System_status.show_pie('<?php echo $id?>', [<?php echo $data?>]);
                            </script>
                            
                            <?php
                            $cont++;
                            break;
                        }
                        ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    
    <tr>
        <td>
            <table class='st_data'>  
                <tr>
                    <td class='_label' valign='middle'><?php echo _("Memory used")?>:</td>
                    <td class='_data' id='r_memory' valign='middle'>
                        <div class='mem_data' id='r_mem_data'>
                            <span class='free'><?php echo " - "?></span><?php echo " "._("free")?>,
                            <span class='used'><?php echo " - "?></span><?php echo " "._("used")?>,
                            <span class='total'><?php echo " - "?></span><?php echo " "._("total")?>
                        </div>
                        <?php echo Avc_utilities::create_progress_bar('r_memory_pbar', '', '190px', '0', 'progress-blue');?> 
                    </td>
                    <td valign='middle'>
                        <div id='r_memory_spark_line' style='margin: auto; width: 200px;'></div>
                    </td>
                </tr>
                
                
                <tr>
                    <td class='_label' valign='middle'><?php echo _("Swap used")?>:</td>
                    <td class='_data' id='s_memory' valign='middle'>
                        <div class='mem_data' id='s_mem_data'>
                            <span class='free'><?php echo " - "?></span><?php echo " "._("free")?>,
                            <span class='used'><?php echo " - "?></span><?php echo " "._("used")?>,
                            <span class='total'><?php echo " - "?></span><?php echo " "._("total")?>
                        </div>
                        <?php echo Avc_utilities::create_progress_bar('s_memory_pbar', '', '190px', '0', 'progress-blue');?> 
                    </td>
                    <td valign='middle'><div id='s_memory_spark_line' style='margin: auto; width: 200px;'></div></td>
                </tr>
            </table>
        </td>
    </tr>
    
    <tr>
        <td>
            <table class='st_data'>     
                <tr>
                    <td class='_label' valign='middle'><?php echo _("CPU")?>:</td>
                    <td class='_data' valign='middle'>
                        <div style='text-align: left; width: 100%;'><?php echo $cpu_proc['cpu0']." - ". count($cpu_proc)." "._("core/s");?></div>
                        <?php echo Avc_utilities::create_progress_bar('cpu_pbar', '', '190px', '0', 'progress-green');?> 
                    </td>
                    <td valign='middle'><div id='cpu_spark_line' style='margin: auto; width: 200px;'></div></td>
                </tr>
            </table>
        </td>
    </tr>
</table>



<script type='text/javascript'>
    r_memory_usage.push('<?php echo $rmp?>');
    s_memory_usage.push('<?php echo $vmp?>');
    cpu_usage.push('<?php echo $cpu_info['cpu']?>');

    $('#r_memory_spark_line').sparkline(r_memory_usage, { width:'120px', height: '30px', chartRangeMin: '0', chartRangeMax: '100'});  
    $('#s_memory_spark_line').sparkline(s_memory_usage, { width:'120px', height: '30px', chartRangeMin: '0', chartRangeMax: '100'});
    $('#cpu_spark_line').sparkline(cpu_usage,           { width:'120px', height: '30px', chartRangeMin: '0', chartRangeMax: '100'});    
</script> 