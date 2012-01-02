use ossim;
SET AUTOCOMMIT=0;
BEGIN;

DELETE FROM host_services WHERE anom=1;

DROP PROCEDURE IF EXISTS addcol;
DELIMITER '//'
CREATE PROCEDURE addcol() BEGIN
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_NAME = 'backlog' AND INDEX_NAME='directive_id')
  THEN
      ALTER TABLE `backlog` ADD INDEX `directive_id` ( `directive_id` );
  END IF;
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'server' AND COLUMN_NAME = 'id')
  THEN
  	  ALTER TABLE  `server` ADD  `id` INT(10) UNSIGNED NULL;
  END IF;
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_NAME = 'log_action' AND INDEX_NAME = 'info')
  THEN
  	   ALTER TABLE `log_action` ADD INDEX ( `info` , `date` );
  END IF;
END;
//
DELIMITER ';'
CALL addcol();
DROP PROCEDURE addcol;

ALTER TABLE `sessions` CHANGE `logon_date` `logon_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE `sessions` CHANGE `activity` `activity` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';

UPDATE `ossim`.`risk_indicators` SET `x` = '455', `y` = '269' WHERE `risk_indicators`.`id` = 56;
UPDATE `ossim`.`risk_indicators` SET `x` = '372', `y` = '186' WHERE `risk_indicators`.`id` = 57;

CREATE TABLE IF NOT EXISTS policy_forward_reference (
    policy_id       int NOT NULL,
    server_id       int NOT NULL,
    priority        smallint(5) unsigned NOT NULL, 
    PRIMARY KEY     (policy_id, server_id)
);

CREATE TABLE IF NOT EXISTS server_forward_hierarchy (
    child_id        int(10) unsigned NOT NULL,
    parent_id       int(10) unsigned NOT NULL,
    priority        smallint(5) unsigned NOT NULL,
    PRIMARY KEY     (child_id,parent_id)    
);

CREATE TABLE IF NOT EXISTS web_interfaces (
  id           INT NOT NULL AUTO_INCREMENT,
  ip           INT(11) UNSIGNED NOT NULL,
  name         VARCHAR(64) NOT NULL,
  status       INT(1) NOT NULL,
  PRIMARY KEY  (id)
);

REPLACE INTO `user_config` VALUES ('admin','custom_report','Honeypot Activity','a:11:{s:2:"ds";a:11:{i:130;a:6:{s:3:"top";s:2:"15";s:8:"category";s:2:"19";s:11:"subcategory";s:1:"0";s:13:"plugin_groups";s:0:"";s:6:"source";s:4:"siem";s:5:"notes";s:0:"";}i:132;a:5:{s:3:"top";s:2:"15";s:10:"sourcetype";s:8:"Honeypot";s:13:"plugin_groups";s:0:"";s:6:"source";s:4:"siem";s:5:"notes";s:0:"";}i:300;a:5:{s:10:"sourcetype";s:0:"";s:8:"category";s:2:"19";s:11:"subcategory";s:1:"0";s:13:"plugin_groups";s:0:"";s:5:"notes";s:0:"";}i:138;a:8:{s:3:"top";s:2:"10";s:10:"num_events";s:1:"5";s:10:"sourcetype";s:0:"";s:8:"category";s:2:"19";s:11:"subcategory";s:1:"0";s:13:"plugin_groups";s:0:"";s:6:"source";s:4:"siem";s:5:"notes";s:0:"";}i:120;a:6:{s:3:"top";s:2:"10";s:10:"sourcetype";s:0:"";s:8:"category";s:2:"19";s:11:"subcategory";s:1:"0";s:13:"plugin_groups";s:0:"";s:5:"notes";s:0:"";}i:187;a:6:{s:3:"top";s:2:"10";s:10:"sourcetype";s:0:"";s:8:"category";s:2:"19";s:11:"subcategory";s:1:"0";s:13:"plugin_groups";s:0:"";s:5:"notes";s:0:"";}i:121;a:6:{s:3:"top";s:2:"10";s:10:"sourcetype";s:0:"";s:8:"category";s:2:"19";s:11:"subcategory";s:1:"0";s:13:"plugin_groups";s:0:"";s:5:"notes";s:0:"";}i:194;a:6:{s:3:"top";s:2:"10";s:10:"sourcetype";s:0:"";s:8:"category";s:2:"19";s:11:"subcategory";s:1:"0";s:13:"plugin_groups";s:0:"";s:5:"notes";s:0:"";}i:189;a:6:{s:3:"top";s:2:"10";s:10:"sourcetype";s:0:"";s:8:"category";s:2:"19";s:11:"subcategory";s:1:"0";s:13:"plugin_groups";s:0:"";s:5:"notes";s:0:"";}i:134;a:6:{s:3:"top";s:2:"20";s:10:"sourcetype";s:0:"";s:8:"category";s:2:"19";s:11:"subcategory";s:1:"0";s:13:"plugin_groups";s:0:"";s:5:"notes";s:0:"";}i:125;a:9:{s:3:"top";s:2:"25";s:9:"plugin_id";s:1:"0";s:6:"format";s:4:"List";s:8:"category";s:2:"19";s:11:"subcategory";s:1:"0";s:13:"plugin_groups";s:0:"";s:7:"orderby";s:10:"eventsdesc";s:6:"source";s:4:"siem";s:5:"notes";s:0:"";}}s:5:"rname";s:17:"Honeypot Activity";s:9:"date_from";N;s:7:"date_to";N;s:10:"date_range";s:4:"year";s:7:"profile";s:7:"Default";s:5:"cdate";s:19:"2011-11-10 17:24:07";s:5:"mdate";s:19:"2011-11-10 17:46:43";s:6:"assets";s:10:"ALL_ASSETS";s:10:"asset_type";s:0:"";s:4:"user";s:1:"0";}');

REPLACE into config (conf,value) values ('frameworkd_nagiosmklivemanager',1);
REPLACE into config (conf,value) values ('frameworkd_postcorrelationmanager',1);

REPLACE INTO config (conf, value) VALUES ('last_update', '2011-11-11');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '3.1.0');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA

