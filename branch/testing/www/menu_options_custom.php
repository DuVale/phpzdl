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

/*
** CUSTOM MENUS EXAMPLE **

** MAIN MENU **

$menu["Dashboards"][] = array(
	"name" => gettext("Risk"),
	"id"   => "Risk",
	"url"  => "risk_maps/riskmaps.php?view=1" // Default first link url
);

** HOROZINTAL TAB MENU **
** Hash key must be $menu "id" content.

$hmenu["Risk"][] = array(
	"name"   => gettext("Risk Maps"),
	"id"     => "Risk",
	"target" => "main",
	"url"    => "risk_maps/riskmaps.php?view=1",
	"help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:dashboards:risk:risk_maps','DashboardHelp');"
);

** WHITE LINKS AT THE END OF MENUBAR **
** Hast key must be $hmenu "id" content

$rmenu["Risk"][] = array(
  "name"   => gettext("Set Indicators"),
  "target" => "main",
  "url"    => "../risk_maps/riskmaps.php"
);

$rmenu["Risk"][] = array(
  "name"   => gettext("Manage maps"),
  "target" => "main",
  "url"    => "../risk_maps/riskmaps.php?view=2"
);

*/

?>