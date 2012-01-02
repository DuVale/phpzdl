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
require_once 'classes/Session.inc';
require_once 'classes/Security.inc';

require_once 'classes/Util.inc';
require_once 'ossim_db.inc';
require_once ('classes/Backup.inc');

Session::logcheck("MenuConfiguration", "ToolsBackup");

$conf     = $GLOBALS["CONF"];
$data_dir = $conf->get_conf("data_dir");

$backup_dir = $conf->get_conf("backup_dir");
$version    = $conf->get_conf("ossim_server_version", FALSE);
$pro        = (preg_match("/pro|demo/i",$version)) ? true : false;
//$backup_dir = "/root/pruebas_backup";

$db         = new ossim_db();
$conn       = $db->snort_connect();
$conn_ossim = $db->connect();
$insert = Array();
$delete = Array();
$executing = Array();
if (!is_dir($backup_dir)) {
    die(ossim_error(_("Could not access backup dir") . ": <b>$backup_dir</b>"));
}
$dir = dir($backup_dir);

$query = OssimQuery("SELECT DISTINCT DATE_FORMAT(timestamp, '%Y%m%d') as day FROM acid_event ORDER BY timestamp DESC");
if (!$rs = $conn->Execute($query)) {
    print 'error: ' . $conn->ErrorMsg() . '<BR>';
    exit;
}

// Executing
$cmd = "ps ax | grep restoredb.pl | grep -v grep";
$output = explode("\n",`$cmd`);
foreach ($output as $line) {
	if (preg_match("/restoredb\.pl\s+insert\s+([\d\,]+)/",$line,$found)) {
		$aux = explode(",", $found[1]);
		foreach ($aux as $d) {
			$executing[$d]++;
		}
	}
}

// Delete
while (!$rs->EOF) 
{
	if (file_exists($backup_dir."/delete-".$rs->fields["day"].".sql.gz")) 
		$delete[] = $rs->fields["day"];
	
    $rs->MoveNext();
}

// Insert
while ($file = $dir->read()) 
{
    if (preg_match("/^insert\-(.+)\.sql\.gz/", $file, $found)) {
        if (!in_array($found[1], $delete) && !$executing[$found[1]]) $insert[] = $found[1];
    }
    
}
rsort($insert);
$dir->close();

$users    = Session::get_users_to_assign($conn_ossim);
$entities = Session::get_entities_to_assign($conn_ossim);



$db->close($conn);
$db->close($conn_ossim);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<title><?=_('Backup')?></title>
 		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  		<meta http-equiv="Pragma" content="no-cache">
  		<link rel="stylesheet" type="text/css" href="../style/style.css"/>
		<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  		<script language="javascript">
  			function boton (form, act) {
  				form.perform.value = act;
  				form.submit();
  			}
			
  			function reload_backup() {
				document.location.href="index.php";
  			}
			
			function switch_status() {
				document.location.href="index.php?status_log=" + $('#status_log').val();
			}		
  		</script>
  	</head>
  	<body>
		<?php include ("../hmenu.php"); ?>
  		<center>
		 <table cellpadding=0 cellspacing=0 class="noborder" style="width: 95%; background: transparent;">   <!-- table for center -->
		<tr valign="top"><td class="noborder">
        <?
        if ($message!="") {
            echo "<b><span style='color:#FFA500'>".$message."</span></b><br><br>";
        }
        ?>
  		<form name="backup" action="launch.php" target="process_iframe" method="post">
  	  	<table  style="width: 100%;">
  			<tr>
  				<th colspan="3" class='headerpr'><?php echo gettext("Backup Manager"); ?></th>
			</tr>
  			<tr>
  				<th><?php echo gettext("Dates to Restore"); ?></th>
  				<td width="10" class="nobborder">&nbsp;</td>
  				<th><?php echo gettext("Dates in Database"); ?></th>
  			</tr>
  			<tr>
  				<td class="nobborder" style="text-align:center;" valign="top">
					<table class="transparent">
						<tr>
							<td class="nobborder">
								<select name="insert[]" size="<?php echo ($pro) ? "7" : "10" ?>" multiple>
									<?php
									if (count($insert) > 0) 
									{
										foreach($insert as $insert_item) 
										{
											?>
										   <option value="<?php echo $insert_item?>">&nbsp;&nbsp;<?php echo preg_replace("/(\d\d\d\d)(\d\d)(\d\d)/", "\\3-\\2-\\1", $insert_item) ?>&nbsp;&nbsp;</option>
											<?php
										}
									} 
									else 
									{ 
										?>
										<option size="100" disabled>&nbsp;&nbsp;--&nbsp;<?php echo _("NONE") ?>&nbsp;--&nbsp;&nbsp;</option>
										<?php
									} 
									?>
								</select>
							</td>
						</tr>
					   <?php 
					   if ($pro) 
					   { 
							?>
							<tr>
								<td class="nobborder">
									<select name="user" style="width: 200px">
										<option value="">- <?php echo _("All Users") ?> -</option>
										<?php 
										foreach ($users as $k => $v) 
										{ 
											echo "<option value='".$v->get_login()."'>".$v->get_login()."</option>";
										} 
										?>
									</select>
									&nbsp;
									<select name="entity" style="width: 200px">
										<option value="">- <?php echo _("All Entities") ?> -</option>
										<?php
										foreach ($entities as $k => $v)
										{
											echo "<option value='$k'>$v</option>";
										}
										?>
									</select>
								</td>
							</tr>
						<?php 
						} ?>
					</table>
  				</td>
				<td class="nobborder">&nbsp;</td>
				<td class="nobborder" style="text-align:center;padding-top:3px" valign="top">
					<select name="delete[]" size="10" multiple='multiple'>
						<?php
						if (count($delete) > 0) 
						{
							foreach($delete as $delete_item) 
							{
								?>
								<option size="100" value="<?php echo $delete_item?>">&nbsp;&nbsp;<?php echo preg_replace("/(\d\d\d\d)(\d\d)(\d\d)/", "\\3-\\2-\\1", $delete_item) ?>&nbsp;&nbsp;</option>
								<?php
							}
						} 
						else 
						{ 
							?>
								<option size="100" disabled>&nbsp;&nbsp;--&nbsp;<?php echo _("NONE") ?>&nbsp;--&nbsp;&nbsp;</option>
							<?php
						} 
						?>
					</select>
				</td>
  			</tr>
  			<tr>
  				<td class="nobborder" style="text-align:center">
  					<input type="button" class="button" name="insertB" value="<?php echo gettext("Restore"); ?>" type="submit" onclick="boton(this.form, 'insert')" <?php echo ($isDisabled) ? "disabled" : "" ?> />
  				</td>
  				<td class="nobborder" style="text-align:center">&nbsp;</td>
  				<td class="nobborder" style="text-align:center">
  					<input type="button" class="button" name="deleteB" value="<?php echo gettext("Purge"); ?>" type="submit" onclick="boton(this.form, 'delete')"  <?php echo ($isDisabled) ? "disabled" : "" ?> />
  				</td>
  			</tr>
  			
			<?php if ($pro) { ?>
  			<tr><td colspan="3" class="nobborder"><table class="transparent"><tr><td class="nobborder"><input type="checkbox" name="nomerge" value="nomerge" checked="checked"></input></td><td class="nobborder"><?php echo _("Restore into a new database") ?></td></tr></table></td></tr>
  			<?php } ?>
  		</table>
  		<input type="hidden" name="perform" value=""/>
  		</form>
  		
		<table class="transparent"  style="width: 100%;">
  			<tr><td class="nobborder"><iframe name="process_iframe" id="process_iframe" src="launch.php" height="50" frameborder="0"></iframe></td></tr>
  		</table>
		
		<table align="center" style="width: 100%;">
			<tr>
				<th colspan="5" class='headerpr'><?php echo gettext("Backup Events"); ?></th>
			</tr>
			
			<tr>
				<th><?php echo gettext("User"); ?></th>
				<th><?php echo gettext("Date"); ?></th>
				<th><?php echo gettext("Action"); ?></th>
				<th><?php echo gettext("Status"); ?></th>
				<th><?php echo gettext("Percent"); ?></th>
			</tr>
			
			<?php
			$db1    = new ossim_db();
			$conn1  = $db1->connect();
			$cmd    = "ps ax | grep restoredb.pl | grep -v grep";
			$output = explode("\n",`$cmd`);
			if (count($output) == 1 && $output[0] == "") {
				// Posibily failed
				$conn1->Execute("UPDATE restoredb_log SET status=-1 WHERE status=1");
			}

			$query = OssimQuery("SELECT * FROM restoredb_log ORDER BY id DESC LIMIT 10");
			if (!$rs1 = $conn1->Execute($query)) {
				print 'error: ' . $conn1->ErrorMsg() . '<BR>';
				exit;
			}
			$results = array();

			while (!$rs1->EOF) {
				$results[] = $rs1->fields;
				$rs1->MoveNext();
			}

			$db1->close($conn1);

			if (count($results) < 1) 
			{ 
				?>
				<tr>
					<td colspan="6"><?=_("No Events found")?></td>
				</tr>
				<?php 
			} 
			else 
			{
				foreach ($results as $rs1) 
				{
					?>
						<tr>
							<td><?php echo $rs1["users"] ?></td>
							<td nowrap><?php echo Util::timestamp2date($rs1["date"]) ?></td>
							<td><?php echo str_replace(",",", ",$rs1["data"]) ?></td>
							<?php
							if ($rs1["status"] == 1) 
							{ 
								?>
								<td><font color="orange"><b><?php echo gettext("Running"); ?></b></font></td>
								<?php
							} 
							elseif ($rs1['status'] == -1) 
							{ 
								?>
								<td><font color="red"><b><?php  echo gettext("Failed"); ?></b></font></td>
							<?php
							} 
							else 
							{ 
								?>
								<td><font color="green"><b><?php echo gettext("Done"); ?></b></font></td>
								<?php
							} 
							?>
						<td><?php echo $rs1["percent"] ?></td>
					</tr>
					<?php
				}
			}
			?>
		</table>
		</td><td class="noborder" style="width: 50px">&nbsp;</td><td class="noborder">
		
		<?php
		
		$array_result = array();
		$file_log     = "/var/log/ossim/framework-notifications.log";
		$number_log   = 20;
		
		$status_flag   = ( !empty($_GET['status_log']) ) ? $_GET['status_log'] : "0";
		switch($status_flag)
		{
			case 1:
				$status_log = " -- INFO --";
				break;
			case 2:
				$status_log = " -- WARNING --";
				break;
			default:
				$status_log = " -- ";
				$status_flag = 0;
				break;
		}
		
		exec("cat ".$file_log." | grep '".$status_log."' | tail -n ".$number_log, $array_result, $flag_error);
		
		?>
		
			<table align="center" style="width:100%"> 
				<thead>
					<tr>
						<th colspan="3" class='headerpr'><?php echo _("Framework Backup Notifications")?></th>
					</tr>
					<tr>
						<th><?php echo _("Date") ?></th>
						<th><?php echo _("Status") ?>
							
							<select name="status_log" id="status_log" onchange="switch_status();return false;" style="font-size:9px; width: 90px;">
								<option value="0" <?php echo ( $status_flag == 0) ? "selected='selected'" : "" ?> ><?php echo _("ALL") ?></option>
								<option value="1" <?php echo ( $status_flag == 1) ? "selected='selected'" : "" ?> ><?php echo _("INFO") ?></option>
								<option value="2" <?php echo ( $status_flag == 2) ? "selected='selected'" : "" ?> ><?php echo _("WARNING") ?></option>
							</select> 
						</th>
						<th><?php echo _("Message") ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					
					if ($flag_error <> 0 || empty($array_result)) {
						echo "<tr><td colspan='3'>" . _("No notifications found") . "</td></tr>";
					}else{
					
						foreach($array_result as $contents ) {
							//2011-07-01 13:41:58.859468 [FRAMEWORKD] -- INFO -- backup file already created (/var/lib/ossim/backup/phpgacl-backup_2011-07-01.sql) 
							if (preg_match("/(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2})\.\d+\s+\[[A-Za-z]+\]\s--\s([A-Za-z]+)\s--\s(.*)/", $contents, $result)) {
								//IF INFO -> COLOR = DFF7FF
								//ELSE (WARNING) --> COLOR = FFFFDF
								$background_color = ( $result[2]=="INFO" ) ? "DFF7FF" : "FFFFDF" ; 
								echo '
									<tr style="background-color: #'.$background_color.';">
										<td nowrap="nowrap">'.$result[1].'</td>
										<td>'.$result[2].'</td>
										<td style="text-align:left">'. preg_replace('/error/i','<strong>ERROR</strong>', $result[3]) .'</td>
									</tr>
								';
							}
						}
					}
					?>
				</tbody>
			</table>
		
		
		</td></tr></table>
		</center>
  	</body>
</html>
