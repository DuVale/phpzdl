
-- saqqara
-- plugin_id: 10002, SIDs(part) for plugin_id 1505
--
-- Revision 1.0  2011/12/01 12:58:50



INSERT IGNORE INTO plugin (id, type, name, description, source_type, vendor) VALUES (10002, 1, "SAQQARA-CA", "SAQQARA-CA: Sistema Colaborativo de Correlacion de Alarmas", "Alarmas", "Telefonica Investigacion y Desarrollo");

-- SIDs for plugin_id 10002:
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 1000, NULL, NULL, 2, 2, "SAQQARA-CA: Unknown event", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 101, NULL, NULL, 2, 2, "SAQQARA-CA: Suspicious activity ruled out", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 102, NULL, NULL, 2, 2, "SAQQARA-CA: Suspicious WEB activity ruled out", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 105, NULL, NULL, 2, 2, "SAQQARA-CA: Virus spread attempt ruled out", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 109, NULL, NULL, 2, 2, "SAQQARA-CA: Suspicious SQL activity ruled out", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 10, NULL, NULL, 2, 2, "SAQQARA-CA: Abnormal behaviour", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 110, NULL, NULL, 2, 2, "SAQQARA-CA: Abnormal behaviour ruled out", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 113, NULL, NULL, 2, 2, "SAQQARA-CA: Network services scan ruled out", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 114, NULL, NULL, 2, 2, "SAQQARA-CA: Suspicious DNS activity ruled out", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 11, NULL, NULL, 2, 2, "SAQQARA-CA: Periodic behaviour", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 13, NULL, NULL, 2, 2, "SAQQARA-CA: Suspicious network scan", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 14, NULL, NULL, 2, 2, "SAQQARA-CA: Suspicious DNS activity", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 198, NULL, NULL, 0, 0, "SAQQARA-CA: Native alarm exists (ruled out)", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 199, NULL, NULL, 0, 0, "SAQQARA-CA: Native alarm exists", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 1, NULL, NULL, 2, 2, "SAQQARA-CA: Suspicious activity", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 201, NULL, NULL, 2, 2, "SAQQARA-CA: Successful brute force attack", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 202, NULL, NULL, 2, 2, "SAQQARA-CA: Brute force attack attempt", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 203, NULL, NULL, 2, 2, "SAQQARA-CA: WEB attack", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 204, NULL, NULL, 2, 2, "SAQQARA-CA: Security policy violated", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 205, NULL, NULL, 2, 2, "SAQQARA-CA: SQL attack", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 206, NULL, NULL, 2, 2, "SAQQARA-CA: Scan and later attack", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 207, NULL, NULL, 2, 2, "SAQQARA-CA: Several network and port scans", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 208, NULL, NULL, 2, 2, "SAQQARA-CA: Several authentication attempts", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 209, NULL, NULL, 2, 2, "SAQQARA-CA: Network scan filtered by the firewall", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 210, NULL, NULL, 2, 2, "SAQQARA-CA: Several wrong WEB requests", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 211, NULL, NULL, 2, 2, "SAQQARA-CA: WEB server scan", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 290, NULL, NULL, 2, 2, "SAQQARA-CA: Security policy violated (PROXY)", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 291, NULL, NULL, 2, 2, "SAQQARA-CA: Virus spread attempt (PROXY)", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 2, NULL, NULL, 2, 2, "SAQQARA-CA: Suspicious WEB activity", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 301, NULL, NULL, 2, 2, "SAQQARA-CA: Host Denial of Service Attack", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 302, NULL, NULL, 2, 2, "SAQQARA-CA: WEB attack", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 303, NULL, NULL, 2, 2, "SAQQARA-CA: SQL injection attack", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 304, NULL, NULL, 2, 2, "SAQQARA-CA: Possible spammer", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 305, NULL, NULL, 2, 2, "SAQQARA-CA: NETBIOS Worm", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 306, NULL, NULL, 2, 2, "SAQQARA-CA: TELNET Worm", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 307, NULL, NULL, 2, 2, "SAQQARA-CA: SQL Worm", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 308, NULL, NULL, 2, 2, "SAQQARA-CA: FTP Server attack", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 309, NULL, NULL, 2, 2, "SAQQARA-CA: Suspicious DNS queries", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 310, NULL, NULL, 2, 2, "SAQQARA-CA: Suspicious network traffic", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 311, NULL, NULL, 2, 2, "SAQQARA-CA: Brute force attack", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 312, NULL, NULL, 2, 2, "SAQQARA-CA: SQL attack", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 313, NULL, NULL, 2, 2, "SAQQARA-CA: Authentication overflow attack", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 314, NULL, NULL, 2, 2, "SAQQARA-CA: Buffer overflow attack", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 315, NULL, NULL, 2, 2, "SAQQARA-CA: Multiple errors generated by the web server", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 316, NULL, NULL, 2, 2, "SAQQARA-CA: Multiple web server errors generated", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 317, NULL, NULL, 2, 2, "SAQQARA-CA: Network scan", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 318, NULL, NULL, 2, 2, "SAQQARA-CA: Possible malware/trojan/bot", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 319, NULL, NULL, 2, 2, "SAQQARA-CA: WEB application scanning", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 320, NULL, NULL, 2, 2, "SAQQARA-CA: Periodic suspicious DNS queries", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 321, NULL, NULL, 2, 2, "SAQQARA-CA: Periodic RBN activity", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 322, NULL, NULL, 2, 2, "SAQQARA-CA: TELNET server attack", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 323, NULL, NULL, 2, 2, "SAQQARA-CA: SSH servers network scan", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 324, NULL, NULL, 2, 2, "SAQQARA-CA: Host scan denied by FW", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 325, NULL, NULL, 2, 2, "SAQQARA-CA: Network scan denied by FW", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 5, NULL, NULL, 2, 2, "SAQQARA-CA: Virus spread attempt", 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (10002, 9, NULL, NULL, 2, 2, "SAQQARA-CA: Suspicious SQL activity", 0.0, NULL);


-- SIDs for plugin_id 1505:

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100010, NULL, NULL, 2, 2, 'SAQQARA-CA: Abnormal behaviour', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100011, NULL, NULL, 2, 2, 'SAQQARA-CA: Periodic behaviour', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100012, NULL, NULL, 2, 2, 'SAQQARA-CA: DoS attack', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100013, NULL, NULL, 2, 2, 'SAQQARA-CA: Suspicious network scan', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100014, NULL, NULL, 2, 2, 'SAQQARA-CA: Suspicious DNS activity', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100001, NULL, NULL, 2, 2, 'SAQQARA-CA: Suspicious activity', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100201, NULL, NULL, 2, 2, 'SAQQARA-CA: Successful brute force attack', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100202, NULL, NULL, 2, 2, 'SAQQARA-CA: Brute force attack attempt', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100203, NULL, NULL, 2, 2, 'SAQQARA-CA: WEB attack', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100204, NULL, NULL, 2, 2, 'SAQQARA-CA: Security policy violated', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100205, NULL, NULL, 2, 2, 'SAQQARA-CA: SQL attack', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100206, NULL, NULL, 2, 2, 'SAQQARA-CA: Scan and later attack', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100207, NULL, NULL, 2, 2, 'SAQQARA-CA: Several network and port scans', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100208, NULL, NULL, 2, 2, 'SAQQARA-CA: Several authentication attempts', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100209, NULL, NULL, 2, 2, 'SAQQARA-CA: Network scan filtered by the firewall', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100210, NULL, NULL, 2, 2, 'SAQQARA-CA: Several wrong WEB requests', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100211, NULL, NULL, 2, 2, 'SAQQARA-CA: WEB server scan', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100290, NULL, NULL, 2, 2, 'SAQQARA-CA: Security policy violated (PROXY)', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100291, NULL, NULL, 2, 2, 'SAQQARA-CA: Virus spread attempt (PROXY)', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100002, NULL, NULL, 2, 2, 'SAQQARA-CA: Suspicious WEB activity', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100301, NULL, NULL, 2, 2, 'SAQQARA-CA: Host Denial of Service Attack', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100302, NULL, NULL, 2, 2, 'SAQQARA-CA: WEB attack', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100303, NULL, NULL, 2, 2, 'SAQQARA-CA: SQL injection attack', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100304, NULL, NULL, 2, 2, 'SAQQARA-CA: Possible spammer', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100305, NULL, NULL, 2, 2, 'SAQQARA-CA: NETBIOS Worm', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100306, NULL, NULL, 2, 2, 'SAQQARA-CA: TELNET Worm', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100307, NULL, NULL, 2, 2, 'SAQQARA-CA: SQL Worm', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100308, NULL, NULL, 2, 2, 'SAQQARA-CA: FTP Server attack', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100309, NULL, NULL, 2, 2, 'SAQQARA-CA: Suspicious DNS queries', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100310, NULL, NULL, 2, 2, 'SAQQARA-CA: Suspicious network traffic', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100311, NULL, NULL, 2, 2, 'SAQQARA-CA: Brute force attack', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100312, NULL, NULL, 2, 2, 'SAQQARA-CA: SQL attack', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100313, NULL, NULL, 2, 2, 'SAQQARA-CA: Authentication overflow attack', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100314, NULL, NULL, 2, 2, 'SAQQARA-CA: Buffer overflow attack', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100315, NULL, NULL, 2, 2, 'SAQQARA-CA: Multiple errors generated by the web server', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100316, NULL, NULL, 2, 2, 'SAQQARA-CA: Multiple web server errors generated', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100317, NULL, NULL, 2, 2, 'SAQQARA-CA: Network scan', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100318, NULL, NULL, 2, 2, 'SAQQARA-CA: Possible malware/trojan/bot', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100319, NULL, NULL, 2, 2, 'SAQQARA-CA: WEB application scanning', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100320, NULL, NULL, 2, 2, 'SAQQARA-CA: Periodic suspicious DNS queries', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100321, NULL, NULL, 2, 2, 'SAQQARA-CA: Periodic RBN activity', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100322, NULL, NULL, 2, 2, 'SAQQARA-CA: TELNET server attack', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100323, NULL, NULL, 2, 2, 'SAQQARA-CA: SSH servers network scan', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100324, NULL, NULL, 2, 2, 'SAQQARA-CA: Host scan denied by FW', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100325, NULL, NULL, 2, 2, 'SAQQARA-CA: Network scan denied by FW', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100003, NULL, NULL, 2, 2, 'SAQQARA-CA: Attack against the server', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100005, NULL, NULL, 2, 2, 'SAQQARA-CA: Virus spread attempt', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100006, NULL, NULL, 2, 2, 'SAQQARA-CA: Corporative policy violation', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100007, NULL, NULL, 2, 2, 'SAQQARA-CA: Port scan', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100008, NULL, NULL, 2, 2, 'SAQQARA-CA: Likely intrussion attempt', 0.0, NULL);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro, subcategory_id) VALUES (1505, 100009, NULL, NULL, 2, 2, 'SAQQARA-CA: Suspicious SQL activity', 0.0, NULL);

