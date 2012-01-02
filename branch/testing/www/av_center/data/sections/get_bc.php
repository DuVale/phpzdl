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
require_once 'data/breadcrumb.php';


$id_section = POST("section");
$pre_data   = POST("pre_data");


ossim_valid($id_section,  OSS_ALPHA, OSS_SCORE, OSS_BRACKET, 'illegal:' . _("Section"));
ossim_valid($pre_data,   OSS_ALPHA, OSS_SCORE, OSS_BRACKET, OSS_DOT, OSS_SPACE, 'illegal:' . _("Host"));

if ( ossim_error() ) 
{
    echo "error###".ossim_error();
    exit;
}


$bc_sections = explode("###", $sections[$id_section]['bc']);

$size = count($bc_sections);
$cont = 0;

echo "<ul class='xbreadcrumbs' id='breadcrumbs'>";

foreach ($bc_sections as $section)
{
    $cont++;
    
    echo ( $size == $cont) ? "<li class='current'>" : "<li>";
    if ( $section == "alienvault_center" )
        echo "<a href='".$sections[$section]['path']."' class='home'>".$sections[$section]['name']."</a>";
    elseif ( $section == "home" )
        echo "<a href='#' onclick=\"section.load_section('".$section."')\">".$pre_data."</a>";
    else
        echo "<a href='#' onclick=\"section.load_section('".$section."')\">".$sections[$section]['name']."</a>";
    
    echo "</li>";
}
echo "</ul>";

?>
