USE ossim;
SET AUTOCOMMIT=0;
BEGIN;

REPLACE INTO `ossim`.`log_config` (`code` , `log` , `descr` , `priority`) VALUES ('95', '1', 'SIEM Components - Web Interfaces: New Web Interface added %1% %2%', '1');
REPLACE INTO `ossim`.`log_config` (`code` , `log` , `descr` , `priority`) VALUES ('96', '1', 'SIEM Components - Webs Interfaces: %1% %2% modified', '2');
REPLACE INTO `ossim`.`log_config` (`code` , `log` , `descr` , `priority`) VALUES ('97', '1', 'SIEM Components - Webs Interfaces: %1% deleted', '3' );

REPLACE INTO config (conf, value) VALUES ('last_update', '2011-12-13');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '3.1.2');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA