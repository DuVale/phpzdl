use ossim;
SET AUTOCOMMIT=0;
BEGIN;

DROP TRIGGER IF EXISTS auto_incidents;

DELIMITER "|"

CREATE TRIGGER auto_incidents AFTER INSERT ON alarm
FOR EACH ROW BEGIN
 IF EXISTS
 (SELECT value FROM config where conf = "alarms_generate_incidents" and value = "yes")
THEN
set @tmp_src_ip = NEW.src_ip;
set @tmp_dst_ip = NEW.dst_ip;
set @tmp_risk = NEW.risk;
set @title = (SELECT TRIM(LEADING "directive_event:" FROM name) as name from plugin_sid where plugin_id = NEW.plugin_id and sid = NEW.plugin_sid);
set @title = REPLACE(@title,"DST_IP", inet_ntoa(NEW.dst_ip));
set @title = REPLACE(@title,"SRC_IP", inet_ntoa(NEW.src_ip));
set @title = REPLACE(@title,"PROTOCOL", NEW.protocol);
set @title = REPLACE(@title,"SRC_PORT", NEW.src_port);
set @title = REPLACE(@title,"DST_PORT", NEW.dst_port);
set @title = CONCAT(@title, " (", inet_ntoa(NEW.src_ip), ":", CAST(NEW.src_port AS CHAR), " -> ", inet_ntoa(NEW.dst_ip), ":", CAST(NEW.dst_port AS CHAR), ")");
insert into incident(title,date,ref,type_id,priority,status,last_update,in_charge,submitter,event_start,event_end) values (@title, NEW.timestamp, "Alarm", "Generic", NEW.risk, "Open", NOW(), "admin", "admin", NEW.timestamp, NEW.timestamp);
set @last_id = (SELECT LAST_INSERT_ID() FROM incident limit 1);
insert into incident_alarm(incident_id, src_ips, dst_ips, src_ports, dst_ports, backlog_id, event_id, alarm_group_id) values (@last_id, inet_ntoa(NEW.src_ip), inet_ntoa(NEW.dst_ip), NEW.src_port, NEW.dst_port, NEW.backlog_id, NEW.event_id, 0);
CALL incident_ticket_populate(@last_id, @tmp_src_ip, @tmp_dst_ip, 1,@tmp_risk);
END IF;
END;
|

DELIMITER ";"

USE datawarehouse;

ALTER TABLE `apn_sfr` ENGINE = INNODB;
ALTER TABLE `apn_sfr` ENGINE = INNODB;
ALTER TABLE `category` ENGINE = INNODB;
ALTER TABLE `geo` ENGINE = INNODB;
ALTER TABLE `incidents_ssi` ENGINE = INNODB;
ALTER TABLE `incidents_ssi_user` ENGINE = INNODB;
ALTER TABLE `ip2country` ENGINE = INNODB;
ALTER TABLE `ip2service` ENGINE = INNODB;
ALTER TABLE `iso27001sid` ENGINE = INNODB;
ALTER TABLE `report_data` ENGINE = INNODB;
ALTER TABLE `report_data_type` ENGINE = INNODB;
ALTER TABLE `ssi` ENGINE = INNODB;
ALTER TABLE `ssi_user` ENGINE = INNODB;

USE ISO27001An;

ALTER TABLE `A05_Security_Policy` ENGINE = INNODB;
ALTER TABLE `A06_IS_Organization` ENGINE = INNODB;
ALTER TABLE `A07_Asset_Mgnt` ENGINE = INNODB;
ALTER TABLE `A08_Human_Resources` ENGINE = INNODB;
ALTER TABLE `A09_Physical_security` ENGINE = INNODB;
ALTER TABLE `A10_Com_OP_Mgnt` ENGINE = INNODB;
ALTER TABLE `A11_Acces_control` ENGINE = INNODB;
ALTER TABLE `A12_IS_acquisition` ENGINE = INNODB;
ALTER TABLE `A13_IS_incident_mgnt` ENGINE = INNODB;
ALTER TABLE `A14_BCM` ENGINE = INNODB;
ALTER TABLE `A15_Compliance` ENGINE = INNODB;

USE PCI;

ALTER TABLE `R01_FW_Config` ENGINE = INNODB;
ALTER TABLE `R02_Vendor_default` ENGINE = INNODB;
ALTER TABLE `R03_Stored_cardholder` ENGINE = INNODB;
ALTER TABLE `R04_Data_encryption` ENGINE = INNODB;
ALTER TABLE `R05_Antivirus` ENGINE = INNODB;
ALTER TABLE `R06_System_app` ENGINE = INNODB;
ALTER TABLE `R07_Access_control` ENGINE = INNODB;
ALTER TABLE `R08_UniqueID` ENGINE = INNODB;
ALTER TABLE `R09_Physical_Access` ENGINE = INNODB;
ALTER TABLE `R10_Monitoring` ENGINE = INNODB;
ALTER TABLE `R11_Security_test` ENGINE = INNODB;
ALTER TABLE `R12_IS_Policy` ENGINE = INNODB;


use ossim;

UPDATE `custom_report_types` SET inputs = REPLACE(inputs, 'Top Used Ports', 'Top Destination Ports') WHERE name not like '% Source%';
UPDATE `custom_report_types` SET inputs = REPLACE(inputs, 'Last Used Ports', 'Last Destination Ports') WHERE name not like '% Source%';
UPDATE `custom_report_types` SET name = 'Top Destination Ports' WHERE name = 'Top Used Ports';
UPDATE `custom_report_types` SET name = 'Last Destination Ports' WHERE name = 'Last Used Ports';

UPDATE `custom_report_types` SET `type` = 'Security' WHERE `custom_report_types`.`type` = 'Security/Logs Events';
UPDATE `custom_report_types` SET `type` = 'Raw Logs' WHERE `custom_report_types`.`type` = 'Logs';

UPDATE `custom_report_types` SET `inputs` = 'Source Database:source:select:OSS_ALPHA:EVENTSOURCELOGGER:;Group by:groupby:select:OSS_ALPHA.OSS_SCORE.OSS_PUNC.OSS_NULLABLE:TRENDGROUPBY:;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:' WHERE `id` =144;

-- ATENCION! Keep this at the end of this file

REPLACE INTO config (conf, value) VALUES ('last_update', '2011-09-16');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '3.0.1');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA

