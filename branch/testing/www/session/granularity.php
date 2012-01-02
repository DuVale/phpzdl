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

/**********************************************************
----------------------- Status Bar ------------------------
***********************************************************/

$granularity['MainMenu']['Index']['sensor'] = 1;
$granularity['MainMenu']['Index']['net']    = 1;


/**********************************************************
----------------------- Dashboards ------------------------
***********************************************************/

$granularity['MenuControlPanel']['ControlPanelExecutive']['sensor'] = 1;
$granularity['MenuControlPanel']['ControlPanelExecutive']['net']    = 1;

$granularity['MenuControlPanel']['ControlPanelExecutiveEdit']['sensor'] = 1;
$granularity['MenuControlPanel']['ControlPanelExecutiveEdit']['net']    = 1;

$granularity['MenuControlPanel']['BusinessProcesses']['sensor'] = 1;
$granularity['MenuControlPanel']['BusinessProcesses']['net']    = 1;

$granularity['MenuControlPanel']['BusinessProcessesEdit']['sensor'] = 1;
$granularity['MenuControlPanel']['BusinessProcessesEdit']['net']    = 1;

$granularity['MenuControlPanel']['ControlPanelMetrics']['sensor'] = 1;
$granularity['MenuControlPanel']['ControlPanelMetrics']['net']    = 1;

$granularity['MenuControlPanel']['ControlPanelExecutive']['sensor'] = 1;
$granularity['MenuControlPanel']['ControlPanelExecutive']['net']    = 1;

$granularity['MenuControlPanel']['MonitorsRiskmeter']['sensor'] = 1;
$granularity['MenuControlPanel']['MonitorsRiskmeter']['net']    = 1;

$granularity['MenuControlPanel']['ControlPanelVulnerabilities']['sensor'] = 1;
$granularity['MenuControlPanel']['ControlPanelVulnerabilities']['net']    = 1;


/**********************************************************
------------------------ Incidents ------------------------
***********************************************************/

$granularity['MenuIncidents']['ControlPanelAlarms']['sensor'] = 1;
$granularity['MenuIncidents']['ControlPanelAlarms']['net']    = 0;

$granularity['MenuIncidents']['ControlPanelAlarmsDelete']['sensor'] = 0;
$granularity['MenuIncidents']['ControlPanelAlarmsDelete']['net']    = 0;

$granularity['MenuIncidents']['ReportsAlarmReport']['sensor'] = 1;
$granularity['MenuIncidents']['ReportsAlarmReport']['net']    = 0;

$granularity['MenuIncidents']['IncidentsIncidents']['sensor'] = 1;
$granularity['MenuIncidents']['IncidentsIncidents']['net']    = 0;

$granularity['MenuIncidents']['IncidentsOpen']['sensor'] = 0;
$granularity['MenuIncidents']['IncidentsOpen']['net']    = 0;

$granularity['MenuIncidents']['IncidentsDelete']['sensor'] = 0;
$granularity['MenuIncidents']['IncidentsDelete']['net']    = 0;

$granularity['MenuIncidents']['IncidentsReport']['sensor'] = 1;
$granularity['MenuIncidents']['IncidentsReport']['net']    = 0;

$granularity['MenuIncidents']['IncidentsTypes']['sensor'] = 0;
$granularity['MenuIncidents']['IncidentsTypes']['net']    = 0;

$granularity['MenuIncidents']['IncidentsTags']['sensor'] = 0;
$granularity['MenuIncidents']['IncidentsTags']['net']    = 0;

$granularity['MenuIncidents']['ConfigurationEmailTemplate']['sensor'] = 0;
$granularity['MenuIncidents']['ConfigurationEmailTemplate']['net']    = 0;

$granularity['MenuIncidents']['Osvdb']['sensor'] = 0;
$granularity['MenuIncidents']['Osvdb']['net']    = 0;


/**********************************************************
------------------------ Analysis -------------------------
***********************************************************/

$granularity['MenuEvents']['EventsForensics']['sensor'] = 1;
$granularity['MenuEvents']['EventsForensics']['net']    = 1;

$granularity['MenuEvents']['EventsForensicsDelete']['sensor'] = 0;
$granularity['MenuEvents']['EventsForensicsDelete']['net']    = 0;

$granularity['MenuEvents']['EventsRT']['sensor'] = 1;
$granularity['MenuEvents']['EventsRT']['net']    = 0;

$granularity['MenuEvents']['ControlPanelSEM']['sensor'] = 1;
$granularity['MenuEvents']['ControlPanelSEM']['net']    = 0;

$granularity['MenuEvents']['EventsVulnerabilities']['sensor'] = 1;
$granularity['MenuEvents']['EventsVulnerabilities']['net']    = 1;

$granularity['MenuEvents']['EventsVulnerabilities']['sensor'] = 1;
$granularity['MenuEvents']['EventsVulnerabilities']['net']    = 1;

$granularity['MenuEvents']['EventsVulnerabilitiesScan']['sensor'] = 1;
$granularity['MenuEvents']['EventsVulnerabilitiesScan']['net']    = 1;

$granularity['MenuEvents']['EventsVulnerabilitiesDeleteScan']['sensor'] = 1;
$granularity['MenuEvents']['EventsVulnerabilitiesDeleteScan']['net']    = 1;

$granularity['MenuEvents']['EventsNids']['sensor'] = 1;
$granularity['MenuEvents']['EventsNids']['net']    = 1;

$granularity['MenuEvents']['EventsHids']['sensor'] = 1;
$granularity['MenuEvents']['EventsHids']['net']    = 0;

$granularity['MenuEvents']['EventsHidsConfig']['sensor'] = 1;
$granularity['MenuEvents']['EventsHidsConfig']['net']    = 0;

$granularity['MenuEvents']['ReportsWireless']['sensor'] = 1;
$granularity['MenuEvents']['ReportsWireless']['net']    = 0;

/*
$granularity['MenuEvents']['EventsAnomalies']['sensor'] = 1;
$granularity['MenuEvents']['EventsAnomalies']['net']    = 1;

$granularity['MenuEvents']['EventsViewer']['sensor'] = 1;
$granularity['MenuEvents']['EventsViewer']['net']    = 0;

*/


/**********************************************************
------------------------ Reports --------------------------
***********************************************************/

$granularity['MenuReports']['ReportsHostReport']['sensor'] = 1;
$granularity['MenuReports']['ReportsHostReport']['net']    = 1;

$granularity['MenuReports']['ReportsSecurityReport']['sensor'] = 1;
$granularity['MenuReports']['ReportsSecurityReport']['net']    = 0;

$granularity['MenuReports']['ReportsPDFReport']['sensor'] = 1;
$granularity['MenuReports']['ReportsPDFReport']['net']    = 0;


/**********************************************************
------------------------- Assets --------------------------
***********************************************************/

$granularity['MenuPolicy']['PolicyHosts']['sensor'] = 1;
$granularity['MenuPolicy']['PolicyHosts']['net']    = 0;

$granularity['MenuPolicy']['PolicyNetworks']['sensor'] = 0;
$granularity['MenuPolicy']['PolicyNetworks']['net']    = 1;

$granularity['MenuPolicy']['PolicyPorts']['sensor'] = 0;
$granularity['MenuPolicy']['PolicyPorts']['net']    = 0;

$granularity['MenuPolicy']['ReportsOCSInventory']['sensor'] = 1;
$granularity['MenuPolicy']['ReportsOCSInventory']['net']    = 1;

$granularity['MenuPolicy']['5DSearch']['sensor'] = 1;
$granularity['MenuPolicy']['5DSearch']['net']    = 1;

$granularity['MenuPolicy']['ToolsScan']['sensor'] = 0;
$granularity['MenuPolicy']['ToolsScan']['net']    = 1;

/*
$granularity['MenuPolicy']['PolicyResponses']['sensor'] = 0;
$granularity['MenuPolicy']['PolicyResponses']['net']    = 0;

$granularity['MenuPolicy']['PolicyPluginGroups']['sensor'] = 0;
$granularity['MenuPolicy']['PolicyPluginGroups']['net']    = 0;
*/

/**********************************************************
---------------------- Intelligence -----------------------
***********************************************************/

$granularity['MenuIntelligence']['PolicyPolicy']['sensor'] = 1;
$granularity['MenuIntelligence']['PolicyPolicy']['net']    = 1;

$granularity['MenuIntelligence']['PolicyActions']['sensor'] = 0;
$granularity['MenuIntelligence']['PolicyActions']['net']    = 0;

$granularity['MenuIntelligence']['CorrelationDirectives']['sensor'] = 1;
$granularity['MenuIntelligence']['CorrelationDirectives']['net']    = 1;

$granularity['MenuIntelligence']['CorrelationBacklog']['sensor'] = 1;
$granularity['MenuIntelligence']['CorrelationBacklog']['net'] = 0;

$granularity['MenuIntelligence']['ComplianceMapping']['sensor'] = 0;
$granularity['MenuIntelligence']['ComplianceMapping']['net']    = 0;

$granularity['MenuIntelligence']['CorrelationCrossCorrelation']['sensor'] = 0;
$granularity['MenuIntelligence']['CorrelationCrossCorrelation']['net']    = 0;


/**********************************************************
------------------ Situational Awareness ------------------
***********************************************************/

$granularity['MenuMonitors']['MonitorsNetflows']['sensor'] = 1;
$granularity['MenuMonitors']['MonitorsNetflows']['net']    = 1;

$granularity['MenuMonitors']['MonitorsNetwork']['sensor'] = 1;
$granularity['MenuMonitors']['MonitorsNetwork']['net']    = 0;

$granularity['MenuMonitors']['TrafficCapture']['sensor'] = 1;
$granularity['MenuMonitors']['TrafficCapture']['net']    = 0;

$granularity['MenuMonitors']['MonitorsAvailability']['sensor'] = 1;
$granularity['MenuMonitors']['MonitorsAvailability']['net']    = 0;


/**********************************************************
---------------------- Configuration ----------------------
**********************************************************/
$granularity['MenuConfiguration']['ConfigurationMain']['sensor'] = 0;
$granularity['MenuConfiguration']['ConfigurationMain']['net']    = 0;

$granularity['MenuConfiguration']['ConfigurationUsers']['sensor'] = 0;
$granularity['MenuConfiguration']['ConfigurationUsers']['net']    = 0;

$granularity['MenuConfiguration']['ConfigurationUserActionLog']['sensor'] = 0;
$granularity['MenuConfiguration']['ConfigurationUserActionLog']['net']    = 0;

$granularity['MenuConfiguration']['PolicySensors']['sensor'] = 1;
$granularity['MenuConfiguration']['PolicySensors']['net']    = 0;

$granularity['MenuConfiguration']['PolicyServers']['sensor'] = 0;
$granularity['MenuConfiguration']['PolicyServers']['net']    = 0;

$granularity['MenuConfiguration']['ConfigurationPlugins']['sensor'] = 0;
$granularity['MenuConfiguration']['ConfigurationPlugins']['net']    = 0;

$granularity['MenuConfiguration']['PluginGroups']['sensor'] = 0;
$granularity['MenuConfiguration']['PluginGroups']['net']    = 0;

$granularity['MenuConfiguration']['ToolsDownloads']['sensor'] = 0;
$granularity['MenuConfiguration']['ToolsDownloads']['net']    = 0;  

$granularity['MenuConfiguration']['MonitorsSensors']['sensor'] = 1;
$granularity['MenuConfiguration']['MonitorsSensors']['net']    = 0;

$granularity['MenuConfiguration']['ToolsUserLog']['sensor'] = 0;
$granularity['MenuConfiguration']['ToolsUserLog']['net']    = 0;

$granularity['MenuConfiguration']['NetworkDiscovery']['sensor'] = 0;
$granularity['MenuConfiguration']['NetworkDiscovery']['net']    = 0;

$granularity['MenuConfiguration']['ToolsBackup']['sensor'] = 0;
$granularity['MenuConfiguration']['ToolsBackup']['net']    = 0;

/*
$granularity['MenuConfiguration']['ConfigurationRRDConfig']['sensor'] = 0;
$granularity['MenuConfiguration']['ConfigurationRRDConfig']['net']    = 0;
*/

?>