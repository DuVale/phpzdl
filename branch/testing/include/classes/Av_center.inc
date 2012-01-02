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
*   but WITHOUT ANY WARRANTY; without even the implied warranty of$
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
* - get_tree()

* - get_siem_list($conn)
* - get_menu($uuid, $profiles)
* - avc_collector_get($uuid, $extra_params='')
* - avc_collector_get($uuid, $extra_params='')
* - avc_collector_status($uuid, $extra_params='')
* - avc_collector_network($uuid, $extra_params='')
* - avc_collector_software($uuid, $extra_params='')
* - get_current_local_data($conn, $where='', $order='ORDER BY hostname')
* - get_changes($conn, $uuid)
* - get_packgages_info($uuid)

* - create_progress_bar($width, $bg_color, $fg_color, $percentage)
* - check_access($msg='', $url='')

* Classes list:
* - Avc_tree
* - Av_center
* - Avc_utilities
*/


require_once 'ossim_db.inc';
require_once 'classes/Util.inc';

class Avc_tree {
    
    public $siem_list;
    
    public function __construct($conn){
        
        $this->siem_list = Av_center::get_siem_list($conn);
    }
    
    public function get_tree(){
        
        $cont        = 0;
        $length_name = 20;
        
        $json['status']    = $this->siem_list['status'];
        $json['error_msg'] = $this->siem_list['error_msg'];
        $json['data']      = '';
        
        $tree  = "{title:'<span>"._("SIEM Components")."</span>', icon:'../../pixmaps/theme/any.png', addClass:'size12', isFolder:'true', profiles:'', children:["; 
               
        if ( $json['status'] == false ) 
        {
            $tree .= "{title: '<span>"._("Load error")."</span>', icon:'../../pixmaps/theme/ltError.gif', addClass:'bold_red',  key:'error'}";  
        } 
        else 
        {
            foreach ($this->siem_list['data'] as $uuid => $data)
            {
                $tree .= ( $cont > 0 ) ? ", " : "";
                
                $hostname = $data['hostname'];
                $host_ip  = $data['admin_ip'];
                
                $hname    = ( strlen($hostname) > $length_name ) ? substr($hostname, 0, $length_name)."..." : $hostname;	 
								              
                $title   = $host_ip." <span class=\"t_label\">[".Util::htmlentities($hname)."]</span>";
                $tooltip = $host_ip." [".$hostname."]";
                
                $tree   .= "{title:'$title', icon:'../../pixmaps/theme/host.png',  key:'".$uuid."',  tooltip:'$tooltip', profiles:'".$data['profile']."'}"; 
                $cont++;
            }
        }
        
        $tree .= "] }";   
        
        $json['data'] = $tree;
        		
		return $json;
    
    }
}




class Av_center {

/*Attributes*/
    public static $base_path   = '/usr/share/alienvault-center';
    public static $command_pre = 'sudo ';
       
    public static $files       = array(
                                    "lduc"  => "last_dist-upgrade-changes", 
                                    "lduce" => "last_dist-upgrade-changes_extended"
                                );

/*Methods*/

    public function __construct(){}
    
    public function get_siem_list($conn){
        
        $res['data']      = null;  
        $res['error_msg'] = null;
        $res['status']    = true;          
        
        $query = "SELECT hostname,admin_ip,uuid, profile FROM avcenter.current_local ORDER BY hostname";
        
        $conn->SetFetchMode(ADODB_FETCH_ASSOC);
        
        if (!$rs = & $conn->Execute($query)) 
        {
            $res['error_msg'] = Util::htmlentities($conn->ErrorMsg(), ENT_QUOTES);
            $res['status']    = false;
            
        } 
        else 
        {
            while (!$rs->EOF) 
            {
                $res['data'][$rs->fields['uuid']] = $rs->fields;
                $rs->MoveNext();
            }
        }
            		
		return $res;
    
    }
    
    
    function avc_collector_get($uuid, $extra_params='')
    {
        $command = Av_center::$command_pre."alienvault-center-collector --server=$uuid --get $extra_params  > /tmp/avc.txt 2>&1";
        
        $ret = 0;
        exec ( $command, $output, $ret);
               
        return array("output" => $output, "ret" => $ret);
    }
    
    function avc_collector_set($uuid, $extra_params='')
    {
        $command = Av_center::$command_pre."alienvault-center-collector --server=$uuid --set $extra_params";
        
        $ret = 0;
        exec ( $command, $output, $ret);
               
        return array("output" => $output, "ret" => $ret);
    }
    
    function avc_collector_status($uuid, $extra_params='')
    {
        $command = Av_center::$command_pre."alienvault-center-collector --server=$uuid --status $extra_params";
        
        $ret = 0;
        exec ( $command, $output, $ret);
        $output = implode("\n", $output);
        $output = parse_ini_string($output, true);
               
        return array("output" => $output, "ret" => $ret);
    }
    
    function avc_collector_network($uuid, $extra_params='')
    {
        $command = Av_center::$command_pre."alienvault-center-collector --server=$uuid --network $extra_params";
        
        $ret = 0;
        exec ( $command, $output, $ret);
        $output = implode("\n", $output);
        $output = parse_ini_string($output, true);
               
        return array("output" => $output, "ret" => $ret);
    }
    
    function avc_collector_software($uuid, $extra_params='')
    {
        $software_info = Av_center::$base_path."/regdir/$uuid/software_status";
        $output        = array();
                      
        if ( !file_exists($software_info) )
        {
            $command = Av_center::$command_pre."alienvault-center-collector --server=$uuid --update_system_info $extra_params";
            $ret = 0;
            exec ( $command, $output, $ret);
        }
               
        $output = parse_ini_file($software_info, true);
        return array("output" => $output, "ret" => 0);
    }
       
   
    function get_current_local_data($conn, $where='', $order='ORDER BY hostname')
    {
        $res['error'] = false;
        $res['data']  = '';
        $query = "SELECT * FROM avcenter.current_local $where $order";
        $conn->SetFetchMode(ADODB_FETCH_ASSOC);
        
        if (!$rs = & $conn->Execute($query)) 
        {
            $res['error'] = true;
            $res['data']  = $conn->ErrorMsg();
        } 
        else 
        {
            $res['data'] = $rs->fields;
        }
        
        return $res;
    }
    
    
    function get_changes($conn, $uuid)
    {
        $res['error'] = false;
        $res['data']  = array();
        
        $fields = array();
        
        $query  = "SELECT * FROM avcenter.current_local WHERE uuid='$uuid'";
        $query2 = "SELECT * FROM avcenter.current_remote WHERE uuid='$uuid'";
        
        $conn->SetFetchMode(ADODB_FETCH_ASSOC);
         
        if ( !$rs1 = & $conn->Execute($query) || !$rs2 = & $conn->Execute($query2) ) 
        {
            $res['error'] = true;
            $res['data']  = $conn->ErrorMsg();
        }
        else
        {
            if ( !empty($rs1->fields) )
            {
                foreach ( $rs1->fields as $k => $v )
                {
                    if ( $rs2->fields[$k] != $v )
                    {
                       $fields[$k] = array ("local"=>$v, "remote"=>$rs2->fields[$k]); 
                    }
                }
                
                unset($fields['timestamp']);
            }
            
            $res['data'] = $fields;
        }
        
        return $res;
    } 
    
    function get_system_info($uuid, $extra_params='')
    {
        $command = Av_center::$command_pre."alienvault-center-collector --server=$uuid --update_system_info $extra_params";
        
        $ret = 0;
        exec ( $command, $output, $ret);
               
        return array("output" => $output, "ret" => $ret);
    }
    
    function get_packages_info($uuid, $type='alienvault')
    {
        $res['error'] = false;
        $res['data']  = array();
        
        $res = Av_center::get_system_info($uuid);
        
        if ( $res['ret'] !== 0 )
        {
            $res['error'] = true;
            $res['data']  = _("Package information not available. Code: 01");
            return $res; 
        }
       
        
        $basepath = Av_center::$base_path."/regdir/$uuid/";
        $file     = $basepath.Av_center::$files['lduc'];
                    
        if ( is_file($file) )
        {
            $ret         = 0;
                      
            $command  = "cat $file | grep";
            $command .= ( $type == 'alienvault' ) ? " " : " -v ";
            $command .= "'data.alienvault.com'";
                       
            exec ($command, $packages_info, $ret);
            
            if ( $ret === 0 )
            {
                if ( is_array($packages_info) && count($packages_info) > 0 )
                {
                    foreach ($packages_info as $k => $v)
                    {
                        //Format: Inst $package [$inst_version] ($latest_version)
                        $pattern = "/Inst\s*(\S*)\s*\[(.*)\]\s*\((.*)\)/";
                                               
                        preg_match($pattern, $v, $matches);
                       
                        $package         = $matches[1];
                        $inst_version    = $matches[2];
                        $latest_version  = $matches[3];

                        $res['data'][$package] = array("inst_version" => $inst_version, "latest_version" => $latest_version);                   
                    }
                }
                else
                {
                     $res['error'] = true;
                     $res['data']  = _("Package information not available. Code: 03");
                }
            } 
            else
            {
                $res['error'] = true;
                $res['data']  = _("Package information not available. Code: 04");
            }
            
        }
        else
        {
            $res['error'] = true;
            $res['data']  = _("Package information not available. Code: 02");
        }
        
        return $res;
    }
    
    function get_packages_info_extended_av($uuid, $package_name)
    {
        $res['error'] = false;
        $res['data']  = null;
        
        $basepath = Av_center::$base_path."/regdir/$uuid/";
        $file     = $basepath.Av_center::$files['lduce'];
                        
        if ( !is_file($file) )
        {
            $res['error'] = true;
            $res['data']  = _("Package information not available. Code: 01");
            return $res; 
        }
       
        
        $basepath = Av_center::$base_path."/regdir/$uuid/";
        $file     = $basepath.Av_center::$files['lduce'];
               
        
        $command = "cat $file | grep \"\[$package_name\]\"";
        
        $ret = 0;
        exec ( $command, $output, $ret);
        
        if ( is_array($output) && !empty($output) )
        {
            foreach ($output as $k => $v)
            {
                //Format: [$package_name] [$inst_version] [$latest_version] [ $package_deb ] [ $description  ] [ $date ]
                                    
                $pattern = "/\[(\S*)\]\s*\[(\S*)\]\s*\[(\S*)\]\s*\[\s*(\S*)\\s*\]\s*\[(.*)\]\s*\[(.*)\]/";
                preg_match($pattern, $v, $matches);
                
                $package        = $matches[1];
                $latest_version = $matches[2];
                $inst_version   = $matches[3];
                $package_deb    = $matches[4];
                $description    = $matches[5];
                $date           = $matches[6];
                
                $res['data'][$inst_version]  = array("package"        => $package, 
                                                     "package_deb"    => $package_deb, 
                                                     "latest_version" => $latest_version, 
                                                     "description"    => $description, 
                                                     "date"           => $date);                      
            }
            
            krsort($res['data']);
        }
        else
        {
            $res['error'] = true;
            $res['data'] = _("No details available");
        }
              
        
        return $res;
    }
    
    function get_packages_info_extended_deb($uuid, $package_name)
    {
        $res['error'] = false;
        $res['data']  = null;
        
        $command = Av_center::$command_pre."alienvault-center-collector --server=$uuid --update_system_info_debian_package=$package_name";
        
        $ret = 0;
        exec ( $command, $output, $ret);
        
        if ( $ret === 0)
        {
            if ( is_array($output) && !empty($output) )
            {
                $res['data'] = implode("<br/>", $output);
                $res['data'] = str_replace("\r", " ", $res['data']);
            }
            else
            {
                $res['error'] = true;
                $res['data'] = _("No details available");
            }
        }
        else
        {
            $res['error'] = true;
            $res['data'] = _("No details available");
        
        }
                
        return $res;
    }
    
    function update_proxy($conn, $uuid, $params)
    {
        $res['error'] = false;
        $res['data']  = array();
        
        
        if ( $params[0] != 'manual')
        {
            $params[1] = "my.proxy.com";
            $params[2] = "disabled";
            $params[3] = "disabled";
            $params[4] = "disabled";
        }
        
        $params[5] = $uuid;
        
        $query  = " UPDATE avcenter.current_local 
                    SET update_proxy=?, update_proxy_dns=?, update_proxy_user=?, update_proxy_pass=?, update_proxy_port=? 
                    WHERE uuid=?";
       
        if ( !$rs = & $conn->Execute($query, $params) ) 
        {
            $res['error'] = true;
            $res['data']  = $conn->ErrorMsg();
        }
        else
        {
            $set = Av_center::avc_collector_set($uuid);
            
            if ( $set['ret'] !== 0 )
            {
                $res['error'] = true;
                $res['data']  = _("The update process did not complete successfully: An error occurred during the update.");
            }
            else
            {
                $res['data']  = _("Proxy settings updated successfully");
            }
        }
        
        return $res;
    }
    
}


class Avc_utilities {
    
    function create_progress_bar($id, $title, $width, $percentage, $class='')
    {
        $title = ( empty($title) ) ? '' : _($title);
        
        return "
                <div id='$id' style='width: $width;'>
                    <div class='ui-progress-bar ui-container'>
                        <div class='ui-title' style='text-align:center;><span class='ui-label'>".$title." <strong class='value'>$percentage %</strong></span></div>
                        <div class='ui-progress $class' style='width:$percentage%'></div>
                    </div>
                </div>";
    }
   
    function show_tooltip($type, $msg, $style=''){
    
        $st_class = 'avc_tooltip_error';
        $img      = 'avc_error.png';
    
        switch ($type){
                
            case 'info':
               $st_class = 'avc_tooltip_info';
               $img      = 'avc_info.png';
            break;
            
            case 'success':
               $st_class = 'avc_tooltip_success';
               $img      = 'avc_success.png';
            break;
            
            case 'warning':
               $st_class = 'avc_tooltip_warning';
               $img      = 'avc_warning.png';
            break;
            
            default:
               $st_class = 'avc_tooltip_error';
               $img      = 'avc_error.png';
        
        } 
        
        $html = "<div id='avc_tooltip' class='$st_class' style='$style'><img src='/ossim/av_center/images/$img'/>$msg</div>";
              
        return $html;
    }
            

    function check_access($msg='', $url='')
    {
        require_once 'classes/Session.inc';
            
        if ( !Session::am_i_admin() )
        {
            if ( !empty($url) )
            {
                header("Location: $url");
                exit();
            }
            
            $error_text = ( !empty($msg) ) ? $msg : _("You don't have permission to access to Alienvault Center");
            
            echo "<div style='text-align:left; margin:auto; padding: 100px 0px 100px 0px'>".ossim_error($error_text, "NOTICE")."</div>";
            exit();
        }
    }
       
}

if(!function_exists('parse_ini_string'))
{
    function parse_ini_string($str, $ProcessSections=false){
        $lines  = explode("\n", $str);
        $return = Array();
        $inSect = false;
        foreach($lines as $line){
            $line = trim($line);
            if(!$line || $line[0] == "#" || $line[0] == ";")
                continue;
            if($line[0] == "[" && $endIdx = strpos($line, "]")){
                $inSect = substr($line, 1, $endIdx-1);
                continue;
            }
            if(!strpos($line, '=')) // (We don't use "=== false" because value 0 is not valid as well)
                continue;
           
            $tmp = explode("=", $line, 2);
            if($ProcessSections && $inSect)
                $return[$inSect][trim($tmp[0])] = ltrim($tmp[1]);
            else
                $return[trim($tmp[0])] = ltrim($tmp[1]);
        }
        return $return;
    }
}

?>
