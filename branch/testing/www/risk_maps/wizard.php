<?php
/*****************************************************************************
*
*    License:
*
*   Copyright (c) 2007-2011 AlienVault
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
$standard_dir = "pixmaps/standard/";
$icons = explode("\n",`ls -1 '$standard_dir'`);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title><?php echo _("New Indicator Wizard") ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css">
	<link rel="stylesheet" type="text/css" href="../style/tree.css" />
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
	<script type="text/javascript" src="../js/jquery.cookie.js"></script>
	<script type="text/javascript" src="../js/jquery.dynatree.js"></script>
	<script type="text/javascript" src="../js/jquery.easySlider.js"></script>
	<script type="text/javascript">
	var wizard_current = 1;
	function next() {
		$('#wizard_'+wizard_current).hide();
		document.getElementById('link_'+wizard_current).className = "normal";
		wizard_current++;
		$('#wizard_'+wizard_current).show();
		document.getElementById('link_'+wizard_current).className = "bold";
		if (wizard_current == 2) {
			load_tree();
		}
	}
	function wizard_goto(num) {
		document.getElementById('wizard_'+wizard_current).style.display = "none";
		document.getElementById('link_'+wizard_current).className = "normal";
		wizard_current = num;
		wizard_refresh();
		if (num == 2) {
			load_tree();
		}
		if (num == 3) {
			$("#standard").easySlider();
		}
	}
	function wizard_refresh() {
		document.getElementById('wizard_'+(wizard_current)).style.display = "block";
		document.getElementById('link_'+wizard_current).className = "bold";
	}
	function check_name() {
		if ($('#indicator_name').val() != "") {
			return true;
		} else {
			$('#msg').html("<?php echo _("Please, insert a valid name") ?>.");
			return false;
		}
	}

	var layer = null;
	var nodetree = null;
	var suf = "c";
	var i=1;
	
	function load_tree(filter) {
		if (nodetree!=null) {
			nodetree.removeChildren();
			$(layer).remove();
		}
		layer = '#srctree'+i;
		$('#tree').append('<div id="srctree'+i+'" class="tree_container"></div>');
		$(layer).dynatree({
			initAjax: { url: "type_tree.php", data: {filter: filter} },
			clickFolderMode: 2,
			onActivate: function(dtnode) {
                if (dtnode.data.key.indexOf(';')!=-1) 
				{
                    dtnode.deactivate();
					var keys = dtnode.data.key.split(/\;/);
                    
					document.getElementById('type').value = keys[0];
                    document.getElementById('elem').value = keys[1];
                    
					if (keys[0] == "host" || keys[0] == "net" || keys[0] == "sensor" ) 
						document.getElementById('check_report').checked = true;
                    else 
						document.getElementById('check_report').checked = false;
                    
					var style = 'background-color:#EFEBDE; padding:2px 5px 2px 5px; border:1px dotted #cccccc; font-size:11px; width: 90%';
					
					var asset_text  = document.f.type.value + " - " + document.f.elem.value;
					
					if ( asset_text.length > 45 )
						asset_text  = "<div style='padding-left: 10px;'>"+ asset_text.substring(0, 42) + "...</div>";
					
											
					document.getElementById('selected_msg').innerHTML = "<div style='"+style+"'<strong><?php echo _("Selected type")?></strong>: "+ asset_text+"</div>";
                   					   
				    if (document.f.type.value == "host_group" || document.f.type.value == "server") 
                        document.getElementById('linktoreport').style.display = 'none';
                    
                    else 
                        document.getElementById('linktoreport').style.display = '';
                    
                }
                else 
                    dtnode.toggleExpand();
                
			},
			onDeactivate: function(dtnode) {},
			onLazyRead: function(dtnode){
				dtnode.appendAjax({
					url: "type_tree.php",
					data: {key: dtnode.data.key, filter: filter, page: dtnode.data.page}
				});
			}
		});
		nodetree = $(layer).dynatree("getRoot");
		i=i+1
	}

	function choose_icon(icon)
	{
		var cat   = document.getElementById('category').value;
		var timg = document.getElementById('chosen_icon');
		timg.src = icon
		changed = 1;
		document.getElementById('save_button').className = "lbutton_unsaved";
	}
	</script>
	<style>
	.normal { font-weight:normal; }
	.bold { font-weight:bold; }
	/* Easy Slider */

		#standard ul, #standard li{
			margin:0;
			padding:0;
			list-style:none;
		}
		
		#standard, #standard li{ 
			width:150px;
			height:100px;
			overflow:hidden;
			border:1px solid #EEEEEE;
		}
		
		span#prevBtn{}
		span#nextBtn{}					
		
	/* // Easy Slider */
	</style>
</head>
<body>
<table class="transparent" width="100%">
	<tr>
		<td class="nobborder">
			<table class="transparent">
				<tr>
					<td class="nobborder"><img src="../pixmaps/wand.png" alt="wizard"></img></td>
					<td class="nobborder" style="font-size:11px" nowrap><?php echo _("Indicator")?> <b><?php echo _("configuration") ?></b>: </td>
					<td class="nobborder" style="font-size:11px" id="step_1" nowrap><a href='' onclick='wizard_goto(1);return false;' class="bold" id="link_1"><?php echo _("Indicator name") ?></a></td>
					<td class="nobborder" style="font-size:11px" id="step_2" nowrap> > <a href='' onclick='wizard_goto(2);return false;' class="normal" id="link_2"><?php echo _("Asset") ?></a></td>
					<td class="nobborder" style="font-size:11px" id="step_3" nowrap> > <a href='' onclick='wizard_goto(3);return false;' class="normal" id="link_3"><?php echo _("Appearance") ?></a></td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<div id="wizard_1">
<table class="transparent" width="100%">
	<tr><th style="white-space: nowrap; padding: 5px;font-size:12px" colspan="2"><?php echo _("Indicator Name")?></th></tr>
	<tr>
		<td class="center nobborder" style="padding-top:20px"><input type="text" style="width:250px" name="indicator_name" id="indicator_name" value="<?php echo $indicator['name'] ?>"> <input type="button" value="<?php echo _("Next") ?>" onclick="if (check_name()) next()" class="button"></td>
	</tr>
	<tr><td class="center nobborder" id="msg"></td></tr>
</table>
</div>

<div id="wizard_2" style="display:none">
<table class="transparent" width="100%">
	<tr><th style="white-space: nowrap; padding: 5px;font-size:12px" colspan="2"><?php echo _("Asset") ?></th></tr>
	<tr><td class="nobborder"><div id="tree"></div></td></tr>
	<tr><td class="right nobborder"><input type="button" value="<?php echo _("Next") ?>" onclick="next()" class="button"></td></tr>
</table>
</div>

<div id="wizard_3" style="display:none">
<table class="transparent" width="100%">
	<tr><th style="white-space: nowrap; padding: 5px;font-size:12px" colspan="2"><?php echo _("Appearance") ?></th></tr>
	<tr>
		<td class="nobborder">
			<table width="100%">
				<tr><th colspan="2" class='rm_tit_section'><?php echo _("Icon")?></th></tr>
				<tr>
					<td colspan='2' class='ne1' id="uploadform" style="display:none;">
						<form action="index.php" method='post' name='f2' enctype="multipart/form-data" onsubmit="return chk(document.f2)">
							<table id='rm_up_icon' width='100%'>
								<tr>
									<th><?php echo _("Name Icon")?>:</th>
									<td><input type='text' class='ne1' name='name'/></td>
								</tr>
								<tr>
									<th><?php echo _("Upload icon file")?>:</th>
									<td>
										<input type='file' class='ne1' size='15' name='fichero'/>
										<input type='hidden' value="<?php echo $map ?>" name='map'>
									</td>
								</tr>
								<tr><td class='cont_submit' colspan='2'><input type='submit' value="<?php echo  _("Upload") ?>" class="lbutton"/></td></tr>
							</table>
						</form>
					</td>
				</tr>
				<tr>
					<td>
						<div style="display:none">
							<input type='hidden' name="alarm_id" value=""/> x <input type='text' size='1' name='posx'/> y <input type='text' size='1' name='posy'/>
						</div>
					</td>
				</tr>
				
				<tr>
					<td style="width:140px">
					<?php
						$docroot = "/var/www/";
						$resolution = "128x128";
						$icon_cats = explode("\n",`ls -1 '$docroot/ossim_icons/Regular/'`);
						
						echo "<select id='category' name='categories'>
								<option value=\"standard\">Default Icons</option>
								<option value=\"flags\">Country Flags</option>
								<option value=\"custom\">Own Uploaded</option>";
							
								foreach($icon_cats as $ico_cat)
								{
									if(!$ico_cat)continue;
									
									echo "<option value=\"$ico_cat\">$ico_cat</option>";
								}
						echo "</select><br>";
					?>
					<div id="standard">
						<ul>				
						<?php 
						foreach($icons as $ico)
						{ 
							if(!$ico) continue; 
							if(is_dir($standard_dir . "/" . $ico) || !getimagesize($standard_dir . "/" . $ico))
								continue 
							
							$ico2 = preg_replace("/\..*/","",$ico); 
							?>
							<li><a href="javascript:choose_icon('<?php echo "$standard_dir/$ico" ?>')"><img src="<?php echo "$standard_dir/$ico" ?>" alt="Click to choose <?php echo $ico2 ?>"/></a></li>
							<?php 
						} 
						?>
						</ul>
					</div>
					</td>
				
					<td align="center" valign="middle">
						<img src="<?php echo (($uploaded_icon) ? $filename : "pixmaps/standard/default.png")?>" name="chosen_icon" id="chosen_icon"/>
					</td>
				</tr>
			
				<tr>
					<td align="left">
						<a onclick="$('#uploadform').show();return false" style="font-size:12px"><?php echo _("Upload your own icon")?></a><br/>
					</td>
				</tr>
				
				<tr><th colspan="2" class='rm_tit_section'><?php echo _("Style")?></th></tr>
				<tr>
					<td colspan="2" class='bold'><?php echo _("Background")?>: 
						<select name="iconbg" id="iconbg" onchange="set_changed()">
							<option value=""><?php echo _("Transparent")?></option>
							<option value="white"><?php echo _("White")?></option>
						</select>
					</td>
				</tr>
				
				<tr>
					<td colspan="2" class='bold'><?php echo _("Size")?>: 
						<select name="iconsize" id="iconsize" onchange="set_changed()">
							<option value="0"><?php echo _("Default")?></option>
							<option value="30"><?php echo _("Small")?></option>
							<option value="40"><?php echo _("Medium")?></option>
							<option value="50"><?php echo _("Big")?></option>
						</select>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	
	<tr><td class="right nobborder"><input type="button" value="<?php echo _("Next") ?>" onclick="next()" class="button"></td></tr>
</table>
</div>

</body>
</html>
