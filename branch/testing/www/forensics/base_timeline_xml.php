<?php 

header("Content-type: application/xml"); 
require_once ('classes/Session.inc');
Session::logcheck("MenuEvents", "EventsForensics");
require_once 'vars_session.php';
require_once 'ossim_db.inc';
require_once 'classes/Util.inc';

$db = new ossim_db();
$conn = $db->connect();
$xml = "";

$sql = "SELECT * FROM `datawarehouse`.`report_data` WHERE id_report_data_type = $events_report_type AND USER = ? ";

$user = $_SESSION['_user'];
settype($user, "string");
$params = array(
	$user
);

			
if (!$rs = $conn->Execute($sql, $params)) {
	print 'Error: ' . $conn->ErrorMsg() . '<br/>';
	exit;
}

$format_date = date("M d Y G:i:s")." GMT";
$xml .= "<data>";

if ($rs->EOF) $xml .= "<event start='$format_date' title='"._("No events matching your search criteria have been found")."' link='' icon=''>".Util::htmlentities(_("No events matching your search criteria have been found"))."</event>";

while( !$rs->EOF ) {
	
	$date = explode (" ",  $rs->fields['dataV2']);
	$d = explode("-", $date[0]);
	$t = explode(":", $date[1]);
	
	$timestamp = mktime($t[0], $t[1], $t[2], $d[1], $d[2], $d[0]);
	$format_date = date("M d Y G:i:s", $timestamp)." GMT";
	
		
	$flag = preg_replace("/http\:\/\/(.*?)\//","/",$rs->fields['dataV4']);
			
	$xml .= "<event start='".$format_date."' title='".str_replace("'", "\"", Util::htmlentities($rs->fields['dataV1']))."' link=''";
	$flag = ( $flag =="" ) ? "/ossim/pixmaps/1x1.png" : $flag;
	$xml .= " icon='$flag'>";
	
	
	$inside = "<div class='bubble_desc' style='text-align:center'>".$rs->fields['dataV1']."<br/><br/><div class='txt_desc'>".$rs->fields['dataV3'];
	if ($rs->fields['dataV4']!="") $inside .= " <img src='".$rs->fields['dataV4']."'/>";
	$inside .= " -> ".$rs->fields['dataV5'];
	if ($rs->fields['dataV6']!="") $inside .= " <img src='".$rs->fields['dataV6']."'/>";
	$inside .= "</div><div class='df'>".$format_date."</div>";
	$inside .= "<br/><a href='./base_qry_alert.php?submit=%23".$rs->fields['dataI1']."-%28".$rs->fields['dataI2']."-".$rs->fields['dataI3']."%29&amp;sort_order=time_d' target='main'>"._("View event detail")."</a></div>";
	
	

	$xml .= htmlentities($inside)."</event>"; 
	$rs->MoveNext();
}
			
$xml .= "</data>";
echo $xml;
$db->close($conn);



?>




	
	
	
	
	
		
       
   
   


