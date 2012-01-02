-- squid
-- plugin_id: 1553
--
-- Plugin sids from apache plugin

-- DELETE FROM plugin WHERE id = "1553";
-- DELETE FROM plugin_sid where plugin_id = "1553";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1553, 1, 'squid', 'Squid');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 0, NULL, NULL, 'squid: Undefined');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 200, NULL, NULL, 'squid: OK');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 201, NULL, NULL, 'squid: Created');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 202, NULL, NULL, 'squid: Accepted');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 203, NULL, NULL, 'squid: Non-Authorative Information');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 204, NULL, NULL, 'squid: No Content');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 205, NULL, NULL, 'squid: Reset Content');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 206, NULL, NULL, 'squid: Partial Content');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 207, NULL, NULL, 'squid: Multi Status');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 299, NULL, NULL, 'squid: Unknown Status code 299');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 300, NULL, NULL, 'squid: Multiple Choices');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 301, NULL, NULL, 'squid: Moved Permanently');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 302, NULL, NULL, 'squid: Moved Temporarily');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 303, NULL, NULL, 'squid: See Other');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 304, NULL, NULL, 'squid: Not Modified');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 305, NULL, NULL, 'squid: Use Proxy');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 307, NULL, NULL, 'squid: Temporary Redirect');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 400, NULL, NULL, 'squid: Bad Request');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 401, NULL, NULL, 'squid: Authorization Required');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 402, NULL, NULL, 'squid: Payment Required');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 403, NULL, NULL, 'squid: Forbidden');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 404, NULL, NULL, 'squid: Not Found');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 405, NULL, NULL, 'squid: Method Not Allowed');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 406, NULL, NULL, 'squid: Not Acceptable (encoding)');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 407, NULL, NULL, 'squid: Proxy Authentication Required');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 408, NULL, NULL, 'squid: Request Timed Out');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 409, NULL, NULL, 'squid: Conflicting Request');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 410, NULL, NULL, 'squid: Gone');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 411, NULL, NULL, 'squid: Content Length Required');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 412, NULL, NULL, 'squid: Precondition Failed');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 413, NULL, NULL, 'squid: Request Entity Too Long');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 414, NULL, NULL, 'squid: Request URI Too Long');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 415, NULL, NULL, 'squid: Unsupported Media Type');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 416, NULL, NULL, 'squid: Request Range Not Satisfiable');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 426, NULL, NULL, 'squid: Upgrade Required');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 499, NULL, NULL, 'squid: Unknown Status code 499');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 500, NULL, NULL, 'squid: Internal Server Error');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 501, NULL, NULL, 'squid: Not implemented');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 502, NULL, NULL, 'squid: Bad Gateway');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 503, NULL, NULL, 'squid: Service Unavailable');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 504, NULL, NULL, 'squid: Gateway Timeout');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 505, NULL, NULL, 'squid: HTTP Version Not Supported');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 999, NULL, NULL, 'squid: Unknown Status code 999');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 1200, NULL, NULL, 'squid: cgi-tunnel');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 1201, NULL, NULL, 'squid: possible-tunnel');
