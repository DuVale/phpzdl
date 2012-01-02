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


//Only admin can access
Avc_utilities::check_access('', '/ossim/session/login.php');

$uuid        = POST('uuid');
$id_section  = POST('id_section');

ossim_valid($uuid, OSS_DIGIT, OSS_LETTER, '-', 'illegal:' . _("UUID"));
ossim_valid($id_section,  OSS_ALPHA, OSS_SCORE, OSS_BRACKET, 'illegal:' . _("Section"));

if ( !ossim_error() ) 
{
    if ( $id_section == 'home')
    {
        $st          = Av_center::avc_collector_status($uuid);
        $status_data = $st['output'];
        
        $jsondata['status']                 = $status_data['System Status']['status'];
        $jsondata['system_time']            = $status_data['System Status']['system_time'];
        $jsondata['system_uptime']          = $status_data['System Status']['system_uptime'];
        $jsondata['loadaverage']            = $status_data['System Status']['loadaverage'];
        $jsondata['running_proc']           = $status_data['System Status']['running_proc'];
      
        $jsondata['memtotal']               = $status_data['System Status']['memtotal'];
        $jsondata['percent_memused']        = $status_data['System Status']['percent_memused'];
        $jsondata['memfree']                = $status_data['System Status']['memfree'];
        $jsondata['memused']                = $status_data['System Status']['memused'];
        
        $jsondata['virtualmem']             = $status_data['System Status']['virtualmem'];
        $jsondata['percent_virtualmemused'] = $status_data['System Status']['percent_virtualmemused'];
        $jsondata['virtualmemfree']         = $status_data['System Status']['virtualmemfree'];
        $jsondata['virtualmemused']         = $status_data['System Status']['virtualmemused'];
        
        $jsondata['current_sessions']       = $status_data['System Status']['current_sessions'];
        $jsondata['cpu']                    = number_format($status_data['CPU']['cpu'], 2, '.', '');
    }
    elseif ( $id_section == 'software')
    {
        $sf = Av_center::avc_collector_software($uuid);
        $software_data = $sf['output']['Software'];
        
        $jsondata['packages_installed']        = $software_data['packages_installed'];
        $jsondata['packages_installed_failed'] = $software_data['packages_installed_failed'];
        $jsondata['packages_pending_updates']  = $software_data['packages_pending_updates'];
        $jsondata['packages_pending_purge']    = $software_data['packages_pending_purge'];
        $jsondata['latest_update']             = $software_data['latest_update'];
    }
    
    echo json_encode($jsondata);
    
}    
    
?>