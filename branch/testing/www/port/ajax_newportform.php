<?php
require_once ('classes/Security.inc');
require_once ('classes/Port.inc');
require_once ('ossim_db.inc');


$ports_name = GET('ports_name');
$protocol   = GET('ports_protocol');
ossim_valid($ports_name, OSS_NOECHARS, OSS_ALPHA, OSS_SCORE, OSS_PUNC, 'illegal:' . _("ports_name"));
ossim_valid($protocol  , OSS_LETTER                                  , 'illegal:' . _("Protocol"));

if (ossim_error()) { 
    die(ossim_error());
}

if (preg_match('/-/',$ports_name)){
    $ports_name_tmp = explode("-",$ports_name);
    if (is_numeric($ports_name_tmp[0])){
        if ( $ports_name_tmp[0]>$ports_name_tmp[1] ){
            die("YYY");
        }else{
            for ($i=$ports_name_tmp[0]; $i<=$ports_name_tmp[1];$i++)
                $list_ports[] = $i;
        }
    }else
        $list_ports[] = $ports_name;
}else
    $list_ports[] = $ports_name;
    
$db = new ossim_db();
$conn = $db->connect();

$output_ajax = "[";

foreach($list_ports as $port){
    if (is_numeric($port)){
        if($port<0 && $port>65535){
            $db->close($conn);
            die("ZZZ");
        }
        $output_ajax .= "'".$port."',";
        continue;
    }

    $port=Port::service2port($conn, $port, $protocol);
    
    if (is_numeric($port)){
        if($port<0 && $port>65535){
            $db->close($conn);
            die("ZZZ");
        }
        $output_ajax .= "'".$port."',";
        continue;
    }else{
        $db->close($conn);
        die("XXX");
    }
}

$output_ajax  = preg_replace('/,$/','',$output_ajax);
$output_ajax .= "];";

$db->close($conn);
echo($output_ajax);

?>