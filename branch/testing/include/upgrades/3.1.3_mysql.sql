USE ossim;
SET AUTOCOMMIT=0;
BEGIN;

REPLACE INTO config (conf, value) VALUES ('tickets_send_mail', 'yes');

REPLACE INTO config (conf, value) VALUES ('last_update', '2011-12-13');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '3.1.3');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA