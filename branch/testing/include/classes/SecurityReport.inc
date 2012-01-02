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
* - SecurityReport()
* - AttackHost()
* - Events()
* - Ports()
* - EventsByRisk()
* - Truncate()
* Classes list:
* - SecurityReport
*/
require_once ('ossim_db.inc');
require_once ('classes/Log_action.inc');
require_once ('classes/Plugin_sid.inc');
class SecurityReport {
    var $ossim_conn;
    var $snort_conn;
    var $dbc;
    function SecurityReport() {
        $this->dbc = new ossim_db();
        $this->ossim_conn = $this->dbc->connect();
        $this->snort_conn = $this->dbc->snort_connect();
    }
    function close_conns() {
        if (is_object($this->dbc) && is_object($this->ossim_conn)) $this->dbc->close($this->ossim_conn);
        if (is_object($this->dbc) && is_object($this->snort_conn)) $this->dbc->close($this->snort_conn);
    }
    function __destruct() { $this->close_conns(); }
    function normalize_date ($from_date, $to_date) {
		$temp_dbc = new ossim_db();
        $temp_ossim_conn = $temp_dbc->connect();
    	// format correction
        $from_date = preg_replace ("/(\d\d)\/(\d\d)\/(\d\d\d\d)/","\\3-\\2-\\1",$from_date);
		$to_date = preg_replace ("/(\d\d)\/(\d\d)\/(\d\d\d\d)/","\\3-\\2-\\1",$to_date);

        // timezone correction
        require_once("classes/Util.inc");
        $tz = Util::get_timezone();
        if ($tz!=0) 
        {
        	$from_date = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($temp_ossim_conn,"$from_date 00:00:00")+(-3600*$tz));
        	$to_date   = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($temp_ossim_conn,"$to_date 23:59:59")+(-3600*$tz));
        }
        if (!preg_match("/\d+\:\d+:\d+/",$from_date))  $from_date .= " 00:00:00";
        if (!preg_match("/\d+\:\d+:\d+/",$to_date))    $to_date   .= " 23:59:59";
        $temp_dbc->close($temp_ossim_conn);
        return array($from_date, $to_date);
    }
    function AttackHost($conn, $ip_target, $limit = 10, $type = "event", $from_date, $to_date, $assets=array(), $sourcetype='', $category=0, $subcategory=0, $plugin_groups=null, $sensors=array(), $order_type = "DESC") {
    	if ($order_type == "") $order_type = "DESC";
    	$limit++;
    	require_once ('classes/Host.inc');
        require_once ('classes/Net.inc');
        require_once ('classes/Session.inc');
		require_once ('classes/Event_viewer.inc');
		require_once ('classes/Plugingroup.inc');
        $list = array();
        $temp_conn = $this->snort_conn;
        
        list ($from_date,$to_date) = $this->normalize_date($from_date,$to_date);
		
        // Taxonomy filters
		$plugin_list = Plugin_sid::get_all_sids($this->ossim_conn,null,$sourcetype,$category,$subcategory,$plugin_groups);
		// Make sql query and get data
		if ($type == "event") {
			$where = SecurityReport::make_where($this->ossim_conn,$from_date,$to_date,$plugin_list,array("assets"=>$assets, "sensors"=>$sensors));
		} else {
			$where = SecurityReport::make_where_alarm($this->ossim_conn,$from_date,$to_date,$plugin_list,array("assets"=>$assets, "sensors"=>$sensors));
		}
                
		// User sensor filtering
		/*
		$sensor_str_alarm = "";
		$sensor_str_event = "";
		if (Session::allowedSensors() != "" || count($sensors)>0) {
			//$user_sensors = explode(",",Session::allowedSensors());
			$user_sensors = (Session::allowedSensors()=="" || !count($sensors)) ? array_merge(explode(",",Session::allowedSensors()),$sensors) : array_intersect(explode(",",Session::allowedSensors()), $sensors);
			foreach ($user_sensors as $user_sensor) if ($user_sensor!="") {
				$sensor_str .= ($sensor_str == "") ? "e.sensor = '".$user_sensor."'" : " OR e.sensor = '".$user_sensor."'";
				//$sensor_str .= ($sensor_str == "") ? "e.sensor LIKE '".$user_sensor."%'" : " OR e.sensor LIKE '".$user_sensor."%'";
			}
			//echo "<br><br><br><br>SENSOR:$sensor_str de ".Session::allowedSensors();
			$sensor_str_alarm = " AND ($sensor_str) AND e.id=a.event_id";
			// snort
			$snortsensors = Event_viewer::GetSensorSids($temp_conn);
			$sensor_str = "";
			foreach ($user_sensors as $user_sensor)
				if (count($snortsensors[$user_sensor]) > 0) $sensor_str .= ($sensor_str != "") ? ",".implode(",",$snortsensors[$user_sensor]) : implode(",",$snortsensors[$user_sensor]);
			if ($sensor_str == "") $sensor_str = "0";
			$sensor_str_event = " AND acid_event.sid in (" . $sensor_str . ")";
		}
		
		// plugin groups filter
		$sql_plugin_groups='';
					
		if($plugin_groups!=null){
			$groups = Plugingroup::get_list($conn,'plugin_group_descr.name="'.$plugin_groups.'"');
			
			switch ($type) {
				case "alarm":
					$nameTable='a';
					break;
				case "event":
				default:
					$nameTable='acid_event';
			}
			
			foreach($groups as $group) {
				$sql_plugin_groups.='AND (';
				$i=1;
				foreach($group->get_plugins() as $p) {	
					if($i!==1){
						$sql_plugin_groups.=' OR ';
					}
					if($p['sids']=='0'||$p['sids']=='ANY'){
						$sql_plugin_groups.='('.$nameTable.'.plugin_id = '.$p['id'].')';
					}else{
						$sql_plugin_groups.='('.$nameTable.'.plugin_id = '.$p['id'].' AND '.$nameTable.'.plugin_sid in ('.$p['sids'].'))';
					}
					$i++;
				}
				$sql_plugin_groups.=')';
			}
		}
		//
		
		if ($sensor_str != "") {
			$sensor_join = " ,event e";
			$sql_where .= $sensor_str;
		} else $sensor_join = "";
		*/
		/*if (!strcmp($ip_target, "ip_src")) {
            if ($type == "alarm") $target = "src_ip";
            $title = "Attacker hosts";
        } elseif (!strcmp($ip_target, "ip_dst")) {
            if ($type == "alarm") $target = "dst_ip";
            $title = "Attacked hosts";
        }*/
        switch ($type) {
            case "alarm":
                if (!strcmp($ip_target, "ip_src")) {
                    $ip_target = "src_ip";
                }
                if (!strcmp($ip_target, "ip_dst")) {
                    $ip_target = "dst_ip";
                }
                $sensor_join = (preg_match("/e\.sensor/", $where)) ? ", event e" : "";
                $query = OssimQuery("SELECT count(a.$ip_target) AS occurrences,
                  a.$ip_target as ip FROM alarm a $sensor_join where a.$ip_target != 0 $where 
                  GROUP BY a.$ip_target ORDER BY occurrences $order_type LIMIT $limit");
                //
                //list($join2,$where2) = SecurityReport::make_join_where("a.",$ip_target,$assets,$sourcetype,$category,$subcategory);
                //
                /*
                $query = OssimQuery("SELECT count(a.$ip_target) AS occurrences,
                  a.$ip_target as ip FROM alarm a $join2 $sensor_join where a.$ip_target != 0 AND (a.timestamp BETWEEN '$from_date' AND '$to_date') $sensor_str_alarm $where2
                  $sql_plugin_groups GROUP BY a.$ip_target ORDER BY occurrences $order_type LIMIT $limit");
                */
                $temp_conn = $this->ossim_conn;
				
                break;

            case "event":
           
			default:
                //
                //list($join2,$where2) = SecurityReport::make_join_where("acid_event.",$ip_target,$assets,$sourcetype,$category,$subcategory);
                //
                /*            
                $query = OssimQuery("SELECT count($ip_target) AS occurrences,
                  $ip_target as ip FROM acid_event $join2 where ossim_risk_c >= 0 AND (timestamp BETWEEN '$from_date' AND '$to_date') AND ossim_risk_a >= 0$sensor_str_event $where2
                  $sql_plugin_groups GROUP BY ip ORDER BY occurrences $order_type LIMIT $limit");
                */
				$join = (count($plugin_list) > 0) ? "LEFT JOIN ossim.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid" : "";
				$query = OssimQuery("SELECT count($ip_target) AS occurrences,
                  $ip_target as ip FROM acid_event $join where ossim_risk_c >= 0 AND ossim_risk_a >= 0 $where 
                  GROUP BY ip ORDER BY occurrences $order_type LIMIT $limit");
                $db = new ossim_db();
				$temp_conn = $db->snort_connect();
				//$temp_conn = $this->snort_conn;
        }
		$limit--;
		
		//echo "<b>SQL</b>: $query<br>";
		
		if ($rs = & $temp_conn->Execute($query))
		{
			$i = 0;
			
            while (!$rs->EOF) {
				// sensor filter in query
				//if (Session::hostAllowed($conn, $rs->fields["ip"])) {
				if ($rs->fields['ip'] == 0) { $rs->MoveNext(); continue; }
				$ip_address = (is_numeric($rs->fields["ip"]))? long2ip($rs->fields["ip"]) : $rs->fields["ip"];
				if ($ip_address == "253.253.253.253") { $rs->MoveNext(); continue; }
				$tmp_array = array(
					$ip_address,
					$rs->fields["occurrences"]
				);
				array_push($list, $tmp_array);
				$i+= 1;
                //}
                $rs->MoveNext();
                /* check limit */
                if ($i >= $limit) {
                    $rs->EOF = true;
                }
            }
        }
		
		if ($type == "event") $db->close($temp_conn);
        return $list;
    }
    function Events($limit = 10, $type = "event", $from_date, $to_date, $assets=array(), $sourcetype='', $category=0, $subcategory=0, $plugin_groups=null, $ips=false, $sensors=array(), $order_type = "DESC") {
        require_once ('classes/Event_viewer.inc');
        require_once ('classes/Status.inc');
		$list = array();
        $temp_conn = $this->snort_conn;
		
		list ($from_date,$to_date) = $this->normalize_date($from_date,$to_date);
		
		if ($order_type == "") $order_type = "DESC";
		
		// Taxonomy filters
		$plugin_list = Plugin_sid::get_all_sids($this->ossim_conn,null,$sourcetype,$category,$subcategory,$plugin_groups);
		// Make sql query and get data 
    	if ($type == "event") {
			$where = SecurityReport::make_where($this->ossim_conn,$from_date,$to_date,$plugin_list,array("assets"=>$assets, "sensors"=>$sensors));
		} else {
			$where = SecurityReport::make_where_alarm($this->ossim_conn,$from_date,$to_date,$plugin_list,array("assets"=>$assets, "sensors"=>$sensors));
		}

		// User sensor filtering
		/*
		$sensor_str_alarm = "";
		$sensor_str_event = "";
		if (Session::allowedSensors() != "" || count($sensors)>0) {
			//$user_sensors = explode(",",Session::allowedSensors());
			$user_sensors = (Session::allowedSensors()=="" || !count($sensors)) ? array_merge(explode(",",Session::allowedSensors()),$sensors) : array_intersect(explode(",",Session::allowedSensors()), $sensors);
			foreach ($user_sensors as $user_sensor) if ($user_sensor!="") {
				$sensor_str .= ($sensor_str == "") ? "e.sensor = '".$user_sensor."'" : " OR e.sensor = '".$user_sensor."'";
				//$sensor_str .= ($sensor_str == "") ? "e.sensor LIKE '".$user_sensor."%'" : " OR e.sensor LIKE '".$user_sensor."%'";
			}
			//echo "<br><br><br><br>SENSOR:$sensor_str de ".Session::allowedSensors();
			$sensor_str_alarm = " AND ($sensor_str) AND e.id=a.event_id";
			// snort
			$snortsensors = Event_viewer::GetSensorSids($temp_conn);
			$sensor_str = "";
			foreach ($user_sensors as $user_sensor)
				if (count($snortsensors[$user_sensor]) > 0) $sensor_str .= ($sensor_str != "") ? ",".implode(",",$snortsensors[$user_sensor]) : implode(",",$snortsensors[$user_sensor]);
			if ($sensor_str == "") $sensor_str = "0";
			$sensor_str_event = " AND acid_event.sid in (" . $sensor_str . ")";
		}
		
		// plugin groups filter
		$sql_plugin_groups='';

		if($plugin_groups!=null){
			switch ($type) {
				case "alarm":
					$nameTable='a';
					break;
				case "event":
				default:
					$nameTable='acid_event';
			}
			
			$groups = Plugingroup::get_list($this->ossim_conn,'plugin_group_descr.name="'.$plugin_groups.'"');
			foreach($groups as $group) {
				$sql_plugin_groups.='AND (';
				$i=1;
				foreach($group->get_plugins() as $p) {	
					if($i!==1){
						$sql_plugin_groups.=' OR ';
					}
					if($p['sids']=='0'||$p['sids']=='ANY'){
						$sql_plugin_groups.='('.$nameTable.'.plugin_id = '.$p['id'].')';
					}else{
						$sql_plugin_groups.='('.$nameTable.'.plugin_id = '.$p['id'].' AND '.$nameTable.'.plugin_sid in ('.$p['sids'].'))';
					}
					$i++;
				}
				$sql_plugin_groups.=')';
			}
		}
		*/
		//
		/*
		if ($sensor_str != "") {
			$sensor_join = " ,event e";
			$sql_where .= $sensor_str;
		} else $sensor_join = "";
		*/
        switch ($type) {
            case "alarm":
            	$sensor_join = (preg_match("/e\.sensor/", $where)) ? ", event e" : "";
            	$query = "SELECT count(*) AS occurrences, plugin_sid.name as sig_name, a.plugin_id, a.plugin_sid
                            FROM alarm a, plugin_sid$sensor_join 
                            WHERE plugin_sid.plugin_id = a.plugin_id AND plugin_sid.sid = a.plugin_sid $where 
            				GROUP BY a.plugin_id, a.plugin_sid
                            ORDER BY occurrences 
                            $order_type LIMIT $limit";
            	
                //
                //list($join2,$where2) = SecurityReport::make_join_where("a.","src_ip;dst_ip",$assets,$sourcetype,$category,$subcategory);
                //
                /*
                if (!preg_match("/plugin_sid/",$join2)) $join2 .= ", plugin_sid";
                $query = "SELECT count(*) AS occurrences, plugin_sid.name as sig_name, a.plugin_id, a.plugin_sid
                            FROM alarm a $join2 $sensor_join
                            WHERE plugin_sid.plugin_id = a.plugin_id AND (a.timestamp BETWEEN '$from_date' AND '$to_date')
                                  AND plugin_sid.sid = a.plugin_sid$sensor_str_alarm $where2
                            $sql_plugin_groups GROUP BY a.plugin_id, a.plugin_sid
                            ORDER BY occurrences 
                            $order_type LIMIT $limit";
                */
                $temp_conn = $this->ossim_conn;
                break;

            case "event":
            default:
                //
                //list($join2,$where2) = SecurityReport::make_join_where("acid_event.","ip_dst;ip_src",$assets,$sourcetype,$category,$subcategory,1);
                //
                if($ips) 
				{
					/*
                    $query = "SELECT count(plugin_sid) AS occurrences, plugin_sid.name as sig_name, INET_NTOA(ip_src) as nip_src, INET_NTOA(ip_dst) as nip_dst, acid_event.plugin_id, acid_event.plugin_sid
                                FROM acid_event LEFT JOIN ossim.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid $join2
                                WHERE (timestamp BETWEEN '$from_date' AND '$to_date') $sensor_str_event
                                $where2
                                $sql_plugin_groups GROUP BY acid_event.plugin_id, acid_event.plugin_sid, nip_src, nip_dst
                                ORDER BY occurrences
                                $order_type LIMIT $limit";
                    */
					$query = "SELECT count(plugin_sid) AS occurrences, plugin_sid.name as sig_name, INET_NTOA(ip_src) as nip_src, INET_NTOA(ip_dst) as nip_dst, acid_event.plugin_id, acid_event.plugin_sid
                                FROM acid_event LEFT JOIN ossim.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid 
                                WHERE 1=1 $where GROUP BY acid_event.plugin_id, acid_event.plugin_sid, nip_src, nip_dst
                                ORDER BY occurrences
                                $order_type LIMIT $limit";
                }
                else 
				{
					/*
                    $query = "SELECT count(plugin_sid) AS occurrences, plugin_sid.name as sig_name, acid_event.plugin_id, acid_event.plugin_sid
                                FROM acid_event LEFT JOIN ossim.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid $join2
                                WHERE (timestamp BETWEEN '$from_date' AND '$to_date') $sensor_str_event
                                $where2
                                $sql_plugin_groups GROUP BY acid_event.plugin_id, acid_event.plugin_sid
                                ORDER BY occurrences
                                $order_type LIMIT $limit";
                    */
					$query = "SELECT count(plugin_sid) AS occurrences, plugin_sid.name as sig_name, acid_event.plugin_id, acid_event.plugin_sid
                                FROM acid_event LEFT JOIN ossim.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid 
                                WHERE 1=1 $where GROUP BY acid_event.plugin_id, acid_event.plugin_sid
                                ORDER BY occurrences
                                $order_type LIMIT $limit";
                }
                $db = new ossim_db();
				$temp_conn = $db->snort_connect();
				//$temp_conn = $this->snort_conn;
        }
        
        //echo "<b>SQL</b>: $query<br>";
        
        if (!$rs = & $temp_conn->Execute($query)) {
            print $temp_conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                $rs->fields['sig_name'] = trim($rs->fields['sig_name']);
                if ($rs->fields['sig_name']=="")
                    $rs->fields['sig_name'] = Status::get_unknown_sid_name($this->ossim_conn,$rs->fields['plugin_id'],$rs->fields['plugin_sid']);
                if($ips) {
                    $tmp_array = array(
                        $rs->fields["sig_name"],
                        $rs->fields["occurrences"],
                        $rs->fields["nip_src"],
                        $rs->fields["nip_dst"]
                    );
                }
                else {
                    $tmp_array = array(
                        $rs->fields["sig_name"],
                        $rs->fields["occurrences"]
                    );
                }
                array_push($list, $tmp_array);
                $rs->MoveNext();
            }
        }
		if ($type == "event") $db->close($temp_conn);
        return $list;
    }
	function EventsByIp($ip, $ip_type, $limit = 10, $type = "event", $from_date, $to_date, $assets=array(), $sourcetype='', $category=0, $subcategory=0, $plugin_groups=null, $ips=false, $sensors=array()) {
		require_once ('classes/Event_viewer.inc');
        require_once ('classes/Status.inc');
		$list = array();
        $temp_conn = $this->snort_conn;
		
		list ($from_date,$to_date) = $this->normalize_date($from_date,$to_date);
		
		// Taxonomy filters
		$plugin_list = Plugin_sid::get_all_sids($this->ossim_conn,null,$sourcetype,$category,$subcategory,$plugin_groups);
		// Make sql query and get data 
		if ($type == "event") {
			$where = SecurityReport::make_where($this->ossim_conn,$from_date,$to_date,$plugin_list,array("assets"=>$assets, "sensors"=>$sensors));
		} else {
			$where = SecurityReport::make_where_alarm($this->ossim_conn,$from_date,$to_date,$plugin_list,array("assets"=>$assets, "sensors"=>$sensors));
		}
		
		// User sensor filtering
		/*
		$sensor_str_alarm = "";
		$sensor_str_event = "";
		if (Session::allowedSensors() != "" || count($sensors)>0) {
			//$user_sensors = explode(",",Session::allowedSensors());
			$user_sensors = (Session::allowedSensors()=="" || !count($sensors)) ? array_merge(explode(",",Session::allowedSensors()),$sensors) : array_intersect(explode(",",Session::allowedSensors()), $sensors);
			foreach ($user_sensors as $user_sensor) if ($user_sensor!="") {
				$sensor_str .= ($sensor_str == "") ? "e.sensor = '".$user_sensor."'" : " OR e.sensor = '".$user_sensor."'";
				//$sensor_str .= ($sensor_str == "") ? "e.sensor LIKE '".$user_sensor."%'" : " OR e.sensor LIKE '".$user_sensor."%'";
			}
			//echo "<br><br><br><br>SENSOR:$sensor_str de ".Session::allowedSensors();
			$sensor_str_alarm = " AND ($sensor_str) AND e.id=a.event_id";
			// snort
			$snortsensors = Event_viewer::GetSensorSids($temp_conn);
			$sensor_str = "";
			foreach ($user_sensors as $user_sensor)
				if (count($snortsensors[$user_sensor]) > 0) $sensor_str .= ($sensor_str != "") ? ",".implode(",",$snortsensors[$user_sensor]) : implode(",",$snortsensors[$user_sensor]);
			if ($sensor_str == "") $sensor_str = "0";
			$sensor_str_event = " AND acid_event.sid in (" . $sensor_str . ")";
		}
		
		// plugin groups filter
		$sql_plugin_groups='';

		if($plugin_groups!=null){
			switch ($type) {
				case "alarm":
					$nameTable='a';
					break;
				case "event":
				default:
					$nameTable='acid_event';
			}
			
			$groups = Plugingroup::get_list($this->ossim_conn,'plugin_group_descr.name="'.$plugin_groups.'"');
			foreach($groups as $group) {
				$sql_plugin_groups.='AND (';
				$i=1;
				foreach($group->get_plugins() as $p) {	
					if($i!==1){
						$sql_plugin_groups.=' OR ';
					}
					if($p['sids']=='0'||$p['sids']=='ANY'){
						$sql_plugin_groups.='('.$nameTable.'.plugin_id = '.$p['id'].')';
					}else{
						$sql_plugin_groups.='('.$nameTable.'.plugin_id = '.$p['id'].' AND '.$nameTable.'.plugin_sid in ('.$p['sids'].'))';
					}
					$i++;
				}
				$sql_plugin_groups.=')';
			}
		}
		//
		
		if ($sensor_str != "") {
			$sensor_join = " ,event e";
			$sql_where .= $sensor_str;
		} else $sensor_join = "";
		*/
        switch ($type) {
            case "alarm":
            	$sensor_join = (preg_match("/e\.sensor/", $where)) ? ", event e" : "";
            	$query = "SELECT count(*) AS occurrences, plugin_sid.name as sig_name, a.plugin_id, a.plugin_sid
                            FROM alarm a $sensor_join
                            WHERE plugin_sid.plugin_id = a.plugin_id AND plugin_sid.sid = a.plugin_sid $where 
                            GROUP BY a.plugin_id, a.plugin_sid
                            ORDER BY occurrences 
                            DESC LIMIT $limit";
                //
                //list($join2,$where2) = SecurityReport::make_join_where("a.","src_ip;dst_ip",$assets,$sourcetype,$category,$subcategory);
                //
                /*
                if (!preg_match("/plugin_sid/",$join2)) $join2 .= ", plugin_sid";
                $query = "SELECT count(*) AS occurrences, plugin_sid.name as sig_name, a.plugin_id, a.plugin_sid
                            FROM alarm a $join2 $sensor_join
                            WHERE plugin_sid.plugin_id = a.plugin_id AND (s.timestamp BETWEEN '$from_date' AND '$to_date')
                                  AND plugin_sid.sid = a.plugin_sid$sensor_str_alarm $where2
                            $sql_plugin_groups GROUP BY a.plugin_id, a.plugin_sid
                            ORDER BY occurrences 
                            DESC LIMIT $limit";
                */
                $temp_conn = $this->ossim_conn;
                break;

            case "event":
            default:
                //
                //list($join2,$where2) = SecurityReport::make_join_where("acid_event.","ip_dst;ip_src",$assets,$sourcetype,$category,$subcategory,1);
                //
                if($ips) 
				{
                    /*
					$query = "SELECT count(plugin_sid) AS occurrences, plugin_sid.name as sig_name, INET_NTOA(ip_src) as nip_src, INET_NTOA(ip_dst) as nip_dst, acid_event.plugin_id, acid_event.plugin_sid
                                FROM acid_event LEFT JOIN ossim.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid $join2
                                WHERE (timestamp BETWEEN '$from_date' AND '$to_date') $sensor_str_event
                                $where2 AND acid_event.$ip_type = $ip
                                $sql_plugin_groups GROUP BY acid_event.plugin_id, acid_event.plugin_sid, nip_src, nip_dst
                                ORDER BY occurrences
                                DESC LIMIT $limit";
                    */
					$query = "SELECT count(plugin_sid) AS occurrences, plugin_sid.name as sig_name, INET_NTOA(ip_src) as nip_src, INET_NTOA(ip_dst) as nip_dst, acid_event.plugin_id, acid_event.plugin_sid
                                FROM acid_event LEFT JOIN ossim.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid 
                                WHERE acid_event.$ip_type = $ip $where 
                                GROUP BY acid_event.plugin_id, acid_event.plugin_sid, nip_src, nip_dst
                                ORDER BY occurrences
                                DESC LIMIT $limit";
                }
                else 
				{
                    /*
					$query = "SELECT count(plugin_sid) AS occurrences, plugin_sid.name as sig_name, acid_event.plugin_id, acid_event.plugin_sid
                                FROM acid_event LEFT JOIN ossim.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid $join2
                                WHERE (timestamp BETWEEN '$from_date' AND '$to_date') $sensor_str_event
                                $where2 AND acid_event.$ip_type = $ip
                                $sql_plugin_groups GROUP BY acid_event.plugin_id, acid_event.plugin_sid
                                ORDER BY occurrences
                                DESC LIMIT $limit";
                    */
					$query = "SELECT count(plugin_sid) AS occurrences, plugin_sid.name as sig_name, acid_event.plugin_id, acid_event.plugin_sid
                                FROM acid_event LEFT JOIN ossim.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid 
                                WHERE acid_event.$ip_type = $ip $where 
                                GROUP BY acid_event.plugin_id, acid_event.plugin_sid
                                ORDER BY occurrences
                                DESC LIMIT $limit";
                }
                $db = new ossim_db();
				$temp_conn = $db->snort_connect();
				//$temp_conn = $this->snort_conn;
        }
		
        //echo "<b>SQL</b>: $query<br>";
        
        if (!$rs = & $temp_conn->Execute($query)) {
            print $temp_conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                $rs->fields['sig_name'] = trim($rs->fields['sig_name']);
                if ($rs->fields['sig_name']=="")
                    $rs->fields['sig_name'] = Status::get_unknown_sid_name($this->ossim_conn,$rs->fields['plugin_id'],$rs->fields['plugin_sid']);
                if($ips) {
                    $tmp_array = array(
                        $rs->fields["sig_name"],
                        $rs->fields["occurrences"],
                        $rs->fields["nip_src"],
                        $rs->fields["nip_dst"]
                    );
                }
                else {
                    $tmp_array = array(
                        $rs->fields["sig_name"],
                        $rs->fields["occurrences"]
                    );
                }
                array_push($list, $tmp_array);
                $rs->MoveNext();
            }
        }
		if ($type == "event") $db->close($temp_conn);
        return $list;
    }
    function Ports($limit = 10, $type = "event", $from_date, $to_date, $assets=array(), $sourcetype='', $category=0, $subcategory=0, $plugin_groups=null, $ips=false, $sensors=array(), $order_type = "DESC") {
    	require_once ('classes/Port.inc');
        require_once ('classes/Event_viewer.inc');
		$list = array();
        $temp_conn = $this->snort_conn;
		
		list ($from_date,$to_date) = $this->normalize_date($from_date,$to_date);
		
		if ($order_type == "") $order_type = "DESC";
		
		// Taxonomy filters
		$plugin_list = Plugin_sid::get_all_sids($this->ossim_conn,null,$sourcetype,$category,$subcategory,$plugin_groups);
		// Make sql query and get data 
    	if ($type == "event") {
			$where = SecurityReport::make_where($this->ossim_conn,$from_date,$to_date,$plugin_list,array("assets"=>$assets, "sensors"=>$sensors));
		} else {
			$where = SecurityReport::make_where_alarm($this->ossim_conn,$from_date,$to_date,$plugin_list,array("assets"=>$assets, "sensors"=>$sensors));
		}
		
		// User sensor filtering
		/*
		$sensor_str_alarm = "";
		$sensor_str_event = "";
		if (Session::allowedSensors() != "" || count($sensors)>0) {
			//$user_sensors = explode(",",Session::allowedSensors());
			$user_sensors = (Session::allowedSensors()=="" || !count($sensors)) ? array_merge(explode(",",Session::allowedSensors()),$sensors) : array_intersect(explode(",",Session::allowedSensors()), $sensors);
			foreach ($user_sensors as $user_sensor) if ($user_sensor!="") {
				$sensor_str .= ($sensor_str == "") ? "e.sensor = '".$user_sensor."'" : " OR e.sensor = '".$user_sensor."'";
				//$sensor_str .= ($sensor_str == "") ? "e.sensor LIKE '".$user_sensor."%'" : " OR e.sensor LIKE '".$user_sensor."%'";
			}
			//echo "<br><br><br><br>SENSOR:$sensor_str de ".Session::allowedSensors();
			$sensor_str_alarm = " AND ($sensor_str) AND e.id=a.event_id";
			// snort
			$snortsensors = Event_viewer::GetSensorSids($temp_conn);
			$sensor_str = "";
			foreach ($user_sensors as $user_sensor)
				if (count($snortsensors[$user_sensor]) > 0) $sensor_str .= ($sensor_str != "") ? ",".implode(",",$snortsensors[$user_sensor]) : implode(",",$snortsensors[$user_sensor]);
			if ($sensor_str == "") $sensor_str = "0";
			$sensor_str_event = " AND acid_event.sid in (" . $sensor_str . ")";
		}
		
		// plugin groups filter
		$sql_plugin_groups='';

		if($plugin_groups!=null){
			switch ($type) {
				case "alarm":
					$nameTable='a';
					break;
				case "event":
				default:
					$nameTable='acid_event';
			}
			
			$groups = Plugingroup::get_list($this->ossim_conn,'plugin_group_descr.name="'.$plugin_groups.'"');
			foreach($groups as $group) {
				$sql_plugin_groups.='AND (';
				$i=1;
				foreach($group->get_plugins() as $p) {	
					if($i!==1){
						$sql_plugin_groups.=' OR ';
					}
					if($p['sids']=='0'||$p['sids']=='ANY'){
						$sql_plugin_groups.='('.$nameTable.'.plugin_id = '.$p['id'].')';
					}else{
						$sql_plugin_groups.='('.$nameTable.'.plugin_id = '.$p['id'].' AND '.$nameTable.'.plugin_sid in ('.$p['sids'].'))';
					}
					$i++;
				}
				$sql_plugin_groups.=')';
			}
		}
		//
		
		if ($sensor_str != "") {
			$sensor_join = " ,event e";
			$sql_where .= $sensor_str;
		} else $sensor_join = "";
		*/
        switch ($type) {
            case "alarm":
            	$sensor_join = (preg_match("/e\.sensor/", $where)) ? ", event e" : "";
            	$query = OssimQuery("SELECT count(a.dst_port) AS occurrences, a.dst_port as layer4_dport
                  FROM alarm a $sensor_join WHERE a.dst_port<>0 $where GROUP BY a.dst_port ORDER BY occurrences $order_type LIMIT $limit");
                //
                //list($join2,$where2) = SecurityReport::make_join_where("a.","dst_ip",$assets,$sourcetype,$category,$subcategory);
                //
                /*
                $query = OssimQuery("SELECT count(a.dst_port) AS occurrences, a.dst_port as layer4_dport
                  FROM alarm a $join2 $sensor_join WHERE (a.timestamp BETWEEN '$from_date' AND '$to_date') AND a.dst_port<>0$sensor_str_alarm $where2 $sql_plugin_groups GROUP BY a.dst_port ORDER BY occurrences $order_type LIMIT $limit");
                */
                $temp_conn = $this->ossim_conn;
                break;

            case "event":
            default:
                //
                //list($join2,$where2) = SecurityReport::make_join_where("acid_event.","ip_dst",$assets,$sourcetype,$category,$subcategory);
                // 
                $join = (count($plugin_list) > 0) ? "LEFT JOIN ossim.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid" : "";
                if ($ips) {
                	/*
                    $query = OssimQuery("SELECT count(layer4_dport) AS occurrences, layer4_dport, INET_NTOA(ip_src) as nip_src, INET_NTOA(ip_dst) as nip_dst
                      FROM acid_event $join2 WHERE (timestamp BETWEEN '$from_date' AND '$to_date') AND layer4_dport<>0$sensor_str_event $where2
                      $sql_plugin_groups GROUP BY layer4_dport,nip_src,nip_dst
                      ORDER BY occurrences $order_type LIMIT $limit");
                    */
                	$query = OssimQuery("SELECT count(layer4_dport) AS occurrences, layer4_dport, INET_NTOA(ip_src) as nip_src, INET_NTOA(ip_dst) as nip_dst
                      FROM acid_event $join WHERE layer4_dport<>0 $where 
                      GROUP BY layer4_dport,nip_src,nip_dst
                      ORDER BY occurrences $order_type LIMIT $limit");
                }
                else {
                    /*
                	$query = OssimQuery("SELECT count(layer4_dport) AS occurrences, layer4_dport
                      FROM acid_event $join2 WHERE (timestamp BETWEEN '$from_date' AND '$to_date') AND layer4_dport<>0$sensor_str_event $where2
                      $sql_plugin_groups GROUP BY layer4_dport
                      ORDER BY occurrences $order_type LIMIT $limit");
                    */
                	$query = OssimQuery("SELECT count(layer4_dport) AS occurrences, layer4_dport
                      FROM acid_event $join WHERE layer4_dport<>0 $where 
                      GROUP BY layer4_dport
                      ORDER BY occurrences $order_type LIMIT $limit");
                }
                $db = new ossim_db();
				$temp_conn = $db->snort_connect();
				//$temp_conn = $this->snort_conn;
        }

        //echo "<b>SQL</b>: $query<br>";
        
        if (!$rs = & $temp_conn->Execute($query)) {
            print $temp_conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                $port = $rs->fields["layer4_dport"];
                $service = "unknown";
                $occurrences = $rs->fields["occurrences"];
                if ($port != "NULL" and $occurrences != 0) {
                    if ($port_list = Port::get_list($this->ossim_conn, "WHERE port_number = $port")) {
                        $service = $port_list[0]->get_service();
                    }
                    
                    if ($ips) { 
                        $tmp_array = array(
                            $port,
                            $service,
                            $occurrences,
                            $rs->fields["nip_src"],
                            $rs->fields["nip_dst"]
                        );
                    }
                    else {
                        $tmp_array = array(
                            $port,
                            $service,
                            $occurrences
                        );
                    }
                    array_push($list, $tmp_array);
                }
                $rs->MoveNext();
            }
        }
		if ($type == "event") $db->close($temp_conn);
        return $list;
    }
    // Deprecated for $type = "event"
    function EventsByRisk($limit = 10, $type = "event", $from_date, $to_date, $assets=array(), $sourcetype='', $category=0, $subcategory=0, $plugin_groups=null, $sensors=array(), $order_type = "DESC") {
        require_once ('classes/Event_viewer.inc');
        require_once ('classes/Status.inc');
		
		$list = array();
        $temp_conn = $this->snort_conn;
        
		list ($from_date,$to_date) = $this->normalize_date($from_date,$to_date);
		
		if ($order_type == "") $order_type = "DESC";
		
		// Taxonomy filters
		$plugin_list = Plugin_sid::get_all_sids($this->ossim_conn,null,$sourcetype,$category,$subcategory,$plugin_groups);
		// Make sql query and get data 
    	if ($type == "event") {
			$where = SecurityReport::make_where($this->ossim_conn,$from_date,$to_date,$plugin_list,array("assets"=>$assets, "sensors"=>$sensors));
		} else {
			$where = SecurityReport::make_where_alarm($this->ossim_conn,$from_date,$to_date,$plugin_list,array("assets"=>$assets, "sensors"=>$sensors));
		}
		
		// User sensor filtering
		/*
		$sensor_str_alarm = "";
		$sensor_str_event = "";
		if (Session::allowedSensors() != "" || count($sensors)>0) {
			// ossim
			//$user_sensors = explode(",",Session::allowedSensors());
			$user_sensors = (Session::allowedSensors()=="" || !count($sensors)) ? array_merge(explode(",",Session::allowedSensors()),$sensors) : array_intersect(explode(",",Session::allowedSensors()), $sensors);
			foreach ($user_sensors as $user_sensor) if ($user_sensor!="") {
				$sensor_str .= ($sensor_str == "") ? "e.sensor = '".$user_sensor."'" : " OR e.sensor = '".$user_sensor."'";
				//$sensor_str .= ($sensor_str == "") ? "e.sensor LIKE '".$user_sensor."%'" : " OR e.sensor LIKE '".$user_sensor."%'";
			}
			$sensor_str_alarm = " AND ($sensor_str) AND e.id=a.event_id";
			// snort
			$snortsensors = Event_viewer::GetSensorSids($temp_conn);
			$sensor_str = "";
			foreach ($user_sensors as $user_sensor)
				if (count($snortsensors[$user_sensor]) > 0) $sensor_str .= ($sensor_str != "") ? ",".implode(",",$snortsensors[$user_sensor]) : implode(",",$snortsensors[$user_sensor]);
			if ($sensor_str == "") $sensor_str = "0";
			$sensor_str_event = " AND acid_event.sid in (" . $sensor_str . ")";
		}
		
		// plugin groups filter
		$sql_plugin_groups='';

		if($plugin_groups!=null){
			switch ($type) {
				case "alarm":
					$nameTable='a';
					break;
				case "event":
				default:
					$nameTable='acid_event';
			}
			$groups = Plugingroup::get_list($this->ossim_conn,'plugin_group_descr.name="'.$plugin_groups.'"');
			foreach($groups as $group) {
				$sql_plugin_groups.='AND (';
				$i=1;
				foreach($group->get_plugins() as $p) {	
					if($i!==1){
						$sql_plugin_groups.=' OR ';
					}
					if($p['sids']=='0'||$p['sids']=='ANY'){
						$sql_plugin_groups.='('.$nameTable.'.plugin_id = '.$p['id'].')';
					}else{
						$sql_plugin_groups.='('.$nameTable.'.plugin_id = '.$p['id'].' AND '.$nameTable.'.plugin_sid in ('.$p['sids'].'))';
					}
					$i++;
				}
				$sql_plugin_groups.=')';
			}
		}
		//
		
		if ($sensor_str != "") {
			$sensor_join = " ,event e";
			$sql_where .= $sensor_str;
		} else $sensor_join = "";
		*/
		switch ($type) {
            case "alarm":
                $sensor_join = (preg_match("/e\.sensor/", $where)) ? ", event e" : "";
                $query = OssimQuery("SELECT a.plugin_id, a.plugin_sid, plugin_sid.name as sig_name, a.risk as ossim_risk_a FROM alarm a, plugin_sid$sensor_join
                  WHERE plugin_sid.plugin_id = a.plugin_id AND plugin_sid.sid = a.plugin_sid $where 
                  GROUP BY a.plugin_id, a.plugin_sid ORDER BY risk $order_type LIMIT $limit");
            	//
                //list($join2,$where2) = SecurityReport::make_join_where("a.","src_ip;dst_ip",$assets,$sourcetype,$category,$subcategory,1);
                //
                /*
                $query = OssimQuery("SELECT a.plugin_id, a.plugin_sid, plugin_sid.name as sig_name, a.risk as ossim_risk_a FROM alarm a $join2, plugin_sid$sensor_join
                  WHERE (a.timestamp BETWEEN '$from_date' AND '$to_date') AND plugin_sid.plugin_id = a.plugin_id AND plugin_sid.sid = a.plugin_sid$sensor_str_alarm $where2
                  $sql_plugin_groups GROUP BY a.plugin_id, a.plugin_sid ORDER BY risk $order_type LIMIT $limit");
				*/
                $temp_conn = $this->ossim_conn;
                break;

            case "event":
            default:
                //
                //list($join2,$where2) = SecurityReport::make_join_where("acid_event.","ip_dst;ip_src",$assets,$sourcetype,$category,$subcategory,1);
                // 
                /*
            	$query = OssimQuery("SELECT plugin_sid.name as sig_name, ossim_risk_a, acid_event.plugin_id, acid_event.plugin_sid
                  FROM acid_event $join2 LEFT JOIN ossim.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid WHERE (timestamp BETWEEN '$from_date' AND '$to_date') $sensor_str_event
                  $where2 $sql_plugin_groups GROUP BY acid_event.plugin_id, acid_event.plugin_sid
                  ORDER BY ossim_risk_a $order_type LIMIT $limit");
                */
            	$query = OssimQuery("SELECT plugin_sid.name as sig_name, ossim_risk_a, acid_event.plugin_id, acid_event.plugin_sid
                  FROM acid_event LEFT JOIN ossim.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid WHERE 1=1 $where 
                  GROUP BY acid_event.plugin_id, acid_event.plugin_sid
                  ORDER BY ossim_risk_a $order_type LIMIT $limit");
                $db = new ossim_db();
				$temp_conn = $db->snort_connect();
				//$temp_conn = $this->snort_conn;
        }

        //echo "<b>SQL</b>: $query<br>";
        
        if (!$rs = & $temp_conn->Execute($query)) {
            print $temp_conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                $rs->fields['sig_name'] = trim($rs->fields['sig_name']);
                if ($rs->fields['sig_name']=="")
                    $rs->fields['sig_name'] = Status::get_unknown_sid_name($this->ossim_conn,$rs->fields['plugin_id'],$rs->fields['plugin_sid']);

                $tmp_array = array(
                    $rs->fields["sig_name"],
                    $rs->fields["ossim_risk_a"]
                );
                array_push($list, $tmp_array);
                $rs->MoveNext();
            }
        }
		if ($type == "event") $db->close($temp_conn);
        return $list;
    }
	function EventsByPrio($limit = 10, $type = "event", $from_date, $to_date, $assets=array(), $sourcetype='', $category=0, $subcategory=0, $plugin_groups=null, $sensors=array(), $order_type = "DESC") {
        require_once ('classes/Event_viewer.inc');
        require_once ('classes/Status.inc');
		
		$list = array();
        $temp_conn = $this->snort_conn;
        
		list ($from_date,$to_date) = $this->normalize_date($from_date,$to_date);
		
		if ($order_type == "") $order_type = "DESC";
		
		// Taxonomy filters
		$plugin_list = Plugin_sid::get_all_sids($this->ossim_conn,null,$sourcetype,$category,$subcategory,$plugin_groups);
		// Make sql query and get data 
		if ($type == "event") {
			$where = SecurityReport::make_where($this->ossim_conn,$from_date,$to_date,$plugin_list,array("assets"=>$assets, "sensors"=>$sensors));
		} else {
			$where = SecurityReport::make_where_alarm($this->ossim_conn,$from_date,$to_date,$plugin_list,array("assets"=>$assets, "sensors"=>$sensors));
		}
		
		switch ($type) {
            case "alarm":
                return array();
                break;

            case "event":
            default:
                //
                //list($join2,$where2) = SecurityReport::make_join_where("acid_event.","ip_dst;ip_src",$assets,$sourcetype,$category,$subcategory,1);
                // 
                /*
            	$query = OssimQuery("SELECT plugin_sid.name as sig_name, ossim_priority, acid_event.plugin_id, acid_event.plugin_sid
                  FROM acid_event $join2 LEFT JOIN ossim.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid WHERE (timestamp BETWEEN '$from_date' AND '$to_date') $sensor_str_event
                  $where2 $sql_plugin_groups GROUP BY acid_event.plugin_id, acid_event.plugin_sid
                  ORDER BY ossim_priority $order_type LIMIT $limit");
                */
            	$query = OssimQuery("SELECT plugin_sid.name as sig_name, ossim_priority, acid_event.plugin_id, acid_event.plugin_sid
                  FROM acid_event LEFT JOIN ossim.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid WHERE 1=1 $where 
                  GROUP BY acid_event.plugin_id, acid_event.plugin_sid
                  ORDER BY ossim_priority $order_type LIMIT $limit");
                $db = new ossim_db();
				$temp_conn = $db->snort_connect();
				//$temp_conn = $this->snort_conn;
        }

        //echo "<b>SQL</b>: $query<br>";

        if (!$rs = & $temp_conn->Execute($query)) {
            print $temp_conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                $rs->fields['sig_name'] = trim($rs->fields['sig_name']);
                if ($rs->fields['sig_name']=="")
                    $rs->fields['sig_name'] = Status::get_unknown_sid_name($this->ossim_conn,$rs->fields['plugin_id'],$rs->fields['plugin_sid']);

                $tmp_array = array(
                    $rs->fields["sig_name"],
                    $rs->fields["ossim_priority"]
                );
                array_push($list, $tmp_array);
                $rs->MoveNext();
            }
        }
		if ($type == "event") $db->close($temp_conn);
        return $list;
    }
    function PromiscuousHost($conn, $limit = 10, $from_date, $to_date, $assets=array(), $sourcetype='', $category=0, $subcategory=0, $plugin_groups=null, $sensors=array(), $order_type = "DESC") {
        require_once ('classes/Host.inc');
        require_once ('classes/Net.inc');
        require_once ('classes/Session.inc');
		require_once ('classes/Event_viewer.inc');
		require_once ('classes/Plugingroup.inc');
        $list = array();
        $temp_conn = $this->snort_conn;
        
		list ($from_date,$to_date) = $this->normalize_date($from_date,$to_date);
		
		if ($order_type == "") $order_type = "DESC";
		
		// Taxonomy filters
		$plugin_list = Plugin_sid::get_all_sids($this->ossim_conn,null,$sourcetype,$category,$subcategory,$plugin_groups);
		// Make sql query and get data 
		$where = SecurityReport::make_where($this->ossim_conn,$from_date,$to_date,$plugin_list,array("assets"=>$assets, "sensors"=>$sensors));

        //list($join2,$where2) = SecurityReport::make_join_where("acid_event.","ip_src",$assets,$sourcetype,$category,$subcategory);
        //
        /*            
        $query = OssimQuery("select count(distinct(ip_dst)) as occurrences,INET_NTOA(ip_src) as ip from snort.acid_event WHERE (timestamp BETWEEN '$from_date' AND '$to_date') $sensor_str_event $where2
          $sql_plugin_groups GROUP BY ip ORDER BY occurrences $order_type LIMIT $limit");
        */
		$join = (count($plugin_list) > 0) ? "LEFT JOIN ossim.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid" : "";
		$query = OssimQuery("select count(distinct(ip_dst)) as occurrences,INET_NTOA(ip_src) as ip from snort.acid_event $join WHERE 1=1 $where 
        GROUP BY ip ORDER BY occurrences $order_type LIMIT $limit");
			
		//echo "<b>SQL</b>: $query<br>";
		
		if ($rs = & $temp_conn->Execute($query))
		{
			$i = 0;
			
            while (!$rs->EOF) {
				// sensor filter in query
				//if (Session::hostAllowed($conn, $rs->fields["ip"])) {
				if ($rs->fields['ip'] == 0) { $rs->MoveNext(); continue; }
				$ip_address = (is_numeric($rs->fields["ip"]))? long2ip($rs->fields["ip"]) : $rs->fields["ip"];
				$tmp_array = array(
					$ip_address,
					$rs->fields["occurrences"]
				);
				array_push($list, $tmp_array);
				$i+= 1;
                //}
                $rs->MoveNext();
                /* check limit */
                if ($i >= $limit) {
                    $rs->EOF = true;
                }
            }
        }
		
        return $list;
    }   
    function MultipleEventsHost($conn, $limit = 10, $from_date, $to_date, $assets=array(), $sourcetype='', $category=0, $subcategory=0, $plugin_groups=null, $sensors=array(), $order_type = "DESC") {
        require_once ('classes/Host.inc');
        require_once ('classes/Net.inc');
        require_once ('classes/Session.inc');
		require_once ('classes/Event_viewer.inc');
		require_once ('classes/Plugingroup.inc');
        $list = array();
        $temp_conn = $this->snort_conn;
        
		list ($from_date,$to_date) = $this->normalize_date($from_date,$to_date);
		
		if ($order_type == "") $order_type = "DESC";
		
		// Taxonomy filters
		$plugin_list = Plugin_sid::get_all_sids($this->ossim_conn,null,$sourcetype,$category,$subcategory,$plugin_groups);
		// Make sql query and get data 
		$where = SecurityReport::make_where($this->ossim_conn,$from_date,$to_date,$plugin_list,array("assets"=>$assets, "sensors"=>$sensors));
		
        //list($join2,$where2) = SecurityReport::make_join_where("acid_event.","ip_src",$assets,$sourcetype,$category,$subcategory);
        //
        /*            
        $query = OssimQuery("select count(distinct plugin_id,plugin_sid) as occurrences,INET_NTOA(ip_src) as ip from snort.acid_event WHERE (timestamp BETWEEN '$from_date' AND '$to_date') $sensor_str_event $where2
          $sql_plugin_groups GROUP BY ip ORDER BY occurrences $order_type LIMIT $limit");
        */
		$join = (count($plugin_list) > 0) ? "LEFT JOIN ossim.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid" : "";
		$query = OssimQuery("select count(distinct acid_event.plugin_id,acid_event.plugin_sid) as occurrences,INET_NTOA(ip_src) as ip from snort.acid_event $join WHERE 1=1 $where 
        GROUP BY ip ORDER BY occurrences $order_type LIMIT $limit");
			
		//echo "<b>SQL</b>: $query<br>";
		
		if ($rs = & $temp_conn->Execute($query))
		{
			$i = 0;
			
            while (!$rs->EOF) {
				// sensor filter in query
				//if (Session::hostAllowed($conn, $rs->fields["ip"])) {
				if ($rs->fields['ip'] == 0) { $rs->MoveNext(); continue; }
				$ip_address = (is_numeric($rs->fields["ip"]))? long2ip($rs->fields["ip"]) : $rs->fields["ip"];
				$tmp_array = array(
					$ip_address,
					$rs->fields["occurrences"]
				);
				array_push($list, $tmp_array);
				$i+= 1;
                //}
                $rs->MoveNext();
                /* check limit */
                if ($i >= $limit) {
                    $rs->EOF = true;
                }
            }
        }
		
        return $list;
    }
    function AttackerCountries($gi, $limit = 5, $from_date, $to_date, $ip, $assets=array(), $sourcetype='', $category=0, $subcategory=0, $plugin_groups=null, $sensors=array()) {
		$conn = $this->ossim_conn;
		
		// Taxonomy filters
		$plugin_list = Plugin_sid::get_all_sids($this->ossim_conn,null,$sourcetype,$category,$subcategory,$plugin_groups);
		// Make sql query and get data 
		$where = SecurityReport::make_where($this->ossim_conn,$from_date,$to_date,$plugin_list,array("assets"=>$assets, "sensors"=>$sensors));
		
    	$total = 0;
    	$list = array();
    	$list_ret = array();
    	$cnames = array();
    	$counts = array();
    	$temp_conn = $this->snort_conn;
    	//
        //list($join2,$where2) = SecurityReport::make_join_where("acid_event.","ip_src",$assets,$sourcetype,$category,$subcategory);
        //
    	//$query = "SELECT INET_NTOA(ip_src) as ip_src, count(ip_src) as occurrences FROM acid_event $join2 where ossim_risk_c >= 0 AND (timestamp BETWEEN '$from_date' AND '$to_date') AND ossim_risk_a >= 0 AND acid_event.plugin_id<>1505 AND ip_dst='$ip' $sensor_str_event $where2 $sql_plugin_groups GROUP BY ip_src ORDER BY occurrences DESC";
    	$join = (count($plugin_list) > 0) ? "LEFT JOIN ossim.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid" : "";
        $query = "SELECT INET_NTOA(ip_src) as ip_src, count(ip_src) as occurrences FROM acid_event $join where ossim_risk_c >= 0 AND ossim_risk_a >= 0 AND acid_event.plugin_id<>1505 AND ip_dst='$ip' $where GROUP BY ip_src ORDER BY occurrences DESC";
    	
        //echo "<b>SQL</b>: $query<br>";
        
        if ($rs = & $temp_conn->Execute($query))
		{
            while (!$rs->EOF && count($list) < $limit) {
				$ip_src = $rs->fields['ip_src'];
            	$s_country = strtolower(geoip_country_code_by_addr($gi, $ip_src));
            	$country_name = geoip_country_name_by_addr($gi, $ip_src);
            	if ($s_country == "") { $s_country = "unknown"; }
            	$list[$s_country] += $rs->fields['occurrences'];
            	$cnames[$s_country] = ($country_name != "") ? $country_name : _("Unknown Country");
            	$counts[$s_country]++;
            	$total += $rs->fields['occurrences'];
                $rs->MoveNext();
            }
        }
        arsort($list);
        // Percents
        foreach ($list as $c=>$val) {
        	$list_ret[$c]['perc'] = number_format($val*100/$total,2);
        	$list_ret[$c]['val'] = $val;
        	$list_ret[$c]['name'] = $cnames[$c];
        	$list_ret[$c]['ips'] = $counts[$c];
        	$list_ret[$c]['width'] = intval(($val*150)/$total)+1;
        }
        return $list_ret;
    }
    function Truncate($str, $length = 10, $trailing = '...') {
        // take off chars for the trailing
        $length-= strlen($trailing);
        if (strlen($str) > $length) {
            // string exceeded length, truncate and add trailing dots
            return substr($str, 0, $length) . $trailing;
        } else {
            // string was already short enough, return the string
            $res = $str;
        }
        return $res;
    }
    // 
    // special sql join and where
    //
    // Deprecated!!
    /*
    function make_join_where($prefix,$field,$assets,$sourcetype,$category,$subcategory,$leftjoin=0) {
    	$join = "";
    	$where = "";
    	// assets
    	if (is_array($assets)) {
    		foreach ($assets as $range => $asset_name) {
    			$ips = explode(",",$range);
    			$fields = explode(";",$field);
    			foreach ($fields as $field) {
    				$pfield = ($prefix!="") ? $prefix.$field : $field;
					if (count($ips)>1)
						$where .= " OR ($pfield>=".$ips[0]." AND $pfield<=".$ips[1].")";
					elseif (preg_match("/\d+\.\d+\.\d+\.\d+/",$ips[0])) // not numeric
						$where .= " OR inet_ntoa($pfield)='".$ips[0]."'";
					else
						$where .= " OR $pfield=".$ips[0];
				}
    		}
    		if ($where!="") $where = " AND (".preg_replace("/^ OR /","",$where).")";
    	}
    	// sourcetype
    	if ($sourcetype!="") {
    		$join .= " LEFT JOIN ossim.plugin ON plugin.id=".$prefix."plugin_id";
    		$where .= " AND plugin.source_type='".str_replace("'","\'",$sourcetype)."'";
    	}
    	// category & subcategory
    	if ($category!=0) {
    		if (!$leftjoin) $join .= " LEFT JOIN ossim.plugin_sid ON plugin_sid.plugin_id=".$prefix."plugin_id AND plugin_sid.sid=".$prefix."plugin_sid";
    		$where .= " AND plugin_sid.category_id=$category";
    		if ($subcategory!=0) $where .= " AND plugin_sid.subcategory_id=$subcategory";
    	}
    	if ($prefix=="acid_event.") $where .= " AND ".$prefix."plugin_id<>1505"; // without directive_event plugin
    	return array($join,$where);
    }
    */
	// make common where clause
	// Called from SIEM/List.php SIEM/TopEvents.php Various/EventsBy.php Various/DataSourceEvents.php Various/Geographic.php
	// Calls are in Various/general.php lib, in all methods of SecurityReport.inc
	function make_where($dbconn,$date_from,$date_to,$plugin_list,$dDB) {
        require_once ('classes/Event_viewer.inc');
		$assets = $dDB["assets"];
		$sensors = $dDB["sensors"];
		// make siem query
	    $sensor_where = "";
	    $plugins = array();
	    
	    // Plugin_sid::get_all_sids array structure
	    foreach ($plugin_list as $plugin_id=>$sid_list) {
	    	$sid_arr = array();
	    	foreach ($sid_list as $sid=>$val) {
	    		$sid_arr[] = $sid;
	    	}
	    	if (count($sid_arr) == 1 && $sid_arr[0] == 0) {
	    		$plugins[] = "acid_event.plugin_id=".$plugin_id;
	    	} else {
	    		$plugins[] = "(acid_event.plugin_id=".$plugin_id." AND acid_event.plugin_sid in (".implode(",",$sid_arr)."))";
	    	}
	    }
	    $plugin_where = (count($plugins)>0) ? "AND (".implode(" OR ",$plugins).")" : "";
	    
	    // sensor filter
		if (Session::allowedSensors() != "" || count($sensors)>0) {		
			$user_sensors = (Session::allowedSensors()=="" || !count($sensors)) ? array_merge(explode(",",Session::allowedSensors()),$sensors) : array_intersect(explode(",",Session::allowedSensors()), $sensors);
			$snortsensors = Event_Viewer::GetSensorSids($dbconn);
			$sensor_str = "";
			foreach ($user_sensors as $user_sensor)
				if (count($snortsensors[$user_sensor]) > 0)
					$sensor_str .= ($sensor_str != "") ? ",".implode(",",$snortsensors[$user_sensor]) : implode(",",$snortsensors[$user_sensor]);
			if ($sensor_str == "") $sensor_str = "0";
			$sensor_where = " AND acid_event.sid in (" . $sensor_str . ")";
		}
	    
		// assets filter
	    $ips = "";
		if (count($assets)>0) {
	        foreach ($assets as $asset => $assetname) {
	            $asst = explode(",",$asset);
	            if ($asst[1])
	                $ips .= " OR ((acid_event.ip_src>=".$asst[0]." AND acid_event.ip_src<=".$asst[1].") OR (acid_event.ip_dst>=".$asst[0]." AND acid_event.ip_dst<=".$asst[1]."))";
	            else
	                $ips .= " OR (acid_event.ip_src=".$asst[0]." OR acid_event.ip_dst=".$asst[0].")";
	        }
	        $ips = "AND (".preg_replace("/^ OR /","",$ips).")";
	    }

	    // date filter
	    if (!preg_match("/\d+\:\d+:\d+/",$date_from) && !preg_match("/\d+\:\d+:\d+/",$date_to)) {
	        list ($date_from,$date_to) = SecurityReport::normalize_date ($date_from,$date_to);
	    }
	    $where = "$plugin_where $sensor_where $ips AND (timestamp BETWEEN '$date_from' AND '$date_to')";
	    //print_r($where);
	    return $where;
	}
	// Same function for alarms queries (Events, Attack... with $type='alarm')
	// a is alarm table, e is event table
	function make_where_alarm($dbconn,$date_from,$date_to,$plugin_list,$dDB) {
		$assets = $dDB["assets"];
		$sensors = $dDB["sensors"];
		// make siem query
	    $sensor_where = "";
	    $plugins = array();
	    
	    // Plugin_sid::get_all_sids array structure
	    foreach ($plugin_list as $plugin_id=>$sid_list) {
	    	$sid_arr = array();
	    	foreach ($sid_list as $sid=>$val) {
	    		$sid_arr[] = $sid;
	    	}
	    	if (count($sid_arr) == 1 && $sid_arr[0] == 0) {
	    		$plugins[] = "a.plugin_id=".$plugin_id;
	    	} else {
	    		$plugins[] = "(a.plugin_id=".$plugin_id." AND a.plugin_sid in (".implode(",",$sid_arr)."))";
	    	}
	    }
	    $plugin_where = (count($plugins)>0) ? "AND (".implode(" OR ",$plugins).")" : "";
	    
	    // sensor filter
		if (Session::allowedSensors() != "" || count($sensors)>0) {
			$user_sensors = (Session::allowedSensors()=="" || !count($sensors)) ? array_merge(explode(",",Session::allowedSensors()),$sensors) : array_intersect(explode(",",Session::allowedSensors()), $sensors);
			foreach ($user_sensors as $user_sensor) if ($user_sensor!="") {
				$sensor_str .= ($sensor_str == "") ? "e.sensor = '".$user_sensor."'" : " OR e.sensor = '".$user_sensor."'";
			}
			$sensor_where = " AND ($sensor_str) AND e.id=a.event_id";
		}
	    
		// assets filter
	    $ips = "";
		if (count($assets)>0) {
	        foreach ($assets as $asset => $assetname) {
	            $asst = explode(",",$asset);
	            if ($asst[1])
	                $ips .= " OR ((a.src_ip>=".$asst[0]." AND a.src_ip<=".$asst[1].") OR (a.dst_ip>=".$asst[0]." AND a.dst_ip<=".$asst[1]."))";
	            else
	                $ips .= " OR (a.src_ip=".$asst[0]." OR a.dst_ip=".$asst[0].")";
	        }
	        $ips = "AND (".preg_replace("/^ OR /","",$ips).")";
	    }

	    // date filter
	    if (!preg_match("/\d+\:\d+:\d+/",$date_from) && !preg_match("/\d+\:\d+:\d+/",$date_to)) {
	        list ($date_from,$date_to) = SecurityReport::normalize_date ($date_from,$date_to);
	    }
	    $where = "$plugin_where $sensor_where $ips AND (a.timestamp BETWEEN '$date_from' AND '$date_to')";
	    //print_r($where);
	    return $where;
	}
}
?>
