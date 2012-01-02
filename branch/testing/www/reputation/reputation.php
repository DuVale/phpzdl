<?php
/*****************************************************************************
*
*   Copyright (c) 2007-2011 AlienVault
*   All rights reserved.
*
****************************************************************************/

require_once ('classes/Session.inc');
require_once ('ossim_conf.inc');
require_once ('general.php');

$conf     = $GLOBALS["CONF"];
$version  = $conf->get_conf("ossim_server_version", FALSE);
$prodemo  = ( preg_match("/pro|demo/i",$version) ) ? true : false;

$rep_file = trim(`grep "reputation" /etc/ossim/server/config.xml | perl -npe 's/.*\"(.*)\".*/$1/'`);

if ( $prodemo && file_exists($rep_file) ) {
    
   ?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html lang="en">
	<head>
        <title> <?php echo gettext("OSSIM Framework"); ?> - <?php echo gettext("IP reputation"); ?> </title>
        <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1"/>
        <META http-equiv="Pragma" content="no-cache">
		<link rel="stylesheet" type="text/css" href="../style/style.css" />
		<script language="javascript" type="text/javascript" src="../js/jqplot/jquery-1.4.2.min.js"></script>
    </head>
    <body style="height:100%">
    <?php
		include ("../hmenu.php"); 
    ?>
    
	    <table border="0" cellpadding="0" cellspacing="0" width="100%">
	    <tr>
		    <td class="noborder">
		    	<iframe src="stats.php?type=matches" frameborder="0" style="width:400;height:250px;"></iframe>
		    </td>
		    <td class="noborder">
		    	<iframe src="stats.php" frameborder="0" style="width:400;height:250px;"></iframe>
		    </td>
		    <td class="noborder">
		    	<iframe src="stats.php" frameborder="0" style="width:400;height:250px;"></iframe>
		    </td>		    
	    </tr>
	    
	    <tr>
		    <td class="noborder">
		    	<iframe src="stats.php" frameborder="0" style="width:400;height:250px;"></iframe>
		    </td>
		    <td class="noborder">
		    	<iframe src="stats.php" frameborder="0" style="width:400;height:250px;"></iframe>
		    </td>
		    <td class="noborder">
		    	<iframe src="stats.php" frameborder="0" style="width:400;height:250px;"></iframe>
		    </td>		    
	    </tr>
		</table>
		
        <br/>
        
    </body>
    </html>
<?php

}

?>
