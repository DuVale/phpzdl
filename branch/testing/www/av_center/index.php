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

require_once 'classes/Session.inc';
require_once 'classes/Av_center.inc';
require_once 'ossim_db.inc';

$avc_path = '/usr/share/ossim/www/av_center';
set_include_path(get_include_path() . PATH_SEPARATOR . $avc_path);

//Only admin can access
Avc_utilities::check_access();

$error = false;

$db       = new ossim_db();
$conn     = $db->connect();

$avc_tree = new Avc_tree($conn);

$json         = $avc_tree->get_tree();
$json['data'] = base64_encode($json['data']);


/*
echo "<pre>";
    print_r($json);
echo "</pre>";
*/

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html;charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type='text/javascript' src='../js/codemirror/codemirror.js' ></script>
	
	<!-- Dynatree libraries: -->
	<script type="text/javascript" src="../js/jquery-ui-1.8.custom.min.js"></script>
	<script type="text/javascript" src="../js/jquery.cookie.js"></script>
	<script type="text/javascript" src="../js/jquery.dynatree.js"></script>
		
	<link type="text/css" rel="stylesheet" href="../style/tree.css" />
    
    <!-- Greybox libraries: -->
    <script type="text/javascript" src="../js/greybox.js"></script>
    <link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
    	
	<!-- Autocomplete libraries: -->
	<script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>
	<link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css"/>
	
	<!-- Elastic textarea: -->
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
	
    <!-- Progress Bar: -->
    <script type="text/javascript" src="js/progress.js"></script>
    <link type="text/css" rel="stylesheet" href="css/progress.css"/>
    
    <!-- Spark Line: -->
    <script type="text/javascript" src="../js/jquery.sparkline.js"></script>
    
    <!-- JQplot: -->
    <!--[if IE]><script language="javascript" type="text/javascript" src="../js/jqplot/excanvas.js"></script><![endif]-->
	<link rel="stylesheet" type="text/css" href="../js/jqplot/jquery.jqplot.css" />
    <script language="javascript" type="text/javascript" src="../js/jqplot/jquery.jqplot.min.js"></script>
    <script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.pieRenderer.js"></script>
        
    <!-- Xbreadcrumbse: -->
    <script type="text/javascript" src="js/xbreadcrumbs.js"></script>
    <link rel="stylesheet" type="text/css" href="css/xbreadcrumbs.css"/>
                
	<!-- Own libraries: -->
    <script type="text/javascript" src="js/messages.php"></script>
    <script type="text/javascript" src="js/common.js"></script>
	<script type='text/javascript' src='../js/utils.js'></script>
	<script type="text/javascript" src="js/av_center.js"></script>
    <script type="text/javascript" src="js/tabs.js"></script>
    
    <link rel="stylesheet" type="text/css" href="../style/style.css"/>
    <link rel="stylesheet" type="text/css" href="css/av_center.css"/>
	
	<script type='text/javascript'>
			
        //Dynatree
        var tree = null;
        
        //Section
        var section = null;
   
        //Spark Line
        var r_memory_usage = ['0'];
        var s_memory_usage = ['0'];
        var cpu_usage      = ['0'];     
            
        $(document).ready(function() {
            tree = new Tree(<?php echo '["'.$json['data'].'","'.$json['error_msg'].'"]'?>);
            tree.load_tree();
                       
            //JQplot
            $.jqplot.config.enablePlugins = true;
            
            $('#breadcrumbs').xBreadcrumbs({ collapsible: false });
                       
            $('#avc_cmcontainer').bind('click', function() {
                toggle_tree();
            });
                        
        });
	</script>
</head>

<body>
    <!--<div style='position:absolute; top: 12px; right: 250px; z-index:100000'><a href="javascript:section.load_section('home');">[ Refresh All ]</a></div>-->
	<div id='container_center'>
        <div id="avc_actions"></div>
        <table id='container_bc'>
			<tr>
				<td id='bc_data'>
                    <ul class="xbreadcrumbs" id="breadcrumbs">
                        <li class='current'><a href='index.php' class="home"><?php echo _("Alienvault Center")?></a></li>
                    </ul>
                </td>
            </tr>
        </table>
		
		<table id='section_container'>
			<tr>
				<td id='avc_clcontainer'>
					<div id='tree_container_top'></div>
					<div id='tree_container_bt'></div>
				</td>
                
                <td id='avc_cmcontainer' valign='top'>
                    <img src='images/left_arrow.png' class='show' title='<?php echo _("Hide tree")?>' alt='<?php echo _("Hide tree")?>'/>
                </td>
				
				<td id='avc_crcontainer'>
					<div class="avc_content">
                    	<div id="avc_data">
                            <div id='load_avc_data'></div>
                        </div>
					</div>
				</td>
			</tr>
		</table>
	</div>

</body>

</html>

