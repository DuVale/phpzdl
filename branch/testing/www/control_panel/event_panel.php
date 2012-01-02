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
/**
* Class and Function List:
* Function list:
* - function getrepimg($prio,$act)
* - function getrepbgcolor($prio)
* - get_idm_data($conn, $sid, $cid)
* Classes list:
*/
require_once ('classes/Session.inc');
Session::logcheck("MenuEvents", "EventsRT");
require_once 'classes/Host.inc';
require_once 'classes/Sensor.inc';
require_once 'classes/Protocol.inc';
require_once 'classes/Plugin.inc';
require_once 'classes/Util.inc';
require_once 'classes/Port.inc';
require_once 'classes/Reputation.inc';
require_once 'ossim_db.inc';


//IDM functions

// function getrepimg($prio,$act) {
    
    // if (empty($prio)) return "";
    // $lnk = "<img align='absmiddle' style='margin:0px 5px 0px 0px' border='0' alt='".trim($act)."' title='".trim($act)."'";
    // if ($prio<=2)     $lnk .= " src='../reputation/images/green.png'";
    // elseif ($prio<=6) $lnk .= " src='../reputation/images/yellow.png'";
    // else              $lnk .= " src='../reputation/images/red.png'";
    // return $lnk."/>";
// }

// function getrepbgcolor($prio) {
    
    // if (empty($prio)) return "transparent";
    // if ($prio<=2)     return "#fcefcc";
    // elseif ($prio<=6) return "#fde5d6";
    // else              return "#fccece";
// }

function get_idm_data($conn, $sid, $cid)
{
    $idm_data = array();
    
    $query = "SELECT rep_prio_src, rep_prio_dst, rep_act_src, rep_act_dst FROM idm_data WHERE sid=? AND cid=?;";
    
    $params = array($sid, $cid);
       
    $conn->SetFetchMode(ADODB_FETCH_ASSOC);
    
    if ($rs = & $conn->Execute($query, $params)) 
    {
       $idm_data[] = $rs->fields['rep_prio_src'];
       $idm_data[] = $rs->fields['rep_act_src'];
       $idm_data[] = $rs->fields['rep_prio_dst'];
       $idm_data[] = $rs->fields['rep_act_dst'];
    } 
       
    return $idm_data;
}

//Geolocation functions

function get_country($ip)
{
    include_once ('geoip.inc');
    
    $flag           = "";
    $gi             = geoip_open("/usr/share/geoip/GeoIP.dat", GEOIP_STANDARD);
    $s_country      = strtolower(geoip_country_code_by_addr($gi, $ip));
    $s_country_name = geoip_country_name_by_addr($gi, $ip);
    
                          
    if ( $s_country != "" && $s_country!= "local" )
    {
        $flag =  "<img src='../pixmaps/flags/$s_country.png' border='0' align='absmiddle' title='"._($s_country_name)."' style='margin: 0px 5px;'/>";
    }
    
    return $flag;
} 


header('Cache-Control: no-cache');

$db         = new ossim_db();
$conn       = $db->connect();
$snort_conn = $db->snort_connect();

//CONFIG
require_once ('ossim_conf.inc');
$conf       = $GLOBALS["CONF"];
$acid_table = ($conf->get_conf("copy_siem_events")=="no") ? "acid_event" : "acid_event_input";
$key_index  = ($conf->get_conf("copy_siem_events")=="no") ? "force index(IND)" : "";
$from_snort = true;
$max_rows   = 15;
$delay      = (preg_match("/MSIE /", $_SERVER['HTTP_USER_AGENT'])) ? 150 : 800; // do not modify

if (!isset($_SESSION['id']))
    $_SESSION['id'] = "0";
    
if ( !isset($row_num) ) 
{
    global $row_num;
    $row_num = 0;
}

if (!isset($_SESSION['plugins_to_show'])) 
    $_SESSION['plugins_to_show'] = array();
// responder js

if (GET('modo') == "responder") 
{
    // Timezone correction
    $tz  = Util::get_timezone();
	$tzc = Util::get_tzc($tz);	
    
    //Plugins
    $plugins = "";
    $plgs    = explode(",", GET('plugins'));
    foreach ($plgs as $encoded) 
        $plugins .= ",".base64_decode($encoded);
    
    $plugins = preg_replace("/^,/","",$plugins);
    
    //Risk
    $risk    = 0;
    
    //Filters
    
    $src_ip   = intval(GET('f_src_ip'));
    $dst_ip   = intval(GET('f_dst_ip'));
    $src_port = intval(GET('f_src_port'));
    $dst_port = intval(GET('f_dst_port'));
    $protocol = intval(GET('f_protocol'));
    
    if ($from_snort) 
    {
    	include_once("../panel/sensor_filter.php");
    	$where  = make_sensor_filter($conn,$acid_table);
    	$where .= make_asset_filter($conn,$acid_table);
        // Read from acid_event
        $where .= ( $plugins  != "") ? " AND $acid_table.sid in ($plugins) AND timestamp>".strtotime("-1 days") : "";
        $where .= ( $src_ip   != 0 )  ? " AND $acid_table.ip_src=$src_ip" : "";
        $where .= ( $dst_ip   != 0 )  ? " AND $acid_table.ip_dst=$dst_ip" : "";
        $where .= ( $src_port != 0 )  ? " AND $acid_table.layer4_sport=$src_port" : "";
        $where .= ( $dst_port != 0 )  ? " AND $acid_table.layer4_dport=$dst_port" : "";
        $where .= ( $protocol != 0 )  ? " AND $acid_table.ip_proto=$protocol" : "";
        
        // Limit in second select when sensor is specified (OJO)
        $key_index  = ($plugins != "") ? "" : str_replace("IND","timestamp",$key_index);

        $sql = "select $acid_table.plugin_id, $acid_table.plugin_sid, unix_timestamp(timestamp) as id, $acid_table.sid, $acid_table.cid, plugin_sid.name as plugin_sid_name, inet_ntoa(ip_src) as aux_src_ip, ip_src, inet_ntoa(ip_dst) as aux_dst_ip, ip_dst, convert_tz(timestamp,'+00:00','$tzc') as timestamp, ossim_risk_a as risk_a, ossim_risk_c as risk_c, (select substring_index(substring_index(hostname,':',1),'-',1) from sensor where sensor.sid = $acid_table.sid) as sensor, layer4_sport as src_port, layer4_dport as dst_port, ossim_priority as priority, ossim_reliability as reliability, ossim_asset_src as asset_src, ossim_asset_dst as asset_dst, ip_proto as protocol, (select interface from sensor where sensor.sid = $acid_table.sid) as interface from $acid_table $key_index LEFT JOIN ossim.plugin_sid ON plugin_sid.plugin_id=$acid_table.plugin_id AND plugin_sid.sid=$acid_table.plugin_sid WHERE 1=1 " . $where . " order by timestamp desc limit $max_rows";
		
		// QUERY DEBUG:
		
		if (!$rs = & $snort_conn->Execute($sql)) 
        {
            echo "// Query error: $sql\n// " . $snort_conn->ErrorMsg() . "\n";
            return;
        }
    } 
    else 
    {
        // read from event_tmp
        $sql = "SELECT *, inet_ntoa(src_ip) as aux_src_ip, inet_ntoa(dst_ip) as aux_dst_ip, '' as sid FROM event_tmp";
        if ($plugins != "" || $risk != "") $sql.= " WHERE 1";
        if ($risk != "") $sql.= " AND risk_a>$risk AND risk_c>$risk";
        if ($plugins != "") $sql.= " AND plugin_id in ($plugins)";
        $sql.= " ORDER BY timestamp DESC limit $max_rows";
        if (!$rs = & $conn->Execute($sql)) {
            echo "// Query error: $sql\n";
            return;
        }
    }
    $i = 0;
    echo "// $sql\n";
    
    while (!$rs->EOF) 
    {
        $risk = ($rs->fields["risk_a"] > $rs->fields["risk_c"]) ? $rs->fields["risk_a"] : $rs->fields["risk_c"];
        echo "edata[$i][0] = '" . $rs->fields["id"] . "';\n";
        echo "edata[$i][1] = '" . $rs->fields["timestamp"] . "';\n";
        echo "edata[$i][2] = '" . str_replace("'", "\'", $rs->fields["plugin_sid_name"]) . "';\n";
        if ($risk > 7) { $rst="style=\"padding:2px 5px 2px 5px;background-color:red;color:white\""; }
        elseif ($risk > 4) { $rst="style=\"padding:2px 5px 2px 5px;background-color:orange;color:black\""; }
        elseif ($risk > 2) { $rst="style=\"padding:2px 5px 2px 5px;background-color:green;color:white\""; }
        else { $rst="style=\"padding:2px 5px 2px 5px;color:black\""; }
        echo "edata[$i][3] = '<span $rst>" . $risk . "</span>';\n";
        echo "var pid = '" . $rs->fields["plugin_id"] . "'; if (pid == '0') pid = sids['id_" . $rs->fields["sid"] . "'];\n";
        echo "edata[$i][4]  = pid;\n";
        echo "edata[$i][5]  = '" . $rs->fields["plugin_sid"] . "';\n";
        echo "edata[$i][6]  = '" . $rs->fields["sensor"] . "';\n";
        echo "edata[$i][7]  = \"". $rs->fields["aux_src_ip"]."\";\n";
        echo "edata[$i][8]  = '" . $rs->fields["src_port"] . "';\n";
        echo "edata[$i][9]  = \"". $rs->fields["aux_dst_ip"]. "\";\n";
        echo "edata[$i][10] = '" . $rs->fields["dst_port"] . "';\n";
        // more detail
        echo "edata[$i][11] = '" . $rs->fields["priority"] . "';\n";
        echo "edata[$i][12] = '" . $rs->fields["reliability"] . "';\n";
        echo "edata[$i][13] = '" . $rs->fields["interface"] . "';\n";
        echo "edata[$i][14] = '" . $rs->fields["protocol"] . "';\n";
        echo "edata[$i][15] = '" . $rs->fields["asset_src"] . "';\n";
        echo "edata[$i][16] = '" . $rs->fields["asset_dst"] . "';\n";
        echo "edata[$i][17] = '" . $rs->fields["alarm"] . "';\n";
        echo "edata[$i][18] = '" . $rs->fields["sid"] . "';\n";
        echo "edata[$i][19] = '" . $rs->fields["cid"] . "';\n";
    
        if ( GET('idm') == 1 )
        {
            $idm_data = get_idm_data($snort_conn, $rs->fields["sid"], $rs->fields["cid"]);
            echo "edata[$i][20] = \"" . Reputation::getreponlyimg($idm_data[0],$idm_data[1]) . "\";\n";
            echo "edata[$i][21] = '"  . Reputation::getrepbgcolor($idm_data[0],2) . "';\n";
            echo "edata[$i][22] = \"" . Reputation::getreponlyimg($idm_data[2],$idm_data[3]) . "\";\n";
            echo "edata[$i][23] = '"  . Reputation::getrepbgcolor($idm_data[2],2) . "';\n";
        }
        else
        {
            echo "edata[$i][20] = '';\n";
            echo "edata[$i][21] = '';\n";
            echo "edata[$i][22] = '';\n";
            echo "edata[$i][23] = '';\n";
        }
        
        if ( GET('geoip'))
        {
            echo "edata[$i][24] = \"".get_country($rs->fields["ip_src"])."\";\n";
            echo "edata[$i][25] = \"".get_country($rs->fields["ip_dst"])."\";\n";
        }
        else
        {
            echo "edata[$i][24] = '';\n";
            echo "edata[$i][25] = '';\n";
        }
           
        $i++;
        
        $rs->MoveNext();
       
    }
  
    // fill rest
    while ($i < $max_rows) { 
        for ($k = 0; $k <= 25; $k++) echo "edata[$i][$k] = '';\n";
        $i++;
    }
    
    echo "draw_edata();\n";
} 
else 
{
    ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html>
    <head>
        <title><?php echo _("Event Tail Viewer")?></title>
        <link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css"/>
        <link rel="stylesheet" type="text/css" href="../style/jquery-ui-1.7.custom.css"/>
        <link rel="stylesheet" type="text/css" href="../style/style.css"/>
        <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
        <script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>
        <style type="text/css">
            
            body { font-family:arial; font-size:11px; }
            
            a { cursor:pointer;}
           
            #viewer_container {
                width: 95%;
                position: relative;
                margin: 0px auto 30px auto;
                height: auto;
            }
            
            #menu_viewer {
                width: 1020px;
                margin: auto;
                padding: 20px 0px 30px 10px;
            }
            #viewer {
                padding-top: 10px;
            }
            
            #viewer table {
                width: 1020px;
                margin: auto;
                border: none;
            }
            
            #viewer_tbody td {border: none;}
            
            #viewer th {
               font-weight: bold;
            }
                       
            .semiopaque { 
                opacity:0.9; 
                -moz-opacity:0.9; 
                -khtml-opacity:0.9; 
                filter:alpha(opacity=90); 
                background-color:#B5C3CF
            }
                        
            .little { font-size:8px }
           
                       
            #filter_panel{
                width: 1020px; 
                margin: auto;
                padding-top: 30px;
            }
            
            #filter_panel table{
                width: 100%;
                margin: auto;
            }
            
            .sep { 
                height: 5px;
                border: none;
            }
                        
            #filter_panel ._label{
                font-weight: bold;
                width: 125px;
                border: none;
                padding-left: 10px;
                text-align: left;
                height: 25px;
                vertical-align: middle;
            }
            
            #filter_panel .data{
                border: none;
                padding-left: 5px;
                text-align: left;
                height: 25px;
                vertical-align: middle;
            }
            
            #filter_panel input[type='text']{
                width: 220px;
                height: 17px;
            }
            
            #filter_chk{
                border: none;
                margin: 0px !important;
                padding: 0px;
            }
            
            #filter_chk ._label{
                padding-left: 7px !important;
            }
                        
            #cont_tplugin { display: none;}
            
            #table_plugin {
                width:98% !important; 
                height: 15px 
                padding-left: 10px;
                text-align: left;
                border: none;
            }
            
            #numeroDiv{
                position:absolute; 
                z-index:999; 
                left:0px; 
                top:0px; 
                visibility:hidden; 
                display:none;
            }
                                  
            
        </style>
    
        <script type='text/javascript'>
           
            var ajaxObj   = null;
            var pause     = false;
            var url       = '<?php echo $SCRIPT_NAME ?>?modo=responder';
            
            var idr = null
            
            var speed      = 2000;
            var fadescount = 0;
            
            function ticketon(i, pagex, pagey) { 
                                       
                if (document.getElementById) 
                {
                    pause = true;
                    
                    if ( $('#footer').text() != '<?php echo _("Stopped")?>.' ) 
                        $('#footer').html('<?php echo _("Paused") ?>.');
                        
                    // Generating detail info
                    var txt1 = '<table border="0" cellpadding="8" cellspacing="0" class="semiopaque">'
                                + '<tr><td class="nobborder" style="line-height:18px" nowrap="nowrap">'
                                         + 'Date: <b>' + edata[i][1] + '</b><br>'
                                         + 'Event: <b>' + edata[i][2] + '</b><br>'
                                         + 'Risk: <b>' + edata[i][3] + '</b><br>'
                    
                    var plugin = (plugins['id_'+edata[i][4]] != undefined) ? plugins['id_'+edata[i][4]] : edata[i][4];
                    txt1 = txt1 + 'Plugin: <b>' + plugin + '</b>' + ',&nbsp; Plugin_sid: <b>' + edata[i][5] + '</b><br>'
                    
                    var sensor = (sensors[edata[i][6]] != undefined) ? sensors[edata[i][6]] : ((hosts[edata[i][6]] != undefined) ? hosts[edata[i][6]] : edata[i][6]);
                    txt1 = txt1 + 'Sensor: <b>' + sensor + '</b> <i>[' + edata[i][6] + ']</i><br>'
                   
                    var host = (edata[i][7]=='0.0.0.0') ? 'N/A' : ((hosts[edata[i][7]] != undefined) ? hosts[edata[i][7]] : edata[i][7]);
                    var ip = edata[i][7];
                    if (host!='N/A' && edata[i][8]!="0") ip = ip + ":" + edata[i][8];
                    txt1 = txt1 + 'Source IP: <b>' + host + '</b> <i>[' + ip + ']</i><br>'
                    host = (edata[i][9]=='0.0.0.0') ? 'N/A' : ((hosts[edata[i][9]] != undefined) ? hosts[edata[i][9]] : edata[i][9]);
                    ip = edata[i][9];
                    
                    if (edata[i][10]!="0") ip = ip + ":" + edata[i][10];
                    txt1  = txt1 + 'Dest IP: <b>' + host + '</b> <i>[' + ip + ']</i><br>'
                    txt1  = txt1 + 'Priority: <b>' + edata[i][11] + '</b>' + ',&nbsp; Reliability: <b>' + edata[i][12] + '</b><br>'
                    proto = (protocols['proto_'+edata[i][14]] != undefined) ? protocols['proto_'+edata[i][14]] : edata[i][14]
                    txt1  = txt1 + 'Interface: <b>' + edata[i][13] + '</b>' + ',&nbsp; Protocol: <b>' + proto + '</b><br>'
                    txt1  = txt1 + 'Asset Src: <b>'+ edata[i][15] + '</b>' + ',&nbsp; Asset Dst: <b>' + edata[i][16] + '</b><br>'
                    
                    if (edata[i][17]!="") txt1 = txt1 + 'Alarm: <b>' + edata[i][17] + '</b><br>'
                    
                    $('#numeroDiv').html(txt1);
                                  
                    $('#numeroDiv').css('left', pagex);
                    $('#numeroDiv').css('top',  pagey);
                    
                    $('#numeroDiv').show();
                    $('#numeroDiv').css('visibility', 'visible');
                    
                }
            }

            function ticketoff() { 
               
                if ( $('#numeroDiv').length >= 1 ) 
                {
                    $('#numeroDiv').css('visibility', 'hidden');
                    $('#numeroDiv').hide();
                    $('#numeroDiv').html('');
                                   
                    if ($('#footer') != '<?php echo _("Stopped") ?>.') 
                        $('#footer').html('<?php echo _("Continue... waiting next refresh")?>');
                    
                    pause = false;
                }
                
            }

            // Combo filter functions
            function newcheckbox (elName,val) {
                var el = document.createElement('input');
                el.type = 'checkbox';
                el.name = elName;
                el.id = elName;
                el.value = val;
                el.className = 'little'
                el.addEventListener("click", reload, true); 
                return el;
            }
            
            function addtocombofilter (text,value) {
                var fo=document.getElementById('filter')
                if (notfound(fo,value)) {
                    fo.appendChild(newcheckbox(text,value))
                    fo.appendChild(document.createTextNode(text))
                    fo.appendChild(document.createElement('br'))
                }
            }
            
            function notfound (fo,value) {
                var inputs = fo.getElementsByTagName("input");
                for (var i=0; i<inputs.length; i++) 
                    if (inputs[i].getAttribute('type')=='checkbox') {
                        if (inputs[i]["value"]==value) {
                            return false
                        }
                    }
                return true
            }
            
            function getdatafromcombo(h) {
                var value = '';
                var myselect=document.getElementById(h)
                for (var i=0; i<myselect.options.length; i++) {
                        if (myselect.options[i].selected==true) {
                                value = value + ((value=='') ? '' : ',') + myselect.options[i].value
                        }
                }
                return value;
            }
            
            
            function getdatafromcheckbox() {
                var inp_chk = '';
                
                $('#table_plugin input[type="checkbox"]:checked').each(function(index) {
                    inp_chk += ( inp_chk == '' ) ? $(this).val() : ", "+$(this).val();
                });
                
                return inp_chk;
            }
            
			function ip2long (IP) {
			    var i = 0;
			    IP = IP.match(/^([1-9]\d*|0[0-7]*|0x[\da-f]+)(?:\.([1-9]\d*|0[0-7]*|0x[\da-f]+))?(?:\.([1-9]\d*|0[0-7]*|0x[\da-f]+))?(?:\.([1-9]\d*|0[0-7]*|0x[\da-f]+))?$/i); // Verify IP format.
			    if (!IP) {
			        return false; // Invalid format.
			    }
			    IP[0] = 0;
			    for (i = 1; i < 5; i += 1) {
			        IP[0] += !! ((IP[i] || '').length);
			        IP[i] = parseInt(IP[i]) || 0;
			    }
			    IP.push(256, 256, 256, 256);
			    // Recalculate overflow of last component supplied to make up for missing components.
			    IP[4 + IP[0]] *= Math.pow(256, 4 - IP[0]);
			    if (IP[1] >= IP[5] || IP[2] >= IP[6] || IP[3] >= IP[7] || IP[4] >= IP[8]) {
			        return false;
			    }
			    return IP[1] * (IP[0] === 1 || 16777216) + IP[2] * (IP[0] <= 2 || 65536) + IP[3] * (IP[0] <= 3 || 256) + IP[4] * 1;
			}        
        
            // Hosts and Sensors to direct-name-resolv
            <?php
            $sensors = $hosts = array();
                            
            $sensors = Sensor::get_list($conn);
            $hosts   = Host::get_list($conn, "");
            ?>
            
                       
            var sensors = new Array(<?php echo count($sensors) ?>);
            var hosts   = new Array(<?php echo count($hosts) ?>);
            
            <?php
            foreach($sensors as $sensor) 
            {
                $sensor_ip   = $sensor->get_ip();
                $sensor_name = $sensor->get_name();
                if ( Session::sensorAllowed($sensor_ip) )
                    echo "sensors['$sensor_ip'] = '$sensor_name';\n";
            }
            
            foreach($hosts as $host)
            {
                $_ip       = $host->get_ip();
                $_hostname = $host->get_hostname();
                
                if ( Session::hostAllowed($conn, $_ip) )
                {
                    echo "hosts['$_ip'] = '$_hostname';\n";
                     
                    //Load available hosts (Autocompleted)
                    if ( $_hostname != $_ip ) 
                        $h_list .= '{ txt:"'.$_hostname.' [Host:'.$_ip.']", id: "'.Host::ip2ulong($_ip).'" },';
                    else 
                        $h_list .= '{ txt:"'.$_ip.'", id: "'.Host::ip2ulong($_ip).'" },';
                }
            }
           
        
            // Protocol list

            if ($protocol_list = Protocol::get_list($conn)) 
            {
                echo "var protocols = new Array(" . count($protocol_list) . ")\n";
                
                foreach($protocol_list as $proto) 
                {
                    //$_SESSION[$id] = $plugin->get_name();
                    echo "protocols['proto_" . $proto->get_id() . "'] = '" . $proto->get_name() . "'\n";
                    
                    //Load available protocols (Autocompleted)
                    $p_list .= '{ txt: "Protocol:'.$proto->get_name().'", id: "'.$proto->get_id().'" },';
                }
            }
            
            //Port list (Autocompleted)
                
            if ($port_list = Port::get_list($conn,"WHERE protocol_name='tcp'") )
            {
                foreach($port_list as $port) 
                    $prt_list .= '{ txt:"'.$port->get_port_number()." - ".$port->get_service().'", id: "'.$port->get_port_number().'" },';
            }
               
           
            // Plugin list

            if ($plugin_list = Plugin::get_list($conn, "")) 
            {
                echo "var plugins = new Array(" . count($plugin_list) . ")\n";
                foreach($plugin_list as $plugin) 
                {
                    //$_SESSION[$id] = $plugin->get_name();
                    echo "plugins['id_" . $plugin->get_id() . "'] = '" . $plugin->get_name() . "';\n";
                    echo "plugins['id_" . $plugin->get_name() . "'] = '" . $plugin->get_name() . "';\n";
                }
            }

            // Sids

            $sids = array();
            //if ($rs = & $snort_conn->Execute("select sid,hostname from sensor")) {
            if ($rs = & $snort_conn->Execute("select distinct sensor.sid,sensor.hostname from sensor,acid_event where acid_event.sid=sensor.sid and acid_event.timestamp>".strtotime("-1 days"))) 
            {
               
                while (!$rs->EOF) 
                {
                    preg_match("/(.*)(\d+\.\d+\.\d+\.\d+)\-(.*)/", $rs->fields["hostname"], $match);
                    
                    $plug_id = $match[3];
                    if ( $plug_id == "") $plug_id = "snort";
                        $sids[$plug_id][] = $rs->fields["sid"]; // extract sid=>plugins
                    
                    $rs->MoveNext();
                }                
                
                echo "var sids = new Array(" . count($sids) . ")\n";
                foreach($sids as $key => $value) {
                    foreach($value as $ss) echo "sids['id_" . $ss . "'] = '$key';\n";
                }
            }
            
            ?>
            
           
            function create_script(url) {
                // make script element
                
                var ajaxObject     = document.createElement('script');
                ajaxObject.src     = url;
                ajaxObject.type    = "text/javascript";
                ajaxObject.charset = "utf-8";
                try {
                    return ajaxObject;
                } finally {
                    ajaxObject = null;
                }
            }
            
            function refresh() {
                // ajax responder
                if ( pause == false ) 
                {
                    $('#footer').html('<?php echo _("Refreshing") ?>...')
                    // load extra parameters from select filter
                    var idm    = ( $('#idm:checked').length > 0 ) ? 1 : 0;
                    var geoip  = ( $('#geoip:checked').length > 0 ) ? 1 : 0;
                    var urlr = url+"&idm="+ idm +"&geoip="+ geoip +"&" + $('#form_filters').serialize();
                    var idf  = getdatafromcheckbox();
                           
                    if ( idf != '') 
                        urlr = urlr + '&plugins=' + idf
                    
                    $.ajax({
                        type: "GET",
                        url: urlr,
                        success: function(msg){
                            eval(msg);
                        }
                    });
                }
            }

            var edata = new Array(<?php echo $max_rows ?>);
            var eprev = new Array(<?php echo $max_rows ?>);
            var efade = new Array(<?php echo $max_rows ?>);


            <?php
            for ($i = 0; $i < $max_rows; $i++) 
            { 
                ?>
                edata[<?php echo $i?>] = new Array(24);
                eprev[<?php echo $i?>] = 0;
                efade[<?php echo $i?>] = 0;
                <?php
            } 

            ?>
                   

            function draw_edata() {
                if (pause == false) 
                {
                    fadescount = 0;
                    
                    for (var i=0; i<<?php echo $max_rows?>; i++) 
                    {
                        // Calculate different rows
                        efade[i] = ( eprev[i] == edata[i][0] ) ? 0 : 1;
                        
                        if ( efade[i] == 1 ) 
                        {
                            $('#row'+i+' td').css({ 'opacity':'0', '-moz-opacity':'0', '-khtml-opacity':'0', 'filter':'alpha(opacity=0)'}); 
                            fadescount++;
                        }
                                                
                        eprev[i] = edata[i][0];
                       
                        
                        // change content
                        $('#date'+i).html(edata[i][1]);
                        urle = "<a class='tooltip' id='link_"+i+"' href=\"../forensics/base_qry_alert.php?submit=%230-%28"+edata[i][18]+"-"+edata[i][19]+"%29\" style='text-decoration:underline'>" + edata[i][2] + "</a>"
                        $('#event'+i).html(urle);
                        $('#trevent'+i).html(edata[i][2]);
                        $('#risk'+i).html(edata[i][3]);
                        
                        
                        plugin = (plugins['id_'+edata[i][4]] != undefined) ? plugins['id_'+edata[i][4]] : edata[i][4];
                        $('#plugin_id'+i).html(plugin);
                        
                        <?php
                        if ( !$from_snort ) 
                            echo "addtocombofilter (plugin,edata[i][4]);\n"; ?>
                        
                        sensor = (sensors[edata[i][6]] != undefined) ? sensors[edata[i][6]] : ((hosts[edata[i][6]] != undefined) ? hosts[edata[i][6]] : edata[i][6]);
                        $('#sensor'+i).html(sensor);
                        
                        //Source IP 
                        
                        var host = (edata[i][7]=='0.0.0.0') ? 'N/A' : ((hosts[edata[i][7]] != undefined) ? hosts[edata[i][7]] : edata[i][7]);
                        
                        if (host!='N/A' && edata[i][8]!="0" && edata[i][8]!="") 
                            host = host + ":" + edata[i][8];
                        
                        host = edata[i][20] + host + edata[i][24];
                        
                        $('#srcip'+i).css('background-color', edata[i][21]);
                       
                        $('#srcip'+i).html(host);
                                            
                        //Destination IP
                        host = (edata[i][9]=='0.0.0.0') ? 'N/A' : ((hosts[edata[i][9]] != undefined) ? hosts[edata[i][9]] : edata[i][9]);
                        if (edata[i][10]!="0" && edata[i][10]!="") host = host + ":" + edata[i][10];
                        
                        host = edata[i][22] + host + edata[i][25];
                        
                        $('#dstip'+i).css('background-color', edata[i][23]);
                        
                        $('#dstip'+i).html(host);
                    }
                    
                    $('.tooltip').bind('mouseover', function(event) { 
                        var id = $(this).attr('id').replace("link_", "") 
                        ticketon(id, event.pageX, event.pageY);
                    });
                    
                    $('.tooltip').bind('mouseout', function() { 
                        ticketoff();
                    });
                    
                            
                    //Effects
                    for (var i=0;i<<?php echo $max_rows?>;i++) 
                    {
                        if (efade[i] == 1)
                            $('#row'+i+' td').fadeTo(1000, 1, function() { });
                            
                        $('#row'+i+' td').css('border-bottom', 'solid 1px #CBCBCB');
                    }
                    
                                   
                    $('#footer').html('<?php echo _("Done") ?>. [<b>' + fadescount + '</b> <?php echo _("new rows") ?>]');
                    
                }
            }

           
            function play() { 
                refresh();
                
                if (idr == null) 
                    idr = setInterval("refresh()",speed);
            }

            function stop() { clearInterval(idr); idr = null; $('#footer').html('<?php echo _("Stopped") ?>.') }

            function reload() { stop(); play() }

            function pausecontinue() { 
                if ( idr==null ) 
                    play(); 
                else 
                    stop(); 
            }

            function toogle_pfilter(){
                
                if ( $('#pf_name').hasClass('show') )
                {
                    $('#pf_name').removeClass('show');
                    $('#pf_name').addClass('hide');
                    
                    $('#p_filter').html("<span>[<?php echo _("Show Plugin filter")?>]</span>");
                    
                    $("#cont_tplugin").slideUp();
                }
                else
                {
                    $('#pf_name').removeClass('hide');
                    $('#pf_name').addClass('show');
                    
                    $('#p_filter').html("<span>[<?php echo _("Hide Plugin filter")?>]</span>");
                            
                    $("#cont_tplugin").slideDown();
                }
            }


            function clean_filter_data(id)
            {
                var h_id = 'f_'+id;
                
                if ( $('#'+id).val() == '' )
                    $('#'+h_id).val('');
            }           

            $(document).ready(function(){
                   
                // Autocomplete hosts
                var hosts_ac     = [ <?php echo  preg_replace("/,$/", "", $h_list) ?> ];
                var protocols_ac = [ <?php echo  preg_replace("/,$/", "", $p_list) ?> ];
                var ports_ac     = [ <?php echo  preg_replace("/,$/", "", $prt_list) ?>];
                
                if (hosts_ac.length > 0)
                {
                    $("#src_ip").autocomplete(hosts_ac, {
                        minChars: 0,
                        width: 220,
                        max: 100,
                        matchContains: true,
                        autoFill: false,
                        formatItem: function(row, i, max) {
                            return row.txt;
                        }
                    }).result(function(event, item) { $('#f_src_ip').val(item.id); });
                    
                    $("#dst_ip").autocomplete(hosts_ac, {
                        minChars: 0,
                        width: 220,
                        max: 100,
                        matchContains: true,
                        autoFill: false,
                        formatItem: function(row, i, max) {
                            return row.txt;
                        }
                    }).result(function(event, item) { $('#f_dst_ip').val(item.id); });
                }
                
                $("#src_ip").change(function() {
                	
                    if ( $('#f_src_ip').val() == '' ) 
                    {
                        var ip_num = ip2long($("#src_ip").val());
                        if ( ip_num == false )
                        {
                            $('#f_src_ip').val('');
                            $('#src_ip').val('');
                        }
                        else
                            $('#f_src_ip').val(ip_num);
                    }
                });

                $("#dst_ip").change(function() {
                	if ( $('#f_dst_ip').val() == '' ) 
                    {
                        var ip_num = ip2long($("#dst_ip").val());
                        if ( ip_num == false )
                        {
                            $('#f_dst_ip').val('');
                            $('#dst_ip').val('');
                        }
                        else
                            $('#f_dst_ip').val(ip_num);
                    }
                });
                                
                if ( protocols_ac.length > 0 )
                {
                    $("#protocol").autocomplete(protocols_ac, {
                        minChars: 0,
                        width: 220,
                        max: 100,
                        matchContains: true,
                        autoFill: false,
                        formatItem: function(row, i, max) {
                            return row.txt;
                        }
                    }).result(function(event, item) { $('#f_protocol').val(item.id); });
                }    

                $("#protocol").change(function() {
                	if ($('#f_protocol').val()=='') $('#f_protocol').val($("#protocol").val());
                });
                                
                if ( ports_ac.length > 0 )
                {
                    $("#src_port").autocomplete(ports_ac, {
                        minChars: 0,
                        width: 220,
                        matchContains: "word",
                        autoFill: false,
                        formatItem: function(row, i, max) {
                            return row.txt;
                        }
                    }).result(function(event, item) { $('#f_src_port').val(item.id); });
                    
                    $("#dst_port").autocomplete(ports_ac, {
                        minChars: 0,
                        width: 220,
                        matchContains: "word",
                        autoFill: false,
                        formatItem: function(row, i, max) {
                            return row.txt;
                        }
                    }).result(function(event, item) { $('#f_dst_port').val(item.id); });
                }            
                
                $("#src_port").change(function() {
                	if ($('#f_src_port').val()=='') 
                        $('#f_src_port').val($("#src_port").val());
                });

                $("#dst_port").change(function() {
                	if ($('#f_dst_port').val()=='') 
                        $('#f_dst_port').val($("#dst_port").val());
                });
                                                         
                $('.inp_filter').bind('blur', function() { clean_filter_data($(this).attr('id')); });
                
                $('.clean').bind('click', function() { 
                    var id = $(this).attr('id').replace("clean_", ""); 
                    
                    $('#'+id).val(''); 
                    clean_filter_data(id) 
                });
                
                $('#p_filter').bind('click', function() {
                   toogle_pfilter();
                });
                
               
                play();

            });

        </script>
    </head>

    <body>
    <?php
        if (GET('withoutmenu')!="1") 
            include ("../hmenu.php"); 
    ?>
    
    
        <div id='viewer_container'>
            
            <div id='menu_viewer'>
                <form name="controls" onsubmit="return false">
                    <table border='0' cellpadding='0' cellspacing='0' class="nobborder" width="100%" align="left">
                        <tr>
                            <td class="nobborder" valign='middle' width='100px'>
                                <input type='button' onclick="pausecontinue()" value="<?php echo _("Pause");?>" class="button"/>
                            </td>
                                      
                            <td id="footer" class="nobborder" valign='middle'></td>
                        </tr>     
                    </table>
                </form>
            </div>
            
            <div id='viewer'>
                <table cellpadding='0' cellspacing='1'>
                    <thead>
                    <tr height="22">
                        <th width="140"><?php echo _("Date"); ?></th>
                        <th width="290" class='left'><?php echo _("Event Name"); ?></th>
                        <th width="40"><?php echo _("Risk"); ?></th>
                        <th width="150"><?php echo _("Generator"); ?></th>
                        <th width="100"><?php echo _("Sensor"); ?></th>
                        <th width="140"><?php echo _("Source IP"); ?></th>
                        <th width="140"><?php echo _("Dest IP"); ?></th>
                    </tr>
                    </thead>
                    
                    <tbody id='viewer_tbody'>
                        <?php
                        for ($i = 0; $i < $max_rows; $i++) 
                        { 
                            ?>
                            <tr height="22" id='row<?php echo $i?>'>
                                <td width="140" id="date<?php echo $i?>"></td>
                                <td width="290" id="event<?php echo $i?>" class='left' style="color:blue;"></td>
                                <td width="40"  id="risk<?php echo $i?>"></td>
                                <td width="150" id="plugin_id<?php echo $i?>"></td>
                                <td width="100" id="sensor<?php echo $i?>"></td>
                                <td width="140" id="srcip<?php echo $i?>"></td>
                                <td width="140" id="dstip<?php echo $i?>"></td>
                            </tr>
                            <tr height='1'></tr>
                            <?php
                        } 
                        ?>
                    </tbody>
                </table>
            </div>    
                                     
            <div id='filter_panel'>
                <table>
                    <tr>
                        <th colspan='4'><?php echo _("Filters")?></th>
                    </tr>
                    
                    <tr>
                        <td class='_label'><?php echo _("Source IP")?>:</td>
                        <td class='data'>
                            <input type='text' class='inp_filter' name='scr_ip' id='src_ip'/>
                            <span style='margin-left: 3px;'><a class='clean' id='clean_src_ip' title='<?php echo _("Clean filter")?>'><img src='../pixmaps/delete.gif' align='absmiddle'/></a></span>
                        </td>
                        <td class='_label'><?php echo _("Destination IP")?>:</td>
                        <td class='data'>
                            <input type='text' class='inp_filter' name='dst_ip' id='dst_ip'/>
                            <span style='margin-left: 3px;'><a class='clean' id='clean_dst_ip' title='<?php echo _("Clean filter")?>'><img src='../pixmaps/delete.gif' align='absmiddle'/></a></span>
                        </td>
                    </tr>
                                
                    <tr>
                        <td class='_label'><?php echo _("Source Port")?>:</td>
                        <td class='data'>
                            <input type='text' class='inp_filter' name='scr_port' id='src_port'/>
                            <span style='margin-left: 3px;'><a class='clean' id='clean_src_port' title='<?php echo _("Clean filter")?>'><img src='../pixmaps/delete.gif' align='absmiddle'/></a></span>
                        </td>
                        <td class='_label'><?php echo _("Destination Port")?>:</td>
                        <td class='data'>
                            <input type='text' class='inp_filter' name='dst_port' id='dst_port'/>
                            <span style='margin-left: 3px;'><a class='clean' id='clean_dst_port' title='<?php echo _("Clean filter")?>'><img src='../pixmaps/delete.gif' align='absmiddle'/></a></span>
                        </td>
                    </tr>
                    
                    <tr>
                        <td class='_label' valign='top'><?php echo _("Protocol")?>:</td>
                        <td class='data'>
                            <input type='text' class='inp_filter' name='protocol' id='protocol'/>
                            <span style='margin-left: 3px;'><a class='clean' id='clean_protocol' title='<?php echo _("Clean filter")?>'><img src='../pixmaps/delete.gif' align='absmiddle'/></a></span>
                        </td>
                        <td class='noborder' colspan='2'>
                            <table id='filter_chk'>
                                <tr>
                                    <?php 
                                    $Reputation = new Reputation();	
                                    if ( $Reputation->existReputation() )
                                    {
                                        ?>
                                        <td class='_label'><span><?php echo _("With Reputation info");?>:</span></td>
                                        <td class='data' style='width:62px !important;'><input type='checkbox' name='idm' id='idm' checked='checked' value="1"/></td>
                                        <?php 
                                    }
                                    ?>
                                    <td class='_label'><span><?php echo _("With Geolocation info");?>:</span></td>
                                    <td class='data'><input type='checkbox' name='geoip' id='geoip' checked='checked' value="1"/></td>
                                </tr>
                            </table>
                        </td>
                        
                    </tr>
                                                                  
                    <?php 
                   
                               
                    if ( $from_snort ) 
                    {
                        ksort($sids);
                        $cont      = 0;
                        $sids_cols = 6;
                        $num_sids  = count($sids);
                        $sids_rows = ceil($num_sids/$sids_cols);
                        $sids_keys = array_keys($sids);
                                                       
                        ?>
                        
                        <tr>
                            <td colspan='4' class='_label'>
                                <div>
                                    <span id='pf_name' class='hide'><?php echo _("Plugins")?>: 
                                        <span id='cont_pfilter' style='margin-left: 5px'>
                                            <a id='p_filter'><span>[<?php echo _("Show Plugin filter")?>]</span></a>
                                        </span>
                                    </span>
                                </div>
                            </td>
                        </tr>
                        
                        <tr>
                            <td class='noborder' colspan='4'>
                                <div id='cont_tplugin'>
                                    <table id='table_plugin'>
                                        <?php
                                        for($i=0; $i<$sids_rows; $i++)
                                        {
                                            echo "<tr>";
                                                for ($j=0; $j<$sids_cols; $j++)
                                                {
                                                    if ( $cont < $num_sids)
                                                    {
                                                        $plugin_key = $sids_keys[$cont];
                                                        $val        = implode(",", $sids[$plugin_key]);
                                                        
                                                        echo "<td class='noborder left'><input type='checkbox' class='little' value='".base64_encode($val)."'/>$plugin_key</td>";
                                                       
                                                        $cont++;
                                                    }
                                                    else
                                                        echo "<td class='noborder'>&nbsp;</td>";
                                                }
                                            echo "</tr>";
                                        }
                                        
                                        ?>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </table>
                
                <form id='form_filters' name='form_filters'>
                    <input type='hidden' name='f_src_ip' id='f_src_ip'/>
                    <input type='hidden' name='f_dst_ip' id='f_dst_ip'/>
                    <input type='hidden' name='f_src_port' id='f_src_port'/>
                    <input type='hidden' name='f_dst_port' id='f_dst_port'/>
                    <input type='hidden' name='f_protocol' id='f_protocol'/>
                </form>
                        
            </div>
        </div>
        
        <div id="numeroDiv"></div>
     
    </body>
    </html>
    <?php
} 

$db->close($conn);

?>
