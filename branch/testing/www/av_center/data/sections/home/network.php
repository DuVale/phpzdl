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
$path = '/usr/share/ossim/www/av_center';
set_include_path(get_include_path() . PATH_SEPARATOR . $path);

/*echo "<pre>";
print_r($network_data);
echo "</pre>";
*/
?>
<table style="width:100%;border:0px">
<?php
if (is_array($network_data)) foreach ($network_data as $iface) {
?>
    <tr>
        <td style="padding-left:15px">
            <strong><?php echo $iface["name"]?></strong>
        </td>
        <td style="background-color:<?php echo ($iface["status"]=="up") ? 'green' : 'red' ?>">
            <img src="images/<?php echo ($iface["rx_Bps"]>0||$iface["tx_Bps"]>0) ? "port_animado.gif" : "no_animado.gif" ?>" border="0"><br>
            <span style='text-align:center;font-weight:bold;line-height:14px;color:white;'><?php echo $iface["status"] ?></span>
        </td>
        <td>
        <?php if ($iface["name"]!="lo" && $iface["status"]!="down") { ?>
            <table style="border:none">
            <tr>
                <td>IP: </td>
                <td><strong><?php echo $iface["address"] ?></strong></td>
            </tr>
            <tr>
                <td>Netmask: </td>
                <td><strong><?php echo $iface["netmask"] ?></strong></td>
            </tr>          
            </table>
        <?php } ?>
        </td>       
        <td>
        <?php if ($iface["name"]!="lo" && $iface["status"]!="down") { ?>
            <table style="border:none">
            <tr>
                <td>Gateway:</td>
                <td><strong><?php echo $iface["gateway"] ?></strong></td>
            </tr>
            <tr>                
                <td>DNS:</td>
                <td><strong><?php echo $iface["dns-nameservers"] ?></strong></td>
            </tr>           
            </table>
        <?php } ?>
        </td>          
        <td>
        <?php if ($iface["name"]!="lo" && $iface["status"]!="down") { ?>
            <table style="border:none">
            <tr>
                <td>Rx: </td>
                <td><strong><?php echo $iface["rx_bytes"] ?></strong></td>
            </tr>
            <tr>
                <td>Tx: </td>
                <td><strong><?php echo $iface["tx_bytes"] ?></strong></td>
            </tr>           
            </table>
        <?php } ?>
        </td>
    </tr>
    <tr><td colspan="5" class="noborder"><hr style="border:0px none;border-top:1px dashed #cccccc;height:1px;width:100%"></td></tr>
<?php
}
?>
</table>