use ossim;
SET AUTOCOMMIT=0;
BEGIN;

UPDATE repository SET `user` = '0';

REPLACE INTO `acl_perm` (`id`, `type`, `name`, `value`, `description`, `granularity_sensor`, `granularity_net`, `enabled`, `ord`) VALUES (84, 'MENU', 'MenuMonitors', 'IPReputation', 'Situational Awareness -> IP Reputation', 0, 0, 1, '07.05');

REPLACE INTO `ossim_acl`.`aco` (`id`, `section_value`, `value`, `order_value`, `name`, `hidden`) VALUES (94, 'MenuMonitors', 'IPReputation', 5, 'IPReputation', 0);

UPDATE  host_services SET  `service` =  'OSSIM Server' WHERE  `host_services`.`port` = 40001 AND  `host_services`.`service` = 'unknown' ;
UPDATE  host_services SET  `service` =  'OpenVAS' WHERE  `host_services`.`port` = 9390 AND  `host_services`.`service` = 'unknown' ;
UPDATE  host_services SET  `service` =  'Nessus' WHERE  `host_services`.`port` = 1241 AND  `host_services`.`service` = 'unknown' ;

REPLACE INTO config (conf, value) VALUES ('last_update', '2011-10-28');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '3.0.6');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA

