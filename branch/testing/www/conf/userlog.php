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
* - submit()
* Classes list:
*/
require_once ('classes/Session.inc');
Session::logcheck("MenuConfiguration", "ConfigurationUserActionLog");
require_once ('ossim_db.inc');
require_once ('classes/Log_config.inc');
require_once ('classes/Security.inc');

$update = ( isset($_POST['update']) && POST('update') != '' ) ? true : false;

/* connect to db */
$db       = new ossim_db();
$conn     = $db->connect();
$status   = true;

$ua_items      = array();
$ua_logged     = array();
$ua_not_logged = array();
	
if ($log_conf_list = Log_config::get_list($conn, "")) 
{
	foreach($log_conf_list as $log_conf) 
	{
		$descr           = preg_replace('|%.*?%|', " ", $log_conf->get_descr());
		$descr           = ( trim($descr) == '' ) ? _("Various") : $descr;
		$code    		 = $log_conf->get_code();
		$ua_items[$code] = array("descr" => $descr, "log" => $log_conf->get_log()); 
		
		if ( $log_conf->get_log() )
			$ua_logged[$code] = $descr;
		else
			$ua_not_logged[$code] = $descr;
	}
}

//Update User Activity items
if ( $update == true) 
{
	$ua_logged     = array();
	$ua_not_logged = array();
	$select_ua     = ( is_array($_POST['select_ua']) && count($_POST['select_ua']) > 0 ) ? $_POST['select_ua'] : array(); 
	
	foreach ($ua_items as $k => $v)
	{
		if ( in_array($k, $select_ua) ) 
		{
			$res = Log_config::update_log($conn, $k, '1');
			$ua_logged[$k] = $v['descr'];
		}
		else
		{
			$res = Log_config::update_log($conn, $k, '0');
			$ua_not_logged[$k] = $v['descr'];
		}
	}
	
	if ( $res !== true ) 
		$status = false;
}

asort($ua_logged, SORT_STRING);
asort($ua_not_logged, SORT_STRING);


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("User logging Configuration"); ?> </title>
	<meta http-equiv="Pragma" content="no-cache"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<link type="text/css" rel="stylesheet" href="../style/style.css"/>	
	
	<!-- Multiselect: -->
	<script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
	<script type="text/javascript" src="../js/ui.multiselect.js"></script>
	<script type="text/javascript" src="../js/jquery.tmpl.1.1.1.js"></script>
	<link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
	<link type="text/css" rel="stylesheet" href="../style/ui.multiselect.css"/>
	
	<style type='text/css'>
		.multiselect {
			width: 78%;
			height: 350px;
		}
		
		.ui-multiselect ul.selected li {
			white-space: normal !important;
		}
		
		#ua_cont{
			width: 80%;
			margin: auto;
			text-align: center;
		}
		
		.ua_table{
			margin: auto;
			text-align: center;
			border: none;
			background: none !important;
		}	
			
		.ua_title { 
			background: url("../../pixmaps/theme/ui-bg_highlight-soft_75_cccccc_1x100.png") repeat-x scroll 50% 50% #CCCCCC;
			-moz-border-radius:4px;
			-webkit-border-radius: 4px;
			-khtml-border-radius: 4px;
			height: 25px;
			color:#222222;
			font-size: 12px;
			font-weight: bold;
		}
		
		#cont_info{
			height: 80px;
			padding-top: 10px;
			width: 90%;
			margin: auto;
		}
		
		#info{
			width: 100%;
			margin: auto;
			text-align: center;
		}
		
	</style>
	
	<script type='text/javascript'>
	
		$(document).ready(function() {

			$(".multiselect").multiselect({
								sortable: '',
								draggable: '',
								searchDelay: 500,
								nodeComparator: function (node1,node2){ return 1 },
								dividerLocation: 0.5,
                                sizelength: 80
							});
			
			<?php if ( $update == true) { ?>	
			setTimeout('$("#info").fadeOut(4000);', 25000);	
			<?php } ?>	
		});
	
	</script>
</head>

<body>

	<?php include ("../hmenu.php");?>

	<div id='ua_cont'>
		<div id='cont_info'>
			<div id='info'>
				<?php
					if ( $update == true ) 
					{
						if ( $status == true )
							Util::print_succesful( _("User activity successfully updated") );
						else
							echo ossim_error(_("Update failed"));
					}
				?>
			</div>
		</div>
	
		<form method="POST" name='form_ua' id='form_ua' action="<?php echo $_SERVER["SCRIPT_NAME"] ?>" />
			<table class='ua_table'>
				<tr>
					<td style='padding: 8px 0px 6px 0px;' class='transparent'>
						<span>(*) <?php echo _('Drag & Drop the item you want to add/remove or use [+] and [-] links')?></span>
					</td>
				</tr>
				<tr>
					<td class='ua_title'>
						<div style='float: left; width: 48%'><?php echo _("Items logged")?></div>
						<div style='float: right; width: 48%'><?php echo ("Items not logged")?></div>
					</td>
				</tr>
				<tr>
					<td style='padding: 3px 0px 20px 0px;' class='nobborder center'>
						<select id='select_ua' class='multiselect' multiple='multiple' name='select_ua[]'>
						<?php
							foreach($ua_logged as $k => $v)
							{
								$text = $v; //(strlen($v) > 80 ) ? substr($v, 0, 80)." [...]" : $v;
								echo "<option value='$k' selected='selected'>$text</option>";
							}
					
							foreach($ua_not_logged as $k => $v)
							{
								$text = $v; //(strlen($v) > 80 ) ? substr($v, 0, 80)." [...]" : $v;
								echo "<option value='$k'>$text</option>";
							}
						?>
						</select>
					</td>
				</tr>
				
				<tr><td style='padding-bottom:10px;' class='noborder center'><input type='submit' class='button' id='update' name='update' value='<?php echo _('Update Configuration')?>'/></td></tr>
			</table>
		</form>
	</div>
</body>
</html>

<?php $db->close($conn); ?>