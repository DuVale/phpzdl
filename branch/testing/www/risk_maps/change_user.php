<?
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

require_once('ossim_conf.inc');
require_once('classes/Session.inc');

$conf    = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);
$pro      = ( preg_match("/pro|demo/i",$version) ) ? true : false;

if (!Session::menu_perms("MenuControlPanel", "BusinessProcessesEdit") ) 
{
	Session::unallowed_section();
	exit();
}


// if ( !Session::am_i_admin() && ( $pro && !Acl::am_i_proadmin()) ) 
// {
	// echo "<br/><br/><center>"._("You don't have permission to see this page.")."</center>";
	// exit;
// }

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<script>
		function switch_user(select) {
			if(select=='entity' && $('#entity').val()!=''){
				$('#user').val('');
			}
			else if (select=='user' && $('#user').val()!=''){
				$('#entity').val('');
			}
		}
	</script>
  
  	<style type='text/css'>
	
	table{		
		text-align:center;
	} 
	
		
	#update { 
		padding:20px 0px 0px 0px;
		border: none;
	}
	
	#user, #entity {width: 209px;}
		
	.format_user,.format_entity{
		margin-right: 3px;
		width: 50px;
		text-align: right;
	}
	
	.select_user,.select_entity{
		width: 260px;
	}
	
	
	.format_or{ 
		text-align:center; 
		border-bottom: none;
	}
	
	.owners {
		margin-bottom: 10px;		
	}
	
	.owners .action { width: 20px; text-align: center;}
	
	.owners td { padding-left: 10px;}
	
	.normal {text-align: left;}
	
	.right { 
		text-align: right !important;
		padding-right: 15px;
	}
	
	</style>
  
</head>
<body>
<?

$id_map      = $_GET["id_map"];
$map_name    = $_GET["map_name"];
$entity      = $_GET["entity"];
$user        = $_GET["user"];
$delete_perm = $_GET["delete"];



ossim_valid($id_map,      OSS_DIGIT, OSS_ALPHA, OSS_DOT,                            'illegal:' . _("ID Map"));
ossim_valid($entity,      OSS_NULLABLE, OSS_DIGIT, OSS_ALPHA,                       'illegal:' . _("Entity"));
ossim_valid($user,        OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_SPACE, '\.',      'illegal:' . _("User"));
ossim_valid($delete_perm, OSS_SCORE, OSS_NULLABLE, OSS_DIGIT, OSS_ALPHA, OSS_SPACE, 'illegal:' . _("Delete Perm"));
ossim_valid($map_name,    OSS_INPUT, OSS_NULLABLE,                                  'illegal:' . _("Wrong Map Name"));

if (ossim_error()) {
    die(ossim_error());
}

$db     = new ossim_db();
$dbconn = $db->connect();

//If neither the entity, user nor name are modified, nothing will be done.
if($entity != '' || $user != '' || $map_name != '') {
	//Checking if the current map already exist in DB.
	$newuser = ( $entity != "" ) ? $entity : $user;
	$query  = "SELECT count(*) as count FROM risk_maps where map='$id_map'";
	$result = $dbconn->Execute($query);
	$result_count = 0;
	if (!$result->EOF) {
		$result_count = $result->fields['count'];
	}	
	//If it exists, Updating the info.
	if($result_count > 0){
		//If we are doing an update and the permission is null, only the name will be updated.
		$update  = ($newuser != "") ? ", perm='$newuser'" : "" ;
		$query   = "UPDATE risk_maps SET name='$map_name' $update WHERE map='$id_map'";		
		$result  = $dbconn->execute($query);
	//If it does not exists, Creating the info.	
	} else { 
		//If we are doing an insert and no permission is specified, we set the own user as permission.
		if ( empty($newuser) ){
			if(Session::am_i_admin()){
				$newuser = '0';
			} else {
				$newuser = Session::get_session_user();
			}
		}
		$query   = "INSERT IGNORE INTO risk_maps (map,perm,name) VALUES ('$id_map', '$newuser','$map_name')";
		$result  = $dbconn->execute($query);
	}
}

$query  = "SELECT perm, name FROM risk_maps where map='$id_map'";
$result = $dbconn->Execute($query);

if(!$result->EOF) {
	$user = $result->fields['perm'];
	$map_name = $result->fields['name'];
}


$users    = Session::get_users_to_assign($dbconn);
$entities = Session::get_entities_to_assign($dbconn);


/*
if ($pro) 
{
	$entities_types_aux = Acl::get_entities_types($dbconn);
	$entities_types = array();
	foreach ($entities_types_aux as $etype) { 
	    $entities_types[$etype['id']] = $etype;
	}
	list($entities_all,$num_entities) = Acl::get_entities($dbconn);
    list($entities_admin,$num) = Acl::get_entities_admin($dbconn,Session::get_session_user());
    $entities_list = array_keys($entities_admin);
}*/

$dbconn->disconnect(); 
?>



<form action="change_user.php" method="get">
	<input type="hidden" name="id_map" value="<?php echo $id_map ?>">
	
	
		<table class="transparent" align="center" style="margin: 15px auto; padding-left: 32px;" width="95%">			
			<tr>
				<td class="nobborder" colspan="2">
				<table class="transparent" style="margin-bottom: 25px;">
					<tr>
						<td class='format_user nobborder'><?php echo _("Name:");?></td>	
						<td class='select_user nobborder'><input type='text' size='28' name='map_name' value="<?php echo $map_name; ?>"/></td>
					</tr>					
				</table>
				</td>
			</tr>
			<tr>
				<td class='format_user nobborder'><?php echo _("User:");?></td>	
				<td class='select_user nobborder'>				
					<select name="user" id="user" onchange="switch_user('user');return false;">
						
						<?php
													
						$num_users = 0;
						foreach( $users as $k => $v )
						{
							$login = $v->get_login();
							if($login == $user) {
								$options .= "<option selected='selected' value='".$login."'>$login</option>\n";
							} else {
								$options .= "<option value='".$login."'>$login</option>\n";
							}		
							
							$num_users++;
													
						}
						
						if ($num_users == 0)
							echo "<option value='' style='text-align:center !important;'>- "._("No users found")." -</option>";
						else
						{
							echo "<option value='' style='text-align:center !important;'>- "._("Entity Selected")." -</option>\n";
							if (Session::am_i_admin()){
								if($user=='0'){
									echo "<option selected='selected' value='0'>All</option>\n";
								} else {
									echo "<option value='0'>All</option>\n";
								}
							
							}
							echo $options;
						}
												
						?>
					</select>
				</td>
			</tr>
		
			
			
			<?php if ( !empty($entities) ) { ?>
			<tr><td class="format_or nobborder" colspan='2'><?php echo _("or");?></td></tr>
			
			<tr>
				<td class='format_entity nobborder'><?php echo _("Entity:");?></td>
				<td class='select_entity nobborder'>	
					<select name="entity" id="entity" onchange="switch_user('entity');return false;">
						<option value="" style='text-align:center !important;'>- <?php echo _("User Selected") ?> -</option>
						<?php
						foreach ( $entities as $k => $v ) 
						{					
							if($k == $user) {
								echo "<option value='$k' selected='selected'>$v</option>";
							} else {
								echo "<option value='$k'>$v</option>";
							}
						}
						?>
					</select>
				</td>
				<?php } ?>
			</tr>
						

			
			
			<tr><td id='update' colspan='2'><input type='submit' class='button' onclick="parent.document.location.href='changemap.php'" value='<?php echo _("Update")?>'/></td></tr>

		</table>		

</form>

</body>
</html>