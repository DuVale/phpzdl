SET AUTOCOMMIT=0;
BEGIN;

use snort;

DROP PROCEDURE IF EXISTS addcol;
DELIMITER '//'
CREATE PROCEDURE addcol() BEGIN
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'acid_event' AND COLUMN_NAME = 'src_username')
  THEN
		alter table acid_event add `src_username` varchar(64) NOT NULL,
		add `dst_username` varchar(64) NULL,
		add `src_domain` varchar(64) NULL,
		add `dst_domain` varchar(64) NULL,
		add `src_hostname` varchar(64) NULL,
		add `dst_hostname` varchar(64) NULL,
		add `src_mac` varchar(17) NULL,
		add `dst_mac` varchar(17) NULL;
  END IF;
END;
//
DELIMITER ';'
CALL addcol();
DROP PROCEDURE addcol;

DROP PROCEDURE IF EXISTS idm_fields;
DELIMITER ;;
CREATE PROCEDURE idm_fields()
BEGIN
DECLARE done BOOLEAN DEFAULT 0;
DECLARE sid INT;
DECLARE cid INT;
DECLARE src_username VARCHAR(64);
DECLARE dst_username VARCHAR(64);
DECLARE src_domain VARCHAR(64);
DECLARE dst_domain VARCHAR(64);
DECLARE src_hostname VARCHAR(64);
DECLARE dst_hostname VARCHAR(64);
DECLARE src_mac VARCHAR(17);
DECLARE dst_mac VARCHAR(17);
DECLARE idm_list CURSOR FOR SELECT sid,cid,src_username,dst_username,src_domain,dst_domain,src_hostname,dst_hostname,src_mac,dst_mac FROM idm_data;
DECLARE CONTINUE HANDLER FOR SQLSTATE '02000' SET done=1;
OPEN idm_list;
REPEAT
FETCH idm_list INTO sid,cid,src_username,dst_username,src_domain,dst_domain,src_hostname,dst_hostname,src_mac,dst_mac;
UPDATE acid_event SET src_username=@src_username, dst_username=@dst_username, src_domain=@src_domain, dst_domain=@dst_domain, src_hostname=@src_hostname, dst_hostname=@dst_hostname, src_mac=@src_mac, dst_mac=@dst_mac WHERE sid=@sid AND cid=@cid;
UNTIL done END REPEAT;
CLOSE idm_list;
END ;;
DELIMITER ;
CALL idm_fields;
DROP PROCEDURE IF EXISTS idm_fields;


DROP PROCEDURE IF EXISTS addcol;
DELIMITER '//'
CREATE PROCEDURE addcol() BEGIN
  IF EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'idm_data' AND COLUMN_NAME = 'src_username')
  THEN
		ALTER TABLE idm_data drop `src_username`,drop `dst_username`,
		drop `src_domain`, drop `dst_domain`,
		drop `src_hostname`, drop `dst_hostname`,
		drop `src_mac`, drop `dst_mac`;
  END IF;
END;
//
DELIMITER ';'
CALL addcol();
DROP PROCEDURE addcol;

use ossim;

DROP PROCEDURE IF EXISTS addcol;
DELIMITER '//'
CREATE PROCEDURE addcol() BEGIN
  IF EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'event' AND COLUMN_NAME = 'rep_ip_src')
  THEN
		ALTER TABLE `event` DROP `rep_ip_src`, DROP `rep_ip_dst`;
  END IF;
END;
//
DELIMITER ';'
CALL addcol();
DROP PROCEDURE addcol;

REPLACE INTO action_type (_type, descr) VALUES ("email", "Send an email message");
REPLACE INTO action_type (_type, descr) VALUES ("exec", "Execute an external program");
REPLACE INTO action_type (_type, descr) VALUES ("ticket", "Open a ticket");
REPLACE INTO `ossim`.`custom_report_types` (`id` ,`name` ,`type` ,`file` ,`inputs` ,`sql` ,`dr`) VALUES ('265',  'Tickets By Tag',  'Tickets Status',  'TicketsStatus/TicketsByTag.php',  '',  '',  '1');

ALTER TABLE  `acl_entities` CHANGE  `name`  `name` VARCHAR( 255 ) NULL DEFAULT NULL;
ALTER TABLE  `acl_templates` CHANGE  `name`  `name` VARCHAR( 255 ) NULL DEFAULT NULL;
ALTER TABLE  `repository` AUTO_INCREMENT =100000;

REPLACE INTO config (conf, value) VALUES ('enable_idm', '0');

REPLACE INTO config (conf, value) VALUES ('last_update', '2011-10-19');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '3.0.4');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA

