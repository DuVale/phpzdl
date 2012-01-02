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
require_once ('ossim_conf.inc');
$conf      = $GLOBALS["CONF"];

$link=GET('lnk')."?j_username=".$conf->get_conf("bi_user", FALSE)."&j_password=".$conf->get_conf("bi_pass", FALSE);

$link_photo="http://127.0.0.1:8080".$link;

$arr = @getimagesize($link_photo);

if (count($arr)>1) 
{ 
	$show_path_photo = $link; 
}
else 
{ 
	$show_path_photo = "../pixmaps/1x1.png";
}

header("Location: " . $show_path_photo);

?>
