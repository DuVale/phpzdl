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

require_once ('ossim_conf.inc');
require_once ('classes/Upgrade.inc');
require_once ('classes/Session.inc');
require_once ('classes/Util.inc');
require_once ('classes/Sensor.inc');
require_once ('classes/Reputation.inc');

$conf               = $GLOBALS["CONF"];
$version            = $conf->get_conf("ossim_server_version", FALSE);
$event_stats_enable = $conf->get_conf("frameworkd_eventstats");
$cloud_instance     = ( $conf->get_conf("cloud_instance", FALSE) == 1 )    ? true : false;
$opensource         = ( !preg_match("/pro|demo/i",$version) )              ? true : false;
$prodemo            = ( preg_match("/pro|demo/i",$version) )               ? true : false;
$av_features        = ( $conf->get_conf("advanced_features", FALSE) == 1 ) ? true : false;

/*Software Upgrade*/
$upgrade = new Upgrade();
if ( Session::am_i_admin() && $upgrade->needs_upgrade() ) 
{
	$menu[md5("Upgrade")]["name"] = "Upgrade";
    $menu[md5("Upgrade")][md5("Upgrade")] = array(
        "name" => gettext("System Upgrade Needed"),
        "id" => "Upgrade",
        "url" => "upgrade/index.php"
    );
    
	$hmenu[md5("Upgrade")][] = array(
        "name" => gettext("Software Upgrade"),
        "id" => "Upgrade",
        "url" => "upgrade/"
    );
    
	$hmenu[md5("Upgrade")][] = array(
        "name" => gettext("Update Notification"),
        "id" => "Updates",
        "url" => "updates/index.php"
    );
    
	$GLOBALS['ossim_last_error'] = false;
}

/**********************************************************
----------------------- Dashboards ------------------------
***********************************************************/

// Dashboard -> Main

if (Session::menu_perms("MenuControlPanel", "ControlPanelExecutive"))
{ 
	$menu[md5("Dashboards")]["name"] = "Dashboards";
	$menu[md5("Dashboards")][md5("Executive Panel")] = array(
		"name" => gettext("Dashboards"),
		"id"   => "Executive Panel",
		"url"  => "panel/"
	);
}

if (Session::menu_perms("MenuControlPanel", "BusinessProcesses") || Session::menu_perms("MenuControlPanel", "ControlPanelMetrics")) 
{ 
    $menu[md5("Dashboards")]["name"] = "Dashboards";
	if (Session::menu_perms("MenuControlPanel", "BusinessProcesses")) 
	{
		$menu[md5("Dashboards")][md5("Risk")] = array(
	      "name" => gettext("Risk"),
	      "id"   => "Risk",
	      "url"  => "risk_maps/riskmaps.php?view=1"
	    );
    } 
	else 
	{
    	$menu[md5("Dashboards")][md5("Risk")] = array(
	      "name" => gettext("Risk"),
	      "id"   => "Risk",
	      "url"  => "control_panel/global_score.php?range=day"
	    );
    }
    
		if (Session::menu_perms("MenuControlPanel", "BusinessProcesses")) 
		{
			$hmenu[md5("Risk")][] = array(
			  "name"   => gettext("Risk Maps"),
			  "id"     => "Risk",
			  "target" => "main",
			  "url"    => "risk_maps/riskmaps.php?view=1",
			  "help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:dashboards:risk:risk_maps','DashboardHelp');"
			);
			
			if ( Session::menu_perms("MenuControlPanel", "BusinessProcessesEdit") ) 
            {
                $rmenu[md5("Risk")][] = array(
                  "name"   => gettext("Set Indicators"),
                  "target" => "main",
                  "url"    => "../risk_maps/riskmaps.php"
                );
                
                $rmenu[md5("Risk")][] = array(
                  "name"   => gettext("Manage maps"),
                  "target" => "main",
                  "url"    => "../risk_maps/riskmaps.php?view=2"
                );
            }
		}
		
		if (Session::menu_perms("MenuControlPanel", "ControlPanelMetrics")) {
			$hmenu[md5("Risk")][] = array(
				"name"   => gettext("Risk Metrics"),
				"id"     => (Session::menu_perms("MenuControlPanel", "BusinessProcesses")) ? "Metrics" : "Risk",
				"target" => "main",
				"url"    => "control_panel/global_score.php?range=day",
				"help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:dashboards:risk:risk_metrics','DashboardHelp');",
				"config" => "metrics"
			);
		}
}

/**********************************************************
----------------------- Incidents -------------------------
***********************************************************/


// Incidents -> Alarms
$controlPanelAlarms 	  = Session::menu_perms("MenuIncidents", "ControlPanelAlarms");
$reportsAlarmReport 	  = Session::menu_perms("MenuIncidents", "ReportsAlarmReport");
$controlPanelAlarmsDelete = Session::menu_perms("MenuIncidents", "ControlPanelAlarmsDelete");

$alarms = 0;
if ( $controlPanelAlarms || $reportsAlarmReport ) 
{ 
    $menu[md5("Incidents")]["name"] = "Incidents";
	if ( $controlPanelAlarms )
	{
		$alarms = 1;	
		$menu[md5("Incidents")][md5("Alarms")] = array(
			"name" => gettext("Alarms"),
			"id"   => "Alarms",
			"url"  => "control_panel/alarm_console.php?hide_closed=1"
		);
		
			$hmenu[md5("Alarms")][] = array(
				"name"   => gettext("Alarms"),
				"id"     => "Alarms",
				"target" => "main",
				"url"    => "control_panel/alarm_console.php?hide_closed=1",
				"help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:incidents:alarms:alarms','Help');",
				"config" => "alarms"
			);
			
			$rmenu[md5("Alarms")][] = array(
				"name"   => gettext("Edit labels"),
				"target" => "main",
				"url"    => "tags_edit.php"
			);
	}
	
	if ( $reportsAlarmReport ) 
	{
		$ids = "Report";
		if ( $alarms == 0)
		{
			$alarms = 1;
			$ids    = "Alarms";
			
			$menu[md5("Incidents")][md5("Alarms")] = array(
				"name" => gettext("Alarms"),
				"id"   => "Alarms",
				"url"  => "report/sec_report.php?section=all&type=alarm"
			);
		}
		
		$hmenu[md5("Alarms")][] = array(
			"name" => gettext("Report"),
			"id"   => $ids,
			"url"  => "report/sec_report.php?section=all&type=alarm",
			"help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:incidents:alarms:report','Help');"
		);
	}
}	


//  Incidents -> Tickets
$incidentsIncidents = Session::menu_perms("MenuIncidents", "IncidentsIncidents");    
$incidentsReport    = Session::menu_perms("MenuIncidents", "IncidentsReport");	
	
if (  $incidentsIncidents || $incidentsReport ) 
{ 
    $menu[md5("Incidents")]["name"] = "Incidents";
    
	if ( $incidentsIncidents )
	{
		$incidents = 1;		
		$menu[md5("Incidents")][md5("Tickets")] = array(
				"name" => gettext("Tickets"),
				"id"   => "Tickets",
				"url"  => "incidents/index.php?status=$status"
		);
				
		$hmenu[md5("Tickets")][] = array(
			"name"   => gettext("Tickets"),
			"id"     => "Tickets",
			"url"    => "incidents/index.php?status=$status",
			"help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:incidents:tickets','Help');",
			"config" => "tickets"
		);
		
			if (Session::menu_perms("MenuIncidents", "IncidentsTypes") || Session::am_i_admin()) 
			{ 
				$rmenu[md5("Tickets")][] = array(
					"name" => gettext("Types"),
					"id"   => "Types",
					"url"  => "../incidents/incidenttype.php"
				);
			}
			
			if (Session::menu_perms("MenuIncidents", "IncidentsTags") || Session::am_i_admin()) 
			{ 
				$rmenu[md5("Tickets")][] = array(
					"name" => gettext("Tags"),
					"id"   => "Tags",
					"url"  => "../incidents/incidenttag.php"
				);
			}
			
			if (Session::menu_perms("MenuIncidents", "ConfigurationEmailTemplate") || Session::am_i_admin()) 
			{ 
				$incidents = 1;
				$rmenu[md5("Tickets")][] = array(
					"name" => gettext("Email Template"),
					"id"   => "Incidents Email Template",
					"url"  => "../conf/emailtemplate.php"
				);
			}
	}	
	
	if ( $incidentsReport )
	{
		$ids = "Report";
		if ($incidents == 0 )
		{
			$incidents = 1;
			$ids       = "Tickets";
			
			$menu[md5("Incidents")][md5("Tickets")] = array(
				"name" => gettext("Tickets"),
				"id"   => "Tickets",
				"url"  => "report/incidentreport.php"
			);
		}
		
		$hmenu[md5("Tickets")][] = array(
			"name" => gettext("Report"),
			"id"   => $ids,
			"url"  => "report/incidentreport.php",
			"help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:incidents:report','Help');"
		);
	}
	
}
	

// Incidents -> Knowledge DB	

if (Session::menu_perms("MenuIncidents", "Osvdb")) 
{ 
    $menu[md5("Incidents")]["name"] = "Incidents";
    $menu[md5("Incidents")][md5("Repository")] = array(
        "name" => gettext("Knowledge DB"),
        "id"   => "Repository",
        "url"  => "repository/index.php"
    );
    
	$hmenu[md5("Repository")][] = array(
        "name" => gettext("Knowledge DB"),
        "id"   => "Repository",
        "url"  => "repository/index.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:incidents:knowledge_db','Help');"
    );
}


/**********************************************************
----------------------- Analysis --------------------------
***********************************************************/

// Analysis -> Security Events
$events = 0;
if ( Session::menu_perms("MenuEvents", "EventsForensics") ) 
{ 
	$events = 1;
	
	$tz        = Util::get_timezone();
	$timetz    = gmdate("U")+(3600*$tz);
	$tmp_month = gmdate("m",$timetz);
	$tmp_day   = gmdate("d",$timetz);
	$tmp_year  = gmdate("Y",$timetz);
	$today     = '&time%5B0%5D%5B0%5D=+&time%5B0%5D%5B1%5D=%3E%3D&time%5B0%5D%5B2%5D=' . $tmp_month . '&time%5B0%5D%5B3%5D=' . $tmp_day . '&time%5B0%5D%5B4%5D=' . $tmp_year . '&time%5B0%5D%5B5%5D=&time%5B0%5D%5B6%5D=&time%5B0%5D%5B7%5D=&time%5B0%5D%5B8%5D=+&time%5B0%5D%5B9%5D=+&time_range=today';
    
	$menu[md5("Analysis")]["name"] = "Analysis";
	$menu[md5("Analysis")][md5("Forensics")] = array(
        "name"  => gettext("Security Events (SIEM)"),
        "id"    => "Forensics",
        "url"   => "forensics/base_qry_main.php?clear_allcriteria=1&num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d".$today
    );
    
	$hmenu[md5("Forensics")][] = array(
        "name"   => gettext("Security Events (SIEM)"),
        "id"     => "Forensics",
        "url"    => "forensics/base_qry_main.php?clear_allcriteria=1&num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d",
        "help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:analysis:SIEM','EventHelp')",
    	"config" => "siem"
    );
	
    
	if (!$cloud_instance && $event_stats_enable) {
        $hmenu[md5("Forensics")][] = array(
            "name" => gettext("Statistics"),
            "id"   => "Events Stats",
            "url"  => "report/event_stats.php",
            "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:analysis:statistics','EventHelp')"
        );
	}
	$rmenu[md5("Forensics")][] = array(
	  "name"   => gettext("Manage References"),
	  "target" => "main",
	  "url"    => "../forensics/manage_references.php"
	);
}

// Analysis -> Logger

if (is_dir("/var/ossim/")) 
{
    // Only show SEM menu if SEM is available
	if (Session::menu_perms("MenuEvents", "ControlPanelSEM")) 
	{ 
		$events = 1;
        
        $sem_url = ($opensource) ? "ossem/index.php" : ( ($conf->get_conf("server_remote_logger", FALSE) == "yes" ) ? "sem/remote_index.php" : "sem/index.php" );
        
        $menu[md5("Analysis")]["name"] = "Analysis";
        
		$menu[md5("Analysis")][md5("Events Stats")] = array(
            "name" => gettext("Raw Logs (Logger)"),
            "id"   => "SEM",
            "url"  => $sem_url
        );
        
		$hmenu[md5("SEM")][] = array(
            "name"   => gettext("Raw Logs (Logger)"),
            "id"     => "SEM",
            "url"    => $sem_url,
            "help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:analysis:logger','EventHelp')",
        	"config" => "logger"
        );
    }
}

//Analysis -> Vulnerabilities

if (Session::menu_perms("MenuEvents", "EventsVulnerabilities")) 
{ 
	$events = 1;
    $menu[md5("Analysis")]["name"] = "Analysis";
	$menu[md5("Analysis")][md5("Vulnerabilities")] = array(
        "name" => gettext("Vulnerabilities"),
        "id"   => "Vulnerabilities",
        "url"  => "vulnmeter/index.php"
    );
    
	$hmenu[md5("Vulnerabilities")][] = array(
        "name"   => gettext("Vulnerabilities"),
        "id"     => "Vulnerabilities",
        "url"    => "vulnmeter/index.php",
        "help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:analysis:vulnerabilities:vulnerabilities','EventHelp')",
    	"config" => "vulnerabilities"
    );
	
	$rmenu[md5("Vulnerabilities")][] = array(
			"name" => gettext("Profiles"),
			"id"   => "ScanProfiles",
			"url"  => "../vulnmeter/settings.php"
		);
		
    
	$hmenu[md5("Vulnerabilities")][] = array(
        "name" => gettext("Reports"),
        "id"   => "Reports",
        "url"  => "vulnmeter/reports.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:analysis:vulnerabilities:reports','EventHelp')"
    );
		
		$rmenu[md5("Reports")][] = array(
			"name" => gettext("Profiles"),
			"id"   => "ScanProfiles",
			"url"  => "../vulnmeter/settings.php"
		);
    
	$hmenu[md5("Vulnerabilities")][] = array(
        "name" => gettext("Scan Jobs"),
        "id"   => "Jobs",
        "url"  => "vulnmeter/manage_jobs.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:analysis:vulnerabilities:jobs','EventHelp')"
    );
    
		$rmenu[md5("Jobs")][] = array(
			"name" => gettext("Profiles"),
			"id"   => "ScanProfiles",
			"url"  => "../vulnmeter/settings.php"
		);
    
	    
	$hmenu[md5("Vulnerabilities")][] = array(
        "name" => gettext("Threats Database"),
        "id"   => "Database",
        "url"  => "vulnmeter/threats-db.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:analysis:vulnerabilities:threats_database','EventHelp')"
    );
	
		$rmenu[md5("Database")][] = array(
			"name" => gettext("Profiles"),
			"id"   => "ScanProfiles",
			"url"  => "../vulnmeter/settings.php"
		);
    
	if( Session::am_i_admin() ) 
	{
        $rmenu[md5("Vulnerabilities")][] = array(
           "name" => gettext("Settings"),
           "id"   => "Settings",
           "url"  => "../vulnmeter/webconfig.php"
        );
        $rmenu[md5("Reports")][] = array(
           "name" => gettext("Settings"),
           "id"   => "Settings",
           "url"  => "../vulnmeter/webconfig.php"
        );
        $rmenu[md5("Jobs")][] = array(
           "name" => gettext("Settings"),
           "id"   => "Settings",
           "url"  => "../vulnmeter/webconfig.php"
        );
        $rmenu[md5("Database")][] = array(
           "name" => gettext("Settings"),
           "id"   => "Settings",
           "url"  => "../vulnmeter/webconfig.php"
        );
    }
}


//Analysis -> Detection

$detection = 0;

$report_wireless    = Session::menu_perms("MenuEvents", "ReportsWireless");
$events_anomalies   = Session::menu_perms("MenuEvents", "EventsAnomalies");
$events_nids        = Session::menu_perms("MenuEvents", "EventsNids");
$events_hids        = Session::menu_perms("MenuEvents", "EventsHids");
$events_hids_config = Session::menu_perms("MenuEvents", "EventsHidsConfig");

if ( $report_wireless || $events_anomalies || $events_nids || $events_hids || $events_hids_config ) 
{ 
	$menu[md5("Analysis")]["name"] = "Analysis";
	// Analysis -> Detection -> NIDS
	if ( $events_nids )
	{
        $detection = 1;
        
		$menu[md5("Analysis")][md5("Detection")] = array(
            "name" => gettext("Detection"),
            "id"   => "Detection",
            "url"  => "panel/nids.php"
        );
				
		$hmenu[md5("Detection")][] = array(
			"name" => gettext("NIDS"),
			"id"   => "Detection",
			"url"  => "panel/nids.php",
			"help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:NIDS','Help');"
		);
	}
		
	// Analysis -> Detection -> NIDS	
	if ( $events_hids || $events_hids_config ) 
	{
		$ids = "HIDS";
		if ( $detection == 0 )
		{
			$detection = 1; 
			$ids       = "Detection";
			
			$menu[md5("Analysis")][md5("Detection")] = array(
				"name" => gettext("Detection"),
				"id"   => "Detection",
				"url"  => "ossec/status.php"
			);
		}
		
		$hmenu[md5("Detection")][] = array(
			"name"   => gettext("HIDS"),
			"id"     =>  $ids,
			"url"    => "ossec/status.php",
			"config" => "hids",
			"help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:HIDS:ossec','Help');"
		);	
		
			$rmenu[md5($ids)][] = array(
			   "name" => gettext("Agents"),
			   "url" => "agent.php"
			);
			
			$rmenu[md5($ids)][] = array(
			   "name" => gettext("Agentless"),
			   "url" => "agentless.php"
			);
		
					
			if ( $events_hids_config ) 
			{
				$rmenu[md5($ids)][] = array(
				   "name" => gettext("Edit Rules"),
				   "url"  => "index.php"
				);
				
				$rmenu[md5($ids)][] = array(
				   "name" => gettext("Config"),
				   "url"  => "config.php"
				);
				
				$rmenu[md5($ids)][] = array(
				   "name" => gettext("Ossec Control"),
				   "url" => "ossec_control.php"
				);
			}
	}
	
	// Analysis -> Detection -> Wireless
	if ( $report_wireless ) 
	{
        $ids = "Wireless";
        if ($detection == 0 ) 
		{
            $detection = 1; 
			$ids       = "Detection";
            
			$menu[md5("Analysis")][md5("Detection")] = array(
                "name" => gettext("Detection"),
                "id"   => "Detection",
                "url"  => "wireless/"
            );
        }
        
		$hmenu[md5("Detection")][] = array(
           "name"   => gettext("Wireless IDS"),
           "id"     => $ids,
           "url"    => "wireless/",
		   "config" => "wids",
           "help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:analysis:wireless','EventHelp')"
        );
        
        $rmenu[md5("Wireless")][] = array(
        	"name" => gettext("Setup"),
        	"url" => "../wireless/setup.php"
        );
    }
	
	if ( $events_anomalies && $av_features ) 
	{
        $ids = "Anomalies";
        if ($detection == 0 )
		{		
            $ids = "Detection";
            $menu[md5("Analysis")][md5("Detection")] = array(
                "name" => gettext("Detection"),
                "id"   => "Detection",
                "url"  => "control_panel/anomalies.php"
            );
        }    
        
		$hmenu[md5("Detection")][] = array(
            "name" => gettext("Anomalies"),
            "id"   => $ids,
            "url"  => "control_panel/anomalies.php",
            "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:analysis:anomalies','EventHelp')"
        );
    }   
	
}

/**********************************************************
------------------------- Reports -------------------------
***********************************************************/
$reports = 0;
$menu[md5("Reports")]["name"] = "Reports";
if ( ( Session::menu_perms("MenuReports", "ReportsGLPI") ) && $conf->get_conf("glpi_link", FALSE) != "" )  
{ 

	$menu[md5("Reports")]["name"] = "Reports";
	$menu[md5("Reports")][md5("GLPI")] = array(
	    "name" => gettext("GLPI"),
	    "id"   => "GLPI",
	    "url"  => "$glpi_link"
	);
}



if ( Session::menu_perms("MenuReports", "ReportsReportServer") || Session::menu_perms("MenuReports", "ReportsScheduler") )
{
    $menu[md5("Reports")]["name"] = "Reports";
	// Jasper Manager
	if ( $opensource ) 
	{
		$reports = 1;		
		$menu[md5("Reports")][md5("Reporting Server")] = array(
		   "name"   => gettext("Reports"),
		   "id"     => "Reporting Server",
		   "target" => "main",
		   "url"    => "report/jasper.php?mode=simple"
		);
		
		$hmenu[md5("Reporting Server")][] = array(
		   "name"   => gettext("Reports"),
		   "id"     => "Reporting Server",
		   "target" => "main",
		   "url"    => "report/jasper.php?mode=simple",
		   "help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:reports:reports','Help');"
		);
		
			 $rmenu[md5("Reporting Server")][] = array(
			   "name"   => gettext("Manager"),
			   "target" => "main",
			   "url"    => "../report/jasper.php?mode=advanced"
			);
		
	}
	else // Pro-version
	{
		if ( Session::menu_perms("MenuReports", "ReportsReportServer") )
		{
			$reports = 1;
			$menu[md5("Reports")][md5("Reporting Server")] = array(
			   "name"   => gettext("Reports"),
			   "id"     => "Reporting Server",
			   "target" => "main",
			   "url"    => "report/wizard_custom_reports.php?hmenu=".md5("Reporting Server")."&smenu=".md5("Reporting Server")
			);
			
			$hmenu[md5("Reporting Server")][] = array(
			   "name"   => gettext("Reports"),
			   "id"     => "Reporting Server",
			   "target" => "main",
			   "url"    => "report/wizard_custom_reports.php",
			   "help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:reports:custom+reports','Help');"
			);
			
			if (Session::am_i_admin()) 
			{
				$hmenu[md5("Reporting Server")][] = array(
				   "name"   => gettext("Modules"),
				   "id"     => "Subreports",
				   "target" => "main",
				   "url"    => "report/wizard_subreports.php",
				   "help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:reports:subreports','Help');"
				);
			}
			
			$hmenu[md5("Reporting Server")][] = array(
			   "name"   => gettext("Layouts"),
			   "id"     => "Parameters",
			   "target" => "main",
			   "url"    => "report/wizard_profiles.php",
			   "help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:reports:layouts','Help');"
			);
		}
		
		if ( Session::menu_perms("MenuReports", "ReportsScheduler") )
		{
			$ids = "Scheduler";
			if ( $reports == 0)
			{
				$reports = 1;
				$ids     = "Scheduler";
				$menu[md5("Reports")][md5("Reporting Server")] = array(
				   "name"   => gettext("Reports"),
				   "id"     => "Reporting Server",
				   "target" => "main",
				   "url"    => "report/wizard_scheduler.php",
				);
			}
			
			$hmenu[md5("Reporting Server")][] = array(
				"name"   => gettext("Scheduler"),
				"id"     => $ids,
				"target" => "main",
				"url"    => "report/wizard_scheduler.php",
				"help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:reports:scheduler','Help');"
			);
		}
		
		if (!$cloud_instance) 
		{        
			$hmenu[md5("Reporting Server")][] = array(
			   "name"   => gettext("FOSS Reports"),
			   "id"     => "OSReports",
			   "target" => "main",
			   "ghost"  => true,
			   "url"    => "report/jasper.php?mode=simple",
			   "help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:reports:reports','Help');"
			);
			
			
				$rmenu[md5("Reporting Server")][] = array(
				   "name"   => gettext("FOSS Reports"),
				   "target" => "main",
				   "url"    => "../report/jasper.php?mode=simple"
				);        
			
				$rmenu[md5("OSReports")][] = array(
				   "name"   => gettext("Customize"),
				   "target" => "main",
				   "url"    => "../report/jasper.php?mode=config"
				);
			
				$rmenu[md5("OSReports")][] = array(
				   "name"   => gettext("Manager"),
				   "target" => "main",
				   "url"    => "../report/jasper.php?mode=advanced"
				);
		}
		
	}
}

/**********************************************************
------------------------- Assets --------------------------
***********************************************************/


$policyHosts    = Session::menu_perms("MenuPolicy", "PolicyHosts");
$policyNetworks = Session::menu_perms("MenuPolicy", "PolicyNetworks");
$policyPorts    = Session::menu_perms("MenuPolicy", "PolicyPorts");
$toolsScan      = Session::menu_perms("MenuPolicy", "ToolsScan");
$assetSearch    = Session::menu_perms("MenuPolicy", "5DSearch");

$assets   = 0;
$asset_mp = null;

if ( $policyHosts || $policyNetworks || $policyPorts || $toolsScan || $assetSearch ) 
{ 
    $menu[md5("Assets")]["name"] = "Assets";
    if ( $prodemo && ($policyHosts && $policyNetworks) ) 
	{ 
		$assets   = 1;
		$asset_mp = "Structure";
		
		$menu[md5("Assets")][md5("Assets")] = array(
			"name" => gettext("Assets"),
			"id"   => "Assets",
			"url"  => "policy/entities.php"
		);
		
		$hmenu[md5("Assets")][] = array(
		  "name" => gettext("Structure"),
		  "id"   => "Assets",
		  "url"  => "policy/entities.php",
		  "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:assets:structure','Help');"
		);
		
		if (Session::menu_perms("MenuPolicy", "ReportsOCSInventory") && $conf->get_conf("ocs_link", FALSE) != "")
		{
		   $rmenu[md5("Assets")][] = array(
				"name"   => gettext("OCS Inventory"),
				"target" => "main",
				"url"    => "../policy/ocs_index.php?hmenu=Assets&smenu=Inventory"
			);
		}
	}
	elseif ( $policyHosts )
	{
		$assets   = 1;
		$asset_mp = "Hosts";
		
		$menu[md5("Assets")][md5("Assets")] = array(
			"name" => gettext("Assets"),
			"id"   => "Assets",
			"url"  => "host/host.php"
        );
	}
	elseif( $policyNetworks )
	{
		$assets   = 1;
		$asset_mp = "Nets";
		
		$menu[md5("Assets")][md5("Assets")] = array(
			"name" => gettext("Assets"),
			"id"   => "Assets",
			"url"  => "net/net.php"
        );
	}
	elseif( $policyPorts )
	{
		$assets   = 1;
		$asset_mp = "Ports";
		
		$menu[md5("Assets")][md5("OCS Inventory")] = array(
			"name" => gettext("Assets"),
			"id"   => "Assets",
			"url"  => "port/port.php",
			"help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:assets:ports','Help');"
        );
	}
	
	//Assets -> Assets -> Hosts
	if ( $policyHosts )
	{
		$ids = ( $asset_mp == "Hosts" ) ? "Assets" : "Hosts";
       
		$hmenu[md5("Assets")][] = array(
		  "name" => gettext("Hosts"),
		  "id"   => $ids,
		  "url"  => "host/host.php",
		  "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:assets:host','Help');"
		);
		
			if (Session::menu_perms("MenuPolicy", "ReportsOCSInventory") && $conf->get_conf("ocs_link", FALSE) != "")
			{
				$rmenu[md5($ids)][] = array(
					"name"   => gettext("OCS Inventory"),
					"target" => "main",
					"url"    => "../policy/ocs_index.php?hmenu=Assets&smenu=Inventory"
				);
			}
			
		$hmenu[md5("Assets")][] = array(
			"name" => gettext("Host groups"),
			"id"   => "Host groups",
			"url"  => "host/hostgroup.php",
			"help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:assets:host','Help');"
		);
       
			if (Session::menu_perms("MenuPolicy", "ReportsOCSInventory") && $conf->get_conf("ocs_link", FALSE) != "")
			{
			    $rmenu[md5("Host groups")][] = array(
					"name" => gettext("OCS Inventory"),
					"target" => "main",
					"url" => "../policy/ocs_index.php?hmenu=Assets&smenu=Inventory"
				);
			}
	 
	}
	
	//Assets -> Assets -> Networks
	if ( $policyNetworks )
	{
		$ids = ( $asset_mp == "Nets" ) ? "Assets" : "Nets";
       
		$hmenu[md5("Assets")][] = array(
			"name" => gettext("Networks"),
			"id"   => $ids,
			"url"  => "net/net.php",
			"help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:assets:networks','Help');"
        );
		
		if (Session::menu_perms("MenuPolicy", "ReportsOCSInventory") && $conf->get_conf("ocs_link", FALSE) != "")
		{
		    $rmenu[md5($ids)][] = array(
				"name"   => gettext("OCS Inventory"),
				"target" => "main",
				"url"    => "../policy/ocs_index.php?hmenu=Assets&smenu=Inventory"
			);
        }
		
		$hmenu[md5("Assets")][] = array(
			"name" => gettext("Network groups"),
			"id"   => "Network groups",
			"url"  => "net/netgroup.php",
			"help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:assets:networks','Help');"
        );
        
			if (Session::menu_perms("MenuPolicy", "ReportsOCSInventory") && $conf->get_conf("ocs_link", FALSE) != "")
			{   
			    $rmenu[md5("Network groups")][] = array(
					"name"   => gettext("OCS Inventory"),
					"target" => "main",
					"url"    => "../policy/ocs_index.php?hmenu=Assets&smenu=Inventory"
				);
			}
	
	}
    
	//Assets -> Assets -> Ports
	if ( $policyPorts )
	{
		$ids = ( $asset_mp == "Ports" ) ? "Assets" : "Ports";
		
		$hmenu[md5("Assets")][] = array(
            "name" => gettext("Ports"),
            "id"   => $ids,
            "url"  => "port/port.php",
            "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:assets:ports','Help');"
        );
        
			if (Session::menu_perms("MenuPolicy", "ReportsOCSInventory") && $conf->get_conf("ocs_link", FALSE) != "")
			{   
			    $rmenu[md5($ids)][] = array(
					"name"   => gettext("OCS Inventory"),
					"target" => "main",
					"url"    => "../policy/ocs_index.php?hmenu=Assets&smenu=Inventory"
				);
			}
	}
	
	if ( Session::menu_perms("MenuPolicy", "ReportsOCSInventory") && $conf->get_conf("ocs_link", FALSE) != "" )
	{
		$hmenu[md5("Assets")][] = array(
			"name"   => gettext("OCS Inventory"),
			"id"     => "Inventory",
			"target" => "main",
			"ghost"  => true,
			"url"    => "policy/ocs_index.php",
			"help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:assets:inventory','Help');"
		);
	}
	
	
	//Assets -> Assets -> Inventory
	if ( $assetSearch )
	{
		$menu[md5("Assets")][md5("Asset Search")] = array(
			"name" => gettext("Asset Search"),
			"id"   => "Asset Search",
			"url"  => "inventorysearch/userfriendly.php"
		);
    
		$hmenu[md5("Asset Search")][] = array(
			"id"   => "Asset Search",
			"name" => gettext("Simple"),
			"url"  => "inventorysearch/userfriendly.php",
			"help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:asset_search','Help');"
		);
	
		$hmenu[md5("Asset Search")][] = array(
			"name" => gettext("Advanced"),
			"id"   => "Advanced",
			"url"  => "inventorysearch/inventory_search.php?new=1",
			"help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:asset_search','Help');"
		);
	}
	
		
	//Assets -> Assets -> Assets Discovery
	if ( $toolsScan || Session::am_i_admin() )
	{
		$menu[md5("Assets")][md5("Asset Discovery")] = array(
			"name" => gettext("Asset Discovery"),
			"id"   => "Asset Discovery",
			"url"  => "netscan/index.php"
		);
    
		$hmenu[md5("Asset Discovery")][] = array(
			"name"   => gettext("Instant Scan"),
			"id"     => "Asset Discovery",
			"url"    => "netscan/index.php",
			"config" => ( Session::am_i_admin() ) ? "assetdiscovery" : "",
			"help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:tools:net_discovery','Help');"
		);
	}
	
}

/**********************************************************
--------------------- Intelligence ------------------------
***********************************************************/

$correlation = 0;

$policyPolicy                = Session::menu_perms("MenuIntelligence", "PolicyPolicy");
$policyActions 		         = Session::menu_perms("MenuIntelligence", "PolicyActions");
$correlationDirectives       = Session::menu_perms("MenuIntelligence", "CorrelationDirectives");
$correlationBacklog          = Session::menu_perms("MenuIntelligence", "CorrelationBacklog");
$complianceMapping           = Session::menu_perms("MenuIntelligence", "ComplianceMapping");
$correlationCrossCorrelation = Session::menu_perms("MenuIntelligence", "CorrelationCrossCorrelation");

// Intelligence -> Policy and Intelligence -> Actions
if ( $policyPolicy || $policyActions )
{ 
    $menu[md5("Intelligence")]["name"] = "Intelligence";
    
	if ( $policyPolicy ) 
	{
        $correlation = 1;	
		
		$menu[md5("Intelligence")][md5("Policy")] = array(
          "name" => gettext("Policy & Actions"),
          "id"   => "Policy",
          "url"  => "policy/policy.php"
        );
		
		$hmenu[md5("Policy")][] = array(
           "name" => gettext("Policy"),
           "id"   => "Policy",
           "url"  => "policy/policy.php",
           "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:intelligence:policy_actions:policy','Help');"
        );
		
			$rmenu[md5("Policy")][] = array(
			   "name" => gettext("Edit Policy groups"),
			   "url"  => "../policy/policygroup.php"
			);
	} 
	    
	if ( $policyActions ) 
	{
		$ids = "Policy";
		if ($correlation == 0)
		{
			$correlation = 1;
			$ids         = "Actions";
			
			$menu[md5("Intelligence")][md5("Actions")] = array(
			  "name" => gettext("Policy & Actions"),
			  "id"   => "Actions",
			  "url"  => "action/action.php"
			);
		}
		
		$hmenu[md5($ids)][] = array(
            "name"   => gettext("Actions"),
            "id"     => "Actions",
            "url"    => "action/action.php",
            "help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:intelligence:policy_actions:actions','Help');"
        );
    }
}


//Intelligence -> Correlation Directives
if ( $correlationDirectives )
{
	$correlation = 1;
    $menu[md5("Intelligence")]["name"] = "Intelligence";
    
	$menu[md5("Intelligence")][md5("Directives")] = array(
        "name" => gettext("Correlation Directives"),
        "id"   => "Directives",
        "url"  => "directive_editor/main.php"
    );
    
	$hmenu[md5("Directives")][] = array(
        "name"   => gettext("Directives"),
        "id"     => "Directives",
        "target" => "main",
        "url"    => "directive_editor/main.php",
        "help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:intelligence:correlation_directives:directives','Help');",
    	"config" => "directives"
    );
    
		$rmenu[md5("Directives")][] = array(
			"name"   => gettext("Numbering and Groups"),
			"target" => "main",
			"url"    => "numbering.php"
		);
    
	if ( $complianceMapping ) 
	{
        $hmenu[md5("Directives")][] = array(
           "name"   => gettext("Properties"),
           "id"     => "Compliance",
           "target" => "main",
           "url"    => "compliance/general.php",
           "help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:intelligence:correlation_directives:properties','Help');"
        );
	}
    
	if ( $correlationBacklog ) {
        $hmenu[md5("Directives")][] = array(
            "name"   => gettext("Backlog"),
            "id"     => "Backlog",
            "target" => "main",
            "url"    => "control_panel/backlog.php",
            "help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:intelligence:correlation_directives:backlog','Help');"
        );
    }
}


///Intelligence -> Compliance Mapping
if( $complianceMapping )
{
	$correlation = 1;
    $menu[md5("Intelligence")]["name"] = "Intelligence";
    
	$menu[md5("Intelligence")][md5("Compliance")] = array(
       "name" => gettext("Compliance Mapping"),
       "id"   => "Compliance",
       "url"  => "compliance/iso27001.php"
    );
    
	$hmenu[md5("Compliance")][] = array(
       "name" => gettext("ISO 27001"),
       "id"   => "Compliance",
       "url"  => "compliance/iso27001.php",
       "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:intelligence:compliance_mapping:iso_27001','Help');"
    );
    
	$hmenu[md5("Compliance")][] = array(
       "name" => gettext("PCI DSS 2.0"),
       "id"   => "PCIDSS",
       "url"  => "compliance/pci-dss.php",
       "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:intelligence:compliance_mapping:pci_dss','Help');"
    );
	
	if (file_exists("/usr/share/ossim/www/compliance/n052.php"))
	{
		$hmenu[md5("Compliance")][] = array(
		   "name" => gettext("Norma 052"),
		   "id"   => "N052",
		   "url"  => "compliance/n052.php",
		   "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:intelligence:compliance_mapping:pci_dss','Help');"
		);
		
		$hmenu[md5("Compliance")][] = array(
		   "name" => gettext("Modelo ATH"),
		   "id"   => "modeloath",
		   "url"  => "compliance/modelo-ath.php",
		   "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:intelligence:compliance_mapping:pci_dss','Help');"
		);
    }

	$rmenu[md5("Compliance")][] = array(
	   "name" => gettext("Launch compliance scripts"),
	   "url"  => "../compliance/mod_scripts.php"
	);
	
	$rmenu[md5("PCIDSS")][] = array(
	   "name" => gettext("Launch compliance scripts"),
	   "url"  => "../compliance/mod_scripts.php"
	);

	if (file_exists("/usr/share/ossim/www/compliance/n052.php"))
	{
		$rmenu[md5("N052")][] = array(
		   "name" => gettext("Launch compliance scripts"),
		   "url"  => "../compliance/mod_scripts.php"
		);
		
		$rmenu[md5("modeloath")][] = array(
		   "name" => gettext("Launch compliance scripts"),
		   "url"  => "../compliance/mod_scripts.php"
		);
	}

}

///Intelligence -> Cross Correlation
if( $correlationCrossCorrelation )
{
	$correlation = 1;
    $menu[md5("Intelligence")]["name"] = "Intelligence";
	$menu[md5("Intelligence")][md5("Cross Correlation")] = array(
        "name" => gettext("Cross Correlation"),
        "id"   => "Cross Correlation",
        "url"  => "conf/pluginref.php"
    );
    
	$hmenu[md5("Cross Correlation")][] = array(
        "name" => gettext("Rules"),
        "id"   => "Cross Correlation",
        "url"  => "conf/pluginref.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:intelligence:cross_correlation','Help');"
    );
}


/**********************************************************
----------------- Situational Awareness -------------------
***********************************************************/

$monitors = 0;

$monitors_monitorsNetflows  = Session::menu_perms("MenuMonitors", "MonitorsNetflows");
$monitors_trafficCapture    = Session::menu_perms("MenuMonitors", "TrafficCapture");
$monitors_monitorsNetwork   = Session::menu_perms("MenuMonitors", "MonitorsNetwork");
$monitors_monitorsIPRep     = Session::menu_perms("MenuMonitors", "IPReputation");

//Situational Awareness -> Network
if (( $monitors_monitorsNetflows || $monitors_trafficCapture || $monitors_monitorsNetwork || $monitors_monitorsIPRep) && !$cloud_instance) 
{
	$monitors = 1;
	$menu[md5("Situational Awareness")]["name"] = "Situational Awareness";
	if ( $monitors_monitorsNetflows ) 
	{
        $monitors_mp = "MonitorsNetflows"; 
		$menu[md5("Situational Awareness")][md5("Network")] = array(
			"name" => gettext("Network"),
			"id"   => "Network",
			"url"  => "nfsen/index.php?tab=2"
		);
	}
	elseif( $monitors_monitorsNetwork )
	{
		$monitors_mp = "MonitorsNetwork"; 
		$menu[md5("Situational Awareness")][md5("Network")] = array(
			"name" => gettext("Network"),
			"id"   => "Network",
			"url"  => "ntop/index.php?opc=services"
		);
	}
	else
	{
		$monitors_mp = "TrafficCapture";
		$menu[md5("Situational Awareness")][md5("Network")] = array(
			"name" => gettext("Network"),
			"id"   => "Network",
			"url"  => "pcap/index.php"
		);
	}	
	
	if( $monitors_monitorsNetflows ) 
	{
        $hmenu[md5("Network")][] = array(
            "name"   => gettext("Traffic") ,
            "id"     => "Network",
            "target" => "main",
            "url"    => "nfsen/index.php?tab=2",
            "help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:monitors:network:traffic','Help');"
        );
    }
    
    if( $monitors_monitorsNetwork ) 
	{
        $ids = ( $monitors_mp == "MonitorsNetwork" ) ? "Network" : "Profiles"; 
		
		$hmenu[md5("Network")][] = array(
            "name"   => gettext("Profiles") ,
            "id"     => $ids,
            "target" => "main",
            "url"    => "ntop/index.php?opc=services",
            "help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:monitors:network:profiles','Help');",
            "nFrame" => ""
        );
		
			$rmenu[md5($ids)][] = array(
			   "name"   => gettext("Services"),
			   "target" => "main",
			   "url"    => "../ntop/index.php?opc=services"
			);
			
			$rmenu[md5($ids)][] = array(
			   "name"   => gettext("Global"),
			   "target" => "main",
			   "url"    => "../ntop/index.php"
			);
			
			$rmenu[md5($ids)][] = array(
			   "name"   => gettext("Throughput"),
			   "target" => "main",
			   "url"    => "../ntop/index.php?opc=throughput"
			);
			
			$rmenu[md5($ids)][] = array(
			   "name"   => gettext("Matrix"),
			   "target" => "main",
			   "url"    => "../ntop/index.php?opc=matrix"
			);
	}
    
	if( $monitors_trafficCapture ) 
	{
		$ids = ( $monitors_mp == "TrafficCapture" ) ? "Network" : "Traffic capture"; 
		
		$hmenu[md5("Network")][] = array(
			"name"   => gettext("Traffic capture") ,
			"id"     => $ids,
			"target" => "main",
			"url"    => "pcap/index.php",
			"help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:monitors:network:traffic_capture','Help');",
			"nFrame" => ""
		);
    }
} 	
	
// Situational Awareness -> Availability
if (Session::menu_perms("MenuMonitors", "MonitorsAvailability") && !$cloud_instance) 
{ 
	$monitors = 1;
    $menu[md5("Situational Awareness")]["name"] = "Situational Awareness";
	$menu[md5("Situational Awareness")][md5("Availability")] = array(
        "name" => gettext("Availability"),
        "id"   => "Availability",
        "url"  => "nagios/index.php"
    );
    
	$hmenu[md5("Availability")][] = array(
        "name"   => gettext("Monitoring"),
        "id"     => "Availability",
        "target" => "main",
        "url"    => "nagios/index.php",
        "help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:monitors:availability','Help');"
    );
    
	$hmenu[md5("Availability")][] = array(
        "name"   => gettext("Reporting"),
        "id"     => "Reporting",
        "target" => "main",
        "url"    => "nagios/index.php?opc=reporting",
        "help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:monitors:availability','Help');"
    );
}
	
//Situational Awareness -> Inventory    
	
if ( $prodemo && !$cloud_instance && Session::menu_perms("MenuMonitors", "MonitorsInventory")) 
{ 
	$monitors = 1;
	$menu[md5("Situational Awareness")]["name"] = "Situational Awareness";
	$menu[md5("Situational Awareness")][md5("Inventory")] = array(
	    "name" => gettext("Inventory"),
	    "id"   => "Inventory",
	    "url"  => "policy/entities.php?onlyinventory=1",
	);
	
	$hmenu[md5("Inventory")][] = array(
	    "name"   => gettext("Inventory"),
	    "id"     => "Inventory",
	    "target" => "main",
	    "url"    => "policy/entities.php?onlyinventory=1",
	    "help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:monitors','Help');"
	);
}	

//Situational Awareness -> IP Reputation
$Reputation = new Reputation();	
if ( $monitors_monitorsIPRep && $Reputation->existReputation() )
{
    $menu[md5("Situational Awareness")]["name"] = "Situational Awareness";
    $menu[md5("Situational Awareness")][md5("IP Reputation")] = array(
        "name" => gettext("IP Reputation"),
        "id"   => "IP Reputation",
        "url"  => "reputation/index.php"
    );

    $hmenu[md5("IP Reputation")][] = array(
        "name" => gettext("IP Reputation"),
        "id"   => "IP Reputation",
        "url"  => "reputation/index.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:intelligence:reputation_db','Help');"
    );    
}

/**********************************************************
-------------------- Configuration ------------------------
***********************************************************/

$configuration = 0;

//Configuration -> Main
if ( Session::menu_perms("MenuConfiguration", "ConfigurationMain") ) 
{ 
    $configuration = 1;
	$menu[md5("Configuration")]["name"] = "Configuration";
	$menu[md5("Configuration")][md5("Main")] = array(
        "name" => gettext("Main"),
        "id"   => "Main",
        "url"  => "conf/main.php"
    );
    
	$hmenu[md5("Main")][] = array(
        "name" => gettext("Simple"),
        "id"   => "Main",
        "url"  => "conf/main.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:configuration:configuration','Help');"
    );
    
	$hmenu[md5("Main")][] = array(
        "name" => gettext("Advanced"),
        "id"   => "Advanced",
        "url"  => "conf/main.php?adv=1",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:configuration:configuration','Help');"
    );
     
	if ($prodemo && $av_features && Session::am_i_admin()) 
	{
        $hmenu[md5("Main")][] = array(
	        "name" => gettext("Customization Wizard"),
	        "id"   => "Customize",
	        "url"  => "session/customize.php",
	        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:configuration:customize','Help');"
	    );
    }
}

//Alienvault Center

$menu[md5("Configuration")][md5("Alienvault Center")] = array(
        "name" => gettext("Alienvault Center"),
        "id"   => "Alienvault Center",
        "url"  => "av_center/index.php"
    );

//Configuration -> Users
if ( Session::menu_perms("MenuConfiguration", "ConfigurationUsers") ) 
{ 
	$configuration = 1;
    $menu[md5("Configuration")]["name"] = "Configuration";
	$users_path = (  $opensource ) ? "session/users.php" : "acl/users.php";
	
	$menu[md5("Configuration")][md5("Users")] = array(
        "name" => gettext("Users"),
        "id"   => "Users",
        "url"  => $users_path
    );
    
	$hmenu[md5("Users")][] = array(
        "name"   => gettext("Configuration"),
        "id"     => "Users",
        "url"    => $users_path,
        "help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:configuration:users:users','Help');",
    	"config" => "users"
    );
	
	if ($prodemo && (Session::am_i_admin() || Acl::am_i_proadmin()) ) 
	{
		$rmenu[md5("Users")][] = array(
			  "name"   => gettext("Entities"),
			  "target" => "main",
			  "url"    => "../acl/entities.php"
			);
		
		$rmenu[md5("Users")][] = array(
			  "name"   => gettext("Templates"),
			  "target" => "main",
			  "url"    => "../acl/templates.php"
			);
		
		$rmenu[md5("Users")][] = array(
			  "name"   => gettext("Password Policy"),
			  "target" => "main",
			  "url"    => "../conf/main.php?adv=1&passpolicy=1&hmenu=Main&smenu=Advanced"
			);        
	}
	
	if (Session::menu_perms("MenuConfiguration", "ConfigurationUserActionLog")) 
	{ 
		$configuration = 1; 
		$hmenu[md5("Users")][] = array(
			"name"   => gettext("User activity"),
			"id"     => "User action logs",
			"url"    => "conf/userlog.php",
			"help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:configuration:users:user_activity','Help');",
			"config" => "userlog"
		);
	}

}

// Configuration -> SIEM Components
$policyServers = Session::menu_perms("MenuConfiguration", "PolicyServers");
$policySensors = Session::menu_perms("MenuConfiguration", "PolicySensors");
	
$policy_s = 0;
if ( $policyServers || $policySensors ) 
{ 
    $menu[md5("Configuration")]["name"] = "Configuration";
    
	if ( $policySensors ) 
	{
        $policy_s = 1;
		
		$menu[md5("Configuration")][md5("SIEM Components")] = array(
			"name" => gettext("Alienvault Components"),
			"id"   => "SIEM Components",
			"url"  => "sensor/sensor.php"
        );
		
		$hmenu[md5("SIEM Components")][] = array(
			"name" => gettext("Sensors"),
			"id"   => "SIEM Components",
			"url"  => "sensor/sensor.php",
			"help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:siem_components:sensors','Help');"
		);
		
    } 
	
		
	if ( $policyServers ) 
	{
        $ids = "Servers";
		if ( $policy_s == 0 )
		{
			$policy_s = 1;
			$ids      = "SIEM Components";
			
			$menu[md5("Configuration")][md5("$ids")] = array(
			  "name" => gettext("Alienvault Components"),
			  "id"   => $ids,
			  "url"  => "server/server.php"
			);
		}
		
		if ( $prodemo )
		{
			$hmenu[md5("SIEM Components")][] = array(
				"name" => gettext("Servers"),
				"id"   => $ids,
				"url"  => "server/server.php",
				"help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:siem_components:servers','Help');"
			);
			
			$hmenu[md5("SIEM Components")][] = array(
			   "name" => gettext("Databases"),
			   "id"   => "DBs",
			   "url"  => "server/dbs.php",
			   "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:siem_components:databases','Help');"
			);

			$hmenu[md5("SIEM Components")][] = array(
				"name" => gettext("Web Interfaces"),
				"id"   => "Web Interfaces",
				"url"  => "server/webinterfaces.php",
				"help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:siem_components:webinferfaces','Help');"
			);

        }
    }
}

//Configuration -> Collection

$configurationPlugins =  Session::menu_perms("MenuConfiguration", "ConfigurationPlugins");
$monitorsSensors      =  Session::menu_perms("MenuConfiguration", "MonitorsSensors");
$pluginGroups         =  Session::menu_perms("MenuConfiguration", "PluginGroups");

$collection = 0;
if ( $configurationPlugins || $monitorsSensors || $pluginGroups )
{ 
    $menu[md5("Configuration")]["name"] = "Configuration";
	$menu[md5("Configuration")][md5("Plugins")] = array(
        "name" => gettext("Collection"),
        "id"   => "Plugins",
        "url"  => "sensor/sensor_plugins.php"
    );
    
	if ( $monitorsSensors ) 
	{
		$collection = 1;
	    $hmenu[md5("Plugins")][] = array(
	        "name"   => gettext("Sensors"),
	        "id"     => "Plugins",
	        "target" => "main",
	        "url"    => "sensor/sensor_plugins.php",
	        "help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:monitors:system:sensors','Help');"
	    );
	}
	
	if ( $configurationPlugins ) 
	{
	    $ids = "Data Sources";
		if ( $collection == 0 )
		{
			$collection = 1;
			$ids 		= "Plugins";
		}
		
		$hmenu[md5("Plugins")][] = array(
	        "name" => gettext("Data Sources"),
	        "id"   => $ids,
	        "url"  => "conf/plugin.php",
	        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:configuration:collection:plugins','Help');"
	    );
		
		if ( $pluginGroups ) 
		{
			$rmenu[md5($ids)][] = array(
				"name"   => gettext("DS Groups"),
				"target" => "main",
				"url"    => "../policy/plugingroups.php",
				"help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:configuration:collection:plugin_groups','Help');"
			);
		}
		
		$rmenu[md5($ids)][] = array(
			"name"   => gettext("Taxonomy"),
			"target" => "main",
			"url"    => "../conf/category.php",
			"help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:configuration:collection:manage_taxonomy','Help');"
		);
	}
    	
	if ($av_features && Session::am_i_admin())
		$hmenu[md5("Plugins")][] = array(
			"name" => gettext("Custom Collectors"),
			"id"   => "Custom Collectors",
			"url"  => "policy/collectors.php",
			"help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:configuration:collection:custom_collectors','Help');"
			);
	
	$hmenu[md5("Plugins")][] = array(
		"name" => gettext("Downloads"),
		"id"   => "Downloads",
		"url"  => "downloads/index.php",
		"help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:tools:downloads','Help');"
	);

	if (file_exists("/usr/local/bin/vagent_server.py") && Session::get_session_user()==ACL_DEFAULT_OSSIM_ADMIN) 
	{
		$hmenu[md5("Plugins")][] = array(
			"name" => gettext("Demo Events"),
			"id"   => "Demo Events",
			"url"  => "sensor/events.php",
			"help" => "javascript:;','Help');"
		);   
	}		
}

//Configuration -> Network Discovery
/* Temporally disabled
if(Session::menu_perms("MenuConfiguration", "NetworkDiscovery")) 
{ 
	$menu[md5("Configuration")][] = array(
        "name" => gettext("Network Discovery"),
        "id"   => "Network Discovery",
        "url"  => "net/assetdiscovery.php"
    );    
    
	$hmenu["Network Discovery"][] = array(
        "id"   => "Network Discovery",
        "name" => gettext("Passive Network Discovery"),
        "url"  => "net/assetdiscovery.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:asset_discovery','Help');"
    );
    
	$hmenu["Network Discovery"][] = array(
        "id"   => "Nedi",
        "name" => gettext("Nedi"),
        "url"  => "net/nedi.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:asset_discovery','Help');"
    );
    
	$hmenu["Network Discovery"][] = array(
        "id"   => "Active Directory",
        "name" => gettext("Active Directory"),
        "url"  => "net/activedirectory.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:asset_discovery','Help');"
    );
}
*/

//Configuration -> Software Upgrade

if ( Session::menu_perms("MenuConfiguration", "ConfigurationUpgrade") || Session::am_i_admin() ) 
{ 
    $menu[md5("Configuration")]["name"] = "Configuration";
	$menu[md5("Configuration")][md5("Update")] = array(
        "name" => gettext("Update Notification"),
        "id"   => "Update",
        "url"  => "updates/"
    );
    
	$hmenu[md5("Update")][] = array(
        "name" => gettext("Update Notification"),
        "id"   => "Update",
        "url"  => "updates/",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:configuration:update_notification','Help');"
    );
}

//Configuration -> Backup

if ( Session::menu_perms("MenuConfiguration", "ToolsBackup") ) 
{
    $menu[md5("Configuration")]["name"] = "Configuration";
    $menu[md5("Configuration")][md5("Backup")] = array(
        "name" => gettext("Backup"),
        "id"   => "Backup",
        "url"  => "backup/index.php"
    );
    
	$hmenu[md5("Backup")][] = array(
        "name" => gettext("Security Events Backup"),
        "id"   => "Backup",
        "url"  => "backup/index.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:tools:backup','Help');"
    );
}







/**********************************************************
----------------------- Side-bar --------------------------
***********************************************************/

$sstatus = 0;

if (Session::menu_perms("MenuReports", "ReportsHostReport")) 
{
    $sstatus = 1;
	$hmenu[md5("Sysinfo")][] = array(
        "name"   => gettext("Data Snapshot"),
        "id"     => "Sysinfo",
        "target" => "main",
        "url"    => "report/host_report.php?asset_type=any&asset_key=&star_date=".date("Y-m-d",time()-604800)."&end_date=".date("Y-m-d"),
        "help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:status','Help');"
    );
}

if (Session::menu_perms("MenuConfiguration", "MonitorsSensors")) 
{
    $hmenu[md5("Sysinfo")][] = array(
        "name"   => gettext("Sensors"),
        "id"     => "Plugins",
        "target" => "main",
        "url"    => "sensor/sensor_plugins.php",
        "help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:monitors:system:sensors','Help');"
      );
}

if (Session::menu_perms("MenuConfiguration", "ToolsUserLog")) 
{
	$hmenu[md5("Sysinfo")][] = array(
		"name"   => gettext("User Activity"),
		"id"     => "User Log",
		"url"    => "userlog/user_action_log.php",
		"target" => "main",
		"help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:monitors:system:user_activity','Help');"
	);
}

if (Session::am_i_admin()) 
{
	$hmenu[md5("Sysinfo")][] = array(
	    "name"   => gettext("Hardware Info"),
	    "id"     => ($sstatus) ? "Hardware Info" : "Sysinfo",
	    "url"    => "sysinfo/index.php",
	    "target" => "main", 
	    "help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:sysinfo','Help');"
	);
}

$hmenu[md5("Sysinfo")][] = array(
    "name"   => gettext("Current Sessions"),
    "id"     => ( $sstatus ) ? "Sessions" : "Sysinfo",
    "url"    => "userlog/opened_sessions.php",
    "target" => "main",
    "help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:opened_sessions','Help');"
);

//Cloud
if ($prodemo && $cloud_instance && (Acl::am_i_proadmin() || Session::am_i_admin() )) {
	$menu[md5("My Test")]["name"] = "My Test";
	$menu[md5("My Test")][md5("MyTest")] = array(
        "name" => gettext("My Test"),
        "id"   => "MyTest",
        "target" => "main",
        "url"  => "cloud/mytest.php"
    );
    
    $hmenu[md5("MyTest")][] = array(
        "name"   => gettext("My Test"),
        "id"     => "MyTest",
        "target" => "main",
        "url"    => "cloud/mytest.php",
        "help"   => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual','Help');",
        "config" => "userlog"
    );
}

?>
