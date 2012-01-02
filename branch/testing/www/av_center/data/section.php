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

require_once 'data/breadcrumb.php';

//Only admin can access
Avc_utilities::check_access('', '/ossim/session/login.php');

$error_msg  = POST('error_msg');
$profiles   = POST('profiles');
$id_section = POST('section');
$uuid       = POST('uuid');


if ( $error_msg != '' )
    echo "error###<div id='section_error'>".ossim_error($error_msg)."</div>";
else
{
    ossim_valid($uuid, OSS_DIGIT, OSS_LETTER, '-', 'illegal:' . _("UUID"));
    ossim_valid($id_section,  OSS_ALPHA, OSS_SCORE, OSS_BRACKET, 'illegal:' . _("Section"));

    if ( ossim_error() ){ 
        echo "error###<div id='section_error'>".ossim_error()."</div>";
    }
    elseif ( $profiles != '' ) 
    {
        $_SESSION['last_section'] = $id_section;
        include($sections[$id_section]['path']);
    }
}

?>