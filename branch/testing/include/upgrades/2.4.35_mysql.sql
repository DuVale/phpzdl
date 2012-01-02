use ossim;
SET AUTOCOMMIT=0;
BEGIN;

UPDATE  `ossim`.`acl_perm` SET  `granularity_sensor` =  '1', `granularity_net` =  '0',`value` =  'TrafficCapture' WHERE  `acl_perm`.`id` =83;
UPDATE  `ossim`.`acl_perm` SET  `granularity_sensor` =  '1', `granularity_net` =  '1' WHERE  `acl_perm`.`id` =74;
UPDATE  `ossim`.`acl_perm` SET  `granularity_sensor` =  '1', `granularity_net` =  '1' WHERE  `acl_perm`.`id` =64;
UPDATE  `ossim`.`acl_perm` SET  `granularity_sensor` =  '1', `granularity_net` =  '1' WHERE  `acl_perm`.`id` =31;
UPDATE  `ossim`.`acl_perm` SET  `granularity_sensor` =  '1', `granularity_net` =  '1' WHERE  `acl_perm`.`id` =66;
UPDATE  `ossim`.`acl_perm` SET  `granularity_sensor` =  '1', `granularity_net` =  '1' WHERE  `acl_perm`.`id` =81;

REPLACE INTO `custom_report_types` (`id`, `name`, `type`, `file`, `inputs`, `sql`, `dr`) VALUES
(125, 'Data Source', 'Unique Signatures by', 'Various/DataSourceEvents.php', 'Top Data Source events:top:text:OSS_DIGIT:25:200;Data Source:plugin_id:select:OSS_DIGIT:PLUGINS:;Format:format:select:OSS_ALPHA:List,Pie:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;DS Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:;Order by:orderby:select:OSS_ALPHA.OSS_NULLABLE:ORDERBY:;Source:source:select:OSS_ALPHA:EVENTSOURCE:', '', 21),
(126, 'Product Type', 'Unique Signatures by', 'Various/DataSourceEvents.php', 'Top Source Type events:top:text:OSS_DIGIT:25:200;Format:format:select:OSS_ALPHA:List,Pie:;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;DS Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:;Order by:orderby:select:OSS_ALPHA.OSS_NULLABLE:ORDERBY:;Source:source:select:OSS_ALPHA:EVENTSOURCE:', '', 30),
(127, 'Source Category', 'Unique Signatures by', 'Various/DataSourceEvents.php', 'Top Source Category events:top:text:OSS_DIGIT:25:200;Format:format:select:OSS_ALPHA:List,Pie,Radar:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;DS Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:;Order by:orderby:select:OSS_ALPHA.OSS_NULLABLE:ORDERBY:;Source:source:select:OSS_ALPHA:EVENTSOURCE:', '', 21);

-- ATENCION! Keep this at the end of this file
use ossim;
REPLACE INTO config (conf, value) VALUES ('last_update', '2011-07-05');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.35');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
