<?php
ini_set("max_execution_time","300"); 

require_once ('classes/Scan.inc');
require_once ('classes/Session.inc');

Session::logcheck("MenuMonitors", "TrafficCapture");

$scan_name = GET("scan_name");
$sensor_ip = GET("sensor_ip");

ossim_valid($scan_name, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_DOT, 'illegal:' . _("Scan name"));
ossim_valid($sensor_ip, OSS_IP_ADDR, 'illegal:' . _("Sensor ip"));

if (ossim_error()) {
    die(ossim_error());
}

$db     = new ossim_db();
$dbconn = $db->connect();

$scan_info = explode("_", $scan_name);
$users = Session::get_users_to_assign($dbconn);

$my_users = array();
foreach( $users as $k => $v ) {  $my_users[$v->get_login()]=1;  }

if($my_users[$scan_info[1]]!=1 && !Session::am_i_admin() )  return;

$scan = new TrafficScan();

$file = $scan->get_pcap_file_wp($scan_name,$sensor_ip);


if(preg_match("/^E/i",$file)) { ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title> <?php echo gettext("OSSIM Framework"); ?> - Traffic capture </title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
        <META http-equiv="Pragma" content="no-cache">
        <link rel="stylesheet" type="text/css" href="../style/style.css"/>

    </head>
    <body>
    <?php
    include ("../hmenu.php");
    ?>
    <div class='ossim_error'><?php echo $file; ?></div>
    </body>
    </html>
<?php
}
else if(file_exists($file)) {
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: no-cache'); // no-cache, public
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    header('Content-Description: File Transfer');
    header('Content-Type: application/binary');
    header('Content-Length: ' . filesize($file));
    header('Content-Disposition: inline; filename='.$scan_name);
    readfile($file);
}
// Clean temp files 
if (file_exists($file)) unlink($file);

$db->close($dbconn);

?>