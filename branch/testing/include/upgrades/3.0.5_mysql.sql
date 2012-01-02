SET AUTOCOMMIT=0;
BEGIN;

REPLACE INTO `ossim_acl`.`aco` (`id`, `section_value`, `value`, `order_value`, `name`, `hidden`) VALUES ('90', 'MenuIncidents', 'ControlPanelAlarmsDelete', '2', 'ControlPanelAlarmsDelete', '0');
REPLACE INTO `ossim_acl`.`aco` (`id`, `section_value`, `value`, `order_value`, `name`, `hidden`) VALUES ('91', 'MenuEvents', 'EventsVulnerabilitiesScan', '7', 'EventsVulnerabilitiesScan', '0');
REPLACE INTO `ossim_acl`.`aco` (`id`, `section_value`, `value`, `order_value`, `name`, `hidden`) VALUES ('92', 'MenuEvents', 'EventsVulnerabilitiesDeleteScan', '8', 'EventsVulnerabilitiesDeleteScan', '0');
REPLACE INTO `ossim_acl`.`aco` (`id`, `section_value`, `value`, `order_value`, `name`, `hidden`) VALUES ('93', 'MenuEvents', 'EventsHidsConfig', '11', 'EventsHidsConfig', '0');

-- Dashboards
UPDATE `ossim_acl`.`aco` SET `order_value` = '1' WHERE `aco`.`id` =15;
UPDATE `ossim_acl`.`aco` SET `order_value` = '2' WHERE `aco`.`id` =16;
UPDATE `ossim_acl`.`aco` SET `order_value` = '3' WHERE `aco`.`id` =60;
UPDATE `ossim_acl`.`aco` SET `order_value` = '4' WHERE `aco`.`id` =61;
UPDATE `ossim_acl`.`aco` SET `order_value` = '5' WHERE `aco`.`id` =17;
UPDATE `ossim_acl`.`aco` SET `order_value` = '6' WHERE `aco`.`id` =20;
UPDATE `ossim_acl`.`aco` SET `order_value` = '7' WHERE `aco`.`id` =21;
UPDATE `ossim_acl`.`aco` SET `order_value` = '8' WHERE `aco`.`id` =22;
UPDATE `ossim_acl`.`aco` SET `order_value` = '9' WHERE `aco`.`id` =44;
UPDATE `ossim_acl`.`aco` SET `order_value` = '10' WHERE `aco`.`id` =19;
UPDATE `ossim_acl`.`aco` SET `order_value` = '11' WHERE `aco`.`id` =18;

-- Incidents
UPDATE `ossim_acl`.`aco` SET `order_value` = '1' WHERE `aco`.`id` =74;
UPDATE `ossim_acl`.`aco` SET `order_value` = '2' WHERE `aco`.`id` =90;
UPDATE `ossim_acl`.`aco` SET `order_value` = '3' WHERE `aco`.`id` =33;
UPDATE `ossim_acl`.`aco` SET `order_value` = '4' WHERE `aco`.`id` =36;
UPDATE `ossim_acl`.`aco` SET `order_value` = '5' WHERE `aco`.`id` =83;
UPDATE `ossim_acl`.`aco` SET `order_value` = '6' WHERE `aco`.`id` =84;
UPDATE `ossim_acl`.`aco` SET `order_value` = '7' WHERE `aco`.`id` =38;
UPDATE `ossim_acl`.`aco` SET `order_value` = '8' WHERE `aco`.`id` =37;
UPDATE `ossim_acl`.`aco` SET `order_value` = '9' WHERE `aco`.`id` =39;
UPDATE `ossim_acl`.`aco` SET `order_value` = '10' WHERE `aco`.`id` =54;
UPDATE `ossim_acl`.`aco` SET `order_value` = '11' WHERE `aco`.`id` =69;

-- Analysis
UPDATE `ossim_acl`.`aco` SET `order_value` = '1' WHERE `aco`.`id`=66;
UPDATE `ossim_acl`.`aco` SET `order_value` = '2' WHERE `aco`.`id`=62;
UPDATE `ossim_acl`.`aco` SET `order_value` = '3' WHERE `aco`.`id`=85;
UPDATE `ossim_acl`.`aco` SET `order_value` = '4' WHERE `aco`.`id`=65;
UPDATE `ossim_acl`.`aco` SET `order_value` = '5' WHERE `aco`.`id`=75;
UPDATE `ossim_acl`.`aco` SET `order_value` = '6' WHERE `aco`.`id`=63;
UPDATE `ossim_acl`.`aco` SET `order_value` = '7' WHERE `aco`.`id`=91;
UPDATE `ossim_acl`.`aco` SET `order_value` = '8' WHERE `aco`.`id`=92;
UPDATE `ossim_acl`.`aco` SET `order_value` = '9' WHERE `aco`.`id`=86;
UPDATE `ossim_acl`.`aco` SET `order_value` = '10' WHERE `aco`.`id`=87;
UPDATE `ossim_acl`.`aco` SET `order_value` = '10' WHERE `aco`.`id`=93;
UPDATE `ossim_acl`.`aco` SET `order_value` = '12' WHERE `aco`.`id`=76;
UPDATE `ossim_acl`.`aco` SET `order_value` = '13' WHERE `aco`.`id`=64;

-- Reports
UPDATE `ossim_acl`.`aco` SET `order_value` = '1' WHERE `aco`.`id` =81;
UPDATE `ossim_acl`.`aco` SET `order_value` = '2' WHERE `aco`.`id` =79;
UPDATE `ossim_acl`.`aco` SET `order_value` = '3' WHERE `aco`.`id` =32;
UPDATE `ossim_acl`.`aco` SET `order_value` = '4' WHERE `aco`.`id` =34;
UPDATE `ossim_acl`.`aco` SET `order_value` = '5' WHERE `aco`.`id` =35;
UPDATE `ossim_acl`.`aco` SET `order_value` = '6' WHERE `aco`.`id` =72;

-- Assets
UPDATE `ossim_acl`.`aco` SET `order_value` = '1' WHERE `aco`.`id` =78;
UPDATE `ossim_acl`.`aco` SET `order_value` = '2' WHERE `aco`.`id` =24;
UPDATE `ossim_acl`.`aco` SET `order_value` = '3' WHERE `aco`.`id` =25;
UPDATE `ossim_acl`.`aco` SET `order_value` = '4' WHERE `aco`.`id` =28;
UPDATE `ossim_acl`.`aco` SET `order_value` = '5' WHERE `aco`.`id` =68;
UPDATE `ossim_acl`.`aco` SET `order_value` = '6' WHERE `aco`.`id` =56;
UPDATE `ossim_acl`.`aco` SET `order_value` = '7' WHERE `aco`.`id` =27;
UPDATE `ossim_acl`.`aco` SET `order_value` = '8' WHERE `aco`.`id` =30;
UPDATE `ossim_acl`.`aco` SET `order_value` = '9' WHERE `aco`.`id` =31;

-- Intelligence
UPDATE `ossim_acl`.`aco` SET `order_value` = '1' WHERE `aco`.`id` =23;
UPDATE `ossim_acl`.`aco` SET `order_value` = '2' WHERE `aco`.`id` =29;
UPDATE `ossim_acl`.`aco` SET `order_value` = '3' WHERE `aco`.`id` =45;
UPDATE `ossim_acl`.`aco` SET `order_value` = '4' WHERE `aco`.`id` =47;
UPDATE `ossim_acl`.`aco` SET `order_value` = '5' WHERE `aco`.`id` =77;
UPDATE `ossim_acl`.`aco` SET `order_value` = '6' WHERE `aco`.`id` =46;

-- Situational Awareness
UPDATE `ossim_acl`.`aco` SET `order_value` = '1' WHERE `aco`.`id` =80;
UPDATE `ossim_acl`.`aco` SET `order_value` = '2' WHERE `aco`.`id` =89;
UPDATE `ossim_acl`.`aco` SET `order_value` = '3' WHERE `aco`.`id` =41;
UPDATE `ossim_acl`.`aco` SET `order_value` = '4' WHERE `aco`.`id` =42;
UPDATE `ossim_acl`.`aco` SET `order_value` = '5' WHERE `aco`.`id` =73;
UPDATE `ossim_acl`.`aco` SET `order_value` = '6' WHERE `aco`.`id` =40;

-- Configuration
UPDATE `ossim_acl`.`aco` SET `order_value` = '1' WHERE `aco`.`id` =48;
UPDATE `ossim_acl`.`aco` SET `order_value` = '2' WHERE `aco`.`id` =49;
UPDATE `ossim_acl`.`aco` SET `order_value` = '3' WHERE `aco`.`id` =53;
UPDATE `ossim_acl`.`aco` SET `order_value` = '4' WHERE `aco`.`id` =26;
UPDATE `ossim_acl`.`aco` SET `order_value` = '5' WHERE `aco`.`id` =27;
UPDATE `ossim_acl`.`aco` SET `order_value` = '6' WHERE `aco`.`id` =50;
UPDATE `ossim_acl`.`aco` SET `order_value` = '7' WHERE `aco`.`id` =82;
UPDATE `ossim_acl`.`aco` SET `order_value` = '8' WHERE `aco`.`id` =71;
UPDATE `ossim_acl`.`aco` SET `order_value` = '9' WHERE `aco`.`id` =43;
UPDATE `ossim_acl`.`aco` SET `order_value` = '10' WHERE `aco`.`id` =59;
UPDATE `ossim_acl`.`aco` SET `order_value` = '11' WHERE `aco`.`id` =88;
UPDATE `ossim_acl`.`aco` SET `order_value` = '12' WHERE `aco`.`id` =51;
UPDATE `ossim_acl`.`aco` SET `order_value` = '13' WHERE `aco`.`id` =58;
UPDATE `ossim_acl`.`aco` SET `order_value` = '14' WHERE `aco`.`id` =52;
UPDATE `ossim_acl`.`aco` SET `order_value` = '15' WHERE `aco`.`id` =55;
UPDATE `ossim_acl`.`aco` SET `order_value` = '16' WHERE `aco`.`id` =70;

use snort;
delete from snort.reference where ref_tag like '%whitehats%';

use ossim;
-- Never use accumulate tables. Changes:
REPLACE INTO `inventory_search` (`type`, `subtype`, `match`, `list`, `query`, `ruleorder`) VALUES
('Alarms', 'IP is Src', 'boolean', '', 'SELECT DISTINCT INET_NTOA(alarm.src_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id', 999),
('META', 'Has Dst IP', 'boolean', '', 'SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid UNION SELECT DISTINCT INET_NTOA(alarm.dst_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id', 999),
('META', 'Date After', 'date', '', 'SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND a.timestamp > ? UNION SELECT DISTINCT INET_NTOA(alarm.src_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id AND alarm.timestamp > ? UNION SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND a.timestamp > ? UNION SELECT DISTINCT INET_NTOA(alarm.dst_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id AND alarm.timestamp > ?', 999),
('META', 'Port as Src or Dst', 'concat', 'SELECT CONCAT(p1.port_number,"-",p2.id) as port_value,CONCAT(p1.port_number,"-",p1.protocol_name) as port_text FROM port p1, protocol p2 WHERE p1.protocol_name=p2.name', 'SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND layer4_sport = ? AND ip_proto = ? UNION SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND layer4_sport = ? AND ip_proto = ? UNION SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND layer4_dport = ? AND ip_proto = ? UNION SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND layer4_dport = ? AND ip_proto = ?', 999),
('META', 'IP as Src', 'ip', 'SELECT ip FROM host', 'SELECT DISTINCT INET_NTOA(ip_dst) AS ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND INET_NTOA(ip_src) %op% ? UNION SELECT DISTINCT INET_NTOA(alarm.dst_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id AND INET_NTOA(alarm.src_ip) %op% ?', 999),
('META', 'IP as Dst', 'ip', 'SELECT ip FROM host', 'SELECT DISTINCT INET_NTOA(ip_src) AS ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND INET_NTOA(ip_dst) %op% ? UNION SELECT DISTINCT INET_NTOA(alarm.src_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id AND INET_NTOA(alarm.dst_ip) %op% ?', 999),
('META', 'IP as Src or Dst', 'ip', 'SELECT DISTINCT ip FROM host', 'SELECT DISTINCT INET_NTOA(ip_dst) AS ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND INET_NTOA(ip_src) %op% ? UNION SELECT DISTINCT INET_NTOA(alarm.dst_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id AND INET_NTOA(alarm.src_ip) %op% ? UNION SELECT DISTINCT INET_NTOA(ip_src) AS ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND INET_NTOA(ip_dst) %op% ? UNION SELECT DISTINCT INET_NTOA(alarm.src_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id AND INET_NTOA(alarm.dst_ip) %op% ?', 999),
('META', 'Source Port', 'concat', 'SELECT CONCAT(p1.port_number,"-",p2.id) as port_value,CONCAT(p1.port_number,"-",p1.protocol_name) as port_text FROM port p1, protocol p2 WHERE p1.protocol_name=p2.name', 'SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND layer4_sport = ? AND ip_proto = ? UNION SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND layer4_sport = ? AND ip_proto = ?', 999),
('META', 'Destination Port', 'concat', 'SELECT CONCAT(p1.port_number,"-",p2.id) as port_value,CONCAT(p1.port_number,"-",p1.protocol_name) as port_text FROM port p1, protocol p2 WHERE p1.protocol_name=p2.name', 'SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND layer4_dport = ? AND ip_proto = ? UNION SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND layer4_dport = ? AND ip_proto = ?', 999),
('SIEM Events', 'Has Different', 'number', '', 'select inet_ntoa(t1.ip) as ip, sensor from (select count(distinct plugin_id, plugin_sid) as total,ip, sensor from (select plugin_id, plugin_sid, ip_src as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid UNION select plugin_id, plugin_sid ,ip_dst as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid) as t group by ip, sensor) as t1 where t1.total >= ?', 5),
('SIEM Events', 'Has Event', 'boolean', '', 'SELECT DISTINCT INET_NTOA(ip_src) as ip, sensor FROM snort.acid_event, snort.sensor WHERE snort.acid_event.sid = snort.sensor.sid UNION SELECT DISTINCT INET_NTOA(ip_dst) as ip, sensor FROM snort.acid_event, snort.sensor WHERE snort.acid_event.sid = snort.sensor.sid', 5),
('SIEM Events', 'Has no Event', 'boolean', '', 'SELECT DISTINCT h.ip, h.id FROM ossim.host h, ossim.host_sensor_reference r, sensor s WHERE h.id = r.host_id AND r.sensor_name = s.name AND CONCAT(h.ip,'','',s.ip) NOT IN (SELECT DISTINCT CONCAT (INET_NTOA(si.ip_src),'','',ss.sensor) FROM snort.acid_event si, snort.sensor ss WHERE si.sid = ss.sid AND CONCAT (INET_NTOA(si.ip_src),'','',ss.sensor) != NULL UNION SELECT DISTINCT CONCAT(INET_NTOA(si.ip_dst),'','',ss.sensor) FROM snort.acid_event si, snort.sensor ss WHERE si.sid = ss.sid AND CONCAT(INET_NTOA(si.ip_dst),'','',ss.sensor) != "NULL")', 5),
('SIEM Events', 'Has Events', 'text', '', 'SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor as sensor FROM snort.acid_event s, ossim.plugin_sid p, snort.sensor ss WHERE s.sid = ss.sid AND s.plugin_id=p.plugin_id AND s.plugin_sid=p.sid AND p.name %op% ? UNION SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor as sensor FROM snort.acid_event s, ossim.plugin_sid p, snort.sensor ss WHERE s.sid = ss.sid AND s.plugin_id=p.plugin_id AND s.plugin_sid=p.sid AND p.name %op% ?', 5),
('SIEM Events', 'Has Plugin Groups', 'fixed', 'SELECT group_id,name FROM plugin_group_descr', 'SELECT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event, snort.sensor ss WHERE snort.acid_event.sid = ss.sid AND plugin_id in (SELECT plugin_id FROM ossim.plugin_group WHERE group_id=?) UNION SELECT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event, snort.sensor ss WHERE snort.acid_event.sid = ss.sid AND plugin_id in (SELECT plugin_id FROM ossim.plugin_group WHERE group_id=?)', 5),
('SIEM Events', 'Has IP', 'ip', 'SELECT ip FROM host', 'SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event i, snort.sensor ss WHERE i.sid = ss.sid AND INET_NTOA(ip_dst) %op% ? UNION SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event i, snort.sensor ss WHERE i.sid = ss.sid AND INET_NTOA(ip_src) %op% ?', 5),
('SIEM Events', 'Has Src IP', 'ip', 'SELECT ip FROM host', 'SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event i, snort.sensor ss WHERE i.sid = ss.sid AND INET_NTOA(ip_src) %op% ?', 5),
('SIEM Events', 'Has Dst IP', 'ip', 'SELECT ip FROM host', 'SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event i, snort.sensor ss WHERE i.sid = ss.sid AND INET_NTOA(ip_dst) %op% ?', 5),
('SIEM Events', 'Has Port', 'concat', 'SELECT DISTINCT CONCAT(p.id,"-",h.port) as protocol_value,CONCAT(h.port,"-",p.name) as protocol_text from host_services h,protocol p where h.protocol=p.id order by h.port', 'SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND ip_proto = ? AND layer4_sport = ? UNION SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND ip_proto = ? AND layer4_dport = ?', 5),
('SIEM Events', 'Has Src Port', 'concat', 'SELECT DISTINCT CONCAT(p.id,"-",h.port) as protocol_value,CONCAT(h.port,"-",p.name) as protocol_text from host_services h,protocol p where h.protocol=p.id order by h.port', 'SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND ip_proto = ? AND layer4_sport = ?', 5),
('SIEM Events', 'Has Dst Port', 'concat', 'SELECT DISTINCT CONCAT(p.id,"-",h.port) as protocol_value,CONCAT(h.port,"-",p.name) as protocol_text from host_services h,protocol p where h.protocol=p.id order by h.port', 'SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND ip_proto = ? AND layer4_dport = ?', 5),
('SIEM Events', 'Has Protocol', 'fixed', 'SELECT id,alias FROM protocol', 'SELECT DISTINCT INET_NTOA(snort.acid_event.ip_src) as ip, ss.sensor FROM snort.acid_event, snort.sensor ss WHERE snort.acid_event.sid = ss.sid AND snort.acid_event.ip_proto=? LIMIT 999', 5),
('Alarms', 'Has Alarm', 'boolean', '', 'SELECT INET_NTOA(alarm.src_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id UNION SELECT INET_NTOA(alarm.dst_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id', 999),
('Alarms', 'Has no Alarm', 'boolean', '', 'SELECT DISTINCT ip, sensor FROM (select host.ip, s.ip as sensor from host, host_sensor_reference r, sensor s WHERE host.id=r.host_id AND r.sensor_name=s.name UNION select distinct INET_NTOA(ip_src) as ip, ss.sensor from snort.acid_event a, snort.sensor ss WHERE a.sid=ss.sid) as todas WHERE CONCAT(ip,'','',sensor) not in (select distinct CONCAT(alarm.src_ip,'','',event.sensor) from alarm, event WHERE alarm.event_id = event.id) AND CONCAT(ip,'','',sensor) not in (select distinct CONCAT(alarm.dst_ip,'','',event.sensor) from alarm, event WHERE alarm.event_id = event.id)', 999),
('Tickets', 'Has Tickets', 'boolean', '', 'SELECT DISTINCT a.src_ips as ip FROM incident i,incident_alarm a WHERE i.id=a.incident_id', 999),
('Tickets', 'Has no Ticket', 'boolean', '', 'select distinct inet_ntoa(ip) as ip from (select inet_aton(ip) as ip from host UNION select distinct ip_src as ip from snort.acid_event) as todas WHERE ip NOT IN (SELECT DISTINCT a.src_ips as ip FROM incident i,incident_alarm a WHERE i.id=a.incident_id)', 999),
('Tickets', 'Has Ticket Type', 'fixed', 'SELECT id as type_value,id as type_text FROM incident_type', 'SELECT DISTINCT a.src_ips as ip FROM incident i,incident_alarm a WHERE i.id=a.incident_id AND i.type_id=?', 999),
('Tickets', 'Has Ticket Tag', 'fixed', 'SELECT id as tag_id,name as tag_name FROM incident_tag_descr', 'SELECT DISTINCT a.src_ips as ip FROM incident i,incident_alarm a,incident_tag t WHERE i.id=a.incident_id AND i.id=t.incident_id AND t.tag_id=?', 999),
('Mac', 'Has Mac', 'boolean', '', 'SELECT DISTINCT ip, sensor FROM host_properties WHERE property_ref=7 UNION SELECT DISTINCT INET_NTOA(ip) as ip, INET_NTOA(sensor) as sensor FROM host_mac', 3),
('Mac', 'Has No Mac', 'boolean', '', 'SELECT host.ip, host.id FROM host, host_sensor_reference, sensor WHERE host.id=host_sensor_reference.host_id AND host_sensor_reference.sensor_name=sensor.name AND CONCAT(host.ip,'','',sensor.ip) NOT IN (SELECT DISTINCT CONCAT(ip,'','',sensor) FROM host_properties WHERE property_ref=7 UNION SELECT DISTINCT CONCAT(INET_NTOA(ip),'','',INET_NTOA(sensor)) FROM host_mac)', 3),
('Mac', 'Has Anomaly', 'boolean', '', 'SELECT DISTINCT INET_NTOA(host_mac.ip) as ip, INET_NTOA(host_mac.sensor) as sensor FROM (SELECT DISTINCT ip as lastip,max(date) as maxdate FROM host_mac GROUP BY ip) maxdates,host_mac WHERE host_mac.ip=maxdates.lastip AND host_mac.date=maxdates.maxdate AND host_mac.anom=1', 3),
('Mac', 'Has no Anomaly', 'boolean', '', 'SELECT DISTINCT INET_NTOA(host_mac.ip) as ip, INET_NTOA(host_mac.sensor) as sensor FROM (SELECT DISTINCT ip as lastip,max(date) as maxdate FROM host_mac GROUP BY ip) maxdates,host_mac WHERE host_mac.ip=maxdates.lastip AND host_mac.date=maxdates.maxdate AND host_mac.anom=0', 3),
('SIEM Events', 'IP is Dst', 'boolean', '', 'SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid LIMIT 999', 5),
('META', 'Has Src or Dst IP', 'boolean', '', 'SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid UNION SELECT DISTINCT INET_NTOA(alarm.src_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id UNION SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid UNION SELECT DISTINCT INET_NTOA(alarm.dst_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id', 999),
('Alarms', 'IP is Dst', 'boolean', '', 'SELECT DISTINCT INET_NTOA(alarm.dst_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id', 999),
('META', 'Has Src IP', 'boolean', '', 'SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid UNION SELECT DISTINCT INET_NTOA(alarm.src_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id', 999),
('META', 'Date Before', 'date', '', 'SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND a.timestamp < ? UNION SELECT DISTINCT INET_NTOA(alarm.src_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id AND alarm.timestamp < ? UNION SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND a.timestamp < ? UNION SELECT DISTINCT INET_NTOA(alarm.dst_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id AND alarm.timestamp < ?', 999),
('SIEM Events', 'IP is Src', 'boolean', '', 'SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid LIMIT 999', 5),
('Alarms', 'Has open Alarms', 'boolean', '', 'SELECT DISTINCT INET_NTOA(alarm.src_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id AND status=''open'' UNION SELECT DISTINCT INET_NTOA(alarm.dst_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id AND status=''open''', 999),
('Alarms', 'Has closed Alarms', 'boolean', '', 'SELECT DISTINCT INET_NTOA(alarm.src_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id AND status=''closed'' UNION SELECT DISTINCT INET_NTOA(alarm.dst_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id AND status=''closed''', 999),
('OS', 'OS is', 'text', 'SELECT DISTINCT os FROM host_os WHERE os != "" ORDER BY os', '(select distinct inet_ntoa(ip) as ip, inet_ntoa(sensor) as sensor from host_properties where property_ref=3 and value like ?) UNION (select distinct inet_ntoa(h.ip) as ip, inet_ntoa(sensor) as sensor from host_os h where h.os %op% ? and h.anom=0 and concat(h.ip,'','',h.sensor) not in (select concat(h1.ip,'','',h1.sensor) from host_os h1 where h1.os<>? and h1.anom=0 and h1.date>h.date)) UNION (select distinct inet_ntoa(ip) as ip, inet_ntoa(sensor) as sensor from host_os where os %op% ? and anom=1 and concat(ip,'','',sensor) not in (select distinct concat(ip,'','',sensor) from host_os where anom=0))', 1),
('OS', 'OS is Not', 'text', 'SELECT DISTINCT os FROM host_os WHERE os != "" ORDER BY os', 'select distinct inet_ntoa(ip) as ip, inet_ntoa(sensor) as sensor from host_os where concat(ip,'','',sensor) not in (select distinct concat(inet_aton(ip),'','',inet_aton(sensor)) from host_properties where property_ref=3 and value like ? UNION select concat(h.ip,'','',h.sensor) from host_os h where h.os %op% ? and h.anom=0 and concat(h.ip,'','',h.sensor) not in (select concat(h1.ip,'','',h1.sensor) from host_os h1 where h1.os<>? and h1.anom=0 and h1.date>h.date)) UNION (select ip, sensor from host_os where os %op% ? and anom=1 and concat(ip,'','',sensor) not in (select distinct concat(ip,'','',sensor) from host_os where anom=0))', 1),
('OS', 'Has Anomaly', 'boolean', '', 'SELECT DISTINCT INET_NTOA(host_os.ip) as ip, INET_NTOA(host_os.sensor) as sensor FROM (SELECT DISTINCT ip as lastip,max(date) as maxdate FROM host_os GROUP BY ip) maxdates,host_os WHERE host_os.ip=maxdates.lastip AND host_os.date=maxdates.maxdate AND host_os.anom=1', 1),
('OS', 'Has no Anomaly', 'boolean', '', 'SELECT DISTINCT INET_NTOA(host_os.ip) as ip, INET_NTOA(host_os.sensor) as sensor FROM (SELECT DISTINCT ip as lastip,max(date) as maxdate FROM host_os GROUP BY ip) maxdates,host_os WHERE host_os.ip=maxdates.lastip AND host_os.date=maxdates.maxdate AND host_os.anom=0', 1),
('Services', 'Has services', 'fixed', 'SELECT DISTINCT service as service_value, service as service_text FROM host_services', 'SELECT DISTINCT INET_NTOA(ip) as ip, INET_NTOA(sensor) as sensor FROM host_services WHERE service=?', 2),
('Services', 'Doesnt have service', 'fixed', 'SELECT DISTINCT service as service_value, service as service_text FROM host_services', 'SELECT DISTINCT host.ip, sensor.ip FROM host, host_sensor_reference, sensor WHERE host.id=host_sensor_reference.host_id AND host_sensor_reference.sensor_name=sensor.name AND CONCAT(host.ip,'','',sensor.ip) NOT IN (SELECT DISTINCT CONCAT(INET_NTOA(ip),'','',INET_NTOA(sensor)) FROM host_services WHERE service=?)', 2),
('Services', 'Has Anomaly', 'boolean', '', 'select distinct inet_ntoa(h.ip) as ip, inet_ntoa(h.sensor) as sensor from host_services h,host_services h1 where h1.ip=h.ip AND h.anom=0 AND h1.anom=1 AND h.port=h1.port AND h.date<=h1.date', 2),
('Services', 'Has no Anomaly', 'boolean', '', 'SELECT DISTINCT INET_NTOA(ip) as ip, INET_NTOA(sensor) as sensor FROM host_services WHERE CONCAT(ip,'','',sensor) NOT IN (select distinct CONCAT(h.ip,'','',h.sensor) from host_services h,host_services h1 where h1.ip=h.ip AND h.anom=0 AND h1.anom=1 AND h.port=h1.port AND h.date<=h1.date)', 2),
('SIEM Events', 'Has user', 'text', '', 'SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event, snort.extra_data, snort.sensor ss WHERE snort.acid_event.sid=ss.sid AND snort.extra_data.sid=snort.acid_event.sid AND snort.extra_data.cid=snort.acid_event.cid AND snort.extra_data.username %op% ?', 5),
('Tickets', 'Priority is greater than', 'number', '', 'SELECT DISTINCT a.src_ips as ip FROM incident i,incident_alarm a WHERE i.id=a.incident_id AND i.priority>?', 999),
('Tickets', 'Priority is lower than', 'number', '', 'SELECT DISTINCT a.src_ips as ip FROM incident i,incident_alarm a WHERE i.id=a.incident_id AND i.priority<?', 999),
('Tickets', 'Is older Than Days', 'number', '', 'SELECT DISTINCT a.src_ips as ip FROM incident i,incident_alarm a WHERE i.id=a.incident_id AND DATEDIFF(CURRENT_TIMESTAMP ,i.last_update) > ?', 999),
('Vulnerabilities', 'Has Vuln', 'fixed', 'SELECT sid as plugin_value,name as plugin_text FROM plugin_sid WHERE plugin_id =3001', 'SELECT DISTINCT host.ip, host.id FROM host, host_plugin_sid WHERE host.id=host_plugin_sid.host_id AND plugin_id = 3001 AND plugin_sid = ?', 4),
('Vulnerabilities', 'Vuln Contains', 'text', '', 'SELECT DISTINCT h.ip as ip, h.id as id FROM host_plugin_sid hp, plugin_sid p, host h WHERE hp.host_id = h.id AND hp.plugin_id = 3001 AND p.plugin_id = 3001 AND hp.plugin_sid = p.sid AND p.name %op% ? UNION SELECT DISTINCT h.ip as ip, h.id as id FROM vuln_nessus_plugins p,host_plugin_sid s, host h WHERE s.host_id = h.id AND s.plugin_id=3001 and s.plugin_sid=p.id AND p.name %op% ?', 4),
('Vulnerabilities', 'Has Vulns', 'boolean', '', 'SELECT DISTINCT host.ip, host.id FROM host_plugin_sid, host WHERE host_plugin_sid.host_id = host.id AND plugin_id = 3001', 4),
('Vulnerabilities', 'Has no Vulns', 'boolean', '', 'SELECT DISTINCT host.ip, host.id FROM host WHERE host.id NOT IN (SELECT host.id FROM host_plugin_sid, host WHERE host_plugin_sid.host_id = host.id AND plugin_id = 3001)', 4),
('Vulnerabilities', 'Vuln Level is greater than', 'number', '', 'SELECT DISTINCT host.ip, host.id FROM host_vulnerability, host WHERE host_vulnerability.host_id = host.id AND vulnerability > ?', 4),
('Vulnerabilities', 'Vuln Level is lower than', 'number', '', 'SELECT DISTINCT host.ip, host.id FROM host_vulnerability, host WHERE host_vulnerability.host_id = host.id AND vulnerability < ?', 4),
('Asset', 'Asset is greater than', 'number', '', 'SELECT DISTINCT h.ip, s.ip as sensor FROM host h, host_sensor_reference r, sensor s WHERE h.id = r.host_id AND r.sensor_name = s.name AND h.asset > ? UNION SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND ossim_asset_src > ? UNION SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND ossim_asset_dst > ?', 999),
('Asset', 'Asset is lower than', 'number', '', 'SELECT DISTINCT h.ip, s.ip as sensor FROM host h, host_sensor_reference r, sensor s WHERE h.id = r.host_id AND r.sensor_name = s.name AND h.asset < ? AND h.asset > 0 UNION SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND ossim_asset_src < ? AND ossim_asset_src > 0 UNION SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND ossim_asset_dst < ? AND ossim_asset_dst > 0', 999),
('Asset', 'Asset is', 'fixed', 'SELECT DISTINCT asset FROM host ORDER BY asset', 'SELECT DISTINCT h.ip, s.ip as sensor FROM host h, host_sensor_reference r, sensor s WHERE h.id=r.host_id AND r.sensor_name=s.name AND asset = ? UNION SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND ossim_asset_src = ? UNION SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND ossim_asset_dst = ?', 999),
('Vulnerabilities', 'Vuln risk is greater than', 'number', '', 'SELECT DISTINCT host.ip, host.id FROM host_plugin_sid h,vuln_nessus_plugins p, host WHERE h.host_id = host.id AND h.plugin_id=3001 AND h.plugin_sid=p.id AND 8-p.risk > ?', 4),
('Vulnerabilities', 'Vuln risk is lower than', 'number', '', 'SELECT DISTINCT host.ip, host.id FROM host_plugin_sid h,vuln_nessus_plugins p, host WHERE h.host_id = host.id AND h.plugin_id=3001 AND h.plugin_sid=p.id AND 8-p.risk < ?', 4),
('Asset', 'Asset is local', 'fixed', 'SELECT DISTINCT asset FROM host ORDER BY asset', 'SELECT DISTINCT id, ip FROM host WHERE asset = ?', 999),
('Asset', 'Asset is remote', 'number', '', 'SELECT ip, sensor FROM (SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND ossim_asset_src = ? UNION SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND ossim_asset_dst = ?) remote WHERE CONCAT(ip,'','',sensor) NOT IN (SELECT DISTINCT CONCAT(h.ip,'','',s.ip) FROM host h, host_sensor_reference r, sensor s WHERE h.id=r.host_id AND r.sensor_name=s.name)', 999),
('Vulnerabilities', 'Has Vuln Service', 'text', 'SELECT DISTINCT app FROM vuln_nessus_results', 'SELECT DISTINCT hostIP as ip FROM vuln_nessus_results WHERE app %op% ?', 999),
('Vulnerabilities', 'Has CVE', 'text', 'SELECT DISTINCT cve_id FROM vuln_nessus_plugins', 'SELECT DISTINCT host.ip, host.id FROM vuln_nessus_plugins p,host_plugin_sid s, host WHERE s.host_id = host.id AND s.plugin_id=3001 and s.plugin_sid=p.id AND p.cve_id %op% ?', 4),
('Property', 'Has Property', 'fixed', 'SELECT DISTINCT id as property_value, name as property_text  FROM host_property_reference ORDER BY name', 'SELECT DISTINCT ip, sensor FROM host_properties WHERE property_ref = ?', 999),
('Property', 'Has not Property', 'fixed', 'SELECT DISTINCT id as property_value, name as property_text  FROM host_property_reference ORDER BY name', 'SELECT DISTINCT ip, sensor FROM host_properties WHERE property_ref != ?', 999),
('Property', 'Contains', 'fixedText', 'SELECT DISTINCT id as property_value, name as property_text  FROM host_property_reference ORDER BY name', 'SELECT DISTINCT ip, sensor FROM host_properties WHERE property_ref = ? AND (value LIKE ''%$value2%'' OR extra LIKE ''%$value2%'')', 999);

-- post_correlation directives
-- plugin_id: 20505
DELETE FROM plugin WHERE id = "20505";
DELETE FROM plugin_sid where plugin_id = "20505";
INSERT INTO plugin (id, type, name, description) VALUES (20505, 1, 'post_correlation_directive', 'Alienvault post correlation engine for SQL queries');
-- All the plugin_sids will be inserted by the alienvault-server. The framework will send the list of directives to the server

UPDATE  `custom_report_types` SET  `inputs` = 'Logo:logo:FILE:OSS_NULLABLE::;Main Title:maintitle:text:OSS_TEXT.OSS_PUNC_EXT.OSS_NULLABLE::;I.T. Security:it_security:text:OSS_TEXT.OSS_PUNC_EXT.OSS_NULLABLE::;Address:address:text:OSS_TEXT.OSS_PUNC_EXT.OSS_NULLABLE::;Tel:tlfn:text:OSS_TEXT.OSS_PUNC_EXT.OSS_NULLABLE::;Date:date:text:OSS_TEXT.OSS_PUNC_EXT.OSS_NULLABLE:#DATE:' WHERE  `custom_report_types`.`id` = 440;

DROP PROCEDURE IF EXISTS incident_ticket_populate;
DELIMITER "|"

CREATE PROCEDURE incident_ticket_populate(incident_id INT, src_ip INT, dst_ip INT, i INT, prio INT)
BEGIN
  DECLARE done INT DEFAULT 0;
  DECLARE count INT;
  DECLARE cnt_src, cnt_dst INT;
  DECLARE name, subname VARCHAR(255);
  DECLARE first_occ, last_occ TIMESTAMP;
  DECLARE source VARCHAR(15);
  DECLARE dest VARCHAR(15);

  DECLARE cur1 CURSOR FOR select count(*) as cnt,  inet_ntoa(event.src_ip) as src, inet_ntoa(event.dst_ip) as dst, plugin.name, plugin_sid.name, min(timestamp) as frst, max(timestamp) as last, count(distinct(event.src_ip)) as cnt_src, count(distinct(event.dst_ip)) as cnt_dst from ossim.event, ossim.plugin, ossim.plugin_sid where (event.src_ip = src_ip or event.dst_ip = src_ip or event.src_ip = dst_ip or event.dst_ip =dst_ip ) and timestamp > DATE_SUB(NOW(), INTERVAL 7 DAY) AND ossim.plugin.id = event.plugin_id and ossim.plugin_sid.sid = event.plugin_sid and ossim.plugin_sid.plugin_id = event.plugin_id group by event.plugin_id, event.plugin_sid ORDER by cnt DESC limit 50;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

OPEN cur1;

INSERT INTO incident_ticket(id,incident_id,date,status,priority,users,description) VALUES (i, incident_id, NOW()-1, "Open", prio, "admin", "The following tickets contain information about the top 50 event types the hosts have been generating during the last 7 days.");
SET i = i + 1;

  REPEAT
	FETCH cur1 INTO count, source, dest, name, subname, first_occ, last_occ, cnt_src, cnt_dst;
	IF NOT done THEN
		SET @desc = CONCAT( "Event Type: ",  name, "\nEvent Description: ", subname, "\nOcurrences: ",CAST(count AS CHAR), "\nFirst Ocurrence: ", CAST(first_occ AS CHAR(50)), "\nLast Ocurrence: ", CAST(last_occ AS CHAR(50)),"\nNumber of different sources: ", CAST(cnt_src AS CHAR), "\nNumber of different destinations: ", CAST(cnt_dst AS CHAR), "\nSource: ", source, "\nDest: ", dest);
		INSERT INTO incident_ticket(id,incident_id,date,status,priority,users,description) VALUES (i, incident_id, NOW(), "Open", prio, "admin", @desc);
		SET i = i + 1;
	END IF;
  UNTIL done END REPEAT;
  
  CLOSE cur1;
END
|

DELIMITER ";"


DROP PROCEDURE IF EXISTS addcol;
DELIMITER '//'
CREATE PROCEDURE addcol() BEGIN
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'server_role' AND COLUMN_NAME = 'reputation')
  THEN
		ALTER TABLE server_role ADD reputation tinyint(1) NOT NULL DEFAULT 1;
  END IF;
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'policy_role_reference' AND COLUMN_NAME = 'reputation')
  THEN
		ALTER TABLE policy_role_reference ADD reputation tinyint(1) NOT NULL DEFAULT 1;
  END IF;  
END;
//
DELIMITER ';'
CALL addcol();
DROP PROCEDURE addcol;

DELETE FROM user_config WHERE category='policy' AND name='servers_layout';

REPLACE INTO config (conf, value) VALUES ("server_reputation", "no");
REPLACE INTO config (conf, value) VALUES ('last_update', '2011-10-26');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '3.0.5');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA

