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
/**
* Class and Function List:
* Function list:
* Classes list:
*/
require_once ('classes/Session.inc');
Session::logcheck("MenuIntelligence", "PolicyPolicy");
?>

<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>
                                                                                
<?php
if (GET('withoutmenu') != "1") include ("../hmenu.php"); ?>

<form method="post" action="newpolicygroup.php">
<input type="hidden" name="withoutmenu" value="<?=intval(GET('withoutmenu'))?>">
<table align="center">
  <input type="hidden" name="insert" value="insert">
  <tr>
    <th> <?php
echo gettext("Name"); ?> </th>
    <td class="left nobborder"><input type="text" name="name"></td>
  </tr>
  <tr>
    <th> <?php
echo gettext("Description"); ?> </th>
    <td class="left nobborder">
      <textarea name="descr" rows="2" cols="20"></textarea>
    </td>
  </tr>
  <tr style="height:35px;">
    <td colspan="2" class="nobborder" style="text-align:center">
      <input type="submit" value="<?=_("OK")?>" class="button">
      <input type="reset" value="<?=_("reset")?>" class="button">
    </td>
  </tr>
</table>
</form>

</body>
</html>

