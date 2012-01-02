-- apache
-- plugin_id: 1501
--
-- $Log: apache.sql,v $
-- Revision 1.4  2009/03/20 12:31:54  dvgil
-- new sid for apache server errors
--
-- Revision 1.3  2007/03/26 18:36:15  juanmals
-- delete previous sids before inserting new ones
--
-- Revision 1.2  2006/10/26 11:28:08  dvgil
-- added status code 307 (Temporary Redirect)
--
-- Revision 1.1  2006/10/26 11:24:38  dvgil
-- first apache plugin commit
--

-- DELETE FROM plugin WHERE id = "1501";
-- DELETE FROM plugin_sid where plugin_id = "1501";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1501, 1, 'apache', 'Apache');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 1, NULL, NULL, 'apache: server error [emerg]', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 2, NULL, NULL, 'apache: server error [alert]', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 3, NULL, NULL, 'apache: server error [crit]', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 4, NULL, NULL, 'apache: server error [error]', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 5, NULL, NULL, 'apache: server error [warn]', 2, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 6, NULL, NULL, 'apache: server error [notice]', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 7, NULL, NULL, 'apache: server error [info]', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 8, NULL, NULL, 'apache: server error [debug]', 0, 0);

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 200, NULL, NULL, 'apache: OK', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 201, NULL, NULL, 'apache: Created');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 202, NULL, NULL, 'apache: Accepted');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 203, NULL, NULL, 'apache: Non-Authorative Information');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 204, NULL, NULL, 'apache: No Content');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 205, NULL, NULL, 'apache: Reset Content');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 206, NULL, NULL, 'apache: Partial Content', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 300, NULL, NULL, 'apache: Multiple Choices');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 301, NULL, NULL, 'apache: Moved Permanently');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 302, NULL, NULL, 'apache: Moved Temporarily');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 303, NULL, NULL, 'apache: See Other');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 304, NULL, NULL, 'apache: Not Modified', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 305, NULL, NULL, 'apache: Use Proxy');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 307, NULL, NULL, 'apache: Temporary Redirect');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 400, NULL, NULL, 'apache: Bad Request');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 401, NULL, NULL, 'apache: Authorization Required', 3, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 402, NULL, NULL, 'apache: Payment Required');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 403, NULL, NULL, 'apache: Forbidden', 3, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 404, NULL, NULL, 'apache: Not Found');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 405, NULL, NULL, 'apache: Method Not Allowed', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 406, NULL, NULL, 'apache: Not Acceptable (encoding)');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 407, NULL, NULL, 'apache: Proxy Authentication Required', 3, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 408, NULL, NULL, 'apache: Request Timed Out');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 409, NULL, NULL, 'apache: Conflicting Request');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 410, NULL, NULL, 'apache: Gone');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 411, NULL, NULL, 'apache: Content Length Required');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 412, NULL, NULL, 'apache: Precondition Failed');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 413, NULL, NULL, 'apache: Request Entity Too Long', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 414, NULL, NULL, 'apache: Request URI Too Long', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 415, NULL, NULL, 'apache: Unsupported Media Type');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 500, NULL, NULL, 'apache: Internal Server Error', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 501, NULL, NULL, 'apache: Not implemented');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 502, NULL, NULL, 'apache: Bad Gateway');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 503, NULL, NULL, 'apache: Service Unavailable');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 504, NULL, NULL, 'apache: Gateway Timeout');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 505, NULL, NULL, 'apache: HTTP Version Not Supported', 2, 2);


