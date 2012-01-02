use ossim;
SET AUTOCOMMIT=0;
BEGIN;

REPLACE INTO `custom_report_types` (`id`, `name`, `type`, `file`, `inputs`, `sql`, `dr`) VALUES
(107, 'Log Events', 'Asset', 'Asset/AssetLogger.php', 'Number of Events:top:text:OSS_DIGIT:5:20;Source:source:select:OSS_ALPHA:EVENTSOURCELOGGER:', '', 1);
UPDATE  `ossim`.`custom_report_types` SET  `name` =  'Collection Sources',
`inputs` =  'Top Collection Sources:top:text:OSS_DIGIT:5:50;Source:source:select:OSS_ALPHA:EVENTSOURCELOGGER:' WHERE  `custom_report_types`.`id` =143;
UPDATE `custom_report_types` SET `name` = 'Logger Events' WHERE `custom_report_types`.`id` =107;
UPDATE custom_report_types SET inputs=CONCAT(inputs,";Source:source:select:OSS_ALPHA:EVENTSOURCE:") WHERE file='Various/Eventsby.php' AND inputs not like '%EVENTSOURCE%';
UPDATE custom_report_types SET inputs=CONCAT(inputs,";Plugin Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:") WHERE file='SIEM/List.php' AND inputs not like '%PLUGINGROUPS%';

ALTER TABLE `user_config` CHANGE `name` `name` VARCHAR( 128 ) NOT NULL;

REPLACE INTO config (conf,value) VALUES ( 'user_life_time',  '0' );
REPLACE INTO config (conf,value) VALUES ( 'ntop_link', '0' );

UPDATE risk_indicators SET name='Engineer pc1' WHERE id='74';
UPDATE risk_indicators SET name='Engineer pc2' WHERE id='76';
UPDATE risk_indicators SET name='Engineer pc3' WHERE id='77';
UPDATE risk_indicators SET name='Engineer pc4' WHERE id='78';
UPDATE risk_indicators SET name='Engineer pc5' WHERE id='79';

DROP TABLE IF EXISTS sem_stats;

TRUNCATE net_cidrs;
DROP PROCEDURE IF EXISTS net_convert;
DELIMITER ;;
CREATE PROCEDURE net_convert()
BEGIN
DECLARE done BOOLEAN DEFAULT 0;
DECLARE cidr VARCHAR(15);
DECLARE mask VARCHAR(3);
DECLARE net_list CURSOR FOR SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(ips,'\r\n',''), ',', 1), '/', 1),SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(ips,'\r\n',''), ',', 1), '/', -1) FROM net UNION SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(ips,'\r\n',''), ',', -1), '/', 1),SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(ips,'\r\n',''), ',', -1), '/', -1) FROM net;
DECLARE CONTINUE HANDLER FOR SQLSTATE '02000' SET done=1;
OPEN net_list;
REPEAT
FETCH net_list INTO cidr, mask;
set @ips = CONCAT(cidr,"/",mask);
SELECT inet_aton(cidr) INTO @begin;
SELECT inet_aton(cidr) + (pow(2, (32-mask))-1) INTO @end;
REPLACE INTO net_cidrs(cidr,begin,end) VALUES (@ips,@begin,@end);
UNTIL done END REPEAT;
CLOSE net_list;
END ;;
DELIMITER ;
CALL net_convert;
DROP PROCEDURE IF EXISTS net_convert;

-- ATENCION! Keep this at the end of this file
use ossim;
REPLACE INTO config (conf, value) VALUES ('last_update', '2011-07-35');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.36');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
