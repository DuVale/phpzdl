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
* - GeoLoc()
* - get_GeoIpId()
* - get_country_code_by_addr()
* - get_country_name_by_addr()
* - get_data_by_ip()
* - get_lat_lon_by_ip()
* - get_data_by_lat_lon()
* - get_lat_lon_by_name_country()
* - get_flag()  -->  GeoLoc::get_flag(ip)
* - close()
* Classes list:
* - GeoLoc
*/
require_once ('geoip.inc');
require_once ('classes/Security.inc');

class GeoLoc {
    var $GeoIpId;
    var $File;
    var $key_Google;
    
    function GeoLoc($file = "/usr/share/geoip/GeoIP.dat", $flag = GEOIP_STANDARD) {
        $this->File       = $file;
        $this->GeoIpId    = geoip_open($file, $flag);
        $this->Key_Google = "";
        
        $conf     = $GLOBALS["CONF"];
        $this->Key_Google = $conf->get_conf("google_maps_key", FALSE);
        if ($this->Key_Google=="") $this->Key_Google="ABQIAAAAbnvDoAoYOSW2iqoXiGTpYBTIx7cuHpcaq3fYV4NM0BaZl8OxDxS9pQpgJkMv0RxjVl6cDGhDNERjaQ";
    }
    function get_GeoIpId() {
        return $this->GeoIpId;
    }
    function get_country_code_by_addr($conn, $ip, $update=false){
        list($code, $name) = $this->get_data_by_ip($conn, $ip, $update);
        if ($code != "")
            return $code;
        else
            return geoip_country_code_by_addr($this->GeoIpId, $ip);
    }
    function get_country_name_by_addr($conn, $ip, $update=false){
        list($code, $name) = $this->get_data_by_ip($conn, $ip, $update);
        if ($name != "")
            return $name;
        else 
            return geoip_country_name_by_addr($this->GeoIpId, $ip);
    }
    function get_data_by_ip($conn, $ip, $update){
        list($lat, $lon, $code, $name, $comes) = $this->get_lat_lon_by_ip($conn, $ip);
        if ( $code != "" && $name != "" ){
            return array($code, $name);
        }elseif ( $lat == "" && $lon == "" && $comes == "" ){
            return array("", "");
        }elseif ( $update && $comes == "" ){
            list($code, $name) = $this->get_data_by_lat_lon($conn, $lat, $lon, $comes, $update);
            return array($code, $name);
        }
        return array("", "");
    }
    function get_lat_lon_by_ip($conn, $ip){
        if (!is_ip($ip)) $ip = long2ip($ip);
        if (!is_ip($ip)) return array("", "", "", "", "");
        
        $sql   = "select h.ip as ip, h.lat as lat, h.lon as lon, replace(substring(substring_index(h.country, ';', 1), length(substring_index(h.country, ';', 1 - 1)) + 1), ';', '') code, replace(substring(substring_index(h.country, ';', 2), length(substring_index(h.country, ';', 2 - 1)) + 1), ';', '') name, 'host' as comes from host as h where h.ip = '".$ip."' ";
        $sql  .= "union all ";
        $sql  .= "select hgr.host_ip as ip, hg.lat as lat, hg.lon as lon, replace(substring(substring_index(hg.country, ';', 1), length(substring_index(hg.country, ';', 1 - 1)) + 1), ';', '') code, replace(substring(substring_index(hg.country, ';', 2), length(substring_index(hg.country, ';', 2 - 1)) + 1), ';', '') name, 'host_group' as comes from host_group as hg inner join host_group_reference as hgr on hg.name = hgr.host_group_name where hgr.host_ip = '".$ip."';";
        
        $lat   = "";
        $lon   = "";
        $code  = "";
        $name  = "";
        $comes = "";
        
        if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                if ( ( $rs->fields["lat"] != "" && $rs->fields["lon"] != "" ) || ( $rs->fields["code"] != "" && $rs->fields["name"] != "" ) ){
                    $lat   = $rs->fields["lat"];
                    $lon   = $rs->fields["lon"];
                    $lon   = explode(";", $lon);
                    $lon   = $lon[0];
                    
                    $code  = $rs->fields["code"];
                    $name  = $rs->fields["name"];
                    
                    $comes = $rs->fields["comes"];
                    
                    return array($lat, $lon, $code, $name, $comes);
                }
                $rs->MoveNext();
            }
        }
        return array($lat, $lon, $code, $name, $comes);
    }
    function get_data_by_lat_lon($conn, $lat, $lon, $comes, $update){
        $lon         = explode(";", $lon);
        $lon         = $lon[0];

        $context = stream_context_create(array(
            'http' => array(
                'timeout' => 5      // Timeout in seconds
            )
        ));

        $json        = @file_get_contents('https://maps.google.com/maps/api/geocode/json?latlng='.$lat.','.$lon.'&sensor=false&language=EN',0,$context);

        $jsondecoded = json_decode($json, true);
        $name        = $jsondecoded['results'][count($jsondecoded['results'])-1]['address_components'][0]['long_name'];
        $code        = $jsondecoded['results'][count($jsondecoded['results'])-1]['address_components'][0]['short_name'];

        if ( $code != "" && $name != "" && $comes != "" && $update){
            $sql = "update ".$comes." set country = '".$code.";".$name."' where lat = '".$lat."' and lon like '".$lon."%';";
            if ($conn->Execute($sql, $params) === false) {
                print 'Error updating: ' . $conn->ErrorMsg() . '<br/>';
                exit;
            }
        }
        return array($code, $name);
    }
    function get_lat_lon_by_name_country($country){
    
        $context = stream_context_create(array(
            'http' => array(
                'timeout' => 5      // Timeout in seconds
            )
        ));

        $json        = @file_get_contents('https://maps.google.com/maps/api/geocode/json?address='.$country.'&sensor=false&language=EN',0,$context);

        $jsondecoded = json_decode($json, true);
        $lat = $jsondecoded['results'][count($jsondecoded['results'])-1]['geometry']['location']['lat'];
        $lon = $jsondecoded['results'][count($jsondecoded['results'])-1]['geometry']['location']['lng'];
        
        return array($lat, $lon);
    }
    function get_flag($ip, $file = "/usr/share/geoip/GeoIP.dat")
    {
        require_once ('classes/GeoLoc.inc');
        require_once ('ossim_db.inc');

        $GeoLoc = new GeoLoc($file);
        $db     = new ossim_db();
        $conn   = $db->connect();
        
        $code   = strtolower($GeoLoc->get_country_code_by_addr($conn, $ip));
        $name   = $GeoLoc->get_country_name_by_addr($conn, $ip);
        
        if ($code != "" && $name != "")
            $flag = "<img src='/ossim/pixmaps/flags/" . $code . ".png' title='" . $name . "' />";
        else
            $flag = "" ;
            
        $GeoLoc->close();
        $db->close($conn);
            
        return $flag;
    }
    function close() {
        geoip_close($this->GeoIpId);
        return true;
    }
    
}
?>
