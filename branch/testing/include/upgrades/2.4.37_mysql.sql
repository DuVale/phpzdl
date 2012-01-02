use ossim;
SET AUTOCOMMIT=0;
BEGIN;

UPDATE `ossim`.`custom_report_types` SET `inputs` = 'Group by:groupby:select:OSS_ALPHA.OSS_SCORE.OSS_PUNC.OSS_NULLABLE:TRENDGROUPBY:' WHERE `custom_report_types`.`id` =2034;

UPDATE custom_report_types SET inputs=REPLACE(inputs,"OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS","OSS_INPUT.OSS_NULLABLE:PLUGINGROUPS") WHERE inputs like '%PLUGINGROUPS%';

-- ATENCION! Keep this at the end of this file
use ossim;
REPLACE INTO config (conf, value) VALUES ('last_update', '2011-08-01');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.37');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
