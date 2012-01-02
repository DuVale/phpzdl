use ossim;
SET AUTOCOMMIT=0;
BEGIN;

use snort;
CREATE TABLE IF NOT EXISTS `idm_data` (
  `sid` int(10) unsigned NOT NULL,
  `cid` int(10) unsigned NOT NULL,
  `src_username` varchar(64) NOT NULL,
  `dst_username` varchar(64) NOT NULL,
  `src_domain` varchar(64) NOT NULL,
  `dst_domain` varchar(64) NOT NULL,
  `src_hostname` varchar(64) NOT NULL,
  `dst_hostname` varchar(64) NOT NULL,
  `src_mac` varchar(17) NOT NULL,
  `dst_mac` varchar(17) NOT NULL,
  `rep_ip_src` int unsigned,
  `rep_ip_dst` int unsigned,
  `rep_prio_src` int unsigned,
  `rep_prio_dst` int unsigned,
  `rep_rel_src` int unsigned,
  `rep_rel_dst` int unsigned,
  `rep_act_src` varchar(64) NOT NULL,
  `rep_act_dst` varchar(64) NOT NULL,
  PRIMARY KEY (`sid`,`cid`)
);

use ossim;

DROP PROCEDURE IF EXISTS addcol;
DELIMITER '//'
CREATE PROCEDURE addcol() BEGIN
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'event' AND COLUMN_NAME = 'rep_ip_src')
  THEN
		alter table event add `rep_ip_src` varchar(64) NOT NULL,
		add `rep_ip_dst` varchar(64) NOT NULL,
		add `rep_prio_src` int unsigned,
		add `rep_prio_dst` int unsigned,
		add `rep_rel_src` int unsigned,
		add `rep_rel_dst` int unsigned,
		add `rep_act_src` varchar(64) NOT NULL,
		add `rep_act_dst` varchar(64) NOT NULL;
  END IF;
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'event' AND COLUMN_NAME = 'src_username')
  THEN
		alter table event add `src_username` varchar(64) NOT NULL,
		add `dst_username` varchar(64) NOT NULL,
		add `src_domain` varchar(64) NOT NULL,
		add `dst_domain` varchar(64) NOT NULL,
		add `src_hostname` varchar(64) NOT NULL,
		add `dst_hostname` varchar(64) NOT NULL,
		add `src_mac` varchar(17) NOT NULL,
		add `dst_mac` varchar(17) NOT NULL;
  END IF;
END;
//
DELIMITER ';'
CALL addcol();
DROP PROCEDURE addcol;

UPDATE custom_report_types SET inputs = 'Source Database:source:select:OSS_ALPHA:EVENTSOURCELOGGER:' WHERE id = 101 AND name = 'Summarized Status';

-- REPLACE INTO config (conf, value) VALUES ('server_logger_if_priority', '0');

REPLACE INTO config (conf, value) VALUES ('last_update', '2011-09-30');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '3.0.3');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA

