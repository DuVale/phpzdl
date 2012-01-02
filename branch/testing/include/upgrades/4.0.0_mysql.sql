-- Added columns for context information (SID column)
-- ALTER TABLE `action` ADD `ctx` INT UNSIGNED NOT NULL;
-- "Alarm" table SID is snort_sid
ALTER TABLE `alarm_group` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `alarm_tags` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `bp_asset` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
-- "bp_asset_member_type" table, I'm not sure if SID is needed here.
ALTER TABLE `bp_member_status` ADD `ctx` INT UNSIGNED DEFAULT 0;
ALTER TABLE `bp_process` ADD `ctx` INT UNSIGNED DEFAULT 0;
-- category? category_changes?
ALTER TABLE `category` ADD `ctx` INT UNSIGNED DEFAULT 0;
ALTER TABLE `category_changes` ADD `ctx` INT UNSIGNED DEFAULT 0;

-- ALTER TABLE `control_panel` DROP PRIMARY KEY;
-- ALTER TABLE `control_panel` CHANGE `id` `id` INT UNSIGNED NOT NULL AUTO_INCREMENT;
-- ALTER TABLE `control_panel` ADD PRIMARY KEY (id, rrd_type, time_range);

ALTER TABLE `credentials` ADD `host_id` INT UNSIGNED NOT NULL; -- this is host.id 
ALTER TABLE `credentials` DROP COLUMN ip; -- we don't use the IP anymore. It is needed to script something to transforme IP's into standard entries during the upgrade.

-- custom_collector_rules ?
-- custom_collectors ?

ALTER TABLE `custom_report_profiles` ADD `ctx` INT UNSIGNED DEFAULT 0;
ALTER TABLE `custom_report_scheduler` ADD `ctx` INT UNSIGNED DEFAULT 0;

ALTER TABLE `databases` DROP PRIMARY KEY;
ALTER TABLE `databases` ADD `ctx` INT UNSIGNED DEFAULT 0;
ALTER TABLE `databases` ADD `id` INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE; -- we remove the name as main identifier
ALTER TABLE `databases` ADD PRIMARY KEY (ctx, id);
ALTER TABLE `databases` DROP INDEX `id`;
-- ALTER TABLE `databases` CHANGE  `id` `id` INT UNSIGNED NOT NULL;

ALTER TABLE `event_tmp` ADD `ctx` INT UNSIGNED DEFAULT 0;

-- Add Host ID to host table. Change PK from IP to ID.
-- remember to do some script to move hosts info to new format.
ALTER TABLE `host` DROP PRIMARY KEY;
ALTER TABLE `host` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `host` ADD `id` INT UNSIGNED  NOT NULL AUTO_INCREMENT UNIQUE; -- UUID
ALTER TABLE `host` ADD PRIMARY KEY (ctx, id);
-- ALTER TABLE `host` DROP INDEX `id`;
-- ALTER TABLE `host` ADD `ipv6` VARBINARY(16) NOT NULL DEFAULT 0;
-- UPDATE `host` SET ipv6=inet_aton(ip);
-- ALTER TABLE `host` DROP `ip`, CHANGE `ipv6` `ip` VARBINARY(16) NOT NULL;

-- ALTER TABLE `host` CHANGE  `id` `id` INT UNSIGNED NOT NULL;

ALTER TABLE `host_agentless` DROP PRIMARY KEY;
ALTER TABLE `host_agentless` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `host_agentless` ADD `id` INT UNSIGNED NOT NULL; -- UUID
ALTER TABLE `host_agentless` ADD PRIMARY KEY (ctx, id);
-- UPDATE host_agentless r, host h set r.ip = h.id WHERE h.ip = r.ip;

ALTER TABLE `host_agentless_entries` CHANGE `id` `id` INT UNSIGNED NOT NULL;
ALTER TABLE `host_agentless_entries` DROP PRIMARY KEY;
ALTER TABLE `host_agentless_entries` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `host_agentless_entries` ADD PRIMARY KEY (ctx, id);
-- UPDATE host_agentless_entries r, host h set r.ip = h.id WHERE h.ip COLLATE latin1_general_ci = r.ip;

-- Change Host IP to ID in host_apps
-- UPDATE host_apps r,host h set r.ip = h.id WHERE INET_ATON(h.ip) = r.ip;
-- ALTER TABLE `host_apps` DROP PRIMARY KEY;
ALTER TABLE `host_apps` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `host_apps` CHANGE `ip` `id` INT UNSIGNED;
ALTER TABLE `host_apps` ADD PRIMARY KEY (ctx, id);

ALTER TABLE `host_group` DROP PRIMARY KEY;
ALTER TABLE `host_group` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `host_group` ADD `id` INT UNSIGNED NOT NULL;
ALTER TABLE `host_group` ADD PRIMARY KEY (ctx, id);

-- This table shouldn't be needed anymore.
-- DROP TABLE host_group_sensor_reference; 

ALTER TABLE `host_ids` DROP PRIMARY KEY;
ALTER TABLE `host_ids` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `host_ids` ADD `id` INT UNSIGNED NOT NULL;
ALTER TABLE `host_ids` ADD PRIMARY KEY (ctx, id, date, plugin_sid, target);


ALTER TABLE `host_mac` DROP PRIMARY KEY;
ALTER TABLE `host_mac` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `host_mac` ADD `id` INT UNSIGNED NOT NULL;
ALTER TABLE `host_mac` ADD PRIMARY KEY (ctx, id, date, sensor);

-- In host_netbios needed to script to move data
ALTER TABLE `host_netbios` DROP PRIMARY KEY;
ALTER TABLE `host_netbios` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `host_netbios` ADD `id` INT UNSIGNED NOT NULL; 
ALTER TABLE `host_netbios` ADD PRIMARY KEY (ctx, id);

ALTER TABLE `host_os` DROP PRIMARY KEY;
ALTER TABLE `host_os` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `host_os` ADD `id` INT UNSIGNED NOT NULL;
ALTER TABLE `host_os` ADD PRIMARY KEY (ctx, id, date, sensor);

-- DELETE FROM host_plugin_sid WHERE host_ip NOT IN (SELECT inet_aton(ip) FROM host);
-- UPDATE host_plugin_sid r, host h set r.host_ip = h.id WHERE inet_aton(h.ip) = r.host_ip;
ALTER TABLE `host_plugin_sid` DROP PRIMARY KEY;
ALTER TABLE `host_plugin_sid` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `host_plugin_sid` ADD `id` INT UNSIGNED NOT NULL; 
ALTER TABLE `host_plugin_sid` DROP COLUMN host_ip; -- script this to move data
ALTER TABLE `host_plugin_sid` ADD PRIMARY KEY (ctx, id, plugin_id, plugin_sid);

ALTER TABLE `host_properties` CHANGE `id` `id` INT UNSIGNED NOT NULL;
ALTER TABLE `host_properties` DROP PRIMARY KEY;
ALTER TABLE `host_properties` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `host_properties` ADD PRIMARY KEY (ctx, id);

ALTER TABLE `host_properties_changes` CHANGE `id` `id` INT UNSIGNED NOT NULL;
ALTER TABLE `host_properties_changes` DROP PRIMARY KEY;
ALTER TABLE `host_properties_changes` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `host_properties_changes` ADD PRIMARY KEY (ctx, id);

ALTER TABLE `host_qualification` DROP PRIMARY KEY;
ALTER TABLE `host_qualification` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `host_qualification` ADD `id` INT UNSIGNED NOT NULL; -- UUID
ALTER TABLE `host_qualification` ADD PRIMARY KEY (ctx, id);
-- UPDATE host_qualification r, host h set r.host_ip = h.id WHERE h.ip = r.host_ip;

ALTER TABLE `host_scan` DROP PRIMARY KEY;
ALTER TABLE `host_scan` ADD `id` INT UNSIGNED NOT NULL; -- UUID (match with host.id)
ALTER TABLE `host_scan` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `host_scan` ADD PRIMARY KEY (ctx, id, plugin_id, plugin_sid);
-- UPDATE host_scan r, host h set r.host_ip = h.id WHERE inet_aton(h.ip) = r.host_ip;

DROP TABLE host_sensor_reference;

ALTER TABLE `host_services` DROP PRIMARY KEY;
ALTER TABLE `host_services` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `host_services` ADD `id` INT UNSIGNED NOT NULL; -- UUID (match with host.id)
ALTER TABLE `host_services` ADD PRIMARY KEY (id, port, protocol, version, date);

-- host_source_reference?
ALTER TABLE `host_vulnerability` DROP PRIMARY KEY;
ALTER TABLE `host_vulnerability` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `host_vulnerability` ADD `id` INT UNSIGNED NOT NULL; -- UUID (match with host.id)
ALTER TABLE `host_vulnerability` ADD PRIMARY KEY (ctx, id, scan_date);
-- UPDATE host_vulnerability r, host h set r.ip = h.id WHERE h.ip = r.ip;

ALTER TABLE `incident` CHANGE `id` `id` INT UNSIGNED NOT NULL;
ALTER TABLE `incident` DROP PRIMARY KEY;
ALTER TABLE `incident` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `incident` ADD PRIMARY KEY (ctx, id);

ALTER TABLE `incident_custom_types` DROP PRIMARY KEY;
ALTER TABLE `incident_custom_types` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `incident_custom_types` CHANGE `id` `id` INT UNSIGNED NOT NULL;
ALTER TABLE `incident_custom_types` ADD PRIMARY KEY (ctx, id, name);

ALTER TABLE `incident_type` DROP PRIMARY KEY;
ALTER TABLE `incident_type` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
-- ALTER TABLE `incident_type` CHANGE `id` `id` INT UNSIGNED NOT NULL;
ALTER TABLE `incident_type` ADD PRIMARY KEY (ctx, id);

ALTER TABLE `inventory_search` DROP PRIMARY KEY;
ALTER TABLE `inventory_search` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `inventory_search` ADD PRIMARY KEY (ctx, type, subtype);

ALTER TABLE `ldap` CHANGE `id` `id` INT UNSIGNED NOT NULL;
ALTER TABLE `ldap` DROP PRIMARY KEY;
ALTER TABLE `ldap` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `ldap` ADD PRIMARY KEY (ctx, id);

ALTER TABLE `log_config` DROP PRIMARY KEY;
ALTER TABLE `log_config` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `log_config` ADD PRIMARY KEY (ctx, code);

ALTER TABLE `log_action` DROP PRIMARY KEY;
ALTER TABLE `log_action` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `log_action` ADD PRIMARY KEY (ctx, date, code, info);

ALTER TABLE `map` DROP PRIMARY KEY;
ALTER TABLE `map` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `map` ADD PRIMARY KEY (ctx, id);

ALTER TABLE `map_element` DROP PRIMARY KEY;
ALTER TABLE `map_element` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `map_element` ADD PRIMARY KEY (ctx, id);

ALTER TABLE `net` DROP PRIMARY KEY;
ALTER TABLE `net` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `net` ADD `id` INT UNSIGNED NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (ctx, id); -- UUID
-- ALTER TABLE `net` ADD PRIMARY KEY (ctx, id);

ALTER TABLE `net_group` DROP PRIMARY KEY;
ALTER TABLE `net_group` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `net_group` ADD `id` INT UNSIGNED NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (ctx, id); -- UUID

-- move old net_group_reference table data to the new one
DROP TABLE `net_group_reference`;
CREATE TABLE IF NOT EXISTS net_group_reference (
    id              int NOT NULL auto_increment,
    ctx             int NOT NULL DEFAULT 0,
    net_group_id    int NOT NULL DEFAULT 0,
    net_id			    int NOT NULL DEFAULT 0,
    PRIMARY KEY     (ctx, id)
);

ALTER TABLE `net_group_scan` DROP PRIMARY KEY;
ALTER TABLE `net_group_scan` CHANGE `net_group_name` `net_group_id` INT UNSIGNED NOT NULL;
ALTER TABLE `net_group_scan` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `net_group_scan` ADD PRIMARY KEY (ctx, net_group_id, plugin_id, plugin_sid);

ALTER TABLE `net_qualification` DROP PRIMARY KEY;
ALTER TABLE `net_qualification` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `net_qualification` ADD `id` INT UNSIGNED NOT NULL; -- same than net.id
ALTER TABLE `net_qualification` ADD PRIMARY KEY (ctx, id);
-- DELETE FROM net_qualification WHERE net_name NOT IN (SELECT DISTINCT name FROM net); -- Clean
-- UPDATE net_qualification r, net n set r.net_name = n.id WHERE r.net_name = n.name;

ALTER TABLE `net_scan` DROP PRIMARY KEY;
ALTER TABLE `net_scan` CHANGE `net_name` `net_id` INT UNSIGNED NOT NULL;
ALTER TABLE `net_scan` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `net_scan` ADD PRIMARY KEY (ctx, net_id, plugin_id, plugin_sid);
-- DELETE FROM net_scan WHERE net_name NOT IN (SELECT DISTINCT name FROM net); -- Clean
-- UPDATE net_scan r, net n set r.net_name = n.id WHERE r.net_name = n.name;

DROP TABLE net_sensor_reference;

-- DELETE FROM net_vulnerability WHERE net NOT IN (SELECT DISTINCT name FROM net); -- Clean
-- UPDATE net_vulnerability r, net n set r.net = n.id WHERE r.net = n.name;
ALTER TABLE `net_vulnerability` DROP PRIMARY KEY;
ALTER TABLE `net_vulnerability` CHANGE `net` `net_id` INT UNSIGNED NOT NULL; -- same than net.id
ALTER TABLE `net_vulnerability` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `net_vulnerability` ADD PRIMARY KEY (ctx, net_id, scan_date);

ALTER TABLE `network_device` DROP PRIMARY KEY;
ALTER TABLE `network_device` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `network_device` ADD `id` INT UNSIGNED NOT NULL;
ALTER TABLE `network_device` ADD PRIMARY KEY (ctx, id);

ALTER TABLE `pass_history` DROP `id`;
ALTER TABLE `pass_history` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `pass_history` ADD `id` INT UNSIGNED NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (ctx, id); -- UUID

DROP TABLE IF EXISTS product_type;
CREATE TABLE IF NOT EXISTS product_type (
    id           INTEGER NOT NULL AUTO_INCREMENT,
    name     VARCHAR (100) NOT NULL,
    PRIMARY KEY (id)
);

-- INSERT INTO product_type (name) SELECT DISTINCT source_type FROM plugin WHERE source_type IS NOT NULL;
-- UPDATE plugin p, product_type t SET p.source_type=t.id WHERE p.source_type=t.name;

ALTER TABLE `plugin` DROP PRIMARY KEY;
ALTER TABLE `plugin` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `plugin` CHANGE  `source_type`  `product_type` INTEGER NOT NULL DEFAULT '0';
ALTER TABLE `plugin` ADD PRIMARY KEY (ctx, id);

ALTER TABLE `plugin_group` DROP PRIMARY KEY;
ALTER TABLE `plugin_group` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `plugin_group` ADD PRIMARY KEY (ctx, group_id, plugin_id);

ALTER TABLE `plugin_reference` DROP PRIMARY KEY;
ALTER TABLE `plugin_reference` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `plugin_reference` ADD PRIMARY KEY (ctx, plugin_id, plugin_sid, reference_id, reference_sid);

ALTER TABLE `plugin_scheduler` DROP PRIMARY KEY;
ALTER TABLE `plugin_scheduler` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `plugin_scheduler` ADD PRIMARY KEY (ctx, id);

ALTER TABLE `plugin_scheduler_host_reference` DROP PRIMARY KEY;
ALTER TABLE `plugin_scheduler_host_reference` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `plugin_scheduler_host_reference` CHANGE `ip` `host_id` INT UNSIGNED NOT NULL;
ALTER TABLE `plugin_scheduler_host_reference` ADD PRIMARY KEY (ctx, host_id, plugin_scheduler_id);

ALTER TABLE `plugin_scheduler_hostgroup_reference` DROP PRIMARY KEY;
ALTER TABLE `plugin_scheduler_hostgroup_reference` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `plugin_scheduler_hostgroup_reference` CHANGE `hostgroup_name` `hostgroup_id` INT UNSIGNED NOT NULL;
ALTER TABLE `plugin_scheduler_hostgroup_reference` ADD PRIMARY KEY (ctx, hostgroup_id, plugin_scheduler_id);

ALTER TABLE `plugin_scheduler_net_reference` DROP PRIMARY KEY;
ALTER TABLE `plugin_scheduler_net_reference` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `plugin_scheduler_net_reference` CHANGE `net_name` `net_id` INT UNSIGNED NOT NULL;
ALTER TABLE `plugin_scheduler_net_reference` ADD PRIMARY KEY (ctx, net_id, plugin_scheduler_id);

ALTER TABLE `plugin_scheduler_netgroup_reference` DROP PRIMARY KEY;
ALTER TABLE `plugin_scheduler_netgroup_reference` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `plugin_scheduler_netgroup_reference` CHANGE `netgroup_name` `netgroup_id` INT UNSIGNED NOT NULL;
ALTER TABLE `plugin_scheduler_netgroup_reference` ADD PRIMARY KEY (ctx, netgroup_id, plugin_scheduler_id);

-- DROP TABLE `net_group_reference`; --I'm not sure about this

ALTER TABLE `plugin_sid` DROP PRIMARY KEY;
ALTER TABLE `plugin_sid` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `plugin_sid` ADD PRIMARY KEY (ctx, plugin_id, sid);

ALTER TABLE `plugin_sid_changes` DROP PRIMARY KEY;
ALTER TABLE `plugin_sid_changes` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `plugin_sid_changes` ADD PRIMARY KEY (ctx, plugin_id, sid);

ALTER TABLE `policy` DROP `id`;
ALTER TABLE `policy` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `policy` ADD `id` INT UNSIGNED NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (ctx, id);

ALTER TABLE `policy_actions` DROP PRIMARY KEY;
ALTER TABLE `policy_actions` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `policy_actions` ADD PRIMARY KEY (ctx, policy_id, action_id);

ALTER TABLE `policy_forward_reference` DROP PRIMARY KEY;
ALTER TABLE `policy_forward_reference` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `policy_forward_reference` ADD `parent_id` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `policy_forward_reference` CHANGE `server_id` `child_id` INT UNSIGNED NOT NULL;
ALTER TABLE `policy_forward_reference` ADD PRIMARY KEY (ctx, policy_id, child_id);


ALTER TABLE `policy_group` DROP `group_id`;
ALTER TABLE `policy_group` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `policy_group` ADD `id` INT UNSIGNED NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (ctx, id);

-- these tables need to move data from old tables
ALTER TABLE `policy_host_group_reference` DROP PRIMARY KEY;
ALTER TABLE `policy_host_group_reference` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `policy_host_group_reference` CHANGE `host_group_name` `host_group_id` INT UNSIGNED NOT NULL;
ALTER TABLE `policy_host_group_reference` ADD PRIMARY KEY (ctx, policy_id, host_group_id, direction);
-- UPDATE host_group_reference r, host h set r.host_ip = h.id WHERE h.ip = r.host_ip;

ALTER TABLE `policy_host_reference` DROP PRIMARY KEY;
ALTER TABLE `policy_host_reference` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `policy_host_reference` CHANGE `host_ip` `host_id` INT UNSIGNED NOT NULL;
ALTER TABLE `policy_host_reference` ADD PRIMARY KEY (ctx, policy_id, host_id, direction);
-- UPDATE policy_host_reference r, host h set r.host_ip = h.id WHERE h.ip = r.host_ip;

-- DELETE FROM net_group_reference WHERE net_name NOT IN (SELECT DISTINCT name FROM net); -- Clean
-- UPDATE net_group_reference r, net n set r.net_name = n.id WHERE n.name = r.net_name;
ALTER TABLE `policy_net_group_reference` DROP PRIMARY KEY;
ALTER TABLE `policy_net_group_reference` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `policy_net_group_reference` CHANGE `net_group_name` `net_group_id` INT UNSIGNED NOT NULL;
ALTER TABLE `policy_net_group_reference` ADD PRIMARY KEY (ctx, policy_id, net_group_id, direction);

ALTER TABLE `policy_net_reference` DROP PRIMARY KEY;
ALTER TABLE `policy_net_reference` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `policy_net_reference` CHANGE `net_name` `net_id` INT UNSIGNED NOT NULL;
ALTER TABLE `policy_net_reference` ADD PRIMARY KEY (ctx, policy_id, net_id, direction);
-- DELETE FROM policy_net_reference WHERE net_name NOT IN (SELECT DISTINCT name FROM net); -- Clean
-- UPDATE policy_net_reference r,net n set r.net_name = n.id WHERE n.name = r.net_name;

ALTER TABLE `policy_port_reference` DROP PRIMARY KEY;
ALTER TABLE `policy_port_reference` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `policy_port_reference` CHANGE `port_group_name` `port_group_src_id` INT UNSIGNED NOT NULL DEFAULT 0; 
ALTER TABLE `policy_port_reference` ADD `port_group_dst_id` INT UNSIGNED NOT NULL DEFAULT 0; 
ALTER TABLE `policy_port_reference` ADD PRIMARY KEY (ctx, policy_id, port_group_src_id, port_group_dst_id);

CREATE TABLE IF NOT EXISTS `policy_idm_reference` (
  `policy_id` int(11) NOT NULL,
  `id` 				INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ctx`       INT NOT NULL DEFAULT 0,
  `src_username` varchar(128) DEFAULT NULL,
  `dst_username` varchar(128) DEFAULT NULL,
  `src_domain` varchar(128) DEFAULT NULL,
  `dst_domain` varchar(128) DEFAULT NULL,
  `src_hostname` varchar(128) DEFAULT NULL,
  `dst_hostname` varchar(128) DEFAULT NULL,
  `src_mac` varchar(128) DEFAULT NULL,
  `dst_mac` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`policy_id`,`ctx`,`id`)
);

CREATE TABLE IF NOT EXISTS `policy_reputation_reference` (
  `policy_id` int(11) NOT NULL,
  `id` 				INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ctx`       INT NOT NULL DEFAULT 0,
  `rep_ip_src` INT NOT NULL DEFAULT 0,
  `rep_ip_dst` INT NOT NULL DEFAULT 0,
  `rep_prio_src` INT NOT NULL DEFAULT 0,
  `rep_prio_dst` INT NOT NULL DEFAULT 0,
  `rep_rel_src` INT NOT NULL DEFAULT 0,
  `rep_rel_dst` INT NOT NULL DEFAULT 0,
  `rep_act_src` INT NOT NULL DEFAULT 0,
  `rep_act_dst` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`policy_id`,`ctx`,`id`)
);


CREATE TABLE IF NOT EXISTS `policy_taxonomy_reference` (
  `policy_id` int(11) NOT NULL,
  `id` 				INT UNSIGNED NOT NULL AUTO_INCREMENT, -- multiple taxonomies may be possible
  `ctx`       INT NOT NULL DEFAULT 0,
  `product_type_id` INT NOT NULL DEFAULT 0,
  `category_id` INT NOT NULL DEFAULT 0,
  `subcategory_id` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`policy_id`,`ctx`,`id`)
);

CREATE TABLE IF NOT EXISTS `policy_extra_data_reference` (
  `policy_id` int(11) NOT NULL,
  `id` 				INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ctx`       INT NOT NULL DEFAULT 0,
  `filename` varchar(128) DEFAULT NULL,
  `username` varchar(128) DEFAULT NULL,
  `password` varchar(128) DEFAULT NULL,
  `userdata1` varchar(128) DEFAULT NULL,
  `userdata2` varchar(128) DEFAULT NULL,
  `userdata3` varchar(128) DEFAULT NULL,
  `userdata4` varchar(128) DEFAULT NULL,
  `userdata5` varchar(128) DEFAULT NULL,
  `userdata6` varchar(128) DEFAULT NULL,
  `userdata7` varchar(128) DEFAULT NULL,
  `userdata8` varchar(128) DEFAULT NULL,
  `userdata9` varchar(128) DEFAULT NULL,
  `data_payload` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`policy_id`,`ctx`,`id`)
); 

CREATE TABLE IF NOT EXISTS `policy_time_reference` (
  `policy_id` int(11) NOT NULL,
  `id` 				INT UNSIGNED NOT NULL AUTO_INCREMENT, -- multiple timemay be possible
  `ctx`       INT NOT NULL DEFAULT 0,
  `minute_start` INT NOT NULL DEFAULT 0,
  `minute_end` INT NOT NULL DEFAULT 0,
  `hour_start` INT NOT NULL DEFAULT 0,
  `hour_end` INT NOT NULL DEFAULT 0,
  `week_day_start` INT NOT NULL DEFAULT 0,
  `week_day_end` INT NOT NULL DEFAULT 0,
  `month_day_start` INT NOT NULL DEFAULT 0,
  `month_day_end` INT NOT NULL DEFAULT 0,
  `month_start` INT NOT NULL DEFAULT 0,
  `month_end` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`policy_id`,`ctx`,`id`)
);

CREATE TABLE IF NOT EXISTS `policy_risk_reference` (
  `policy_id` int(11) NOT NULL,
  `id` 				INT UNSIGNED NOT NULL AUTO_INCREMENT, 
  `ctx`       INT NOT NULL DEFAULT 0,
  `priority` INT DEFAULT NULL,
  `reliability` INT DEFAULT NULL,
  PRIMARY KEY (`policy_id`,`ctx`,`id`)
);



ALTER TABLE `policy_sensor_reference` DROP PRIMARY KEY;
ALTER TABLE `policy_sensor_reference` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `policy_sensor_reference` CHANGE `sensor_name` `sensor_id` INT UNSIGNED NOT NULL; 
ALTER TABLE `policy_sensor_reference` ADD PRIMARY KEY (ctx, policy_id, sensor_id);

ALTER TABLE `policy_sig_reference` DROP PRIMARY KEY;
ALTER TABLE `policy_sig_reference` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `policy_sig_reference` CHANGE `sig_group_name` `sig_group_id` INT UNSIGNED NOT NULL; 
ALTER TABLE `policy_sig_reference` ADD PRIMARY KEY (ctx, policy_id, sig_group_id);

ALTER TABLE `policy_target_reference` DROP PRIMARY KEY;
ALTER TABLE `policy_target_reference` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `policy_target_reference` CHANGE `target_name` `target_id` INT UNSIGNED NOT NULL; 
ALTER TABLE `policy_target_reference` ADD PRIMARY KEY (ctx, policy_id, target_id);

ALTER TABLE `port` DROP PRIMARY KEY;
ALTER TABLE `port` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `port` ADD PRIMARY KEY (ctx, port_number, protocol_name);

-- ALTER TABLE `port_group` DROP `id`;
ALTER TABLE `port_group` DROP PRIMARY KEY;
ALTER TABLE `port_group` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `port_group` ADD `id` INT UNSIGNED NOT NULL;
ALTER TABLE `port_group` ADD PRIMARY KEY (ctx, id);

ALTER TABLE `port_group_reference` DROP PRIMARY KEY;
ALTER TABLE `port_group_reference` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `port_group_reference` CHANGE `port_group_name` `port_group_id` INT UNSIGNED NOT NULL;
ALTER TABLE `port_group_reference` ADD PRIMARY KEY (ctx, port_group_id, port_number, protocol_name);

ALTER TABLE `protocol` DROP PRIMARY KEY;
ALTER TABLE `protocol` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `protocol` ADD PRIMARY KEY (ctx, id);

ALTER TABLE `repository` DROP `id`;
ALTER TABLE `repository` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `repository` ADD `id` INT UNSIGNED AUTO_INCREMENT, ADD PRIMARY KEY (ctx, id); -- MUL needed in title, text and keywords??

ALTER TABLE `repository_attachments` DROP `id`;
ALTER TABLE `repository_attachments` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `repository_attachments` ADD `id` INT UNSIGNED AUTO_INCREMENT, ADD PRIMARY KEY (ctx, id);

ALTER TABLE `repository_relationships` DROP `id`;
ALTER TABLE `repository_relationships` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `repository_relationships` ADD `id` INT UNSIGNED AUTO_INCREMENT, ADD PRIMARY KEY (ctx, id);

-- response_* tables? what are they doing?

ALTER TABLE `restoredb_log` DROP `id`;
ALTER TABLE `restoredb_log` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `restoredb_log` ADD `id` INT UNSIGNED AUTO_INCREMENT, ADD PRIMARY KEY (ctx, id);

ALTER TABLE `risk_indicators` DROP `id`;
ALTER TABLE `risk_indicators` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `risk_indicators` ADD `id` INT UNSIGNED AUTO_INCREMENT, ADD PRIMARY KEY (ctx, id);

ALTER TABLE `risk_maps` DROP PRIMARY KEY;
ALTER TABLE `risk_maps` CHANGE `perm` `ctx` INT UNSIGNED NOT NULL;
ALTER TABLE `risk_maps` CHANGE `map` `id` INT UNSIGNED NOT NULL; 
ALTER TABLE `risk_maps` ADD PRIMARY KEY (ctx, id);

ALTER TABLE `rrd_anomalies` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `rrd_anomalies` ADD `id` INT UNSIGNED AUTO_INCREMENT, ADD PRIMARY KEY (ctx, id);

-- rrd_anomalies_global only visible by admin?

ALTER TABLE `rrd_config` DROP PRIMARY KEY;
ALTER TABLE `rrd_config` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `rrd_config` ADD PRIMARY KEY (ctx, profile, rrd_attrib);

ALTER TABLE `sem_stats` DROP PRIMARY KEY;
ALTER TABLE `sem_stats` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `sem_stats` DROP `sensor`; -- needed to move the right perms to ctx
ALTER TABLE `sem_stats` ADD PRIMARY KEY (ctx, day, type, value);

ALTER TABLE `sensor` DROP PRIMARY KEY;
ALTER TABLE `sensor` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `sensor` ADD `id` INT UNSIGNED NOT NULL;
ALTER TABLE `sensor` ADD PRIMARY KEY (ctx, id);

ALTER TABLE `sensor_interfaces` DROP PRIMARY KEY;
ALTER TABLE `sensor_interfaces` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `sensor_interfaces` CHANGE `sensor` `sensor_id` INT UNSIGNED NOT NULL; 
ALTER TABLE `sensor_interfaces` ADD PRIMARY KEY (ctx, sensor_id, interface); 

ALTER TABLE `sensor_properties` DROP PRIMARY KEY;
ALTER TABLE `sensor_properties` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `sensor_properties` ADD `sensor_id` INT UNSIGNED NOT NULL;
ALTER TABLE `sensor_properties` ADD PRIMARY KEY (ctx, sensor_id); 

ALTER TABLE `sensor_stats` DROP PRIMARY KEY;
ALTER TABLE `sensor_stats` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `sensor_stats` ADD `sensor_id` INT UNSIGNED NOT NULL;
ALTER TABLE `sensor_stats` ADD PRIMARY KEY (ctx, sensor_id); 

ALTER TABLE `server` DROP PRIMARY KEY;
ALTER TABLE `server` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `server` ADD PRIMARY KEY (ctx, id); 

CREATE TABLE IF NOT EXISTS server_forward_hierarchy (
    child_id        int NOT NULL,
    parent_id       int NOT NULL,
    priority        int NOT NULL,
    ctx             int NOT NULL,
    PRIMARY KEY     (ctx, child_id, parent_id)
);

ALTER TABLE `server_role` DROP PRIMARY KEY;
ALTER TABLE `server_role` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `server_role` ADD `server_id` INT UNSIGNED NOT NULL;
ALTER TABLE `server_role` ADD PRIMARY KEY (ctx, server_id); 

ALTER TABLE `sessions` DROP PRIMARY KEY;
ALTER TABLE `sessions` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `sessions` ADD PRIMARY KEY (ctx, id, login); 

ALTER TABLE `signature` DROP PRIMARY KEY;
ALTER TABLE `signature` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `signature` ADD `id` INT UNSIGNED AUTO_INCREMENT, ADD PRIMARY KEY (ctx, id);

ALTER TABLE `signature_group` DROP PRIMARY KEY;
ALTER TABLE `signature_group` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `signature_group` ADD `signature_id` INT UNSIGNED NOT NULL;
ALTER TABLE `signature_group` ADD PRIMARY KEY (ctx, signature_id); 

ALTER TABLE `signature_group_reference` DROP PRIMARY KEY;
ALTER TABLE `signature_group_reference` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `signature_group_reference` ADD `signature_group_id` INT UNSIGNED NOT NULL;
ALTER TABLE `signature_group_reference` ADD `signature_id` INT UNSIGNED NOT NULL;
ALTER TABLE `signature_group_reference` ADD PRIMARY KEY (ctx, signature_group_id, signature_id); 

ALTER TABLE `subcategory` DROP `id`;
ALTER TABLE `subcategory` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `subcategory` ADD `id` INT UNSIGNED AUTO_INCREMENT, ADD PRIMARY KEY (ctx, id); 

ALTER TABLE `subcategory_changes` DROP PRIMARY KEY;
ALTER TABLE `subcategory_changes` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `subcategory_changes` ADD PRIMARY KEY (ctx, id); 

ALTER TABLE `tags_alarm` DROP `id`;
ALTER TABLE `tags_alarm` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `tags_alarm` ADD `id` INT UNSIGNED AUTO_INCREMENT, ADD PRIMARY KEY (ctx, id); 

ALTER TABLE `user_config` DROP PRIMARY KEY;
ALTER TABLE `user_config` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `user_config` ADD PRIMARY KEY (ctx, login, category, name);

ALTER TABLE `users` DROP PRIMARY KEY;
ALTER TABLE `users` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `users` ADD PRIMARY KEY (ctx, login);

ALTER TABLE `vuln_Incidents` DROP `id`;
ALTER TABLE `vuln_Incidents` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `vuln_Incidents` ADD `id` INT UNSIGNED AUTO_INCREMENT, ADD PRIMARY KEY (ctx, id);

ALTER TABLE `web_interfaces` DROP `id`;
ALTER TABLE `web_interfaces` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `web_interfaces` ADD `id` INT UNSIGNED AUTO_INCREMENT, ADD PRIMARY KEY (ctx, id);

ALTER TABLE `wireless_aps` DROP PRIMARY KEY;
ALTER TABLE `wireless_aps` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `wireless_aps` ADD PRIMARY KEY (ctx, mac, ssid); -- sensor is not needed anymore as key, I think

ALTER TABLE `wireless_clients` DROP PRIMARY KEY;
ALTER TABLE `wireless_clients` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `wireless_clients` ADD PRIMARY KEY (ctx, client_mac, mac, ssid); -- client_mac & mac???? :?

ALTER TABLE `wireless_locations` DROP PRIMARY KEY;
ALTER TABLE `wireless_locations` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `wireless_locations` ADD PRIMARY KEY (ctx, location, user); 

ALTER TABLE `wireless_networks` DROP PRIMARY KEY;
ALTER TABLE `wireless_networks` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `wireless_networks` ADD PRIMARY KEY (ctx, ssid); -- sensor is not needed anymore as key, I think

ALTER TABLE `wireless_sensors` DROP PRIMARY KEY;
ALTER TABLE `wireless_sensors` ADD `ctx` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `wireless_sensors` ADD PRIMARY KEY (ctx, sensor, location); 

-- Change Host IP to ID in host_sensor_reference
-- UPDATE host_sensor_reference r,host h set r.host_ip=h.id WHERE h.ip=r.host_ip;
-- ALTER TABLE `host_sensor_reference` CHANGE `host_ip` `host_id` INT UNSIGNED NOT NULL;

-- Change Network Name to ID in net_sensor_reference
-- DELETE FROM net_sensor_reference WHERE net_name NOT IN (SELECT DISTINCT name FROM net); -- Clean
-- UPDATE net_sensor_reference r,net n set r.net_name=n.id WHERE n.name=r.net_name;
-- ALTER TABLE `net_sensor_reference` CHANGE `net_name` `net_id` INT UNSIGNED NOT NULL;

-- ASSET SEARCH UPDATES:
-- REPLACE INTO `inventory_search` (`type`, `subtype`, `match`, `list`, `query`, `ruleorder`) VALUES
-- ('Alarms', 'IP is Src', 'boolean', '', 'SELECT DISTINCT INET_NTOA(alarm.src_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id', 999),
-- ('META', 'Has Dst IP', 'boolean', '', 'SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid UNION SELECT DISTINCT INET_NTOA(alarm.dst_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id', 999),
-- ('META', 'Date After', 'date', '', 'SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND a.timestamp > ? UNION SELECT DISTINCT INET_NTOA(alarm.src_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id AND alarm.timestamp > ? UNION SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND a.timestamp > ? UNION SELECT DISTINCT INET_NTOA(alarm.dst_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id AND alarm.timestamp > ?', 999),
-- ('META', 'Port as Src or Dst', 'concat', 'SELECT CONCAT(p1.port_number,"-",p2.id) as port_value,CONCAT(p1.port_number,"-",p1.protocol_name) as port_text FROM port p1, protocol p2 WHERE p1.protocol_name=p2.name', 'SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND layer4_sport = ? AND ip_proto = ? UNION SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND layer4_sport = ? AND ip_proto = ? UNION SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND layer4_dport = ? AND ip_proto = ? UNION SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND layer4_dport = ? AND ip_proto = ?', 999),
-- ('META', 'IP as Src', 'ip', 'SELECT ip FROM host', 'SELECT DISTINCT INET_NTOA(ip_dst) AS ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND INET_NTOA(ip_src) %op% ? UNION SELECT DISTINCT INET_NTOA(alarm.dst_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id AND INET_NTOA(alarm.src_ip) %op% ?', 999),
-- ('META', 'IP as Dst', 'ip', 'SELECT ip FROM host', 'SELECT DISTINCT INET_NTOA(ip_src) AS ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND INET_NTOA(ip_dst) %op% ? UNION SELECT DISTINCT INET_NTOA(alarm.src_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id AND INET_NTOA(alarm.dst_ip) %op% ?', 999),
-- ('META', 'IP as Src or Dst', 'ip', 'SELECT DISTINCT ip FROM host', 'SELECT DISTINCT INET_NTOA(ip_dst) AS ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND INET_NTOA(ip_src) %op% ? UNION SELECT DISTINCT INET_NTOA(alarm.dst_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id AND INET_NTOA(alarm.src_ip) %op% ? UNION SELECT DISTINCT INET_NTOA(ip_src) AS ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND INET_NTOA(ip_dst) %op% ? UNION SELECT DISTINCT INET_NTOA(alarm.src_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id AND INET_NTOA(alarm.dst_ip) %op% ?', 999),
-- ('META', 'Source Port', 'concat', 'SELECT CONCAT(p1.port_number,"-",p2.id) as port_value,CONCAT(p1.port_number,"-",p1.protocol_name) as port_text FROM port p1, protocol p2 WHERE p1.protocol_name=p2.name', 'SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND layer4_sport = ? AND ip_proto = ? UNION SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND layer4_sport = ? AND ip_proto = ?', 999),
-- ('META', 'Destination Port', 'concat', 'SELECT CONCAT(p1.port_number,"-",p2.id) as port_value,CONCAT(p1.port_number,"-",p1.protocol_name) as port_text FROM port p1, protocol p2 WHERE p1.protocol_name=p2.name', 'SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND layer4_dport = ? AND ip_proto = ? UNION SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND layer4_dport = ? AND ip_proto = ?', 999),
-- ('SIEM Events', 'Has Different', 'number', '', 'select inet_ntoa(t1.ip) as ip, sensor from (select count(distinct plugin_id, plugin_sid) as total,ip, sensor from (select plugin_id, plugin_sid, ip_src as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid UNION select plugin_id, plugin_sid ,ip_dst as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid) as t group by ip, sensor) as t1 where t1.total >= ?', 5),
-- ('SIEM Events', 'Has Event', 'boolean', '', 'SELECT DISTINCT INET_NTOA(ip_src) as ip, sensor FROM snort.acid_event, snort.sensor WHERE snort.acid_event.sid = snort.sensor.sid UNION SELECT DISTINCT INET_NTOA(ip_dst) as ip, sensor FROM snort.acid_event, snort.sensor WHERE snort.acid_event.sid = snort.sensor.sid', 5),
-- ('SIEM Events', 'Has no Event', 'boolean', '', 'SELECT DISTINCT h.ip, h.id FROM ossim.host h, ossim.host_sensor_reference r, sensor s WHERE h.id = r.host_id AND r.sensor_name = s.name AND CONCAT(h.ip,'','',s.ip) NOT IN (SELECT DISTINCT CONCAT (INET_NTOA(si.ip_src),'','',ss.sensor) FROM snort.acid_event si, snort.sensor ss WHERE si.sid = ss.sid AND CONCAT (INET_NTOA(si.ip_src),'','',ss.sensor) != NULL UNION SELECT DISTINCT CONCAT(INET_NTOA(si.ip_dst),'','',ss.sensor) FROM snort.acid_event si, snort.sensor ss WHERE si.sid = ss.sid AND CONCAT(INET_NTOA(si.ip_dst),'','',ss.sensor) != "NULL")', 5),
-- ('SIEM Events', 'Has Events', 'text', '', 'SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor as sensor FROM snort.acid_event s, ossim.plugin_sid p, snort.sensor ss WHERE s.sid = ss.sid AND s.plugin_id=p.plugin_id AND s.plugin_sid=p.sid AND p.name %op% ? UNION SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor as sensor FROM snort.acid_event s, ossim.plugin_sid p, snort.sensor ss WHERE s.sid = ss.sid AND s.plugin_id=p.plugin_id AND s.plugin_sid=p.sid AND p.name %op% ?', 5),
-- ('SIEM Events', 'Has Plugin Groups', 'fixed', 'SELECT group_id,name FROM plugin_group_descr', 'SELECT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event, snort.sensor ss WHERE snort.acid_event.sid = ss.sid AND plugin_id in (SELECT plugin_id FROM ossim.plugin_group WHERE group_id=?) UNION SELECT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event, snort.sensor ss WHERE snort.acid_event.sid = ss.sid AND plugin_id in (SELECT plugin_id FROM ossim.plugin_group WHERE group_id=?)', 5),
-- ('SIEM Events', 'Has IP', 'ip', 'SELECT ip FROM host', 'SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event i, snort.sensor ss WHERE i.sid = ss.sid AND INET_NTOA(ip_dst) %op% ? UNION SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event i, snort.sensor ss WHERE i.sid = ss.sid AND INET_NTOA(ip_src) %op% ?', 5),
-- ('SIEM Events', 'Has Src IP', 'ip', 'SELECT ip FROM host', 'SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event i, snort.sensor ss WHERE i.sid = ss.sid AND INET_NTOA(ip_src) %op% ?', 5),
-- ('SIEM Events', 'Has Dst IP', 'ip', 'SELECT ip FROM host', 'SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event i, snort.sensor ss WHERE i.sid = ss.sid AND INET_NTOA(ip_dst) %op% ?', 5),
-- ('SIEM Events', 'Has Port', 'concat', 'SELECT DISTINCT CONCAT(p.id,"-",h.port) as protocol_value,CONCAT(h.port,"-",p.name) as protocol_text from host_services h,protocol p where h.protocol=p.id order by h.port', 'SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND ip_proto = ? AND layer4_sport = ? UNION SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND ip_proto = ? AND layer4_dport = ?', 5),
-- ('SIEM Events', 'Has Src Port', 'concat', 'SELECT DISTINCT CONCAT(p.id,"-",h.port) as protocol_value,CONCAT(h.port,"-",p.name) as protocol_text from host_services h,protocol p where h.protocol=p.id order by h.port', 'SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND ip_proto = ? AND layer4_sport = ?', 5),
-- ('SIEM Events', 'Has Dst Port', 'concat', 'SELECT DISTINCT CONCAT(p.id,"-",h.port) as protocol_value,CONCAT(h.port,"-",p.name) as protocol_text from host_services h,protocol p where h.protocol=p.id order by h.port', 'SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND ip_proto = ? AND layer4_dport = ?', 5),
-- ('SIEM Events', 'Has Protocol', 'fixed', 'SELECT id,alias FROM protocol', 'SELECT DISTINCT INET_NTOA(snort.acid_event.ip_src) as ip, ss.sensor FROM snort.acid_event, snort.sensor ss WHERE snort.acid_event.sid = ss.sid AND snort.acid_event.ip_proto=? LIMIT 999', 5),
-- ('Alarms', 'Has Alarm', 'boolean', '', 'SELECT INET_NTOA(alarm.src_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id UNION SELECT INET_NTOA(alarm.dst_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id', 999),
-- ('Alarms', 'Has no Alarm', 'boolean', '', 'SELECT DISTINCT ip, sensor FROM (select host.ip, s.ip as sensor from host, host_sensor_reference r, sensor s WHERE host.id=r.host_id AND r.sensor_name=s.name UNION select distinct INET_NTOA(ip_src) as ip, ss.sensor from snort.acid_event a, snort.sensor ss WHERE a.sid=ss.sid) as todas WHERE CONCAT(ip,'','',sensor) not in (select distinct CONCAT(alarm.src_ip,'','',event.sensor) from alarm, event WHERE alarm.event_id = event.id) AND CONCAT(ip,'','',sensor) not in (select distinct CONCAT(alarm.dst_ip,'','',event.sensor) from alarm, event WHERE alarm.event_id = event.id)', 999),
-- ('Tickets', 'Has Tickets', 'boolean', '', 'SELECT DISTINCT a.src_ips as ip FROM incident i,incident_alarm a WHERE i.id=a.incident_id', 999),
-- ('Tickets', 'Has no Ticket', 'boolean', '', 'select distinct inet_ntoa(ip) as ip from (select inet_aton(ip) as ip from host UNION select distinct ip_src as ip from snort.acid_event) as todas WHERE ip NOT IN (SELECT DISTINCT a.src_ips as ip FROM incident i,incident_alarm a WHERE i.id=a.incident_id)', 999),
-- ('Tickets', 'Has Ticket Type', 'fixed', 'SELECT id as type_value,id as type_text FROM incident_type', 'SELECT DISTINCT a.src_ips as ip FROM incident i,incident_alarm a WHERE i.id=a.incident_id AND i.type_id=?', 999),
-- ('Tickets', 'Has Ticket Tag', 'fixed', 'SELECT id as tag_id,name as tag_name FROM incident_tag_descr', 'SELECT DISTINCT a.src_ips as ip FROM incident i,incident_alarm a,incident_tag t WHERE i.id=a.incident_id AND i.id=t.incident_id AND t.tag_id=?', 999),
-- ('Mac', 'Has Mac', 'boolean', '', 'SELECT DISTINCT ip, sensor FROM host_properties WHERE property_ref=7 UNION SELECT DISTINCT INET_NTOA(ip) as ip, INET_NTOA(sensor) as sensor FROM host_mac', 3),
-- ('Mac', 'Has No Mac', 'boolean', '', 'SELECT host.ip, host.id FROM host, host_sensor_reference, sensor WHERE host.id=host_sensor_reference.host_id AND host_sensor_reference.sensor_name=sensor.name AND CONCAT(host.ip,'','',sensor.ip) NOT IN (SELECT DISTINCT CONCAT(ip,'','',sensor) FROM host_properties WHERE property_ref=7 UNION SELECT DISTINCT CONCAT(INET_NTOA(ip),'','',INET_NTOA(sensor)) FROM host_mac)', 3),
-- ('Mac', 'Has Anomaly', 'boolean', '', 'SELECT DISTINCT INET_NTOA(host_mac.ip) as ip, INET_NTOA(host_mac.sensor) as sensor FROM (SELECT DISTINCT ip as lastip,max(date) as maxdate FROM host_mac GROUP BY ip) maxdates,host_mac WHERE host_mac.ip=maxdates.lastip AND host_mac.date=maxdates.maxdate AND host_mac.anom=1', 3),
-- ('Mac', 'Has no Anomaly', 'boolean', '', 'SELECT DISTINCT INET_NTOA(host_mac.ip) as ip, INET_NTOA(host_mac.sensor) as sensor FROM (SELECT DISTINCT ip as lastip,max(date) as maxdate FROM host_mac GROUP BY ip) maxdates,host_mac WHERE host_mac.ip=maxdates.lastip AND host_mac.date=maxdates.maxdate AND host_mac.anom=0', 3),
-- ('SIEM Events', 'IP is Dst', 'boolean', '', 'SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid LIMIT 999', 5),
-- ('META', 'Has Src or Dst IP', 'boolean', '', 'SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid UNION SELECT DISTINCT INET_NTOA(alarm.src_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id UNION SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid UNION SELECT DISTINCT INET_NTOA(alarm.dst_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id', 999),
-- ('Alarms', 'IP is Dst', 'boolean', '', 'SELECT DISTINCT INET_NTOA(alarm.dst_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id', 999),
-- ('META', 'Has Src IP', 'boolean', '', 'SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid UNION SELECT DISTINCT INET_NTOA(alarm.src_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id', 999),
-- ('META', 'Date Before', 'date', '', 'SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND a.timestamp < ? UNION SELECT DISTINCT INET_NTOA(alarm.src_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id AND alarm.timestamp < ? UNION SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND a.timestamp < ? UNION SELECT DISTINCT INET_NTOA(alarm.dst_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id AND alarm.timestamp < ?', 999),
-- ('SIEM Events', 'IP is Src', 'boolean', '', 'SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid LIMIT 999', 5),
-- ('Alarms', 'Has open Alarms', 'boolean', '', 'SELECT DISTINCT INET_NTOA(alarm.src_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id AND status=''open'' UNION SELECT DISTINCT INET_NTOA(alarm.dst_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id AND status=''open''', 999),
-- ('Alarms', 'Has closed Alarms', 'boolean', '', 'SELECT DISTINCT INET_NTOA(alarm.src_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id AND status=''closed'' UNION SELECT DISTINCT INET_NTOA(alarm.dst_ip) as ip, event.sensor FROM alarm, event WHERE alarm.event_id = event.id AND status=''closed''', 999),
-- ('OS', 'OS is', 'text', 'SELECT DISTINCT os FROM host_os WHERE os != "" ORDER BY os', '(select distinct inet_ntoa(ip) as ip, inet_ntoa(sensor) as sensor from host_properties where property_ref=3 and value like ?) UNION (select distinct inet_ntoa(h.ip) as ip, inet_ntoa(sensor) as sensor from host_os h where h.os %op% ? and h.anom=0 and concat(h.ip,'','',h.sensor) not in (select concat(h1.ip,'','',h1.sensor) from host_os h1 where h1.os<>? and h1.anom=0 and h1.date>h.date)) UNION (select distinct inet_ntoa(ip) as ip, inet_ntoa(sensor) as sensor from host_os where os %op% ? and anom=1 and concat(ip,'','',sensor) not in (select distinct concat(ip,'','',sensor) from host_os where anom=0))', 1),
-- ('OS', 'OS is Not', 'text', 'SELECT DISTINCT os FROM host_os WHERE os != "" ORDER BY os', 'select distinct inet_ntoa(ip) as ip, inet_ntoa(sensor) as sensor from host_os where concat(ip,'','',sensor) not in (select distinct concat(inet_aton(ip),'','',inet_aton(sensor)) from host_properties where property_ref=3 and value like ? UNION select concat(h.ip,'','',h.sensor) from host_os h where h.os %op% ? and h.anom=0 and concat(h.ip,'','',h.sensor) not in (select concat(h1.ip,'','',h1.sensor) from host_os h1 where h1.os<>? and h1.anom=0 and h1.date>h.date)) UNION (select ip, sensor from host_os where os %op% ? and anom=1 and concat(ip,'','',sensor) not in (select distinct concat(ip,'','',sensor) from host_os where anom=0))', 1),
-- ('OS', 'Has Anomaly', 'boolean', '', 'SELECT DISTINCT INET_NTOA(host_os.ip) as ip, INET_NTOA(host_os.sensor) as sensor FROM (SELECT DISTINCT ip as lastip,max(date) as maxdate FROM host_os GROUP BY ip) maxdates,host_os WHERE host_os.ip=maxdates.lastip AND host_os.date=maxdates.maxdate AND host_os.anom=1', 1),
-- ('OS', 'Has no Anomaly', 'boolean', '', 'SELECT DISTINCT INET_NTOA(host_os.ip) as ip, INET_NTOA(host_os.sensor) as sensor FROM (SELECT DISTINCT ip as lastip,max(date) as maxdate FROM host_os GROUP BY ip) maxdates,host_os WHERE host_os.ip=maxdates.lastip AND host_os.date=maxdates.maxdate AND host_os.anom=0', 1),
-- ('Services', 'Has services', 'fixed', 'SELECT DISTINCT service as service_value, service as service_text FROM host_services', 'SELECT DISTINCT INET_NTOA(ip) as ip, INET_NTOA(sensor) as sensor FROM host_services WHERE service=?', 2),
-- ('Services', 'Doesnt have service', 'fixed', 'SELECT DISTINCT service as service_value, service as service_text FROM host_services', 'SELECT DISTINCT host.ip, sensor.ip FROM host, host_sensor_reference, sensor WHERE host.id=host_sensor_reference.host_id AND host_sensor_reference.sensor_name=sensor.name AND CONCAT(host.ip,'','',sensor.ip) NOT IN (SELECT DISTINCT CONCAT(INET_NTOA(ip),'','',INET_NTOA(sensor)) FROM host_services WHERE service=?)', 2),
-- ('Services', 'Has Anomaly', 'boolean', '', 'select distinct inet_ntoa(h.ip) as ip, inet_ntoa(h.sensor) as sensor from host_services h,host_services h1 where h1.ip=h.ip AND h.anom=0 AND h1.anom=1 AND h.port=h1.port AND h.date<=h1.date', 2),
-- ('Services', 'Has no Anomaly', 'boolean', '', 'SELECT DISTINCT INET_NTOA(ip) as ip, INET_NTOA(sensor) as sensor FROM host_services WHERE CONCAT(ip,'','',sensor) NOT IN (select distinct CONCAT(h.ip,'','',h.sensor) from host_services h,host_services h1 where h1.ip=h.ip AND h.anom=0 AND h1.anom=1 AND h.port=h1.port AND h.date<=h1.date)', 2),
-- ('SIEM Events', 'Has user', 'text', '', 'SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event, snort.extra_data, snort.sensor ss WHERE snort.acid_event.sid=ss.sid AND snort.extra_data.sid=snort.acid_event.sid AND snort.extra_data.cid=snort.acid_event.cid AND snort.extra_data.username %op% ?', 5),
-- ('Tickets', 'Priority is greater than', 'number', '', 'SELECT DISTINCT a.src_ips as ip FROM incident i,incident_alarm a WHERE i.id=a.incident_id AND i.priority>?', 999),
-- ('Tickets', 'Priority is lower than', 'number', '', 'SELECT DISTINCT a.src_ips as ip FROM incident i,incident_alarm a WHERE i.id=a.incident_id AND i.priority<?', 999),
-- ('Tickets', 'Is older Than Days', 'number', '', 'SELECT DISTINCT a.src_ips as ip FROM incident i,incident_alarm a WHERE i.id=a.incident_id AND DATEDIFF(CURRENT_TIMESTAMP ,i.last_update) > ?', 999),
-- ('Vulnerabilities', 'Has Vuln', 'fixed', 'SELECT sid as plugin_value,name as plugin_text FROM plugin_sid WHERE plugin_id =3001', 'SELECT DISTINCT host.ip, host.id FROM host, host_plugin_sid WHERE host.id=host_plugin_sid.host_id AND plugin_id = 3001 AND plugin_sid = ?', 4),
-- ('Vulnerabilities', 'Vuln Contains', 'text', '', 'SELECT DISTINCT h.ip as ip, h.id as id FROM host_plugin_sid hp, plugin_sid p, host h WHERE hp.host_id = h.id AND hp.plugin_id = 3001 AND p.plugin_id = 3001 AND hp.plugin_sid = p.sid AND p.name %op% ? UNION SELECT DISTINCT h.ip as ip, h.id as id FROM vuln_nessus_plugins p,host_plugin_sid s, host h WHERE s.host_id = h.id AND s.plugin_id=3001 and s.plugin_sid=p.id AND p.name %op% ?', 4),
-- ('Vulnerabilities', 'Has Vulns', 'boolean', '', 'SELECT DISTINCT host.ip, host.id FROM host_plugin_sid, host WHERE host_plugin_sid.host_id = host.id AND plugin_id = 3001', 4),
-- ('Vulnerabilities', 'Has no Vulns', 'boolean', '', 'SELECT DISTINCT host.ip, host.id FROM host WHERE host.id NOT IN (SELECT host.id FROM host_plugin_sid, host WHERE host_plugin_sid.host_id = host.id AND plugin_id = 3001)', 4),
-- ('Vulnerabilities', 'Vuln Level is greater than', 'number', '', 'SELECT DISTINCT host.ip, host.id FROM host_vulnerability, host WHERE host_vulnerability.host_id = host.id AND vulnerability > ?', 4),
-- ('Vulnerabilities', 'Vuln Level is lower than', 'number', '', 'SELECT DISTINCT host.ip, host.id FROM host_vulnerability, host WHERE host_vulnerability.host_id = host.id AND vulnerability < ?', 4),
-- ('Asset', 'Asset is greater than', 'number', '', 'SELECT DISTINCT h.ip, s.ip as sensor FROM host h, host_sensor_reference r, sensor s WHERE h.id = r.host_id AND r.sensor_name = s.name AND h.asset > ? UNION SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND ossim_asset_src > ? UNION SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND ossim_asset_dst > ?', 999),
-- ('Asset', 'Asset is lower than', 'number', '', 'SELECT DISTINCT h.ip, s.ip as sensor FROM host h, host_sensor_reference r, sensor s WHERE h.id = r.host_id AND r.sensor_name = s.name AND h.asset < ? AND h.asset > 0 UNION SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND ossim_asset_src < ? AND ossim_asset_src > 0 UNION SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND ossim_asset_dst < ? AND ossim_asset_dst > 0', 999),
-- ('Asset', 'Asset is', 'fixed', 'SELECT DISTINCT asset FROM host ORDER BY asset', 'SELECT DISTINCT h.ip, s.ip as sensor FROM host h, host_sensor_reference r, sensor s WHERE h.id=r.host_id AND r.sensor_name=s.name AND asset = ? UNION SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND ossim_asset_src = ? UNION SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND ossim_asset_dst = ?', 999),
-- ('Vulnerabilities', 'Vuln risk is greater than', 'number', '', 'SELECT DISTINCT host.ip, host.id FROM host_plugin_sid h,vuln_nessus_plugins p, host WHERE h.host_id = host.id AND h.plugin_id=3001 AND h.plugin_sid=p.id AND 8-p.risk > ?', 4),
-- ('Vulnerabilities', 'Vuln risk is lower than', 'number', '', 'SELECT DISTINCT host.ip, host.id FROM host_plugin_sid h,vuln_nessus_plugins p, host WHERE h.host_id = host.id AND h.plugin_id=3001 AND h.plugin_sid=p.id AND 8-p.risk < ?', 4),
-- ('Asset', 'Asset is local', 'fixed', 'SELECT DISTINCT asset FROM host ORDER BY asset', 'SELECT DISTINCT id, ip FROM host WHERE asset = ?', 999),
-- ('Asset', 'Asset is remote', 'number', '', 'SELECT ip, sensor FROM (SELECT DISTINCT INET_NTOA(ip_src) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND ossim_asset_src = ? UNION SELECT DISTINCT INET_NTOA(ip_dst) as ip, ss.sensor FROM snort.acid_event a, snort.sensor ss WHERE a.sid = ss.sid AND ossim_asset_dst = ?) remote WHERE CONCAT(ip,'','',sensor) NOT IN (SELECT DISTINCT CONCAT(h.ip,'','',s.ip) FROM host h, host_sensor_reference r, sensor s WHERE h.id=r.host_id AND r.sensor_name=s.name)', 999),
-- ('Vulnerabilities', 'Has Vuln Service', 'text', 'SELECT DISTINCT app FROM vuln_nessus_results', 'SELECT DISTINCT hostIP as ip FROM vuln_nessus_results WHERE app %op% ?', 999),
-- ('Vulnerabilities', 'Has CVE', 'text', 'SELECT DISTINCT cve_id FROM vuln_nessus_plugins', 'SELECT DISTINCT host.ip, host.id FROM vuln_nessus_plugins p,host_plugin_sid s, host WHERE s.host_id = host.id AND s.plugin_id=3001 and s.plugin_sid=p.id AND p.cve_id %op% ?', 4),
-- ('Property', 'Has Property', 'fixed', 'SELECT DISTINCT id as property_value, name as property_text  FROM host_property_reference ORDER BY name', 'SELECT DISTINCT ip, sensor FROM host_properties WHERE property_ref = ?', 999),
-- ('Property', 'Has not Property', 'fixed', 'SELECT DISTINCT id as property_value, name as property_text  FROM host_property_reference ORDER BY name', 'SELECT DISTINCT ip, sensor FROM host_properties WHERE property_ref != ?', 999),
-- ('Property', 'Contains', 'fixedText', 'SELECT DISTINCT id as property_value, name as property_text  FROM host_property_reference ORDER BY name', 'SELECT DISTINCT ip, sensor FROM host_properties WHERE property_ref = ? AND (value LIKE ''%$value2%'' OR extra LIKE ''%$value2%'')', 999);

ALTER TABLE `event` MODIFY `src_ip` VARBINARY(16);
ALTER TABLE `event` MODIFY `dst_ip` VARBINARY(16);
ALTER TABLE `event_tmp` MODIFY `src_ip` VARBINARY(16);
ALTER TABLE `event_tmp` MODIFY `dst_ip` VARBINARY(16);
ALTER TABLE `alarm` MODIFY `src_ip` VARBINARY(16);
ALTER TABLE `alarm` MODIFY `dst_ip` VARBINARY(16);
ALTER TABLE `host_mac` MODIFY `ip` VARBINARY(16);
ALTER TABLE `host_mac` MODIFY `sensor` VARBINARY(16);
ALTER TABLE `host_services` MODIFY `ip` VARBINARY(16);
ALTER TABLE `host_services` MODIFY `sensor` VARBINARY(16);
ALTER TABLE `host_ids` MODIFY `ip` VARBINARY(16);
ALTER TABLE `host_os` MODIFY `ip` VARBINARY(16);
ALTER TABLE `host_os` MODIFY `sensor` VARBINARY(16);
ALTER TABLE `host` MODIFY `ip` VARCHAR(39);
ALTER TABLE `sensor` MODIFY `ip` VARCHAR(39);
ALTER TABLE `server` MODIFY `ip` VARCHAR(39);
ALTER TABLE `host_group_reference` MODIFY `host_ip` VARCHAR(39);
ALTER TABLE `host_qualification` MODIFY `host_ip` VARCHAR(39);
ALTER TABLE `host_properties` MODIFY `ip` VARCHAR(39);
ALTER TABLE `rrd_anomalies` MODIFY `ip` VARCHAR(39);

