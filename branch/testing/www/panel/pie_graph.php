<?php
require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
require_once ('classes/Util.inc');
require_once ('sensor_filter.php');
require_once ('ossim_db.inc');

$m_perms  = array ("MenuControlPanel", "MenuEvents", "MenuEvents");
$sm_perms = array ("ControlPanelExecutive", "EventsHids", "EventsHidsConfig");

if ( Session::menu_perms($m_perms, $sm_perms) == false )
{
	if ( Session::menu_perms($m_perms[0], $sm_perms[0]) == false )	
		Session::unallowed_section(null, 'noback',$m_perms[0], $sm_perms[0]);
	else
		Session::unallowed_section(null, 'noback',$m_perms[1], $sm_perms[1]);
}

$nodata_text = _("No events found");

$db   = new ossim_db();
$conn = $db->connect();
session_write_close();

$data   = "";
$urls   = "";
$colors = '"#E9967A","#9BC3CF"';

//$range         =  604800; // Week
$range = 24*60*60*6;
$h             = 250; // Graph Height
$forensic_link = "forensics/base_qry_main.php?clear_allcriteria=1&time_range=week&time[0][0]=+&time[0][1]=>%3D&time[0][2]=".gmdate("m",$timetz-$range)."&time[0][3]=".gmdate("d",$timetz-$range)."&time[0][4]=".gmdate("Y",$timetz-$range)."&time[0][5]=&time[0][6]=&time[0][7]=&time[0][8]=+&time[0][9]=+&submit=Query+DB&num_result_rows=-1&time_cnt=1&sort_order=time_d&hmenu=Forensics&smenu=Forensics";
$tz = Util::get_timezone();

$sensor_where  = make_sensor_filter($conn,"a");
$query     = "SELECT count(a.sid) as num_events,c.cat_id,c.id,c.name FROM snort.acid_event a,ossim.plugin_sid p,ossim.subcategory c WHERE c.id=p.subcategory_id AND p.plugin_id=a.plugin_id AND p.sid=a.plugin_sid AND a.timestamp BETWEEN '".gmdate("Y-m-d H:i:s",strtotime(date("Y-m-d 00:00:00"))-$range+(-$tz))."' AND '".gmdate("Y-m-d 23:59:59")."' $sensor_where TAXONOMY group by c.id,c.name order by num_events desc LIMIT 10";
/*
ac_ deprecated
if ($sensor_where!="")
	$query     = "SELECT count(a.sid) as num_events,c.cat_id,c.id,c.name FROM snort.acid_event a,ossim.plugin_sid p,ossim.subcategory c WHERE c.id=p.subcategory_id AND p.plugin_id=a.plugin_id AND p.sid=a.plugin_sid AND a.timestamp BETWEEN '".gmdate("Y-m-d 00:00:00",gmdate("U")-$range+(-$tz))."' AND '".gmdate("Y-m-d 23:59:59")."' $sensor_where TAXONOMY group by c.id,c.name order by num_events desc LIMIT 10";
else
	$query     = "SELECT sum(sig_cnt) as num_events,c.cat_id,c.id,c.name FROM snort.ac_alerts_signature a,ossim.plugin_sid p,ossim.subcategory c WHERE c.id=p.subcategory_id AND p.plugin_id=a.plugin_id AND p.sid=a.plugin_sid AND a.day BETWEEN '".gmdate("Y-m-d",gmdate("U")-$range+(-$tz))."' AND '".gmdate("Y-m-d")."' TAXONOMY group by c.id,c.name order by num_events desc LIMIT 10";
*/
switch(GET("type")) {

	// Top 10 Events by Product Type - Last Week
	case "source_type":
		$types = $ac = array();
		
		if (!$rp = & $conn->Execute("SELECT id,source_type FROM ossim.plugin")) {
		    print $conn->ErrorMsg();
		} else {
		    while (!$rp->EOF) {
		    	if ($rp->fields["source_type"]=="") $rp->fields["source_type"] = _("Unknown type");
		        $types[$rp->fields["id"]] = $rp->fields["source_type"];
		        $rp->MoveNext();
		    }
		}
		
		if ($sensor_where!="") 
			$sqlgraph = "SELECT count(a.sid) as num_events,a.plugin_id FROM snort.acid_event a where a.timestamp BETWEEN '".gmdate("Y-m-d 00:00:00",gmdate("U")-$range)."' AND '".gmdate("Y-m-d 23:59:59")."' $sensor_where group by a.plugin_id";
		else
			$sqlgraph = "SELECT sum(sig_cnt) as num_events,plugin_id FROM snort.ac_alerts_signature where day BETWEEN '".gmdate("Y-m-d",gmdate("U")-$range)."' AND '".gmdate("Y-m-d")."' group by plugin_id";
		if (!$rg = & $conn->Execute($sqlgraph)) {
		    print $conn->ErrorMsg();
		} else {
		    while (!$rg->EOF) {
		    	$type = ($types[$rg->fields["plugin_id"]]!="") ? $types[$rg->fields["plugin_id"]] : _("Unknown type");
		    	$ac[$type] += $rg->fields["num_events"];
		        $rg->MoveNext();
		    }
		}
		
		arsort($ac); $ac=array_slice($ac, 0, 10);
		foreach ($ac as $st => $events) {
			$data .= "['<a target=\"topmenu\" class=\"no_text_decoration\" href=\"../top.php?hmenu=".md5("Analysis")."&smenu=".md5("Forensics")."&url=".urlencode($forensic_link."&sourcetype=".urlencode($st))."\">".$st."</a>',".$events."],";
			$urls .= "'../top.php?hmenu=".md5("Analysis")."&smenu=".md5("Forensics")."&url=".urlencode($forensic_link."&sourcetype=".urlencode($st))."',";
		}
		$colors = '"#D1E8EF","#ADD8E6","#6FE7FF","#00BFFF","#4169E1","#4682B4","#0000CD","#483D8B","#5355DF","#00008B"';
		break;
		
		
	// Top 10 Event Categories - Last Week
	case "category":
		if ($sensor_where!="")
			$sqlgraph = "SELECT count(a.sid) as num_events,p.category_id,c.name FROM snort.acid_event a,ossim.plugin_sid p,ossim.category c WHERE c.id=p.category_id AND p.plugin_id=a.plugin_id AND p.sid=a.plugin_sid AND a.timestamp BETWEEN '".gmdate("Y-m-d 00:00:00",gmdate("U")-$range)."' AND '".gmdate("Y-m-d 23:59:59")."' $sensor_where group by p.category_id order by num_events desc LIMIT 10";
		else
			$sqlgraph = "SELECT sum(a.sig_cnt) as num_events,p.category_id,c.name FROM snort.ac_alerts_signature a,ossim.plugin_sid p,ossim.category c WHERE c.id=p.category_id AND p.plugin_id=a.plugin_id AND p.sid=a.plugin_sid AND a.day BETWEEN '".gmdate("Y-m-d",gmdate("U")-$range)."' AND '".gmdate("Y-m-d")."' group by p.category_id order by num_events desc LIMIT 10";
			
		if (!$rg = & $conn->Execute($sqlgraph)) {
		    print $conn->ErrorMsg();
		} else {
		    while (!$rg->EOF) {
		    	if ($rg->fields["name"]=="") $rg->fields["name"] = _("Unknown category");
		        $data .= "['<a target=\"topmenu\" class=\"no_text_decoration\" href=\"../top.php?hmenu=".md5("Analysis")."&smenu=".md5("Forensics")."&url=".urlencode($forensic_link."&category%5B1%5D=&category%5B0%5D=".$rg->fields["category_id"])."\">".str_replace("_"," ",$rg->fields["name"])."</a>',".$rg->fields["num_events"]."],";
                $urls .= "'../top.php?hmenu=".md5("Analysis")."&smenu=".md5("Forensics")."&url=".urlencode($forensic_link."&category%5B1%5D=&category%5B0%5D=".$rg->fields["category_id"])."',";
		        $rg->MoveNext();
		    }
		}
		$colors = '"#FFD0BF","#FFBFBF","#FF9F9F","#F08080","#FF6347","#FF4500","#FF0000","#DC143C","#B22222","#7F1717"';
		break;
				
	// Top 10 Ossec Categories - Last Week
	case "hids":
		require_once("classes/Plugin.inc");
		$oss_p_id_name = Plugin::get_id_and_name($conn, "WHERE name LIKE 'ossec%'");
		$plugins = implode(",",array_flip ($oss_p_id_name));
		if ($sensor_where!="")
			$sqlgraph = "SELECT count(a.sid) as num_events,p.id,p.name FROM snort.acid_event a,ossim.plugin p WHERE p.id=a.plugin_id AND a.timestamp BETWEEN '".gmdate("Y-m-d 00:00:00",gmdate("U")-$range)."' AND '".gmdate("Y-m-d 23:59:59")."' AND a.plugin_id in ($plugins) $sensor_where group by p.name order by num_events desc LIMIT 8";
		else
			$sqlgraph = "SELECT sum(sig_cnt) as num_events,p.id,p.name FROM snort.ac_alerts_signature a,ossim.plugin p WHERE p.id=a.plugin_id AND a.day BETWEEN '".gmdate("Y-m-d",gmdate("U")-$range)."' AND '".gmdate("Y-m-d")."' AND a.plugin_id in ($plugins) group by p.name order by num_events desc LIMIT 8";
		if (!$rg = & $conn->Execute($sqlgraph)) {
		    print $conn->ErrorMsg();
		} else {
		    while (!$rg->EOF) {
		    	$name = ucwords(str_replace("_"," ",str_replace("ossec-","ossec: ",$rg->fields["name"])));
		        $data .= "['<a target=\"topmenu\" class=\"no_text_decoration\" href=\"../top.php?hmenu=".md5("Analysis")."&smenu=".md5("Forensics")."&url=".urlencode($forensic_link."&plugin=".$rg->fields["id"])."\">".$name."</a>',".$rg->fields["num_events"]."],";
                $urls .= "'../top.php?hmenu=".md5("Analysis")."&smenu=".md5("Forensics")."&url=".urlencode($forensic_link."&plugin=".$rg->fields["id"])."',";
		        $rg->MoveNext();
		    }
		}
		$colors = '"#FFD0BF","#FFBFBF","#FF9F9F","#F08080","#FF6347","#FF4500","#FF0000","#DC143C","#B22222","#7F1717"';
		$h = 220;
		break;
						
	// Authentication Login vs Failed Login Events - Last Week
	case "login":
		$taxonomy = make_where($conn,array("Authentication" => array("Login","Failed")));
		$sqlgraph = str_replace("TAXONOMY",$taxonomy,$query);
		//print_r($sqlgraph);
		if (!$rg = & $conn->Execute($sqlgraph)) 
		    print $conn->ErrorMsg();
		else 
		{
		    while (!$rg->EOF) {
		    	if ($rg->fields["name"]=="") $rg->fields["name"] = _("Unknown category");
		        $data .= "['<a target=\"topmenu\" class=\"no_text_decoration\" href=\"../top.php?hmenu=".md5("Analysis")."&smenu=".md5("Forensics")."&url=".urlencode($forensic_link."&category%5B0%5D=".$rg->fields["cat_id"]."&category%5B1%5D=".$rg->fields["id"])."\">".str_replace("_"," ",$rg->fields["name"])."</a>',".$rg->fields["num_events"]."],";
                $urls .= "'../top.php?hmenu=".md5("Analysis")."&smenu=".md5("Forensics")."&url=".urlencode($forensic_link."&category%5B0%5D=".$rg->fields["cat_id"]."&category%5B1%5D=".$rg->fields["id"])."',";
		        $rg->MoveNext();
		    }
		}
		$colors = '"#E9967A","#9BC3CF"';
		break;
		
	// Malware - Last Week
	case "malware":
		$taxonomy = make_where($conn,array("Malware" => array("Spyware","Adware","Fake_Antivirus","KeyLogger","Trojan","Virus","Worm","Generic","Backdoor","Virus_Detected")));
		$sqlgraph = str_replace("TAXONOMY",$taxonomy,$query);
		//print_r($sqlgraph);
		if (!$rg = & $conn->Execute($sqlgraph)) 
		    print $conn->ErrorMsg();
		else
		{
		    while (!$rg->EOF) {
		    	if ($rg->fields["name"]=="") $rg->fields["name"] = _("Unknown category");
		        $data .= "['<a target=\"topmenu\" class=\"no_text_decoration\" href=\"../top.php?hmenu=".md5("Analysis")."&smenu=".md5("Forensics")."&url=".urlencode($forensic_link."&category%5B0%5D=".$rg->fields["cat_id"]."&category%5B1%5D=".$rg->fields["id"])."\">".str_replace("_"," ",$rg->fields["name"])."</a>',".$rg->fields["num_events"]."],";
                $urls .= "'../top.php?hmenu=".md5("Analysis")."&smenu=".md5("Forensics")."&url=".urlencode($forensic_link."&category%5B0%5D=".$rg->fields["cat_id"]."&category%5B1%5D=".$rg->fields["id"])."',";
		        $rg->MoveNext();
		    }
		}
		$colors = '"#FFD0BF","#FFBFBF","#FF9F9F","#F08080","#FF6347","#FF4500","#FF0000","#DC143C","#B22222","#7F1717"';
		break;        

    // Firewall permit vs deny - Last Week
	case "firewall":
		$taxonomy = make_where($conn,array("Access" => array("Firewall_Permit","Firewall_Deny","ACL_Permit","ACL_Deny")));
		$sqlgraph = str_replace("TAXONOMY",$taxonomy,$query);
		//print_r($sqlgraph);
		if (!$rg = & $conn->Execute($sqlgraph)) 
		    print $conn->ErrorMsg();
		else 
		{
		    while (!$rg->EOF) {
		    	if ($rg->fields["name"]=="") $rg->fields["name"] = _("Unknown category");
		        $data .= "['<a target=\"topmenu\" class=\"no_text_decoration\" href=\"../top.php?hmenu=".md5("Analysis")."&smenu=".md5("Forensics")."&url=".urlencode($forensic_link."&category%5B0%5D=".$rg->fields["cat_id"]."&category%5B1%5D=".$rg->fields["id"])."\">".str_replace("_"," ",$rg->fields["name"])."</a>',".$rg->fields["num_events"]."],";
                $urls .= "'../top.php?hmenu=".md5("Analysis")."&smenu=".md5("Forensics")."&url=".urlencode($forensic_link."&category%5B0%5D=".$rg->fields["cat_id"]."&category%5B1%5D=".$rg->fields["id"])."',";
		        $rg->MoveNext();
		    }
		}
		$colors = '"#E9967A","#E97A7A","#9BC3CF","#9C9BCF"';
		break;        

    // Antivirus - Last Week
	case "virus":
		$taxonomy = make_where($conn,array("Antivirus" => array("Virus_Detected")));
		$sqlgraph = "SELECT count(a.sid) as num_events,inet_ntoa(a.ip_src) as name FROM snort.acid_event a,ossim.plugin_sid p LEFT JOIN ossim.subcategory c ON c.cat_id=p.category_id AND c.id=p.subcategory_id WHERE p.plugin_id=a.plugin_id AND p.sid=a.plugin_sid AND a.timestamp BETWEEN '".gmdate("Y-m-d H:i:s",gmdate("U")-$range)."' AND '".gmdate("Y-m-d H:i:s")."' $taxonomy group by a.ip_src order by num_events desc limit 10";
		//print_r($sqlgraph);
		if (!$rg = & $conn->Execute($sqlgraph))
		    print $conn->ErrorMsg();
		else 
		{
		    while (!$rg->EOF) {
		    	if ($rg->fields["name"]=="") $rg->fields["name"] = _("Unknown category");
		        $data .= "['<a target=\"topmenu\" class=\"no_text_decoration\" href=\"../top.php?hmenu=".md5("Analysis")."&smenu=".md5("Forensics")."&url=".urlencode($forensic_link."&category%5B0%5D=".$rg->fields["cat_id"]."&category%5B1%5D=".$rg->fields["id"])."\">".str_replace("_"," ",$rg->fields["name"])."</a>',".$rg->fields["num_events"]."],";
                $urls .= "'../top.php?hmenu=".md5("Analysis")."&smenu=".md5("Forensics")."&url=".urlencode($forensic_link."&category%5B0%5D=".$rg->fields["cat_id"]."&category%5B1%5D=".$rg->fields["id"])."',";
		        $rg->MoveNext();
		    }
		}
		$colors = '';
		break;
        
    // Exploits by type - Last Week
	case "exploits":
		$taxonomy = make_where($conn,array("Exploit" => array("Shellcode","SQL_Injection","Browser","ActiveX","Command_Execution","Cross_Site_Scripting","FTP","File_Inclusion","Windows","Directory_Traversal","Attack_Response","Denial_Of_Service","PDF","Buffer_Overflow","Spoofing","Format_String","Misc","DNS","Mail","Samba","Linux")));
		$sqlgraph = str_replace("TAXONOMY",$taxonomy,$query);
		//print_r($sqlgraph);
		if (!$rg = & $conn->Execute($sqlgraph)) 
		    print $conn->ErrorMsg();
		else 
		{
		    while (!$rg->EOF) {
		    	if ($rg->fields["name"]=="") $rg->fields["name"] = _("Unknown category");
		        $data .= "['<a target=\"topmenu\" class=\"no_text_decoration\" href=\"../top.php?hmenu=".md5("Analysis")."&smenu=".md5("Forensics")."&url=".urlencode($forensic_link."&category%5B0%5D=".$rg->fields["cat_id"]."&category%5B1%5D=".$rg->fields["id"])."\">".str_replace("_"," ",$rg->fields["name"])."</a>',".$rg->fields["num_events"]."],";
                $urls .= "'../top.php?hmenu=".md5("Analysis")."&smenu=".md5("Forensics")."&url=".urlencode($forensic_link."&category%5B0%5D=".$rg->fields["cat_id"]."&category%5B1%5D=".$rg->fields["id"])."',";
		        $rg->MoveNext();
		    }
		}
		$colors = '"#D1E8EF","#ADD8E6","#6FE7FF","#00BFFF","#4169E1","#4682B4","#0000CD","#483D8B","#5355DF","#00008B"';
		break;

    // System status - Last Week
	case "system":
		$taxonomy = make_where($conn,array("System" => array("Warning","Emergency","Critical","Error","Notification","Information","Debug","Alert")));
		$sqlgraph = str_replace("TAXONOMY",$taxonomy,$query);
		//print_r($sqlgraph);
		if (!$rg = & $conn->Execute($sqlgraph))
		    print $conn->ErrorMsg();
		else
		{
		    while (!$rg->EOF) {
		    	if ($rg->fields["name"]=="") $rg->fields["name"] = _("Unknown category");
		        $data .= "['<a target=\"topmenu\" class=\"no_text_decoration\" href=\"../top.php?hmenu=".md5("Analysis")."&smenu=".md5("Forensics")."&url=".urlencode($forensic_link."&category%5B0%5D=".$rg->fields["cat_id"]."&category%5B1%5D=".$rg->fields["id"])."\">".str_replace("_"," ",$rg->fields["name"])."</a>',".$rg->fields["num_events"]."],";
                $urls .= "'../top.php?hmenu=".md5("Analysis")."&smenu=".md5("Forensics")."&url=".urlencode($forensic_link."&category%5B0%5D=".$rg->fields["cat_id"]."&category%5B1%5D=".$rg->fields["id"])."',";
		        $rg->MoveNext();
		    }
		}
		$colors = '"#FFFBCF","#EEE8AA","#F0E68C","#FFD700","#FF8C00","#DAA520","#D2691E","#B8860B","#7F631F"';
		break;
	
	// Honeypot Plugins - Last Week
	case "honeypot":
		$nodata_text .= _(" for <i>Honeypot</i>");
		$sqlgraph = "select count(*) as num_events,pl.name,pl.id as plugin_id FROM snort.acid_event a, ossim.plugin pl, ossim.plugin_sid p WHERE p.plugin_id=a.plugin_id AND p.sid=a.plugin_sid AND p.plugin_id=pl.id AND p.category_id=19 AND a.timestamp BETWEEN '".gmdate("Y-m-d H:i:s",gmdate("U")-$range)."' AND '".gmdate("Y-m-d H:i:s")."' $sensor_where group by p.plugin_id order by num_events desc limit 10";
		if (!$rg = & $conn->Execute($sqlgraph))
		    print $conn->ErrorMsg();
		else
		{
		    while (!$rg->EOF) {
		    	if ($rg->fields["name"]=="") $rg->fields["name"] = _("Unknown plugin");
		        $data .= "['<a target=\"topmenu\" class=\"no_text_decoration\" href=\"../top.php?hmenu=".md5("Analysis")."&smenu=".md5("Forensics")."&url=".urlencode($forensic_link."&plugin=".$rg->fields["plugin_id"])."\">".str_replace("_"," ",$rg->fields["name"])."</a>',".$rg->fields["num_events"]."],";
                $urls .= "'../top.php?hmenu=".md5("Analysis")."&smenu=".md5("Forensics")."&url=".urlencode($forensic_link."&plugin=".$rg->fields["plugin_id"])."',";
		        $rg->MoveNext();
		    }
		}
		$colors = '"#FFFBCF","#EEE8AA","#F0E68C","#FFD700","#FF8C00","#DAA520","#D2691E","#B8860B","#7F631F"';
		break;
	// Honeypot Countries - Last Week
	case "honeypot_countries":
		include ("geoip.inc");
		$gi = geoip_open("/usr/share/geoip/GeoIP.dat", GEOIP_STANDARD);
		$nodata_text .= _(" for <i>Honeypot</i>");
		$sqlgraph = "select INET_NTOA(a.ip_src) as ip, count(*) as num_events FROM snort.acid_event a, ossim.plugin pl, ossim.plugin_sid p WHERE p.plugin_id=a.plugin_id AND p.sid=a.plugin_sid AND p.plugin_id=pl.id AND p.category_id=19 AND a.timestamp BETWEEN '".gmdate("Y-m-d H:i:s",gmdate("U")-$range)."' AND '".gmdate("Y-m-d H:i:s")."' $sensor_where group by a.ip_src order by num_events desc";
		//echo $sqlgraph;
		$countries = array();
		$country_names = array();
		//echo $sqlgraph;
		if (!$rg = & $conn->Execute($sqlgraph))
		    print $conn->ErrorMsg();
		else
		{
		    while (!$rg->EOF && count($countries) < 10) {
		    	$country = strtolower(geoip_country_code_by_addr($gi, $rg->fields['ip']));
        		$country_name = geoip_country_name_by_addr($gi, $rg->fields['ip']);
        		if ($country_name != "") { $countries[$country] += $rg->fields['num_events']; $country_names[$country] = $country_name; }
		        $rg->MoveNext();
		    }
		}
		arsort($countries);
		foreach ($countries as $c=>$val) {
			$data .= "['<a target=\"topmenu\" class=\"no_text_decoration\" href=\"../top.php?hmenu=".md5("Analysis")."&smenu=".md5("Forensics")."&url=".urlencode("forensics/base_stat_country_alerts.php?cc=$c&location=alerts&category=19")."\">".$country_names[$c]."</a>',".$val."],";
			$urls .= "'../top.php?hmenu=".md5("Analysis")."&smenu=".md5("Forensics")."&url=".urlencode("forensics/base_stat_country_alerts.php?cc=$c&location=alerts&category=19")."',";
		}
		$colors = '"#FFFBCF","#EEE8AA","#F0E68C","#FFD700","#FF8C00","#DAA520","#D2691E","#B8860B","#7F631F","#6e5925"';
		break;
    // Honeypot VoIP - Last Week
	case "honeypot_voip":
		$nodata_text .= _(" for <i>Honeypot</i>");
		$sqlgraph = "select count(*) as num_events,x.userdata1 as name FROM snort.acid_event a, snort.extra_data x, ossim.plugin_sid p WHERE p.plugin_id=a.plugin_id AND p.sid=a.plugin_sid AND p.category_id=19 AND a.sid=x.sid AND a.cid=x.cid AND a.timestamp BETWEEN '".gmdate("Y-m-d H:i:s",gmdate("U")-$range)."' AND '".gmdate("Y-m-d H:i:s")."' $sensor_where group by x.userdata1 order by num_events desc limit 10";
		//echo $sqlgraph;
		if (!$rg = & $conn->Execute($sqlgraph))
		    print $conn->ErrorMsg();
		else
		{
		    while (!$rg->EOF) {
		    	if ($rg->fields["name"]=="") $rg->fields["name"] = _("Unknown plugin");
		        $data .= "['<a target=\"topmenu\" class=\"no_text_decoration\" href=\"../top.php?hmenu=".md5("Analysis")."&smenu=".md5("Forensics")."&url=".urlencode($forensic_link."&category%5B0%5D=19&userdata%5B0%5D=userdata1&userdata%5B1%5D=%3D&userdata%5B2%5D=".$rg->fields["name"])."\">".str_replace("_"," ",$rg->fields["name"])."</a>',".$rg->fields["num_events"]."],";
                $urls .= "'../top.php?hmenu=".md5("Analysis")."&smenu=".md5("Forensics")."&url=".urlencode($forensic_link."&category%5B0%5D=19&userdata%5B0%5D=userdata1&userdata%5B1%5D=%3D&userdata%5B2%5D=".$rg->fields["name"])."',";
		        $rg->MoveNext();
		    }
		}
		$colors = '"#FFFBCF","#EEE8AA","#F0E68C","#FFD700","#FF8C00","#DAA520","#D2691E","#B8860B","#7F631F"';
		break;
	default:
		// ['Sony',7], ['Samsumg',13.3], ['LG',14.7], ['Vizio',5.2], ['Insignia', 1.2]
		$data = "['"._("Unknown Type")."', 100]";
}
$data = preg_replace("/,$/","",$data);
$urls = preg_replace("/,$/","",$urls);
/*
if ($data=="") {
	$data = "['"._("No events")."', 0]";
}
*/
$db->close($conn);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
<head>
	  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	  <title><?php echo _("Pie Charts")?></title>
	  <!--[if IE]><script language="javascript" type="text/javascript" src="../js/jqplot/excanvas.js"></script><![endif]-->
	  <link rel="stylesheet" type="text/css" href="../js/jqplot/jquery.jqplot.css" />
		
	  <!-- BEGIN: load jquery -->
	  <script language="javascript" type="text/javascript" src="../js/jqplot/jquery-1.4.2.min.js"></script>
	  <!-- END: load jquery -->
	  
	  <!-- BEGIN: load jqplot -->
	  <script language="javascript" type="text/javascript" src="../js/jqplot/jquery.jqplot.min.js"></script>
	  <script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.pieRenderer.js"></script>
	  	 
  <!-- END: load jqplot -->

	<style type="text/css">
		
		#chart .jqplot-point-label {
		  border: 1.5px solid #aaaaaa;
		  padding: 1px 3px;
		  background-color: #eeccdd;
		}
		
		.jqplot-data-label { font-size: 12px;}
        .no_text_decoration {
            text-decoration: none !important;
            color:#606060 !important;
        }
	</style>
    <?php if ($data != "") { ?>
	<script class="code" type="text/javascript">
	
		var links     = [<?php echo $urls?>];
		var isShowing = -1;

		function myClickHandler(ev, gridpos, datapos, neighbor, plot) {
            //mouseX = ev.pageX; mouseY = ev.pageY;
            url = links[neighbor.pointIndex];
            if (typeof(url)!='undefined' && url!='') top.frames['topmenu'].location.href = url;
        }
        
				
		function myMoveHandler(ev, gridpos, datapos, neighbor, plot) {
			if (neighbor == null) {
	            $('#myToolTip').hide().empty();
	            isShowing = -1;
	        }
	        if (neighbor != null) {
	        	if ( neighbor.pointIndex != isShowing ) {
	            	$('#myToolTip').html(neighbor.data[0]).css({left:gridpos.x, top:gridpos.y-5}).show();
	            	isShowing = neighbor.pointIndex;
	            }
	        }
        }
            		
		$(document).ready(function(){
					
			$.jqplot.config.enablePlugins = true;
			$.jqplot.eventListenerHooks.push(['jqplotClick', myClickHandler]); 
			$.jqplot.eventListenerHooks.push(['jqplotMouseMove', myMoveHandler]);
			
			s1 = [<?php echo $data?>];

			plot1 = $.jqplot('chart', [s1], {
				grid: {
					drawBorder: false, 
					drawGridlines: false,
					background: 'rgba(255, 255, 255, 0)',
					shadow:false
				},
				<? if ($colors!="") { ?>seriesColors: [ <?=$colors?> ], <? } ?>
				axesDefaults: {
					
				},
				seriesDefaults:{
                    padding:14,
					renderer:$.jqplot.PieRenderer,
					rendererOptions: {
						showDataLabels: true,
						<?php if (GET('type') == "honeypot_countries") { ?>
						dataLabelFormatString: '%d%',
						dataLabels: "percent"
						<?php } else { ?>
						dataLabelFormatString: '%d',
						dataLabels: "value"
						<?php } ?>
					}				
				},
				legend: {
					show: true,
					rendererOptions: {
						numberCols: 1
					},
					location: 'w'
				}
			}); 
			
			$('#chart').append('<div id="myToolTip"></div>');
    
		});
	</script>
	<?php } ?>
    
  </head>
	<body style="overflow:hidden" scroll="no">
		<?php if ($data != "") { ?>
		<div id="chart" style="width:100%; height:<?=$h?>px;"></div>
		<?php } else { ?>
		<table class="transparent" align="center"><tr><td class="center nobborder" style="font-family:arial;font-size:12px;background-color:white;padding-top:40px"><?php echo $nodata_text ?></td></tr></table>
		<?php } ?>
	</body>
</html>

