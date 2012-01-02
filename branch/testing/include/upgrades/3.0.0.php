<?php
$path_class = '/usr/share/ossim/include/';
ini_set('include_path', $path_class);
require_once 'classes/Upgrade_base.inc';

class upgrade_300 extends upgrade_base
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
		$db = $dbsock->connect();
		
		$sql = "SELECT * FROM user_config WHERE category='custom_report'";
		if (!$rs = $db->Execute($sql)) {
            print $db->ErrorMsg();
        } elseif (!$rs->EOF) { // Found -> Update
			while (!$rs->EOF) {		
				$name = preg_replace('/Logger\s/', 'Log ', $rs->fields['name']);
				$name = preg_replace('/SIEM\s/', 'Security Events ', $name);

				$name = preg_replace('/Security Events\s/', 'Security Events: ', $name);
				$name = preg_replace('/Security Event\s/', 'Security Events: ', $name);
				$name = preg_replace('/Log\s/', 'Raw Logs: ', $name);
				$name = preg_replace('/\sevents\s*$/', '', $name);
				$name = preg_replace('/^Logger$/', 'Raw Logs', $name);
				
				$value  = $rs->fields['value'];
				$report = unserialize($value);
				
				if (serialize($report)!='b:0;') { //error to unserialize

					$report['rname'] = preg_replace('/Logger\s/', 'Log ', $report['rname']);
					$report['rname'] = preg_replace('/SIEM\s/', 'Security Events ', $report['rname']);

					$report['rname'] = preg_replace('/Security Events\s/', 'Security Events: ', $report['rname']);
					$report['rname'] = preg_replace('/Security Event\s/', 'Security Events: ', $report['rname']);
					$report['rname'] = preg_replace('/Log\s/', 'Raw Logs: ', $report['rname']);
					$report['rname'] = preg_replace('/\sevents\s*$/', '', $report['rname']);
					$report['rname'] = preg_replace('/^Logger$/', 'Raw Logs', $report['rname']);
					
					$value = serialize($report);
				}
			
				$sql = "UPDATE `user_config` SET NAME='".$name."', VALUE='".$value."' WHERE name='".$rs->fields['name']."' and LOGIN='".$rs->fields['login']."' and CATEGORY='custom_report'";
				
				//echo($sql."<br /><br />");
				
				if (!$db->Execute($sql)) die($db->ErrorMsg());
				$rs->MoveNext();
			
			}
			
		}
	
        return true;
    }
}
?>
