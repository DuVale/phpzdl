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
* - get_date_str()
* - get_range_csv()
* Classes list:
*/
function get_date_str($year = "", $month = "", $day = "", $hour = "") {
    if ($month == "") return $year;
    elseif ($day == "") return strftime("%b", mktime(0, 0, 0, $month+1, 0, 0)).", " . $year;
    elseif ($hour == "") return strftime("%b", mktime(0, 0, 0, $month+1, 0, 0)) . " $day, " . date("Y");
    elseif ($hour != "") return "$day/".preg_replace("/^(\d)$/","0\\1",$month)."/$year at " . sprintf("%02d", $hour) . "(h)";
	else return "unknown";
}
function get_range_csv($date_from,$date_to,$range,$tz,$allowed_sensors = "") {
	$config = parse_ini_file("/usr/share/ossim/www/sem/everything.ini");
	// time format range
	$from = strtotime($date_from);
	if ($range != "hour") { $date_to .= " 23:59:59"; }
	$to = strtotime($date_to);
	$add = 3600; // Increases one hour always
	$data = array();
	if ($allowed_sensors != "") {
		$sensors = explode (",",str_replace("|",",",$allowed_sensors));
		foreach ($sensors as $s) if ($s != "") {
			$from = strtotime($date_from) + $add;
			while ($from < $to+$add) {
				$y = date("Y",$from);
				$m = date("m",$from);
				$d = date("d",$from);
				$h = date("H",$from);
				$y_tz = date("Y",$from+(-3600*$tz));
				$m_tz = date("m",$from+(-3600*$tz));
				$d_tz = date("d",$from+(-3600*$tz));
				$h_tz = date("H",$from+(-3600*$tz));
				//if ($range == "month") $add = 86400*cal_days_in_month(CAL_GREGORIAN, $m, $y);
				if (file_exists($config['log_dir'] . "$y_tz/$m_tz/$d_tz/$h_tz/.total_events_$s")) $csv = file($config['log_dir'] . "$y_tz/$m_tz/$d_tz/$h_tz/.total_events_$s");
				else $csv[0] = "0";
				if ($range == "hour") {
					$data[get_date_str($y, $m, $d, $h)] += trim($csv[0]);
				} elseif ($range == "day") {
					$data[get_date_str($y, $m, $d)] += trim($csv[0]);
				} elseif ($range == "month") {
					$data[get_date_str($y, $m)] += trim($csv[0]);
				} elseif ($range == "year") {
					$data[get_date_str($y)] += trim($csv[0]);
				}
				$from += $add;
			}
		}
	} else {
		while ($from < $to+$add) {
			$y = date("Y",$from);
			$m = date("m",$from);
			$d = date("d",$from);
			$h = date("H",$from);
			$y_tz = date("Y",$from+(-3600*$tz));
			$m_tz = date("m",$from+(-3600*$tz));
			$d_tz = date("d",$from+(-3600*$tz));
			$h_tz = date("H",$from+(-3600*$tz));
			//if ($range == "month") $add = 86400*cal_days_in_month(CAL_GREGORIAN, $m, $y);
			//echo "Open $y_tz/$m_tz/$d_tz/$h_tz/.total_events<br>\n";
			if (file_exists($config['log_dir'] . "$y_tz/$m_tz/$d_tz/$h_tz/.total_events")) $csv = file($config['log_dir'] . "$y_tz/$m_tz/$d_tz/$h_tz/.total_events");
			else $csv[0] = "0";
			if ($range == "hour") {
				$data[get_date_str($y, $m, $d, $h)] = trim($csv[0]);
			} elseif ($range == "day") {
				$data[get_date_str($y, $m, $d)] += trim($csv[0]);
			} elseif ($range == "month") {
				$data[get_date_str($y, $m)] += trim($csv[0]);
			} elseif ($range == "year") {
				$data[get_date_str($y)] += trim($csv[0]);
			}
			$from += $add;
		}
		//ksort($months[$lyear]);
	}
	//print_r($data); DISCOMMENT FOR DEBUG
	return $data;
}
function get_day_csv($year, $month, $day, $allowed_sensors = "") {
        $config = parse_ini_file("/usr/share/ossim/www/sem/everything.ini");
    if (Session::allowedSensors() != "") {
                $allowed_sensors .= ($allowed_sensors != "") ? ",".Session::allowedSensors() : Session::allowedSensors();
        }
        if ($allowed_sensors != "") {
                $sensors = explode (",",str_replace("|",",",$allowed_sensors));
                $days = array();
                for ($a = 23; $a >= 0; $a--) $days[$a] = 0;
                foreach ($sensors as $s) if ($s != "") {
                        $file = $config['log_dir'] . "$year/$month/" . sprintf("%02d", $day) . "/.csv_total_events_$s";
                        if (file_exists($file)) $csv = file_get_contents($file);
                        else $csv = array();
                        if (strlen($csv) > 0) $lines = explode("\n", $csv);
                        if (count($lines) > 1) foreach($lines as $line) {
                                $val = explode(",", trim($line));
                                if ($val[0]!="") $days[sprintf("%d", $val[0]) ] += $val[1];
                        }
                }
        } else {
                $file = $config['log_dir'] . "$year/$month/" . sprintf("%02d", $day) . "/.csv_total_events";
                //print_r($file);
                if (file_exists($file)) $csv = file_get_contents($file);
                else $csv = array();
                $days = array();
                for ($a = 23; $a >= 0; $a--) $days[$a] = 0;
                if (strlen($csv) > 0) $lines = explode("\n", $csv);
                if (count($lines) > 1) foreach($lines as $line) {
                        $val = explode(",", trim($line));
                        if ($val[0]!="") $days[sprintf("%d", $val[0])] = $val[1];
                }
        }
    return array_reverse($days);
}
function get_all_csv() {
    $config = parse_ini_file("/usr/share/ossim/www/sem/everything.ini");
    if (file_exists($config['log_dir'] . ".csv_total_events")) $csv = file_get_contents($config['log_dir'] . ".csv_total_events");
    else $csv = array();
    $all = array();
    if (strlen($csv) > 0) $lines = explode("\n", $csv);
    if (count($lines) > 1) foreach($lines as $line) {
        $val = explode(",", $line);
        if ($val[0] >= 1) $all[$val[0]] = $val[1];
    }
    if (count($all) >= 1) for ($i = 2005; $i <= 2015; $i++) if (!isset($all[$i])) $all[$i] = 0;
    ksort($all);
    return $all;
}
function get_year_csv($year,$allowed_sensors = "") {
    $config = parse_ini_file("/usr/share/ossim/www/sem/everything.ini");
    if (Session::allowedSensors() != "") {
                $allowed_sensors .= ($allowed_sensors != "") ? ",".Session::allowedSensors() : Session::allowedSensors();
        }
        if ($allowed_sensors != "") {
                $sensors = explode (",",str_replace("|",",",$allowed_sensors));
                $months = array();
                foreach ($sensors as $s) if ($s != "") {
                        if (file_exists($config['log_dir'] . "$year/.csv_total_events_$s")) $csv = file_get_contents($config['log_dir'] . "$year/.csv_total_events_$s");
                        else $csv = array();
                        if (strlen($csv) > 0) $lines = explode("\n", $csv);
                        if (count($lines) > 1) foreach($lines as $line) {
                                $val = explode(",", $line);
                                if ($val[0] >= 1) $months[(int)$val[0]] += $val[1];
                        }
                }
                for ($i = 1; $i <= 12; $i++) if (!isset($months[$i])) $months[$i] = 0;
                ksort($months);
        } else {
                if (file_exists($config['log_dir'] . "$year/.csv_total_events")) $csv = file_get_contents($config['log_dir'] . "$year/.csv_total_events");
                else $csv = array();
                $months = array();
                if (strlen($csv) > 0) $lines = explode("\n", $csv);
                if (count($lines) > 1) foreach($lines as $line) {
                        $val = explode(",", $line);
                        if ($val[0] >= 1) $months[(int)$val[0]] = $val[1];
                }
                for ($i = 1; $i <= 12; $i++) if (!isset($months[$i])) $months[$i] = 0;
                ksort($months);
        }
    return $months;
}
function get_month_csv($year, $month, $allowed_sensors = "") {
    $config = parse_ini_file("/usr/share/ossim/www/sem/everything.ini");
        if (Session::allowedSensors() != "") {
                $allowed_sensors .= ($allowed_sensors != "") ? ",".Session::allowedSensors() : Session::allowedSensors();
        }
        if ($allowed_sensors != "") {
                $sensors = explode (",",str_replace("|",",",$allowed_sensors));
                $days = array();
                foreach ($sensors as $s) if ($s != "") {
                        if (file_exists($config['log_dir'] . "$year/$month/.csv_total_events_$s")) $csv = file_get_contents($config['log_dir'] . "$year/$month/.csv_total_events_$s");
                        else $csv = array();
                        if (strlen($csv) > 0) $lines = explode("\n", $csv);
                        if (count($lines) > 1) foreach($lines as $line) {
                                $val = explode(",", $line);
                                if ($val[0] >= 1) $days[sprintf("%d", $val[0]) ] += $val[1];
                        }
                }
        } else {
                if (file_exists($config['log_dir'] . "$year/$month/.csv_total_events")) $csv = file_get_contents($config['log_dir'] . "$year/$month/.csv_total_events");
                else $csv = array();
                $days = array();
                //echo $csv;
                if (strlen($csv) > 0) $lines = explode("\n", $csv);
                if (count($lines) > 1) foreach($lines as $line) {
                        $val = explode(",", $line);
                        if ($val[0] >= 1) $days[sprintf("%d", $val[0]) ] = $val[1];
                }
        }
        $final = array();
    $num_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        for ($a = $num_days; $a >= 1; $a--) {
                $ind = sprintf("%d", $a);
                //              echo $days[$ind]."<br>";
                if ($days[$ind] <= 0) $final[$ind] = "0";
                else $final[$ind] = $days[$ind];
        }
    return array_reverse($final);
}
?>
