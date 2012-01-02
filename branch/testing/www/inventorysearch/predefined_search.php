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
// Get Predefined Searches
// Database Object


require_once ('classes/Session.inc');
Session::logcheck("MenuPolicy", "5DSearch");
require_once ('classes/Host.inc');
require_once ('classes/User_config.inc');
require_once ('classes/Util.inc');
require_once ('ossim_db.inc');
require_once ('ossim_conf.inc');

$db2    = new ossim_db();
$conn2  = $db2->connect();
$config = new User_config($conn2);

$user     = Session::get_session_user();
$profiles = $config->get_all($user, "inv_search");


?>
<a style='cursor: pointer;' onclick="$('#searches').toggle()"><img src="../pixmaps/arrow_green.gif" align="absmiddle"/><strong><?php echo _("Select a Predefined Search")?></strong></a>
<div style="position:relative">
    <div id="searches" style="position:absolute;right:0;top:0;display:none">
    <table>
        <tr><th><?php echo _("Select a profile to search")?></th></tr>
        <?php 
            if (count($profiles) < 1)
                echo "<tr><td class='center nobborder'>"._("No profiles found")."</td></tr>";
            else
            {
                $i = 1;
                foreach ($profiles as $profile) 
                { 
                    $color        = ($i%2==0) ? "#FFFFFF" : "#EEEEEE";
                    $name         = (mb_detect_encoding($profile." ",'UTF-8,ISO-8859-1') == 'UTF-8') ? $profile : mb_convert_encoding($profile, 'UTF-8', 'ISO-8859-1');
                    $profile_b64  = base64_encode($name);
                    echo "<tr><td class='nobborder' style='background-color:$color'><a href='inventory_search.php?profile=".$profile_b64."&run_search=1'>$profile</a></td></tr>";    
                    $i++;
                }
            }
        ?>
        </table>
    </div>
</div>