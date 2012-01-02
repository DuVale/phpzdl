use ossim;
SET AUTOCOMMIT=0;
BEGIN;

ALTER TABLE `incident` CHANGE `status` `status` ENUM( 'Open', 'Assigned', 'Studying', 'Waiting', 'Testing', 'Closed') NOT NULL DEFAULT 'Open';
ALTER TABLE `incident_ticket` CHANGE `status` `status` ENUM( 'Open', 'Assigned', 'Studying', 'Waiting', 'Testing', 'Closed') NOT NULL DEFAULT 'Open';

UPDATE `ossim`.`custom_report_types` SET `inputs` = 'Status:status:select:OSS_LETTER:All,Open,Assigned,Studying,Waiting,Testing,Closed' WHERE `custom_report_types`.`id` =320;
UPDATE `ossim`.`custom_report_types` SET `inputs` = 'Status:status:select:OSS_LETTER:All,Open,Assigned,Studying,Waiting,Testing,Closed' WHERE `custom_report_types`.`id` =321;
UPDATE `ossim`.`custom_report_types` SET `inputs` = 'Status:status:select:OSS_LETTER:All,Open,Assigned,Studying,Waiting,Testing,Closed' WHERE `custom_report_types`.`id` =322;
UPDATE `ossim`.`custom_report_types` SET `inputs` = 'Status:status:select:OSS_LETTER:All,Open,Assigned,Studying,Waiting,Testing,Closed' WHERE `custom_report_types`.`id` =323;
UPDATE `ossim`.`custom_report_types` SET `inputs` = 'Status:status:select:OSS_LETTER:All,Open,Assigned,Studying,Waiting,Testing,Closed' WHERE `custom_report_types`.`id` =324;

UPDATE `ossim`.`incident_tag_descr` SET `name` = 'AlienVault_INTERNAL_FALSE_POSITIVE',
`descr` = 'Vulnerability scanner false positive tag - Prevents this event from being detected in the future' WHERE `incident_tag_descr`.`id` =65002;
UPDATE `ossim`.`incident_tag_descr` SET `name` = 'AlienVault_INTERNAL_PENDING',
`descr` = 'Vulnerability scanner pending tag - Prevents this event from being detected until tag is unset' WHERE `incident_tag_descr`.`id` =65001;

REPLACE INTO config (conf, value) VALUES ('last_update', '2011-09-23');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '3.0.2');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA

