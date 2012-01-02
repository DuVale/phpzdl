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
require_once 'classes/Security.inc';
require_once 'ossim_db.inc';
require_once 'classes/Incident.inc';
require_once 'classes/Incident_tag.inc';

Session::logcheck("MenuIncidents", "IncidentsIncidents");

if ( !Session::menu_perms("MenuIncidents", "IncidentsIncidents") ) 
{
	Session::unallowed_section();
	exit();
}

$selected_incidents = POST('selected_incidents');
$action             = POST('action');

if ( $action != 'apply_tags' && $action != 'remove_tags' )
{
    echo ossim_error(_("Action not allowed"));
    exit();
}


ossim_valid($selected_incidents, OSS_DIGIT, "\,",  'illegal:' . _("Selected Incidents"));
ossim_valid($action, OSS_ALPHA, OSS_SCORE, 'illegal:' . _("Action"));

if ( ossim_error() ) 
{
   $error = ossim_get_error_clean();
   echo "error###".$error;
   exit();
}

if ( $action == 'apply_tags' )
{
    $tag = POST('tag');
    if ( !ossim_valid($tag, OSS_DIGIT,  'illegal:' . _("Tag")) )
    {
        $error = ossim_get_error_clean();
        echo "error###".$error;
        exit();
    }
}
    
//DB connection
$db   = new ossim_db();
$conn = $db->connect();

   
$ids            = explode(",", $selected_incidents);
$ids_updated    = array();

$size           = count($ids);

if ( is_array($ids) &&  $size > 0 )
{
    for($i=0; $i<$size; $i++)
    {
        $incident_id = $ids[$i];
                      
        if ( $incident_id != "" && Incident::user_incident_perms($conn, $incident_id, 'show') )
        {
            if ( $action == 'apply_tags' ){
                $res  = Incident::insert_incident_tag($conn, $incident_id, $tag);
            }
            else{
                $res = Incident::delete_incident_tags($conn, $incident_id);
            }
            
            if ( $res === true)
                $ids_updated[$incident_id] = $incident_id;
        }
    }
              
    if ( $action == 'apply_tags' )
    {
        $incident_tag = new Incident_tag($conn);
        
        if ( count($ids) != count($ids_updated) )
        {
            echo "OK###DB Error###".implode(",", $ids_updated)."###";
        }
        else
            echo "OK###Tag added###";
        
        echo "<div style='color:grey; font-size: 10px; padding-bottom: 3px;'>".$incident_tag->get_html_tag($tag)."</div>";
    }
    else
    {
        if ( count($ids ) != count($ids_updated) )
            echo "OK###DB Error###".implode(",", $ids_updated);
        else
             echo "OK###Tags removed";
    }
}
else{
    echo "OK###No incidents";
}

$db->close($conn);
    


?>