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
* - check_writable_relative()
* Classes list:
*/
require_once 'classes/Session.inc';
require_once 'classes/Security.inc';
require_once 'ossim_db.inc';
require_once 'classes/User_config.inc';
require_once 'ossim_conf.inc';

if (!Session::menu_perms("MenuControlPanel", "BusinessProcessesEdit") ) 
{
	Session::unallowed_section();
	exit();
}
//Function to check if an user or entity has permission to see a map.
function mapAllowed($perm,$version) {

	//If I am an admin user, retur true
	if (Session::am_i_admin()) return true;
	
	$ret = false;
	
	//If the permission is 0, everybody can see it, return true.
	if($perm == '0'){
		$ret = true;
	
	}elseif(strlen($perm) > 0){
		// ENTITY
		if (preg_match("/^\d+$/",$perm)) {
			if (preg_match("/pro|demo/i",$version) && $_SESSION['_user_vision']['entity'][$perm]) {
				$ret = true;
			}
		// USER
		} elseif (Session::get_session_user() == $perm) {
			$ret = true;
		}
	}
	
	return $ret;
}
//Function to check if an user or entity has permission to edit a map.
function mapAllowedEdit($conn,$perm,$version) {

	//If I am an admin user, retur true
	if (Session::am_i_admin()) 
		return true;
		
	$ret = false;	
	
	if(strlen($perm) > 0){
		// ENTITY
		//If the user is the admin of the entity, then it can edit the map. return true.
		if (preg_match("/^\d+$/",$perm) && preg_match("/pro|demo/i",$version)) {			
			$aux = Acl::get_entities_admin($conn,Session::get_session_user());
			if($aux[0][$perm]) $ret = true;

		// USER
		} elseif (Session::get_session_user() == $perm) {
			$ret = true;
		}
	}
	return $ret;
}

$conf    = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);

$db   = new ossim_db();
$conn = $db->connect();
$map  = (POST("map") != "") ? POST("map") : ((GET("map") != "") ? GET("map") : (($_SESSION["riskmap"]!="") ? $_SESSION["riskmap"] : 1));
$name = POST('name');
$description = POST('description');
$erase_element = GET('delete');
$setdefault = GET('default');

ossim_valid($erase_element, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_DIGIT, ";,.", 'illegal:'._("erase_element"));
ossim_valid($name, OSS_ALPHA, OSS_NULLABLE, OSS_DIGIT, OSS_SCORE, ".,%", 'illegal:'._("name"));
ossim_valid($map, OSS_DIGIT, 'illegal:'._("type"));
ossim_valid($description, OSS_INPUT, OSS_NULLABLE, 'illegal:' . _("Wrong Map Name"));
ossim_valid($setdefault, OSS_DIGIT, OSS_NULLABLE, 'illegal:'._("default"));
if (ossim_error()) {
	die(ossim_error());
}

$config = new User_config($conn);
$login = Session::get_session_user();

if ($setdefault != "") {
	$config->set($login, "riskmap", $setdefault, 'simple', "main");
}

$default_map = $config->get($login, "riskmap", 'simple', 'main');
//
if (is_uploaded_file($HTTP_POST_FILES['ficheromap']['tmp_name'])) {
	$filename = "maps/" . $name . ".jpg";
	$newid = 0;
	if (preg_match("/map(\d+)/",$name,$found)) {
		$newid = $found[1];
	}
	if(getimagesize($HTTP_POST_FILES['ficheromap']['tmp_name'])){
		move_uploaded_file($HTTP_POST_FILES['ficheromap']['tmp_name'], $filename);
		if (!Session::am_i_admin()) {	//If I am not an admin, I will add, as default, permission to see and edit the map to the current user.
			$conn->Execute("INSERT IGNORE INTO risk_maps (map,perm,name) VALUES ('$newid','".$_SESSION['_user']."', '$description')");
		} else{  //If I am an admin user, I will add permission to see the map to everyone and only edit permission to the admin.
			$conn->Execute("INSERT IGNORE INTO risk_maps (map,perm,name) VALUES ('$newid','0', '$description')");
		}
	}
}
if ($erase_element != "") {
	if (getimagesize("maps/".$erase_element)) {
		unlink("maps/" . $erase_element);
		$_SESSION["riskmap"] = $map = 1;
		preg_match("/(\d+)\./",$erase_element,$found);
		$map_id = $found[1];
		if ($map_id > 0) {
			//Deleting the indicators that appear in the map to be deleted.
			$query = "DELETE FROM risk_indicators WHERE map=$map_id";
			$result = $conn->Execute($query);
			//Deleting the indicator that are linking to the map to be deleted.
			$query = "DELETE FROM risk_indicators WHERE url='view.php?map=$map_id'";
			$result = $conn->Execute($query);
			//Deleting the map.
			$query = "DELETE FROM risk_maps WHERE map='$map_id'";
			$result = $conn->Execute($query);
		}
	}
}
//
$conn->Execute("CREATE TABLE IF NOT EXISTS `risk_maps` ( `map` varchar(64) NOT NULL, `perm` varchar(64) NOT NULL, `name` varchar(128) NULL, PRIMARY KEY (`map`,`perm`));");
//
$perms = array();
$map_names = array();
$query = "SELECT map,perm,name FROM risk_maps";
$result = $conn->Execute($query);
while (!$result->EOF) {
    $perms[$result->fields['map']] = $result->fields['perm'];
	$map_names[$result->fields['map']] = $result->fields['name'];
    $result->MoveNext();
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?= _("Alarms") ?> - <?= _("View")?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<link rel="stylesheet" type="text/css" href="./custom_style.css">
	<link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/greybox.js"></script>
	<script type="text/javascript">
	  // GrayBox
		$(document).ready(function(){
			GB_TYPE = 'w';
			$("a.greyboxo").click(function(){
				var t = this.title || $(this).text() || this.href;
				GB_show(t,this.href,250,370);
				return false;
			});
		});
	
		//Reloading the page after closing the graybox
		function GB_onclose() {
			document.location.href='changemap.php';
		}
	</script>
	<style type="text/css">
		.itcanbemoved { position:absolute; }
		.headerpr { padding: 1px !important;}
	</style>
</head>
<body leftmargin=5 topmargin=5 class="ne1">
<table align="center" style="border:0px; margin-top: 10px">
	<tr>
		<td class="ne1" align="center" colspan="5">
			<form action="changemap.php" method='POST' name='f1' enctype="multipart/form-data">
			<?= _("Map Name") ?>: <input type='text' class='ne1' size='30' name='description'/>
			<?= _("Upload map file") ?>: <input type='hidden' value="<? echo $map ?>" name=map>
            <?php
            $max_id = 0;
            $limage = explode("\n",`ls -1t 'maps'`);
            foreach ($limage as $line) if (preg_match("/map(\d+)\.jpg/",$line,$found)) {
            	if ($found[1] > $max_id) {
            		$max_id = $found[1];
            	}
            }
            ?>
			<input type='hidden' name='name' value="map<? echo ($max_id+1) ?>"><input type='file' class='ne1' size='30' name='ficheromap'/>	
			<input type='submit' value="<?php echo _("Upload") ?>" class="button"/>
			</form>
		</td>
	</tr>
	<tr>
		<td>
			<table style="border:0px;margin-top: 20px">
				<tr>
				<?				
				$maps = explode("\n",`ls -tr 'maps' | grep -v CVS`);
				$i=0; $n=0; $txtmaps = ""; $mn=-1;
				foreach ($maps as $ico) if (trim($ico)!="") {
					if(!getimagesize("maps/" . $ico)) { continue;}
					$n = str_replace("map","",str_replace(".jpg","",$ico));
					//Checking the permission to see a map: If we have permissions to see it, the map will appear, otherwise it will be skipped.
					if (strlen($perms[$n]) > 0 && !mapAllowed($perms[$n],$version)) continue;
					$defaultborder = ($n == $default_map) ? " style='text-decoration:italic'" : " style='font-weight:bold;font-size:12px'";
					$deftxt = ($n == $default_map) ? _("DEFAULT MAP") : _("Set as Default");
					if (intval($n)>$mn) $mn=intval($n);
					?>
					<td>
						<table style="background-color:<?php echo ($n == $default_map) ? "#F2F2F2" : "#FFFFFF"?>">
							<tr>
								<td>
									<table class="headerpr" width='100%'>
										<tr>
											<td style="text-align:left; padding-left:3px;" align="left">
												<?php 
												//Shorting the name of the map to avoid too big names. In the title will appear the whole name.
												$map_name = ( strlen($map_names[$n]) > 20 ) ? substr($map_names[$n], 0, 17)."..." : $map_names[$n] ?>
												<span title='<?php echo $map_names[$n];?>'><?php echo $map_name;?></span>
											</td>
											<td style="text-align:right" align="right">
												<a href='changemap.php?map=<?php echo $map?>&delete=<?php echo urlencode($ico)?>' onclick="if (!confirm('<?php echo  Util::js_entities(_("Are you sure?"))?>')) return false" title='<?php echo _("Delete map") ?>'><img src='../pixmaps/cross-circle-frame.png' border=0></a>
											</td>		
										</tr>
									</table>
								</td>
														
							</tr>
							<tr>
								<td>
									<a href='view.php?map=<?php echo $n?>'><img src='maps/<?php echo $ico?>' border='<?php echo (($default_map==$n) ? "1" : "0")?>' width=150 height=150></a>
								</td>
							</tr>
							
							<tr>
								<td align="center">
									<?php 
									//Checking the permission to edit a map: If we have permissions to edit it, an icon will be shown to edit the map.
									if (mapAllowedEdit($conn,$perms[$n],$version)) {?>
				                    <a class="greyboxo" href="change_user.php?id_map=<?php echo $n?>" title="<?=("Change owner")?>"><img src="../pixmaps/group.png" title="<?_("Change owner")?>" alt="<?_("Change owner")?>" border="0"></a>&nbsp;
						            <? } ?>
									<?php if ($n == $default_map) { ?>
									<font style=""><?php echo _("DEFAULT MAP") ?></font>
									<?php } else {?>
									<input type="button" onclick="document.location.href='changemap.php?map=<?php echo $map?>&default=<?php echo $n?>'" value="<?php echo $deftxt?>" class="button"></input>
									<?php }?>
								</td>
							</tr>
						</table>
					</td>
					<?php 
					$i++; if ($i % 5 == 0) {
					?>
					</tr><tr>
					<?php }
				}
				?> 
				</tr>
			</table>
		</td>
	</tr>
</table>
</body>
</html>
