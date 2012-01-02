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
require_once ('classes/Security.inc');
require_once ('ossim_db.inc');
require_once ('classes/Webinterfaces.inc');
Session::logcheck("MenuConfiguration", "PolicyServers");
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
	<script type="text/javascript" src="../js/ajax_validator.js"></script>
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
	<script type="text/javascript" src="../js/messages.php"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
  
	<script type="text/javascript">
		$(document).ready(function(){

			$('textarea').elastic();
			
			$('.vfield').bind('blur', function() {
			     validate_field($(this).attr("id"), "<?php echo ( GET('id') != "") ? "modifywebinterfaces.php" : "newwebinterfaces.php" ?>");
			});

		});
	</script>
  
	<style type='text/css'>
		<?php
		if ( GET('withoutmenu') == "1" )
		{
			echo "#table_form {width: 500px;}";
		    echo "#table_form th {width: 150px;}";
		}
		else
		{
			echo "#table_form {width: 500px;}";
		    echo "#table_form th {width: 150px;}";
		}
		?>
		input[type='text'], select, textarea {width: 90%; height: 18px;}
		input[type='file'] {width: 90%; border: solid 1px #CCCCCC;}
		textarea { height: 45px;}
		label {border: none; cursor: default;}
		.bold {font-weight: bold;}
		div.bold {line-height: 18px;}
		.val_error { width: 270px;}
	</style>
  
</head>
<body>
                                                                                
<?php 

$db    = new ossim_db();
$conn  = $db->connect();

$webinterfaces_id = GET('id');
if ( isset($_SESSION['_webinterfaces']) )
{

	$webinterfaces_id = $_SESSION['_webinterfaces']['id'];
	$ip               = $_SESSION['_webinterfaces']['ip'];
	$name             = $_SESSION['_webinterfaces']['name'];
	$status           = $_SESSION['_webinterfaces']['status'];
    	
	unset($_SESSION['_webinterfaces']);
}
else
{
	$ip      = $name  = "";
	$status  = 1;
		
	if ($webinterfaces_id != '')
	{
		ossim_valid($webinterfaces_id, OSS_DIGIT, 'illegal:' . _("Web_Interfaces_Id"));
	
		if (ossim_error()) 
			die(ossim_error());
			
		if ($webinterfaces_list = Webinterfaces::get_list($conn, "WHERE id = '$webinterfaces_id'"))
		{
			$webinterface     = $webinterfaces_list[0];
			$webinterfaces_id = $webinterface->get_id();
			$ip               = $webinterface->get_ip();
			$name             = $webinterface->get_name();
			$status           = $webinterface->get_status();
        }
	}
	
}



if (GET('withoutmenu') != "1") 
	include ("../hmenu.php"); 
?>


<div id='info_error' class='ossim_error' style='display: none;'></div>

<form name='form_webinterfaces' id='form_webinterfaces' method="POST" action="<?php echo ( GET('id') != "") ? "modifywebinterfaces.php" : "newwebinterfaces.php" ?>" enctype="multipart/form-data">

<input type="hidden" name="insert" value="insert"/>
<input type="hidden" name="withoutmenu" id='withoutmenu' value="<?php echo GET('withoutmenu')?>"/>

<table align="center" id='table_form'>
	  
	<?php 
    if ( GET('id') != '' ) 
    {
    ?>
        <tr>
            <th><label for='webinterfaces_id'><?php echo gettext("Id"); ?></label></th>
            <td class="left">
                <?php
                    echo "<input type='hidden' class='req_field vfield' name='webinterfaces_id' id='webinterfaces_id' value='".$webinterfaces_id."'/>";
                    echo "<div class='bold'>$webinterfaces_id</div>";
                ?>
            </td>
        </tr>
    <?php 
    }
    ?>
  
	<tr>
		<th><label for='ip'><?php echo gettext("IP"); ?></label></th>
		<td class="left">
			<input type="text" class='req_field vfield' name="ip" id="ip" value="<?php echo $ip?>"/>
			<span style="padding-left: 3px;">*</span>
		</td>
	</tr>
	
  	<tr>
		<th><label for='name'><?php echo gettext("Name"); ?></label></th>
		<td class="left">
			<input type="text" class='req_field vfield' name="name" id="name" value="<?php echo $name;?>"/>
			<span style="padding-left: 3px;">*</span>
		</td>
	</tr>
	
	<tr>
		<th><label for='status'><?php echo gettext("Status"); ?></label></th>
		<td class="left">
            <?
            $checked = ($status == 1) ? " checked='checked' " : "";
            ?>
            <input type="checkbox" value="1" <?php echo($checked); ?> name="status">
			<span style="padding-left: 3px;"> </span>
		</td>
	</tr>
  
	<tr>
		<td colspan="2" align="center" style="border-bottom: none; padding: 10px;">
			<input type="button" class="button" id='send' onclick="submit_form();" value="<?php echo _("Update")?>"/>
			<input type="reset"  class="button" value="<?php echo gettext("Clear form");?>"/>
		</td>
	</tr>

  </table>
</form>

</body>
</html>

