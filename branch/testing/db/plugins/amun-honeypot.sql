-- amun honeypot
-- plugin_id: 1662

-- DELETE FROM plugin WHERE id = "1662";
-- DELETE FROM plugin_sid where plugin_id = "1662";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1662, 1, "amun", "Amun Honeypot");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1662, 1, NULL, NULL, 1, 1, "Amun: Incoming Connection");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1662, 2, NULL, NULL, 1, 1, "Amun: Exploit Detected");

