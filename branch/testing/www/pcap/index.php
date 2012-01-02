<?php
/*****************************************************************************
*
*   Copyright (c) 2007-2011 AlienVault
*   All rights reserved.
*
****************************************************************************/
ini_set("max_execution_time","300"); 

require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
require_once ('classes/Sensor.inc');
require_once ('classes/Scan.inc');
require_once ('ossim_conf.inc');

$conf = $GLOBALS["CONF"];
$unlimited_traffic_capture = ( $conf->get_conf("unlimited_traffic_capture", FALSE) == 1 )    ? true : false;

Session::logcheck("MenuMonitors", "TrafficCapture");

$info_error = array();

$src              = $parameters['src']              = GET("src");
$dst              = $parameters['dst']              = GET("dst");
$timeout          = $parameters['timeout']          = GET("timeout");
$cap_size         = $parameters['cap_size']         = GET("cap_size");
$raw_filter       = $parameters['raw_filter']       = GET("raw_filter");
$sensor_ip        = $parameters['sensor_ip']        = GET("sensor_ip");
$sensor_interface = $parameters['sensor_interface'] = GET("sensor_interface");

$soptions         = intval(GET('soptions'));

$validate  = array (
        "src"              => array("validation" => "OSS_NULLABLE, OSS_DIGIT, OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_PUNC, OSS_NL, '\.\,\/'", "e_message" => 'illegal:' . _("Source")),
        "dst"              => array("validation" => "OSS_NULLABLE, OSS_DIGIT, OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_PUNC, OSS_NL, '\.\,\/'", "e_message" => 'illegal:' . _("Destination")),
        "timeout"          => array("validation" => "OSS_DIGIT"           , "e_message" => 'illegal:' . _("Timeout")),
        "cap_size"         => array("validation" => "OSS_NULLABLE, OSS_DIGIT"           , "e_message" => 'illegal:' . _("Cap. size")),
        "raw_filter"       => array("validation" => "OSS_NULLABLE, OSS_ALPHA , '\.\|\&\=\<\>\!\^'"  , "e_message" => 'illegal:' . _("Raw Filter")),
        "sensor_ip"        => array("validation" => "OSS_IP_ADDR"         , "e_message" => 'illegal:' . _("Sensor")),
        "sensor_interface" => array("validation" => "OSS_ALPHA, OSS_PUNC" , "e_message" => 'illegal:' . _("Interface"))
    );

    foreach ($parameters as $k => $v )
    {
        eval("ossim_valid(\$v, ".$validate[$k]['validation'].", '".$validate[$k]['e_message']."');");

        if ( ossim_error() )
        {
            $info_error[] = ossim_get_error();
            ossim_clean_error();
        }
    }


$db     = new ossim_db();
$dbconn = $db->connect();

$scan = new TrafficScan();

$states = array("0" => _("Idle"), "1" => _("A Pending Capture"), "2" => _("Capturing"), "-1" => _("Error When Capturing"));

$scans_by_sensor = $scan->get_scans();
$sensors_status = $scan->get_status();

if(!$scans_by_sensor) $scans_by_sensor = array();
if(!$sensors_status)  $sensors_status  = array();

foreach ($sensors_status as $key => $value) {
    
    $csensor_ip = Sensor::get_sensor_ip($dbconn, $key);
    
    if(!Session::sensorAllowed($csensor_ip))   {
        unset($sensors_status[$key]);
    }
}

// get sensors to get scan status
$ips_to_ckeck = array();

foreach($sensors_status as $sensor_name => $sensor_info) {
    if( intval($sensor_info[0]) == 2 ) {
        $ips_to_ckeck[] = Sensor::get_sensor_ip($dbconn,$sensor_name);
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> - Traffic capture </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<META http-equiv="Pragma" content="no-cache">
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
	<link rel="stylesheet" type="text/css" href="../style/tree.css" />
    <link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
    <link rel="stylesheet" type="text/css" href="../style/progress.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
	<script type="text/javascript" src="../js/jquery.tmpl.1.1.1.js"></script>
	<script type="text/javascript" src="../js/jquery.dynatree.js"></script>
    <script src="../js/greybox.js" type="text/javascript"></script>
    <script type="text/javascript" src="../js/utils.js"></script>
	<script type="text/javascript" src="../js/progress.js"></script>
    <script type="text/javascript">
$(document).ready(function() {
    <?php
    if(!empty($ips_to_ckeck)) { ?>
        setTimeout (show_status,2000);
    <?php
    }
    ?>
		var sfilter = "";
		$("#stree").dynatree({
			initAjax: { url: "../vulnmeter/draw_tree.php", data: {filter: sfilter, traffic_capture: '1'} },
			clickFolderMode: 2,
			onActivate: function(dtnode) {
                dtnode.data.url = html_entity_decode(dtnode.data.url);
				var ln = ($('#src').val()!='') ? '\n' : '';
				var inside = 0;
				if (dtnode.data.url.match(/NODES/)) {
					// add childrens if is a C class
					var children = dtnode.tree.getAllNodes(dtnode.data.key.replace('.','\\.')+'\\.');
					for (c=0;c<children.length; c++) {
						if (children[c].data.url != '') {
							var ln = ($('#src').val()!='') ? '\n' : '';
							$('#src').val($('#src').val() + ln + children[c].data.url)
							inside = true;
						}
					}
					if (inside==0 && dtnode.data.key.match(/^hostgroup_/)) {
						dtnode.appendAjax({
					    	url: "../vulnmeter/draw_tree.php",
					    	data: {key: dtnode.data.key, page: dtnode.data.page},
			                success: function(msg) {
			                    dtnode.expand(true);
			                    var children = dtnode.tree.getAllNodes(dtnode.data.key.replace('.','\\.')+'\\.');
								for (c=0;c<children.length; c++) {
									if (children[c].data.url != '') {
										var ln = ($('#src').val()!='') ? '\n' : '';
										$('#src').val($('#src').val() + ln + children[c].data.url)
									}
								}
			                }
						});
					}
				} else {
					if (dtnode.data.url != '') $('#src').val($('#src').val() + ln + dtnode.data.url)
				}
			},
			onDeactivate: function(dtnode) {},
            onLazyRead: function(dtnode){
                dtnode.appendAjax({
                    url: "../vulnmeter/draw_tree.php",
                    data: {key: dtnode.data.key, page: dtnode.data.page, traffic_capture: '1'}
                });
            }
		});
		var dfilter = "";
		$("#dtree").dynatree({
			initAjax: { url: "../vulnmeter/draw_tree.php", data: {filter: dfilter, traffic_capture: '1'} },
			clickFolderMode: 2,
			onActivate: function(dtnode) {
                dtnode.data.url = html_entity_decode(dtnode.data.url);
				var ln = ($('#dst').val()!='') ? '\n' : '';
				var inside = 0;
				if (dtnode.data.url.match(/NODES/)) {
					// add childrens if is a C class
					var children = dtnode.tree.getAllNodes(dtnode.data.key.replace('.','\\.')+'\\.');
					for (c=0;c<children.length; c++) {
						if (children[c].data.url != '') {
							var ln = ($('#dst').val()!='') ? '\n' : '';
							$('#dst').val($('#dst').val() + ln + children[c].data.url)
							inside = true;
						}
					}
					if (inside==0 && dtnode.data.key.match(/^hostgroup_/)) {
						dtnode.appendAjax({
					    	url: "../vulnmeter/draw_tree.php",
					    	data: {key: dtnode.data.key, page: dtnode.data.page},
			                success: function(msg) {
			                    dtnode.expand(true);
			                    var children = dtnode.tree.getAllNodes(dtnode.data.key.replace('.','\\.')+'\\.');
								for (c=0;c<children.length; c++) {
									if (children[c].data.url != '') {
										var ln = ($('#dst').val()!='') ? '\n' : '';
										$('#dst').val($('#dst').val() + ln + children[c].data.url)
									}
								}
			                }
						});
					}
				} else {
					if (dtnode.data.url != '') $('#dst').val($('#dst').val() + ln + dtnode.data.url)
				}
			},
			onDeactivate: function(dtnode) {},
            onLazyRead: function(dtnode){
                dtnode.appendAjax({
                    url: "../vulnmeter/draw_tree.php",
                    data: {key: dtnode.data.key, page: dtnode.data.page, traffic_capture: '1'}
                });
            }
		});
});
    <?php
    if(!empty($ips_to_ckeck)) { ?>  // if some sensor is running we get its state
        function show_status() {
            $.ajax({
                type: "POST",
                data: "ips=<?php echo implode("#",$ips_to_ckeck);?>",
                url: "get_status.php",
                success: function(html){
                    var lines = html.split("\n");
                    
                    for (var i=0;i<lines.length;i++) {
                        if(lines[i]!="") {
                            var data = lines[i].split("|");
                            if (data[4]!="0.00" && data[7]!="0.00") {
                                $('#ppbar'+data[0]+' .ui-progress').animateProgress(data[4]); // packages
                                $('#tpbar'+data[0]+' .ui-progress').animateProgress(data[7]); // time
                                $('#scan_status'+data[0]).show();         // show scan status
                                setTimeout (show_status,5000);
                            } else {
                                $('#ppbar'+data[0]+' .ui-progress').animateProgress(100);
                                $('#tpbar'+data[0]+' .ui-progress').animateProgress(100);                    
                                document.location.reload()
                            }
                        }
                    }
                    
                }
            });
        }
    <?php
    }
    ?>
    function stop_capture(ip) {
        $.ajax({
            type: "GET",
            data: "",
            url: "manage_scans.php?sensor_ip="+ip+"&op=stop",
            success: function(html){
                document.location.reload();
            }
        });
    }

    function confirmDelete(data){
        var ans = confirm("<?php echo Util::js_entities(_("Are you sure you want to delete this capture?"))?>");
        if (ans) document.location.href='manage_scans.php?'+data;
    }
    GB_TYPE = 'w';
    function showGreybox(title, width, dest){
        GB_show(title,dest,450,width);
    }
    function GB_onclose() {
        document.location.href='index.php';
    }
    
    function check_interface() {
        if($("#sensors option:selected").val().indexOf("#")!=-1){ // sensor doesn't have any interfaces
            var data = $("#sensors option:selected").val().split(/#/);
            showGreybox('<?php echo _("Edit sensor:")?>',900,'../sensor/interfaces.php?sensor='+data[0]+'&name='+data[1]+'&withoutmenu=1');
        }
        else {
            $("#link_to_greybox").hide();
        }
    }
    </script>
</head>
<body>
<?php
include ("../hmenu.php");

?>

<table cellspacing="0" align="center" cellpadding="0" border="0" width="90%">
    <tr><td class="headerpr" style="border:0;"><?php echo gettext("Sensors Status") ?></td></tr>
</table>

<?php
if(count($sensors_status)==0) {
?>
    <table width="90%" align="center">
        <tr>
            <td class="nobborder" style="text-align:center"><?php echo _("No available sensors")?></td>
        </tr>
    </table>
<?php
}
else {
?>
    <table width="90%" align="center">
        <tr>
            <th width="30%"><?php echo _("Sensor Name")?>     </th>
            <th width="30%"><?php echo _("Sensor IP")?>       </th>
            <th width="20%"><?php echo _("Total Captures")?> </th>
            <th width="20%"><?php echo _("Status")?>          </th>
        </tr>
            <?php
                $i=1;
                foreach($sensors_status as $sensor_name => $sensor_info) {
                    // check permissions
                    $users = array();
                    
                    $users_in_perms    = Session::get_users_to_assign($dbconn);
                    foreach ($users_in_perms as $user) {
                        $users[$user->get_login()] = $user->get_login();
                    }
                    
                    $iper = 0;
                    if (is_array($scans_by_sensor[$sensor_name]))
                    foreach($scans_by_sensor[$sensor_name] as $data) {
                        $scan_info_to_check = explode("_",$data);
                        if($users[$scan_info_to_check[1]]=="") {
                           unset($scans_by_sensor[$sensor_name][$iper]);
                        }
                        $iper++;
                    }
                    // *************
                    $seclass="";
                    if(count($scans_by_sensor[$sensor_name])>0 || count($sensor_status)==$i) $seclass = "class=\"nobborder\"";
                    $i++;
                    ?>
                    <tr><td style="text-align:center;" <?php echo $seclass ?>><?php echo $sensor_name;?></td>
                        <td style="text-align:center;" <?php echo $seclass ?>><?php echo Sensor::get_sensor_ip($dbconn,$sensor_name); ?></td>
                        <td style="text-align:center;" <?php echo $seclass ?>><?php echo count($scans_by_sensor[$sensor_name])?></td>
                        <td style="text-align:center;" <?php echo $seclass ?>><span class="sensor_status_<?php echo Sensor::get_sensor_ip($dbconn,$sensor_name); ?>"><?php echo $states[$sensor_info[0]] ?></span></td>
                    </tr>
                    <tr id="scan_status<?php echo md5(Sensor::get_sensor_ip($dbconn,$sensor_name)); ?>" style="display:none">
                        <td colspan="4" class="nobborder" style="text-align:center;">
                            <table align="center" class="transparent">
                                <tr>
                                    <th>
                                    <strong><?php echo _("Current capture");?></strong><br>
                                    <input type='button' class='button' style="margin-top:7px" onclick='this.value="<?php echo _("Stopping...")?>";stop_capture("<?php echo Sensor::get_sensor_ip($dbconn,$sensor_name);?>");' value='<?php echo _("Stop now")?>'/>
                                    </th>
                                    <td width="10" class="nobborder">&nbsp;</td> <!-- space between tds -->
                                    <td class="nobborder" style="text-align:left;width:300px">

                                        <div id="ppbar<?php echo md5(Sensor::get_sensor_ip($dbconn,$sensor_name)); ?>" class="ui-progress-bar ui-container">
                                          <div class="ui-progress" style="width:70%">
                                            <span class="ui-label" style="display:none;"><?php echo _("Packages");?> <b class="value">0%</b></span>
                                          </div>
                                        </div>

                                        <div id="tpbar<?php echo md5(Sensor::get_sensor_ip($dbconn,$sensor_name)); ?>" class="ui-progress-bar ui-container">
                                          <div class="ui-progress" style="width:70%">
                                            <span class="ui-label" style="display:none;"><?php echo _("Time");?> <b class="value">0%</b></span>
                                          </div>
                                        </div>

                                        <!--<div style="display:block;">
                                            <div style="float:left"><span style="margin-right:5px;" class="ui-progress" id="ppbar<?php echo md5(Sensor::get_sensor_ip($dbconn,$sensor_name)); ?>"></span></div>
                                            <div style="float:right"><?php echo _("Packages");?></div>
                                        </div>

                                        <div style="display:block;">
                                        <div style="float:left"><span style="margin-right:5px;" class="ui-progress" id="tpbar<?php echo md5(Sensor::get_sensor_ip($dbconn,$sensor_name)); ?>"></span></div>
                                        <div style="float:right"><?php echo _("Time");?></div>
                                        </div>-->
                                    </td>
                            </table>
                        </td>
                    </tr>
                    <?php if(is_array($scans_by_sensor[$sensor_name]) && count($scans_by_sensor[$sensor_name])>0) { ?>
                        <tr><td colspan="4" class="nobborder">
                            <table width="90%" style="margin:auto">
                                <tr>
                                    <th width="30%"><?php echo gettext("Capture Start Time"); ?></th>
                                    <th width="20%"><?php echo gettext("Duration (seconds)"); ?></th>
                                    <th width="30%"><?php echo gettext("User"); ?></th>
                                    <th width="20%"><?php echo gettext("Action"); ?></th>
                                </tr>
                            
                            <?php
                                $j=1;
                                foreach($scans_by_sensor[$sensor_name] as $data) {
                                    $scclass="";
                                    if(count($scans_by_sensor[$sensor_name])==$j) $scclass = "class=\"nobborder\"";
                                    $j++;
                            ?>
                                <tr>
                                    <td style="text-align:center" <?php echo $scclass;?>><?php
                                        $scan_info = explode("_",$data);
                                        
                                        $sensor_ip = $scan_info[4];
                                        $sensor_ip = str_replace(".pcap", "", $sensor_ip);
                                        
                                        echo date("Y-m-d H:i:s", $scan_info[2] );
                                      ?>
                                    </td>
                                    <td style="text-align:center" <?php echo $scclass;?>><?php echo $scan_info[3]?></td>
                                    <td style="text-align:center" <?php echo $scclass;?>><?php echo $scan_info[1]?></td>
                                    <td style="text-align:center" <?php echo $scclass;?>>
                                        <a href="javascript:;" onclick="return confirmDelete('op=delete&scan_name=<?php echo $data?>&sensor_ip=<?php echo $sensor_ip?>');">
                                            <img align="absmiddle" src="../vulnmeter/images/delete.gif" title="<?php echo gettext("Delete")?>" alt="<?php echo gettext("Delete")?>" border=0>
                                        </a>
                                        <a href="download.php?scan_name=<?php echo $data?>&sensor_ip=<?php echo $sensor_ip?>">
                                        <img align="absmiddle" src="../pixmaps/theme/mac.png" title="<?php echo gettext("Download")?>" alt="<?php echo gettext("Download")?>" border="0">
                                        <a href="tshark/viewcapture.php?scan_name=<?php echo $data?>&sensor_ip=<?php echo $sensor_ip?>" target="AnalisysConsole" >
                                            <img align="absmiddle" src="../pixmaps/wireshark.png" title="<?php echo _("View Payload") ?>" alt="<?php echo _("View Payload") ?>" border="0">
                                        </a>
                                    </td>
                                 </tr>
                            <?php
                            }
                            ?>
                                </table>
                            </td></tr>
                        <?php
                    }
                }
            ?>
    </table>
<?php
}
?>

<br />

<table width="90%" align="center" class="transparent" cellspacing="0" cellpadding="0">
    <tr>
        <td style="text-align:left" class="nobborder">
            <a href="javascript:;" onclick="$('.tscans').toggle();$('#message_show').toggle();$('#message_hide').toggle();" colspan="2"><img src="../pixmaps/arrow_green.gif" align="absmiddle" border="0">
                <span id="message_show" <?php echo ((count($info_error)>0 || $soptions==1 )? "style=\"display:none\"":"")?>><?php echo gettext("Run Capture Now")?></span>
                <span id="message_hide"<?php echo ((count($info_error)>0 || $soptions==1 )? "":"style=\"display:none\"")?>><?php echo gettext("Hide Capture Options")?></span>
            </a>
        </td>
    </tr>
</table>

<form method="get" action="manage_scans.php">
    <br />
    <table cellspacing="0" align="center" cellpadding="0" border="0" width="90%" class="tscans" <?php echo (count($info_error)>0 || $soptions==1 )? "":"style=\"display:none;\""?>>
        <tr><td class="headerpr" style="border:0;"><?php echo gettext("Capture Options") ?></td></tr>
    </table>
	<table border="0" width="90%" align="center" class="tscans" <?php echo (count($info_error)>0 || $soptions==1 )? "":"style=\"display:none;\""?>>
		<tr>
           <td class="nobborder">
                <table align="center" class="nobborder">
                    <tr><td colspan="9" class="nobborder" height="15">&nbsp;</td></tr>
                    <tr>
                        <th width="30"> <?php echo _("Timeout");?> </th>
                        <td class="nobborder" style="padding-left:10px;" width="110">
                            <?php
                                if( !$unlimited_traffic_capture ) {
                            ?>
                                <select name="timeout" style="width:50px;">
                                  <option <?php echo (($timeout=="10") ? "selected=\"selected\"":"") ?>>10</option>
                                  <option <?php echo (($timeout=="20") ? "selected=\"selected\"":"") ?>>20</option>
                                  <option <?php echo (($timeout=="30") ? "selected=\"selected\"":"") ?>>30</option>
                                  <option <?php echo (($timeout=="60") ? "selected=\"selected\"":"") ?>>60</option>
                                  <option <?php echo (($timeout=="90") ? "selected=\"selected\"":"") ?>>90</option>
                                  <option <?php echo (($timeout=="120") ? "selected=\"selected\"":"") ?>>120</option>
                                  <option <?php echo (($timeout=="180") ? "selected=\"selected\"":"") ?>>180</option>
                                </select> <?php echo _("seconds");
                            }
                            else {
                            ?>
                                <input type="text" size="10" name="timeout" value="<?php echo ( (intval($timeout)!=0) ? $timeout : "10" ); ?>"/> <?php echo _("seconds");?>
                            <?php
                            }
                            ?>
                        </td>
                        <td width="50" class="nobborder">&nbsp;
                        </td>
                        <th>
                            <?php echo _("Cap size");?>
                        </th>
                        <?php
                        if( !$unlimited_traffic_capture ) {
                        ?>
                            <td class="nobborder" style="padding-left:10px;">
                                <div id="cap_size" style="width:150px;margin-right:6px;"></div>
                            </td>
                            <td class="nobborder" width="80">
                                <span id="cap_size_value" style="color:#000000; font-weight:bold;"><?php echo ( (intval($cap_size)!=0) ? $cap_size : "4000" ); ?></span><?php echo " "._("packages");?>
                                <input type="hidden" id="cap_size_input" name="cap_size" value="<?php echo ( (intval($cap_size)!=0) ? $cap_size : "4000" ); ?>" />
                            </td>
                        <?php
                        }
                        else {
                        ?>
                            <td class="nobborder" style="padding-left:10px;">
                                <input type="text" size="10" id="cap_size_input" name="cap_size" value="<?php echo ( (intval($cap_size)!=0) ? $cap_size : "4000" ); ?>" /><?php echo " "._("packages");?>
                            </td>
                        <?php 
                        }
                        ?>
                        <td width="50" class="nobborder">&nbsp;
                        </td>
                        <script type='text/javascript'>
                            $("#cap_size").slider({
                                animate: true,
                                range: "min",
                                value: <?php echo ( (intval($cap_size)!=0) ? $cap_size : "4000" ); ?>,
                                min:   100,
                                max:   8000,
                                step:  100,
                                slide: function(event, ui) {
                                    $("#cap_size_value").html(ui.value);
                                    $("#cap_size_input").val(ui.value);
                                }
                            });
                        </script>
                        <th>
                            <?php echo _("Raw filter");?>
                        </th>
                        <td class="nobborder" style="padding-left:10px;">
                            <input type="text" name="raw_filter" value="<?php echo (($raw_filter!="") ? $raw_filter : ""); ?>" />
                        </td>
                        <tr><td colspan="9" class="nobborder" height="15">&nbsp;</td></tr>
                </tr>
                </table>
            </td>
    	</tr>
        <tr>
			<td class="nobborder" width="100%">
				<table class="transparent" width="100%">
					<tr>
						<th colspan="3"><?php echo _("Settings")?></th>
					</tr>
                    <tr>
                        <th><?php echo _("Sensor");?></th>
                        <th><?php echo _("Source");?></th>
                        <th><?php echo _("Destination");?></th>
                    </tr>
					<tr>
						<td valign="top" style="padding:4px;text-align:center" class="nobborder"  width="30%">
                            <table width="100%" class="transparent">
                                <tr>
                                    <td class="nobborder" style="text-align:center;">
                                    <?php
                                    $display_a  = false; // to show link with greybox
                                    $first_ip   = "";
                                    $first_name = "";
                                    
                                    $sensor_list = $scan->get_sensors();

                                    /*
                                    only for debugging 
                                        $sensor_list = array();      //clean array
                                        $sensor_list["5.5.5.5"]      = array("5_5_5_5","");
                                        $sensor_list["192.168.10.4"] = array("no existe","eth0");
                                        $sensor_list["192.168.10.1"] = array("juanma","eth0,eth1" );
                                    */
                                    
                                    foreach ($sensor_list as $key => $value) { // check permissions
                                        if(!Session::sensorAllowed($key))   {
                                            unset($sensor_list[$key]);
                                        }
                                    }
                                    
                                    if(count($sensor_list)==0) { echo _("No available sensors"); }
                                    else {?>
                                        <select id="sensors" onchange="check_interface();" name="sensor" style="width:90%">
                                            <?php
                                            $isensors = 0;
                                            foreach($sensor_list as $ip => $sensor_data) if (Session::sensorAllowed($ip)) {
                                                $interfaces = explode(",",$sensor_data[1]);
                                                $interfaces = array_unique($interfaces);
                                                
                                                if($interfaces[0]!="")  $hinterfaces = true;
                                                else                    $hinterfaces = false;
                                                
                                                foreach($interfaces as $interface) {
                                                    $selected = "";
                                                    if($sensor_ip==$ip && $interface==$sensor_interface) $selected = "selected=\"selected\""; 
                                                    
                                                    if($hinterfaces) {
                                                        $data_to_select    = $ip." (".$sensor_data[0]." / ".$interface.")"; 
                                                        $value_to_select   = $ip."-".$interface;
                                                    }
                                                    else {
                                                        $data_to_select    =  $ip." (".$sensor_data[0]." / "._("INTERFACE NOT FOUND").")"; 
                                                        $value_to_select   =  $ip."#".$sensor_data[0];
                                                        if($isensors == 0) { 
                                                            $display_a  = true;
                                                            $first_ip   = $ip;
                                                            $first_name = $sensor_data[0];
                                                        }
                                                    }
                                                    ?>
                                                    <option value="<?php echo $value_to_select;?>" <?php echo $selected; ?>><?php echo $data_to_select; ?></option>
                                                    <?php
                                                    $isensors++;
                                                }
                                            }?>
                                        </select>
                                    <?php
                                    }
                                    ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="nobborder" style="text-align:center;padding-top:10px;">
                                        <a id="link_to_greybox" <?php echo (!$display_a) ? "style=\"display:none\"":"" ?> onclick="showGreybox('<?php echo _("Edit sensor:")?>',900,'../sensor/interfaces.php?sensor=<?php echo $first_ip; ?>&name=<?php echo $first_name; ?>&withoutmenu=1');" class="greybox" href="javascript:;"><?php echo _("Create interface")?></a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td width="35%" valign="top" style="padding-top:4px;" class="nobborder">
                            <table width="100%" class="transparent">
                                <tr><td class="nobborder" style="text-align:center"><textarea rows="8" cols="32" id="src" name="src"><?php echo $src ?></textarea></td></tr>
                                <tr><td class="nobborder"><div id="stree" style="width:300px;margin:auto"></div></td></tr>
                            </table>
                        </td>
                        <td width="35%" valign="top" style="padding-top:4px;" class="nobborder">
                            <table width="100%" class="transparent">
                                <tr><td class="nobborder" style="text-align:center"><textarea rows="8" cols="32" id="dst" name="dst"><?php echo $dst ?></textarea></td></tr>
                                <tr><td class="nobborder"><div id="dtree" style="width:300px;margin:auto"></div></td></tr>
                            </table>
                        </td>
					</tr>
				</table>
			</td>
		</tr>
        <tr>
            <td style="text-align:right;padding:0px 5px 5px 0px" class="nobborder" s>
                <input type="submit" class="button" name="command" value="<?php echo _("Launch capture");?>" />
                
            </td>
        </tr>
	</table>

</form>

</body>
</html>
<?php

$db->close($dbconn);

function display_errors($info_error)
{
	$errors       = implode ("</div><div style='padding-top: 3px;'>", $info_error);
	$error_msg    = "<div>"._("We found the following errors:")."</div><div style='padding-left: 15px;'><div>$errors</div></div>";
							
	return "<div class='ossim_error'>$error_msg</div>";
}
?>
