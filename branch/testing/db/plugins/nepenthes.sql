-- optener antispam
-- plugin_id: 1564

-- DELETE FROM plugin WHERE id = "1564";
-- DELETE FROM plugin_sid where plugin_id = "1564";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1564, 1, "nepenthes", "Nepenthes Honeypot");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1564, 1, NULL, NULL, 1, 1, "nepenthes: Incoming Connection");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1564, 2, NULL, NULL, 1, 1, "nepenthes: Shellcode Detected");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1564, 3, NULL, NULL, 1, 1, "nepenthes: Transfer Attempt");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1564, 4, NULL, NULL, 1, 1, "nepenthes: Handler download attempt");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1564, 5, NULL, NULL, 1, 1, "nepenthes: Download failed");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1564, 6, NULL, NULL, 1, 1, "nepenthes: Download done");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1564, 7, NULL, NULL, 1, 1, "nepenthes: File submission");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1564, 8, NULL, NULL, 1, 1, "nepenthes: Malware on download file");
