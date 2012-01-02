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

?>

<table class='cont_software'>
    <tr>
        <td>
            <table class='soft_info'>
                <tr>
                    <td valign='top'>
                        <table class='noborder'>
                            <tr>
                                <td class='_label'><?php echo _("Packages Installed")?>:</td>
                                <td class='_data'><?php echo $packages_installed?></td>
                            </tr>
                            <tr>
                                <td class='_label'><?php echo _("Packages Installed failed")?>:</td>
                                <td class='_data'><?php echo $packages_installed_failed;?></td>
                            </tr>
                            <tr>
                                <td class='_label'><?php echo _("Packages pending updated")?>:</td>
                                <td class='_data'><?php echo $packages_pending_updates;?></td>
                            </tr>
                            <tr>
                                <td class='_label'><?php echo _("Packages pending purge")?>:</td>
                                <td class='_data'><?php echo $packages_pending_purge;?></td>
                            </tr>
                            <tr>
                                <td class='_label'><?php echo _("Lastest update")?>:</td>
                                <td class='_data'><?php echo $latest_update;?></td>
                            </tr>
                            
                            <tr>
                                <td class='_label'><?php echo _("Access to Alienvault Repositories")?>:</td>
                                <td class='_data'><?php echo $access_repositories; ?>          
                            </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
            
    <tr>
        <td>
            <table class='soft_info'>
                <tr>
                    <td class='_label'><?php echo _("Latest_updates")?>:</td>
                </tr>
                <tr>
                    <td>
                        <div id='updates_info'>
                          
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>


                           



