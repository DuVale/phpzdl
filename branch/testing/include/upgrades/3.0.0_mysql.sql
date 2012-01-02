use ossim;
SET AUTOCOMMIT=0;
BEGIN;

use ossim;
REPLACE INTO config (conf, value) VALUES ('alarms_lifetime', '0');
DELETE FROM user_config where CATEGORY='policy';

-- acl_perm table

UPDATE `acl_perm` SET `description` = 'Analysis -> Security Events (SIEM)' WHERE `acl_perm`.`id` = 48;
UPDATE `acl_perm` SET `description` = 'Analysis -> Security Events (SIEM) -> Real Time' WHERE `acl_perm`.`id` = 51;
UPDATE `acl_perm` SET `description` = 'Analysis -> Security Events (SIEM) -> Delete Events' WHERE `acl_perm`.`id` = 71;
UPDATE `acl_perm` SET `description` = 'Analysis -> Raw Logs (Logger)' WHERE `acl_perm`.`id` = 61;
UPDATE `acl_perm` SET  `description` =  'Configuration -> AlienVault Components -> Sensors' WHERE  `acl_perm`.`id` =12;
UPDATE `acl_perm` SET  `description` =  'Configuration -> AlienVault Components -> Servers' WHERE  `acl_perm`.`id` =53;

-- custom_report_types --

-- type field

UPDATE `custom_report_types` SET `type` = 'Security Events' WHERE `custom_report_types`.`type` = 'SIEM Events';
UPDATE `custom_report_types` SET `type` = 'Security/Log Events' WHERE `custom_report_types`.`type` = 'SIEM/Logger Events';
UPDATE `custom_report_types` SET `type` = 'Security' WHERE `custom_report_types`.`type` = 'Security/Log Events';

UPDATE `custom_report_types` SET `type` = 'Custom Security Events' WHERE `custom_report_types`.`type` = 'Custom SIEM Events';
UPDATE `custom_report_types` SET `type` = 'Log Events' WHERE `custom_report_types`.`type` = 'Logger';
UPDATE `custom_report_types` SET `type` = 'Raw Logs' WHERE `custom_report_types`.`type` = 'Log Events';

-- name field

UPDATE `custom_report_types` SET `name` = 'Security Events' WHERE `custom_report_types`.`name` = 'SIEM Events';
UPDATE `custom_report_types` SET `name` = 'Security Event Clouds' WHERE `custom_report_types`.`name` = 'SIEM Clouds';
UPDATE `custom_report_types` SET `name` = 'Logs Events' WHERE `custom_report_types`.`name` = 'Logger Events';
UPDATE `custom_report_types` SET `name` = 'Raw Logs' WHERE `custom_report_types`.`name` = 'Logs Events';

UPDATE `custom_report_types` SET `name` = 'Threat Overview' WHERE `custom_report_types`.`id` =180;

-- description field

UPDATE `custom_report_types` SET `inputs` = REPLACE(inputs, 'Top SIEM Events', 'Top Security Events');
UPDATE `custom_report_types` SET `inputs` = REPLACE(inputs, 'Top Logger Events', 'Top Log Events');
UPDATE `custom_report_types` SET `inputs` = REPLACE(inputs, 'Source:source:select:OSS_ALPHA:EVENTSOURCE', 'Source Database:source:select:OSS_ALPHA:EVENTSOURCE');

-- OpenSource ACL
use ossim_acl;
UPDATE `aco` SET `section_value` = 'MenuConfiguration' WHERE `aco`.`id` =59 AND `aco`.`section_value` = 'MenuStatus';
UPDATE `aco` SET `section_value` = 'MenuConfiguration' WHERE `aco`.`id` =43 AND `aco`.`section_value` = 'MenuStatus';
REPLACE INTO `aco` (`id`, `section_value`, `value`, `order_value`, `name`, `hidden`) VALUES 
(89, 'MenuMonitors', 'TrafficCapture', 3, 'TrafficCapture', 0);
UPDATE `aco` SET `order_value` = '4' WHERE `aco`.`id` =42 AND `aco`.`value` = 'MonitorsAvailability';

use ossim;
REPLACE INTO config (conf, value) VALUES ('server_logger_if_priority', '1');

-- ATENCION! Keep this at the end of this file

REPLACE INTO config (conf, value) VALUES ('last_update', '2011-09-01');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '3.0.0');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
