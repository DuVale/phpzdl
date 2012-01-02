-- vyatta
-- plugin_id: 1610
-- vyatta.cfg, v 0.14 2011/02/22 hnoguera@openredes.com - http://www.openredes.com

-- DELETE FROM plugin WHERE id = "1610";
-- DELETE FROM plugin_sid where plugin_id = "1610";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1610, 1, 'vyatta', 'Vyatta events');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 10, NULL, NULL, 1, 1, "vyatta: firewall: Accept");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 20, NULL, NULL, 1, 1, "vyatta: firewall: Drop");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 30, NULL, NULL, 1, 1, "vyatta: firewall: Reject");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 40, NULL, NULL, 1, 1, "vyatta: openvpn: sts connection ok");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 50, NULL, NULL, 1, 1, "vyatta: openvpn: ra connection ok");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 60, NULL, NULL, 1, 1, "vyatta: openvpn: TLS key expired");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 70, NULL, NULL, 1, 1, "vyatta: openvpn: Inactivity timeout (--ping-restart)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 80, NULL, NULL, 1, 1, "vyatta: openvpn: [EHOSTUNREACH]: No route to host");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 90, NULL, NULL, 1, 1, "vyatta: openvpn: RA client disconnected");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 100, NULL, NULL, 1, 1, "vyatta: ospfd: Adjacency change (HelloReceived)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 110, NULL, NULL, 1, 1, "vyatta: ospfd: Adjacency change (Start)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 120, NULL, NULL, 1, 1, "vyatta: ospfd: Adjacency change (2-WayReceived)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 130, NULL, NULL, 1, 1, "vyatta: ospfd: Adjacency change (1-WayReceived)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 140, NULL, NULL, 1, 1, "vyatta: ospfd: Adjacency change (NegotiationDone)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 150, NULL, NULL, 1, 1, "vyatta: ospfd: Adjacency change (ExchangeDone)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 160, NULL, NULL, 1, 1, "vyatta: ospfd: Adjacency change (BadLSReq)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 170, NULL, NULL, 1, 1, "vyatta: ospfd: Adjacency change (Loading Done)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 180, NULL, NULL, 1, 1, "vyatta: ospfd: Adjacency change (AdjOK?)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 190, NULL, NULL, 1, 1, "vyatta: ospfd: Adjacency change (SeqNumberMismatch)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 200, NULL, NULL, 1, 1, "vyatta: ospfd: Adjacency change (1-Way)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 210, NULL, NULL, 1, 1, "vyatta: ospfd: Adjacency change (KillNbr)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 220, NULL, NULL, 1, 1, "vyatta: ospfd: Adjacency change (InactivityTimer)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 230, NULL, NULL, 1, 1, "vyatta: ospfd: Adjacency change (LLDown)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 240, NULL, NULL, 1, 1, "vyatta: ospfd: NSM change (now Deleted)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 250, NULL, NULL, 1, 1, "vyatta: ospfd: NSM change (now Init)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 260, NULL, NULL, 1, 1, "vyatta: ospfd: NSM change (now ExStart)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 270, NULL, NULL, 1, 1, "vyatta: ospfd: NSM change (now 2-Way)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 280, NULL, NULL, 1, 1, "vyatta: ospfd: NSM change (now Exchange)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 290, NULL, NULL, 1, 1, "vyatta: ospfd: NSM change (now Loading)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 300, NULL, NULL, 1, 1, "vyatta: ospfd: NSM change (now Full)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 310, NULL, NULL, 1, 1, "vyatta: zebra: interface changes");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 320, NULL, NULL, 1, 1, "vyatta: zebra: interface deleted");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 330, NULL, NULL, 1, 1, "vyatta: zebra: interface added");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 340, NULL, NULL, 1, 1, "vyatta: system: new config loaded");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 350, NULL, NULL, 1, 1, "vyatta: system: shutdown system");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 360, NULL, NULL, 1, 1, "vyatta: wan_lb: interface state change (now FAILED)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 370, NULL, NULL, 1, 1, "vyatta: wan_lb: interface state change (now ACTIVE)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 380, NULL, NULL, 1, 1, "vyatta: pmacctd: memory resources warning");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 390, NULL, NULL, 1, 1, "vyatta: pam_unix: auth failure");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 400, NULL, NULL, 1, 1, "vyatta: pam_unix: more than # auth failures");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 410, NULL, NULL, 1, 1, "vyatta: pam_unix: max retries");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 420, NULL, NULL, 1, 1, "vyatta: pam_unix: unknown user");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 430, NULL, NULL, 1, 1, "vyatta: pam_unix: auth failure");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1610, 440, NULL, NULL, 1, 1, "vyatta: pam_unix: too many login tries");
