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
*/
require_once ("ossim_db.inc");
class Wireless {

	// get bssid/ssid info from extra_data
	// plugin_id = 1596 Kismet, pluin_sids =1, 2, 3, 19
	// 
	// userdata1 == always SSID if available
	// userdata2 == always SSID/AP mac i/a
	// userdata3 == always client mac i/a
	// userdata4 == always client ip i/a
	// userdata5+== whatever
	function get_networks($conn,$order="",$sids="") {
		$networks = array();
		$data = array();
		$clients = array();
		$extra = array();
		if ($sids!="") $sids = " and o.sid in ($sids)";
		else return $data;
		$sql = "select e.* from extra_data e, ossim_event o where o.sid=e.sid and o.cid=e.cid and o.plugin_id=1596 and o.plugin_sid in (1,2) $sids and e.userdata1 is not null";
		if (!$rs = & $conn->Execute($sql)) {
			print $conn->ErrorMsg();
			return $data;
		}
		while (!$rs->EOF) {
			$ssid = str_replace("\"","",$rs->fields["userdata1"]);
			$mac = str_replace("\"","",$rs->fields["userdata2"]);
			$networks[$ssid][$mac]++;
			// clients
			if (!isset($clients[$ssid][$mac])) {
				$clients[$ssid][$mac] = 0;
				$sql = "(select count(distinct e.userdata3) as clients from extra_data e, ossim_event o where o.sid=e.sid and o.cid=e.cid and o.plugin_id=1596 and o.plugin_sid=3 $sids and e.userdata2='$mac') UNION (select count(distinct e.userdata4) as clients from extra_data e, ossim_event o where o.sid=e.sid and o.cid=e.cid and o.plugin_id=1596 and o.plugin_sid=19 $sids and e.userdata2='$mac')";
				//echo $sql; 
				if (!$rsc = & $conn->Execute($sql)) {
					print $conn->ErrorMsg();
					return $data;
				}
				while (!$rsc->EOF) {
					$clients[$ssid][$mac] += $rsc->fields["clients"];
					$rsc->MoveNext();
				}
			}
			//
			// extra data
			$extra[$ssid] = Wireless::get_network_data($ssid);
			$rs->MoveNext();
		}
		// merge all data
		foreach ($networks as $ssid => $arr) {
			$cls = 0; foreach ($clients[$ssid] as $cl) $cls+=$cl;
			$data[] = array("ssid" => $ssid, "aps" => $arr, "clients" => $cls, "extra" => $extra[$ssid]);
		}
		//
		if ($order=="ssid") Wireless::qsort2($data,$order);
		elseif ($order=="aps" || $order=="clients") Wireless::qsort2($data,$order,SORT_DESC);

		return $data;
	}
	
	// get wireless networks from wireless_network table
	function get_wireless_networks_info($conn,$order="",$sensors="") {
		$networks = array();
		$data = array();
		$clients = array();
		$extra = array();
		$encryption = $seen = $cloaked = array();
		#if ($sids!="") $sids = " and o.sid in ($sids)";
		#else return $data;
		if ($sensors!="") $sensors = " and w.sensor in ('".str_replace(",","','",$sensors)."')";
		$sql = "select w.ssid,w.description,w.type,w.notes as netnotes,ap.* from ossim.wireless_networks w,ossim.wireless_aps ap where w.ssid=ap.ssid and w.sensor=ap.sensor $sensors";
		if (!$rs = & $conn->Execute($sql)) {
			print $conn->ErrorMsg();
			return $data;
		}
		while (!$rs->EOF) {
			$ssid = $rs->fields["ssid"];
			$keyssid = ($ssid=="<no ssid>") ? "�<no ssid>" : $ssid;
			$mac = $rs->fields["mac"];
			if ($rs->fields["nettype"]=="infrastructure") $networks[$keyssid][$mac]++;
			// extra data
			$extra[$keyssid] = $rs->fields;
			// clients
			if (!isset($clients[$keyssid])) {
				$clients[$keyssid] = 0;
				$sql = "select count(distinct w.client_mac) as clients from ossim.wireless_clients w where w.ssid=? $sensors";
				//$sql = "(select count(distinct e.userdata3) as clients from extra_data e, ossim_event o where o.sid=e.sid and o.cid=e.cid and o.plugin_id=1596 and o.plugin_sid=3 $sids and e.userdata2='$mac') UNION (select count(distinct e.userdata4) as clients from extra_data e, ossim_event o where o.sid=e.sid and o.cid=e.cid and o.plugin_id=1596 and o.plugin_sid=19 $sids and e.userdata2='$mac')";
				//echo $sql; 
				if (!$rsc = & $conn->Execute($sql,array($ssid))) {
					print $conn->ErrorMsg();
					return $data;
				}
				if (!$rsc->EOF) {
					$clients[$keyssid] = $rsc->fields["clients"];
				}
			}
            // encryption
            $sql = "select distinct w.encryption from ossim.wireless_aps w where w.ssid=? $sensors";
            if (!$rsc = & $conn->Execute($sql,array($ssid))) {
            print $conn->ErrorMsg();
                return $data;
            }
            while (!$rsc->EOF) {
                $encs = explode(",",$rsc->fields["encryption"]);
                foreach ($encs as $enc) if ($enc!="") $encryption[$keyssid][$enc]++;
                $rsc->MoveNext();
            }
            // first and last seen
            $sql = "select min(firsttime) as firsttime,max(lasttime) as lasttime from ossim.wireless_aps w where w.ssid=? $sensors";
            if (!$rsc = & $conn->Execute($sql,array($ssid))) {
            print $conn->ErrorMsg();
                return $data;
            }
            if (!$rsc->EOF) {
                if (!isset($seen[$keyssid]["first"]) || strtotime($seen[$keyssid]["first"]) > strtotime($rsc->fields["firsttime"])) $seen[$ssid]["first"] = $rsc->fields["firsttime"];
                if (!isset($seen[$keyssid]["last"]) || strtotime($seen[$keyssid]["last"]) < strtotime($rsc->fields["lasttime"])) $seen[$ssid]["last"] = $rsc->fields["lasttime"];
            }
            // cloaked
            $sql = "select distinct w.cloaked from ossim.wireless_aps w where w.ssid=? $sensors";
            if (!$rsc = & $conn->Execute($sql,array($ssid))) {
            print $conn->ErrorMsg();
                return $data;
            }
            while (!$rsc->EOF) {
                $encs = explode(",",$rsc->fields["cloaked"]);
                foreach ($encs as $enc) if ($enc!="") $cloaked[$keyssid][$enc]++;
                $rsc->MoveNext();
            }
            //
			$rs->MoveNext();
		}
		// merge all data
		foreach ($networks as $ssid => $arr) {
			//$cls = 0; foreach ($clients[$ssid] as $cl) $cls+=$cl;
			$data[] = array(
                "ssid" => $ssid,
                "aps" => $arr,
                "clients" => $clients[$ssid],
                "encryption" => implode(" ",array_keys($encryption[$ssid])),
                "cloaked" => implode("/",array_keys($cloaked[$ssid])),
                "seen" => $seen[$ssid],
                "extra" => $extra[$ssid]
            );
		}
		//
		if ($order=="ssid") Wireless::qsort2($data,$order);
		elseif ($order=="aps" || $order=="clients") Wireless::qsort2($data,$order,SORT_DESC);

        // change � for <no ssid> networks
        if (count($data)>0) $data[count($data)-1]["ssid"] = str_replace("�","",$data[count($data)-1]["ssid"]);

		return $data;
	}

    // get wireless networks from wireless_network table
    function get_wireless_networks($conn,$order="",$sensors="",$timefilter="") {
        $data = $nossid = array();
        if ($sensors!="") $sensors = "and sensor in ('".str_replace(",","','",$sensors)."')";
        $trusted = $hideold = "";
        if ($_SESSION["trusted"]==2) {
            $trusted = "and type='Trusted'";
        } elseif ($_SESSION["trusted"]==3) {
            $trusted = "and type='Un-Trusted'";
        }
        if ($_SESSION["hideold"]==1) {
            $hideold = "and lasttime > '".date("Y-m-d H:i:s",strtotime("3 days ago"))."'";
        }
        if ($order!="") $order = "order by $order";
        $sql = "select * from wireless_networks where 1=1 $sensors $trusted $timefilter $hideold $order";
        
        //print_r($sql);
        
        if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
            return $data;
        }
        while (!$rs->EOF) {
            if ($rs->fields["ssid"]=="<no ssid>")
                $nossid = $rs->fields;
            else
                $data[] = $rs->fields;
            $rs->MoveNext();
        }
        if (count($nossid)>0) $data[] = $nossid;
        return $data;
    }

	// get wireless networks from wireless_network table
	function get_wireless_aps_networks($conn,$type=1,$sensors="",$timefilter="") {
		$networks = array();
		$data = array();
		$extra = array();
		$vendors = Wireless::get_mac_vendor_list($conn);
		if ($sensors!="") $sensors = " and w.sensor in ('".str_replace(",","','",$sensors)."')";
        $trusted = $hideold = "";
        if ($_SESSION["trusted"]==2) {
            $trusted = "and w.type='Trusted'";
        } elseif ($_SESSION["trusted"]==3) {
            $trusted = "and w.type='Un-Trusted'";
        }
        if ($_SESSION["hideold"]==1) {
            $hideold = "and w.lasttime > '".date("Y-m-d H:i:s",strtotime("3 days ago"))."'";
        }
        if ($type == 1) // cloked
            $sql = "select w.ssid,ap.* from ossim.wireless_networks w,ossim.wireless_aps ap where w.ssid=ap.ssid and w.sensor=ap.sensor $sensors $timefilter $trusted $hideold"; //  and ap.cloaked='No'
        elseif ($type == 2)  // encrypted
            $sql = "select w.ssid,ap.* from ossim.wireless_networks w,ossim.wireless_aps ap where w.ssid=ap.ssid and w.sensor=ap.sensor $sensors $timefilter $trusted $hideold"; //  and ap.encryption='None'
        elseif ($type == 3)  // weak
            $sql = "select w.ssid,ap.* from ossim.wireless_networks w,ossim.wireless_aps ap where w.ssid=ap.ssid and w.sensor=ap.sensor $sensors $timefilter $trusted $hideold and ap.encryption='WEP'";
		if (!$rs = & $conn->Execute($sql)) {
			print $conn->ErrorMsg();
			return $data;
		}
		while (!$rs->EOF) {
			$ssid = $rs->fields["ssid"];
			$keyssid = ($ssid=="<no ssid>") ? "�<no ssid>" : $ssid;
			$sensor = $rs->fields["sensor"];
			$mac = $rs->fields["mac"];
			$rs->fields["vendor"] = ($vendors[substr($mac,0,8)]!="") ? $vendors[substr($mac,0,8)] : "Unknown";
			// clients
			$sql = "select count(distinct w.client_mac) as clients from ossim.wireless_clients w where w.ssid=? and w.mac=? and sensor=?";
			//$sql = "(select count(distinct e.userdata3) as clients from extra_data e, ossim_event o where o.sid=e.sid and o.cid=e.cid and o.plugin_id=1596 and o.plugin_sid=3 $sids and e.userdata2='$mac') UNION (select count(distinct e.userdata4) as clients from extra_data e, ossim_event o where o.sid=e.sid and o.cid=e.cid and o.plugin_id=1596 and o.plugin_sid=19 $sids and e.userdata2='$mac')";
			if (!$rsc = & $conn->Execute($sql,array($ssid,$mac,$sensor))) {
				print $conn->ErrorMsg();
				return $aps;
			}
			while (!$rsc->EOF) {
				$rs->fields["clients"] += $rsc->fields["clients"];
				$rsc->MoveNext();
			}
			// Net Type
			//if ($rs->fields["nettype"]=="infrastructure") $rs->fields["nettype"] = "<img src='../pixmaps/network-adapters.png' border=0 alt='Infrastructure' title='Infrastructure'>";
			//elseif ($rs->fields["nettype"]=="probe") $rs->fields["nettype"] = "<img src='../pixmaps/laptop.png' border=0 alt='Probe' title='Probe'>";
			//else $rs->fields["nettype"] = "<img src='../pixmaps/otherwifi.png' border=0 alt='Other' title='Other'>";
			//
			$networks[$keyssid][$mac] = $rs->fields;
			$rs->MoveNext();
		}
		// merge all data
		foreach ($networks as $ssid => $arr) {
            $data[] = array(
                "ssid" => $ssid,
                "aps" => $arr
            );
		}
		//
		Wireless::qsort2($data,"ssid");
        // change � for <no ssid> networks
        if (count($data)>0) $data[count($data)-1]["ssid"] = str_replace("�","",$data[count($data)-1]["ssid"]);

		return $data;
	}

	// get aps info from extra_data
	function get_aps($conn,$ssid,$sids="") {
		$aps = array();
		if ($sids!="") $sids = " and o.sid in ($sids)";
		$vendors = Wireless::get_mac_vendor_list($conn);
		$sql = "select e.* from extra_data e, ossim_event o where o.sid=e.sid and o.cid=e.cid and o.plugin_id=1596 and o.plugin_sid in (1,2) $sids and e.userdata1='$ssid'";
		//echo $sql;
		if (!$rs = & $conn->Execute($sql)) {
			print $conn->ErrorMsg();
			return $data;
		}
		while (!$rs->EOF) {
			$sid = $rs->fields["sid"];
			$cid = $rs->fields["cid"];
			$mac = str_replace("\"","",$rs->fields["userdata2"]);
			$channel = str_replace("\"","",$rs->fields["userdata6"]);
			$freq = ($channel <= 20) ? 2.4 : 5;
			if ($channel=="") $channel = "-";
			$mbit = intval($rs->fields["userdata7"]);
			$type = ($mbit<=11) ? "B" : (($mbit<=54) ? "G" : "N");
			$vendor = ($vendors[substr($mac,0,8)]!="") ? $vendors[substr($mac,0,8)] : "Unknown";
			// clients
			$clients = 0;
			$sql = "(select count(distinct e.userdata3) as clients from extra_data e, ossim_event o where o.sid=e.sid and o.cid=e.cid and o.plugin_id=1596 and o.plugin_sid=3 $sids and e.userdata2='$mac') UNION (select count(distinct e.userdata4) as clients from extra_data e, ossim_event o where o.sid=e.sid and o.cid=e.cid and o.plugin_id=1596 and o.plugin_sid=19 $sids and e.userdata2='$mac')";
			if (!$rsc = & $conn->Execute($sql)) {
				print $conn->ErrorMsg();
				return $aps;
			}
			while (!$rsc->EOF) {
				$clients += $rsc->fields["clients"];
				$rsc->MoveNext();
			}
			//
			// fechas
			$first = $last = "";
			$sql = "select min(timestamp) as first,max(timestamp) as last from acid_event where sid=$sid and cid=$cid";
			//echo $sql;
			if (!$rsc = & $conn->Execute($sql)) {
				print $conn->ErrorMsg();
				return $aps;
			}
			if (!$rsc->EOF) {
				$first = $rsc->fields["first"];
				$last = $rsc->fields["last"];
			}
			//
			$aps[$mac] = array(
				"mac" => $mac,
				"vendor" => $vendor,
				"clients" => $clients,
				"channel" => $channel,
				"first" => $first,
				"last" => $last,
				"freq" => $freq,
				"type" => $type
			);
			$rs->MoveNext();
		}
		return $aps;
	}
	
	// get wireless aps info from wireless_aps table
	function get_wireless_aps($conn,$ssid,$sensor="") {
		$aps = array();
		if ($sensor!="") $sensor = " and sensor='$sensor'";
		$vendors = Wireless::get_mac_vendor_list($conn);
		$sql = "select * from ossim.wireless_aps where ssid=? $sensor";
		//echo $sql;
		if (!$rs = & $conn->Execute($sql,array($ssid))) {
			print $conn->ErrorMsg();
			return $data;
		}
		while (!$rs->EOF) {
			$data = $rs->fields;
			$mac = $rs->fields["mac"];
			$data["vendor"] = ($vendors[substr($mac,0,8)]!="") ? $vendors[substr($mac,0,8)] : "Unknown";
			// clients
			$sql = "select count(distinct w.client_mac) as clients from ossim.wireless_clients w where w.ssid=? and w.mac=? $sensor";
			//$sql = "(select count(distinct e.userdata3) as clients from extra_data e, ossim_event o where o.sid=e.sid and o.cid=e.cid and o.plugin_id=1596 and o.plugin_sid=3 $sids and e.userdata2='$mac') UNION (select count(distinct e.userdata4) as clients from extra_data e, ossim_event o where o.sid=e.sid and o.cid=e.cid and o.plugin_id=1596 and o.plugin_sid=19 $sids and e.userdata2='$mac')";
			if (!$rsc = & $conn->Execute($sql,array($ssid,$mac))) {
				print $conn->ErrorMsg();
				return $aps;
			}
			while (!$rsc->EOF) {
				$data["clients"] += $rsc->fields["clients"];
				$rsc->MoveNext();
			}
			// Net Type
			if ($data["nettype"]=="infrastructure") $data["nettype"] = "<img src='../pixmaps/network-adapters.png' border=0 alt='Infrastructure' title='Infrastructure'>";
			elseif ($data["nettype"]=="probe") $data["nettype"] = "<img src='../pixmaps/laptop.png' border=0 alt='Probe' title='Probe'>";
			else $data["nettype"] = "<img src='../pixmaps/otherwifi.png' border=0 alt='Other' title='Other'>";
			//
			$aps[$mac] = $data;
			$rs->MoveNext();
		}
		return $aps;
	}

	// get ap notes from wireless_aps table
	function get_ap_data($conn,$mac) {
		$data = array();
		$sql = "select * from wireless_aps where mac=?";
		if (!$rsc = & $conn->Execute($sql,array($mac))) {
			print $conn->ErrorMsg();
			return $data;
		}
		if (!$rsc->EOF) $data = $rsc->fields;
		return $data;
	}
	
	// update extra info for ap
	function update_ap_data($conn,$mac,$ssid,$sensor,$notes) {
		$sql = "insert into wireless_aps (mac,ssid,sensor,notes) values (?,?,?,?) on duplicate key update notes=?";
		$params = array( $mac, $ssid, $sensor, $notes, $notes );
		if ($conn->Execute($sql, $params) === false) {
			print $conn->ErrorMsg();
		}
	}
	
	// get clients info from extra_data
	// plugin_id = 1596, pluin_sid =19 => without encrypt
	// plugin_id = 1596, pluin_sid =3 => with encrypt (mac)
	function get_clients($conn,$order="",$sids="",$aps="") {
		require_once 'ossim_db.inc';
		$db = new ossim_db();
		$ossim = $db->connect();
		$data = array();
		if ($sids!="") $sids = " and o.sid in ($sids)";
		else return $data;
		$vendors = Wireless::get_mac_vendor_list();
		if ($aps!="") $aps = " and e.userdata2 in ('".str_replace(",","','",$aps)."')";
		$sql = "select distinct o.plugin_sid,e.userdata2,e.userdata3,e.userdata4 from extra_data e, ossim_event o where o.sid=e.sid and o.cid=e.cid and o.plugin_id=1596 and o.plugin_sid not in (1,2) $sids $aps";
		//$sql = "select e.* from extra_data e, ossim_event o where o.sid=e.sid and o.cid=e.cid and o.plugin_id=1596 and o.plugin_sid=19 $sids";
		//echo $sql;
		if (!$rs = & $conn->Execute($sql)) {
			print $conn->ErrorMsg();
			return $data;
		}
		while (!$rs->EOF) {
			$ssid = str_replace("\"","",$rs->fields["userdata2"]);
			$mac = str_replace("\"","",$rs->fields["userdata3"]);
			$sid = $rs->fields["plugin_sid"];
			if ($rs->fields["userdata4"]!="") {
				$ip = $rs->fields["userdata4"];
				// wit ip
				// data from ossim host table
				$name = "Unknown";
				$desc = "";
				$sql = "select * from host where ip=?";
				if (!$rsc = & $ossim->Execute($sql,array($ip))) {
					print $ossim->ErrorMsg();
					return $data;
				}
				if (!$rsc->EOF) { 
					$name = $rsc->fields["hostname"];
					$desc =  $rsc->fields["desc"];
				}
				$mac = "";
				$sql = "select mac from host_mac where inet_ntoa(ip) = ?";
				if (!$rsc = & $ossim->Execute($sql,array($mac))) {
					print $ossim->ErrorMsg();
					return $data;
				}
				if (!$rsc->EOF) $mac = $rsc->fields["mac"];

			} else {
				// without ip, only mac
				$ip = "";
				// data from ossim host table
				$sql = "select inet_ntoa(ip) as ip from host_mac where mac = ?";
				if (!$rsc = & $ossim->Execute($sql,array($mac))) {
					print $ossim->ErrorMsg();
					return $data;
				}
				if (!$rsc->EOF) $ip = $rsc->fields["ip"];
				$name = "Unknown";
				$desc = "";
				$sql = "select * from host where ip='$ip'";
				if (!$rsc = & $ossim->Execute($sql)) {
					print $ossim->ErrorMsg();
					return $data;
				}
				if (!$rsc->EOF) { 
					$name = $rsc->fields["hostname"];
					$desc =  $rsc->fields["desc"];
				}
			}
			//
			$notes = "";
			$data[] = array("name" => $name, "mac" => $mac, vendor => $vendors[substr($mac,0,8)], "ip" => $ip, "description" => $desc, "connected" => $ssid, "notes" => $notes, "sid" => $sid );
			$rs->MoveNext();
		}
		if ($order=="name" || $order=="ip" || $order=="connected") Wireless::qsort2($data,$order);
		$db->close($conn);
		return $data;
	}

	// get_ssids => mac/ssids
	function get_ssids($conn,$sids="") {
		$networks = array();
		if ($sids!="") $sids = " and o.sid in ($sids)";
		else return $networks;
		$sql = "select distinct e.userdata1,e.userdata2 from extra_data e, ossim_event o where o.sid=e.sid and o.cid=e.cid and o.plugin_id=1596 and o.plugin_sid in (1,2) $sids and e.userdata1 is not null";
		if (!$rs = & $conn->Execute($sql)) {
			print $conn->ErrorMsg();
			return $data;
		}
		while (!$rs->EOF) {
			$networks[$rs->fields["userdata2"]] = $rs->fields["userdata1"];
			$rs->MoveNext();
		}
		return $networks;
	}

	// get_ssids => mac/ssids
	function get_plugin_sids($conn) {
		$plugin = array();
		$sql = " SELECT sid,name FROM plugin_sid where plugin_id=1596";
		if (!$rs = & $conn->Execute($sql)) {
			print $conn->ErrorMsg();
			return $plugin;
		}
		while (!$rs->EOF) {
			$plugin[$rs->fields["sid"]] = str_replace("Kismet: ","",$rs->fields["name"]);
			$rs->MoveNext();
		}
		return $plugin;
	}

	// get unique clients info from extra_data
	// group by client mac and sid
	// plugin_id = 1596, pluin_sid <>1,2
	function get_unique_clients($conn,$order="",$sids="",$aps="") {
		require_once 'ossim_db.inc';
		$db = new ossim_db();
		$ossim = $db->connect();
		$data = array();
		$acdata = array();
		$clients = array();
		$ssids = Wireless::get_ssids($conn,$sids);
		$vendors = Wireless::get_mac_vendor_list($ossim);
		if ($sids!="") $sids = " and o.sid in ($sids)";
		else return $data;
		if ($aps!="") $aps = " and e.userdata2 in ('".str_replace(",","','",$aps)."')";
		$sql = "select distinct o.plugin_sid,e.userdata2,e.userdata3,e.userdata4 from extra_data e, ossim_event o where o.sid=e.sid and o.cid=e.cid and o.plugin_id=1596 and o.plugin_sid not in (1,2) $sids $aps";
		//$sql = "select e.* from extra_data e, ossim_event o where o.sid=e.sid and o.cid=e.cid and o.plugin_id=1596 and o.plugin_sid=19 $sids";
		//echo $sql;
		if (!$rs = & $conn->Execute($sql)) {
			print $conn->ErrorMsg();
			return $data;
		}
		while (!$rs->EOF) {
			$ssid = str_replace("\"","",$rs->fields["userdata2"]);
			$mac = str_replace("\"","",$rs->fields["userdata3"]);
			$sid = $rs->fields["plugin_sid"];
			if ($rs->fields["userdata4"]!=""  && !preg_match("/null/",$rs->fields["userdata4"])) {
				$ip = $rs->fields["userdata4"];
				// wit ip
				// data from ossim host table
				$name = "Unknown";
				$desc = "";
				$sql = "select * from host where ip=?";
				if (!$rsc = & $ossim->Execute($sql,array($ip))) {
					print $ossim->ErrorMsg();
					return $data;
				}
				if (!$rsc->EOF) { 
					$name = $rsc->fields["hostname"];
					$desc =  $rsc->fields["desc"];
				}
				$mac = "";
				$sql = "select mac from host_mac where inet_ntoa(ip) = ?";
				if (!$rsc = & $ossim->Execute($sql,array($mac))) {
					print $ossim->ErrorMsg();
					return $data;
				}
				if (!$rsc->EOF) $mac = $rsc->fields["mac"];

			} else {
				// without ip, only mac
				$ip = "";
				// data from ossim host table
				$sql = "select inet_ntoa(ip) as ip from host_mac where mac = ?";
				if (!$rsc = & $ossim->Execute($sql,array($mac))) {
					print $ossim->ErrorMsg();
					return $data;
				}
				if (!$rsc->EOF) $ip = $rsc->fields["ip"];
				$name = "Unknown";
				$desc = "";
				$sql = "select * from host where ip='$ip'";
				if (!$rsc = & $ossim->Execute($sql)) {
					print $ossim->ErrorMsg();
					return $data;
				}
				if (!$rsc->EOF) { 
					$name = $rsc->fields["hostname"];
					$desc =  $rsc->fields["desc"];
				}
			}
			//
			$notes = "";
			$key = ($mac!="") ? $mac : $ip;
			$clients[$key] = array("name" => $name, "mac" => $mac, vendor => $vendors[substr($mac,0,8)], "ip" => $ip, "description" => $desc, "notes" => $notes );
			$bssid = ($ssids[$ssid]!="") ? preg_replace("/\<|\>/","",$ssid." [".$ssids[$ssid]."]") : $ssid;
			$acdata[$key]["connected"][$bssid]++;
			$acdata[$key]["sids"][$sid]++;
			$rs->MoveNext();
		}
		// merge data
		foreach ($clients as $key => $arr) {
			$arr["connected"] = array_keys($acdata[$key]["connected"]);
			$arr["sids"] = array_keys($acdata[$key]["sids"]);
			$data[] = $arr;
		}
		if ($order=="name" || $order=="ip") Wireless::qsort2($data,$order);
		$db->close($conn);
		return $data;
	}

    // get unique clients info from wireless_clients
    function get_wireless_clients($conn,$order="",$sensors="",$aps="",$timefilter="") {
        $data = array();
        $acdata = array();
        $clients = array();
        $vendors = Wireless::get_mac_vendor_list($conn);
        $hosts = Wireless::resolve_hosts($conn);
        if ($sensors!="") $sensors = " and c.sensor in ('".str_replace(",","','",$sensors)."')";
        if ($aps!="") $aps = " and c.mac in ('".str_replace(",","','",$aps)."')";
        $hideold = "";
        if ($_SESSION["hideold"]==1) {
            $hideold = "and c.lasttime > '".date("Y-m-d H:i:s",strtotime("3 days ago"))."'";
        }
        if ($order!="") $order = "order by c.$order desc";
        //
        $sql = "select * from wireless_clients c where 1=1 $sensors $timefilter $aps $trusted $hideold $order";
        if ($_SESSION["trusted"]==2) {
            $sql = "select c.* from wireless_clients c,wireless_networks n where n.ssid=c.ssid and n.sensor=c.sensor and n.type='Trusted' $sensors $timefilter $aps $trusted $hideold $order";
        } elseif ($_SESSION["trusted"]==3) {
            $sql = "select c.* from wireless_clients c,wireless_networks n where n.ssid=c.ssid and n.sensor=c.sensor and n.type='Un-Trusted' $sensors $timefilter $aps $trusted $hideold $order";
        } elseif ($_SESSION["hideold"]==1) {
            $sql = "select c.* from wireless_clients c,wireless_networks n where n.ssid=c.ssid and n.sensor=c.sensor $sensors $timefilter $aps $trusted $hideold $order";
        }
        //echo $sql;
        if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
            return $data;
        }
        while (!$rs->EOF) {
            $ssid = $rs->fields["ssid"];
            $mac = $rs->fields["client_mac"];
            $sid = $rs->fields["plugin_sid"]; if ($sid=="") $sid=0;
            $ip = $rs->fields["ip"];
            $name = ($hosts[$ip]!="") ? $hosts[$ip] : "Unknown";
            $key = ($mac!="") ? $mac : $ip;
            if ($_SESSION["knownmac"]==2 || !isset($_SESSION["knownmac"]) || ($_SESSION["knownmac"]==1 && $vendors[substr($mac,0,8)]!="")) {
                $clients[$key] = array(
                    "name" => $name,
                    "mac" => $mac,
                    "ssid" => $ssid,
                    "sensor" => $rs->fields["sensor"],
                    "vendor" => $vendors[substr($mac,0,8)],
                    "ip" => $ip,
                    "encryption" => $rs->fields["encryption"],
                    "type" => $rs->fields["type"],
                    "encoding" => $rs->fields["encoding"],
                    "firsttime" => $rs->fields["firsttime"],
                    "lasttime" => $rs->fields["lasttime"],
                    "sid" => $sid,
                    "notes" => $rs->fields["notes"] );
                $bssid = preg_replace("/\<|\>/","",$rs->fields["mac"]." [$ssid]");
                $acdata[$key]["connected"][$bssid]++;
                $acdata[$key]["sids"][$sid]++;
            }
            $rs->MoveNext();
        }
        // merge data
        foreach ($clients as $key => $arr) {
            $arr["connected"] = array_keys($acdata[$key]["connected"]);
            $arr["sids"] = array_keys($acdata[$key]["sids"]);
            $data[] = $arr;
        }
        //if ($order=="name" || $order=="ip") Wireless::qsort2($data,$order);
        return $data;
    }

	// get extra info for netword ssid
	function get_network_data($conn,$ssid,$sensor) {
		$data = array();
		$sql = "select * from wireless_networks where ssid=? and sensor=?";
		if (!$rsc = & $conn->Execute($sql,array($ssid,$sensor))) {
			print $conn->ErrorMsg();
			return $data;
		}
		if (!$rsc->EOF) $data = $rsc->fields;
		return $data;
	}

	// update extra info for netword ssid
	function update_network_data($conn,$ssid,$sensor,$desc,$type,$notes) {
		$sql = "insert into wireless_networks (ssid,sensor,description,type,notes) values (?,?,?,?,?) on duplicate key update description=?, type=?, notes=?";
		$params = array( $ssid, $sensor, $desc, $type, $notes, $desc, $type, $notes);
		if ($conn->Execute($sql, $params) === false) {
			print $conn->ErrorMsg();
		}
	}
	
	// get locations/stores
	function get_locations($conn,$location="") {
		$data = array();
		//$user = ($_SESSION["_user"]!="admin") ? " and user='".$_SESSION["_user"]."'" : "";
		$user = " and user='".$_SESSION["_user"]."'";
		if ($location!="") {
			$sql = "select * from wireless_locations where location=? $user";
			$rs = &$conn->Execute($sql, array($location));
		} else {
			$sql = "select * from wireless_locations where 1=1 $user";
			$rs = &$conn->Execute($sql);
		}
		if (!$rs) {
			print $conn->ErrorMsg();
			return $data;
		}
		while (!$rs->EOF) {
			$arr = $rs->fields;
			// clients
			$sql = "select distinct w.*,s.ip,h.mac from wireless_sensors w,sensor s 
			LEFT JOIN host_mac h ON inet_ntoa(h.ip) = s.ip
			where s.name=w.sensor and w.location='".$arr["location"]."'";
			if (!$rsc = & $conn->Execute($sql)) {
				print $conn->ErrorMsg();
				return $data;
			}
			$sensors = array();
			while (!$rsc->EOF) {
				$sensors[] = $rsc->fields;
				$rsc->MoveNext();
			}
			$arr["sensors"] = $sensors;
			$data[] = $arr;
			$rs->MoveNext();
		}
		return $data;
	}
	
	// add locations/stores
	function add_location($conn,$location,$desc) {
		$sql = "insert ignore into wireless_locations (location,user,description) values (?,?,?)";
		$params = array( $location, $_SESSION["_user"], $desc);
		if ($conn->Execute($sql, $params) === false) {
			print $conn->ErrorMsg();
		}
	}

	// delete locations/stores
	function del_location($conn,$location) {
		$sql = "delete from wireless_locations where location=?";
		$params = array( $location );
		if ($conn->Execute($sql, $params) === false) {
			print $conn->ErrorMsg();
		}
		$sql = "delete from wireless_sensors where location=?";
		$params = array( $location );
		if ($conn->Execute($sql, $params) === false) {
			print $conn->ErrorMsg();
		}		
	}

	// add location sensors
	function add_locations_sensor($conn,$location,$sensor,$model,$serial,$mounting) {
		require_once 'classes/Sensor.inc';
		$ossim_sensors = Sensor::get_list($conn,"s, sensor_properties p WHERE s.ip=p.ip AND p.has_kismet=1");
		$perms = false; foreach ($ossim_sensors as $ss) if ($ss->get_name()==$sensor) $perms=true;
		if ($perms) {
			$sql = "insert into wireless_sensors (location,sensor,model,serial,mounting_location) values (?,?,?,?,?) on duplicate key update model=?,serial=?,mounting_location=?";
			$params = array( $location, $sensor, $model, $serial, $mounting, $model, $serial, $mounting);
			if ($conn->Execute($sql, $params) === false) {
				print $conn->ErrorMsg();
			}
		}
	}

	// delete location sensors
	function del_locations_sensor($conn,$location,$sensor) {
		$sql = "delete from wireless_sensors where location=? and sensor=?";
		$params = array( $location, $sensor );
		if ($conn->Execute($sql, $params) === false) {
			print $conn->ErrorMsg();
		}
	}

	// delete network
	function del_network($conn,$ssid,$sensor) {
		$sql = "delete from wireless_networks where ssid=? and sensor=?";
		$params = array( $ssid, $sensor );
		if ($conn->Execute($sql, $params) === false) {
			print $conn->ErrorMsg();
		}
		$sql = "delete from wireless_aps where ssid=? and sensor=?";
		$params = array( $ssid, $sensor );
		if ($conn->Execute($sql, $params) === false) {
			print $conn->ErrorMsg();
		}
		$sql = "delete from wireless_clients where ssid=? and sensor=?";
		$params = array( $ssid, $sensor );
		if ($conn->Execute($sql, $params) === false) {
			print $conn->ErrorMsg();
		}
	}

	// delete ap
	function del_ap($conn,$mac,$ssid,$sensor) {
		$sql = "delete from wireless_aps where mac=? and ssid=? and sensor=?";
		$params = array( $mac, $ssid, $sensor );
		if ($conn->Execute($sql, $params) === false) {
			print $conn->ErrorMsg();
		}
		$sql = "delete from wireless_clients where mac=? and ssid=? and sensor=?";
		$params = array( $mac, $ssid, $sensor );
		if ($conn->Execute($sql, $params) === false) {
			print $conn->ErrorMsg();
		}
	}

	// delete client
	function del_clients($conn,$client_mac,$sensor,$ssid="") {
		if ($ssid!="") {
            $sql = "delete from wireless_clients where client_mac=? and sensor=? and ssid=?";
            $params = array( $client_mac, $sensor, $ssid );
        } else {
            $sql = "delete from wireless_clients where client_mac=? and sensor=?";
            $params = array( $client_mac, $sensor);
        }
		if ($conn->Execute($sql, $params) === false) {
			print $conn->ErrorMsg();
		}
	}

	// gets sids from snort sensor for kismet plugin
	function get_sids($conn,$sensor_list) {
		$sids = array();
		foreach ($sensor_list as $sensor) {
			$sql = "select sid from sensor where hostname like '$sensor-kismet'";
			if (!$rsc = & $conn->Execute($sql)) {
				print $conn->ErrorMsg();
				return "";
			}
			if (!$rsc->EOF) $sids[] = $rsc->fields["sid"];
		}
		return implode(",",array_unique($sids));
	}

	function get_firstevent_date ($conn,$ip) {
		$sids = Wireless::get_sids($conn,array($ip));
		if ($sids!="") $sids = " and sid in ($sids)";
		$sql = "select timestamp from acid_event where plugin_id=1596 $sids order by timestamp limit 1";
		if (!$rs = & $conn->Execute($sql)) {
			print $conn->ErrorMsg();
			return "";
		}
		if (!$rs->EOF) return $rs->fields["timestamp"];
	}

	function get_events($conn,$sids="") {
		$data = array();
		if ($sids!="") $sids = "AND acid_event.sid in ($sids)";
		else return $data;
		$sql = "SELECT DISTINCT count(*) as sig_cnt, min(timestamp), max(timestamp), plugin_sid.name as sig_name, count(DISTINCT(acid_event.sid)), count(DISTINCT(ip_src)), count(DISTINCT(ip_dst)) FROM acid_event LEFT JOIN ossim.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid  WHERE 1 $sids GROUP BY acid_event.plugin_id,acid_event.plugin_sid";
		if (!$rs = &$conn->Execute($sql)) {
			print $conn->ErrorMsg();
			return $data;
		}
		while (!$rs->EOF) {
			$data[] = array(
				"signature" => $rs->fields["sig_name"],
				"total" => $rs->fields["sig_cnt"],
				"sensor" => $rs->fields[5],
				"src" => $rs->fields[6],
				"dst" => $rs->fields[7],
				"first" => $rs->fields[2],
				"last" => $rs->fields[3],
			);
			$rs->MoveNext();
		}
		return $data;
	}
	// get_mac_vendors array
	function get_mac_vendor_list($conn) {
		$vendors = array(); // first load vendors
		$sql = "SELECT * from ossim.host_mac_vendors";
		if (!$aux = & $conn->Execute($sql)) {
			print $conn->ErrorMsg();
			return $vendors;
		}
		do {
			$mac = $aux->fields["mac"];
			$vendor = $aux->fields["vendor"];
			$vendors[$mac] = $vendor;
			$aux->MoveNext();
		}
		while (!$aux->EOF);
		return $vendors;
	}

	function resolve_hosts($conn) {
		$hosts = array(); // first load vendors
		$sql = "SELECT * from ossim.host";
		if (!$aux = & $conn->Execute($sql)) {
			print $conn->ErrorMsg();
			return $hosts;
		}
		do {
			$hosts[$aux->fields["ip"]] = $aux->fields["hostname"];
			$aux->MoveNext();
		}
		while (!$aux->EOF);
		return $hosts;
	}
    
	// multiarray quicksort
	function qsort2 (&$array, $column=0, $order=SORT_ASC, $first=0, $last=-2) { 
		// $array  - the array to be sorted
		// $column - index (column) on which to sort can be a string if using an asso
		// $order  - SORT_ASC (default) for ascending or SORT_DESC for descending    
		// $first  - start index (row) for partial array sort 
		// $last  - stop  index (row) for partial array sort  

		if($last == -2) $last = count($array) - 1;
		if($last > $first) { 
			$alpha = $first;   
			$omega = $last;    
			$guess = $array[$alpha][$column];
			while($omega >= $alpha) { 
				#if($order == SORT_ASC) {
				#	while($array[$alpha][$column] < $guess) $alpha++;
				#	while($array[$omega][$column] > $guess) $omega--;
				#} else { 
				#	while($array[$alpha][$column] > $guess) $alpha++;
				#	while($array[$omega][$column] < $guess) $omega--;
				#} 
				if($order == SORT_ASC) {
					while(strcasecmp($array[$alpha][$column],$guess)<0) $alpha++;
					while(strcasecmp($array[$omega][$column],$guess)>0) $omega--;
				} else { 
					while(strcasecmp($array[$alpha][$column],$guess)>0) $alpha++;
					while(strcasecmp($array[$omega][$column],$guess)<0) $omega--;
				} 
				if($alpha > $omega) break;
				$temporary = $array[$alpha];
				$array[$alpha++] = $array[$omega];
				$array[$omega--] = $temporary;  
			} 
			Wireless::qsort2 ($array, $column, $order, $first, $omega);
			Wireless::qsort2 ($array, $column, $order, $alpha, $last); 
		}
	}
    
    function date_color($date,$limit=1) {
        if ($limit==1) {
            if (time()-strtotime($date)<=86400) return "#3A6F29";
            return "";
        }
        if ($limit==2) {
            if (time()-strtotime($date)>86400) return "#8F231A";
            return "";
        }
        return "";
    }
    // 
	function extractWord($string,$MaxString,$c=" "){
	    $word1 = substr($string, 0, $MaxString);
	    $word2 = substr($string, $MaxString);
	
	    if(strlen($word2)>$MaxString){
	        $word2=Wireless::extractWord($word2,$MaxString,$c);
	    }
	
	    return $word1.$c.$word2;
	}
	function truncate_string($string,$MaxString=15) {
	    $result = "";
	    $string = str_replace("<", " <", $string);
	    $words = explode(" ",$string);
	    foreach($words as $word) {
	        if(strlen($word)<=$MaxString) {
	            $result.= $word." ";
	        } else if (preg_match("/.*http.*/",$word)){
	            $w1 = substr($word,0,35);
	            $w2 = substr($word,-30);
	            $result .= $w1."...".$w2."<br> ";
	        } else {            
	            $result .= Wireless::extractWord($word,$MaxString);
	        }
	    }
	    return $result;
	}
}

?>