<?php
session_start();

require_once("includes/tshark.inc");
$tshark = unserialize($_SESSION['TSHARK_tshark']);


echo("<table width=100% cellspacing=0 cellpadding=0 >");
$tshark->print_psml_body();
echo("</table>");

?>
