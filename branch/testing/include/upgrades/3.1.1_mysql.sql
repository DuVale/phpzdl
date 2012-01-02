USE ossim;
SET AUTOCOMMIT=0;
BEGIN;

DELETE FROM alarm_tags WHERE id_alarm NOT IN (SELECT backlog_id FROM alarm);
UPDATE `ISO27001An`.`A11_Acces_control` SET `SIDSS_Ref` = '1,5,6,7,8,9,13,21,22,103,104,105,106,107,108' WHERE `A11_Acces_control`.`Ref` = 'A.11.4.6' and `A11_Acces_control`.`SIDSS_Ref` = '1,5,6,7,8,9,13,\t21,22,103,104,105,106,107,108';

REPLACE INTO `custom_report_types` (`id`, `name`, `type`, `file`, `inputs`, `sql`, `dr`) VALUES 
(195, 'Top Events by Risk', 'Security Events', 'SIEM/TopEventsByRisk.php', 'Top Events by Risk:top:text:OSS_DIGIT:15:50;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;DS Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:', '', 30),
(196, 'Last Events by Risk', 'Security Events', 'SIEM/LastEventsByRisk.php', 'Last Events by Risk:top:text:OSS_DIGIT:15:50;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;DS Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:', '', 30);

DROP PROCEDURE IF EXISTS addcol;
DELIMITER '//'
CREATE PROCEDURE addcol() BEGIN

  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'custom_report_scheduler' AND COLUMN_NAME = 'last_compilation_time')
  THEN
  	  ALTER TABLE `custom_report_scheduler` ADD `last_compilation_time` VARCHAR(128) NOT NULL;
  END IF;
  
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'host_group' AND COLUMN_NAME = 'lat')
  THEN
      ALTER TABLE `host_group` ADD `lat` VARCHAR( 255 ) NULL DEFAULT '0',
        ADD `lon` VARCHAR( 255 ) NULL DEFAULT '0',
        ADD `country` VARCHAR( 255 ) NULL DEFAULT '';
      ALTER TABLE `host` ADD `country` VARCHAR( 255 ) NULL DEFAULT '';
  END IF;

  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'risk_maps' AND COLUMN_NAME = 'name')
  THEN
      ALTER TABLE `risk_maps` ADD `name` VARCHAR( 128 ) NULL;
  END IF;
  
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'alarm' AND COLUMN_NAME = 'removable')
  THEN
      ALTER TABLE `alarm` ADD `removable` BOOLEAN NOT NULL DEFAULT FALSE;
  END IF;
    
END;
//
DELIMITER ';'
CALL addcol();
DROP PROCEDURE addcol;


REPLACE INTO config (conf, value) VALUES ('last_update', '2011-12-02');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '3.1.1');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA