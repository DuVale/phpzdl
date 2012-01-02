<?php
$path_class = '/usr/share/ossim/include/';
ini_set('include_path', $path_class);
require_once 'classes/Upgrade_base.inc';

class upgrade_302 extends upgrade_base
{

    function start_upgrade(){
       print "<br/>";       print "<br/>";
       print "<br/>";
       print _("Due to this upgrade being quite big, your browser might not show the 'end of 
upgrade' message. If you see the browser has stopped loading the page, reload and your system
 should be upgraded.");
       print "<br/>";
       print "<br/>";
       print "<br/>";    
    }

    function end_upgrade()
    {
		require_once ('ossim_db.inc');
		$dbsock = new ossim_db();
		$db     = $dbsock->connect();
		
		$sql = "SELECT * FROM user_config WHERE category='custom_report' and name='Ticket Report'";
		
        if (!$rs = $db->Execute($sql)) 
        {
            print $db->ErrorMsg();
        } 
        elseif (!$rs->EOF) 
        { 		
			$value  = $rs->fields['value'];
			$report = unserialize($value);
			
            //Error to unserialize            
            if (serialize($report)!='b:0;') 
            { 
                $report['ds'][320]['status'] = 'All';
                $report['ds'][321]['status'] = 'All';
                $report['ds'][322]['status'] = 'All';
                $report['ds'][323]['status'] = 'All';
                $report['ds'][324]['status'] = 'All';
                              
                $value = serialize($report);
            }
			
			$sql = "UPDATE `user_config` SET value='".$value."' WHERE name='Ticket Report' and LOGIN='".$rs->fields['login']."' and CATEGORY='custom_report'";
				
			//echo($sql."<br /><br />");
				
			if (!$db->Execute($sql)) 
                die($db->ErrorMsg());
            
		}
	
        return true;
    }
}
?>
