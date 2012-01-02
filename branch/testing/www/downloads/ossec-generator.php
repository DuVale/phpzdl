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
ob_implicit_flush();
require_once ('classes/Session.inc');
Session::logcheck("MenuConfiguration", "ToolsDownloads");
$user = $_SESSION["_user"];

if(Session::am_i_admin()) {
    header("Location: ../cloud/mytest.php");
}
else {
    //$ip = (GET('ip')!="")? GET('ip') : $_SERVER["REMOTE_ADDR"];

    $aux = explode(",", Session::allowedSensors());

    $local_ip = `grep framework_ip /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`;
    $local_ip = trim($local_ip);

    foreach($aux as $key => $value) {
        if($value!=$local_ip) $ip = $value;
    }
    $suf = GET('suf'); if ($suf=='') $suf='001';
    $file = "/usr/share/ossim/www/ossec/agents/ossec_installer_".$ip."_".$suf.".exe";
    if (!file_exists($file) || GET('suf')=='') {
    ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html>
    <head>
      <title> <?php echo gettext("OSSIM Framework"); ?> </title>
      <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
      <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
      <link rel="stylesheet" type="text/css" href="../style/style.css"/>
    </head>
    <body leftmargin="30">
    <?php include ("../hmenu.php");
      ?>
        <div id="endmessage" style="display:none">
          <table class="transparent" align="center">
            <tr><td class="nobborder">
                <span style="font-size: 13px;">
                <?php echo _("Congratulations a preconfigured agent has been compiled and you have downloaded the installer, please execute the downloded installer"); ?></td></tr>
                </span>
            <tr><td class="nobborder" style="padding-top:15px;text-align:center">
                  <a href="../cloud/mytest.php" ><span class="buttonlink" style="font-size: 6px;"><?php echo _("Back") ?></span></a>
                </td>
            </tr>
          </table>
        </div>
      <?php
      echo "<div id=\"messages\">";
      echo _("Generating ossec-installer with remote ip: <b>$ip</b><br>\nPlease wait a few seconds...<br>\n");
      ob_flush();
      system("cd /usr/share/ossec-generator;perl gen_install_exe.pl '$ip' '$user' > /tmp/ossec-gen 2>&1");
      echo _("Finished... Restarting OSSEC daemons<br>\n");
      system("sudo /var/ossec/bin/ossec-control restart >> /tmp/ossec-gen 2>&1");
      $now = explode(".",system("ls -t1 /usr/share/ossim/www/ossec/agents/ossec_installer_".$ip."* | head -1 | awk 'BEGIN { FS = \"_\" } ;{print \$4}'"));
      $file = "/usr/share/ossim/www/ossec/agents/ossec_installer_".$ip."_".$now[0].".exe";
      if (file_exists($file)) {
        echo _("Successfully created.<br>\n");
        echo "</div>";
        ?>
        <script type="text/javascript">
            //<![CDATA[
              $('#messages').hide();
              $('#endmessage').show();
              document.location.href='?ip=<?php echo $ip ?>&suf=<?php echo $now[0];?>'
            //]]>
        </script>
        <?php
      } else {
        echo "<b>Error file don't generated!</b>\n";
        echo "</div>";
      }
    ?>
    </body>
    </html>
    <?php
    }
    else
    {
      header("Location: /ossim/ossec/agents/ossec_installer_".$ip."_".$suf.".exe");
    }
}
?>


