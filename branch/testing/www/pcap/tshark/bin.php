<?php
session_start();

require_once("includes/tshark.inc");
$tshark = unserialize($_SESSION['TSHARK_tshark']);

$id = isset($_GET['id']) ? $_GET['id'] : 1 ;

$tshark->print_text_bin($id);

?>