use ossim;
SET AUTOCOMMIT=0;
BEGIN;

UPDATE `ossim`.`custom_report_types` SET `inputs` = 'Logo:logo:FILE:OSS_NULLABLE::;Main Title:maintitle:text:OSS_TEXT.OSS_PUNC_EXT.OSS_NULLABLE::;I.T. Security:it_security:text:OSS_TEXT.OSS_PUNC_EXT.OSS_NULLABLE::;Address:address:text:OSS_TEXT.OSS_PUNC_EXT.OSS_NULLABLE::;Tel:tlfn:text:OSS_TEXT.OSS_PUNC_EXT.OSS_NULLABLE::;Date:date:text:OSS_TEXT.OSS_PUNC_EXT.OSS_NULLABLE::' WHERE `custom_report_types`.`id` =440 LIMIT 1 ;

UPDATE risk_indicators SET name='USA' WHERE id='3';
UPDATE risk_indicators SET name='Development Center North' WHERE id='98';
UPDATE risk_indicators SET name='Development Center San Jose' WHERE id='99';
UPDATE risk_indicators SET name='Development Center South' WHERE id='100';

DROP PROCEDURE IF EXISTS addcol;
DELIMITER '//'
CREATE PROCEDURE addcol() BEGIN
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'server' AND COLUMN_NAME = 'remoteadmin')
  THEN
      ALTER TABLE `server` ADD `remoteadmin` VARCHAR( 64 ) NOT NULL , ADD `remotepass` VARCHAR( 128 ) NOT NULL , ADD `remoteurl` VARCHAR( 128 ) NOT NULL;
   END IF;        
END;
//
DELIMITER ';'
CALL addcol();
DROP PROCEDURE addcol;

-- ATENCION! Keep this at the end of this file
use ossim;
REPLACE INTO config (conf, value) VALUES ('last_update', '2011-07-05');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.34');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
