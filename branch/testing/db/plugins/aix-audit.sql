--
-- aix-audit
--
-- description: IBM AIX Audit Logs
-- type: detector
-- plugin_id: 1649
--
-- **Logs fetched using Snare Epilog**
-- 

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1649, 1, 'aix-audit',
'IBM AIX Audit Logs');

-- USER 
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability,
priority, name) VALUES (1649, 1, NULL, NULL, 1, 1, 'AIX Audit: USER_Login');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 2, NULL, NULL, 1, 1, 'AIX Audit: USER_Logout');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 3, NULL, NULL, 1, 1, 'AIX Audit: USER_Exit');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 4, NULL, NULL, 1, 1, 'AIX Audit: USER_Shell');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 5, NULL, NULL, 1, 1, 'AIX Audit: USER_SU');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 6, NULL, NULL, 1, 1, 'AIX Audit: USER_Check');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 7, NULL, NULL, 1, 1, 'AIX Audit: USER_Change');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 8, NULL, NULL, 1, 1, 'AIX Audit: USER_Remove');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 9, NULL, NULL, 1, 1, 'AIX Audit: USER_Create');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 10, NULL, NULL, 1, 1, 'AIX Audit: USER_Exit');

-- TERM 
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 11, NULL, NULL, 1, 1, 'AIX Audit: TERM_Logout');

-- PASSWORD
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 12, NULL, NULL, 1, 1, 'AIX Audit: PASSWORD_Flags');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 13, NULL, NULL, 1, 1, 'AIX Audit: PASSWORD_Check');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 14, NULL, NULL, 1, 1, 'AIX Audit: PASSWORD_Ckerr');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 15, NULL, NULL, 1, 1, 'AIX Audit: PASSWORD_Change');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 16, NULL, NULL, 1, 1, 'AIX Audit: PASSWD_WRITE');

-- GROUP
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 17, NULL, NULL, 1, 1, 'AIX Audit: GROUP_User');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 18, NULL, NULL, 1, 1, 'AIX Audit: GROUP_Adms');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 19, NULL, NULL, 1, 1, 'AIX Audit: GROUP_Change');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 20, NULL, NULL, 1, 1, 'AIX Audit: GROUP_Create');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 21, NULL, NULL, 1, 1, 'AIX Audit: GROUP_Remove');

-- SYSCK
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 22, NULL, NULL, 1, 1, 'AIX Audit: SYSCK_Check');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 23, NULL, NULL, 1, 1, 'AIX Audit: SYSCK_Update');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 24, NULL, NULL, 1, 1, 'AIX Audit: SYSCK_Install');

-- S_
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 25, NULL, NULL, 1, 1, 'AIX Audit: S_ENVIRON_WRITE');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 26, NULL, NULL, 1, 1, 'AIX Audit: S_GROUP_WRITE');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 27, NULL, NULL, 1, 1, 'AIX Audit: S_LIMITS_WRITE');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 28, NULL, NULL, 1, 1, 'AIX Audit: S_LOGIN_WRITE');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 29, NULL, NULL, 1, 1, 'AIX Audit: S_PASSWD_READ');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 30, NULL, NULL, 1, 1, 'AIX Audit: S_PASSWD_WRITE');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 31, NULL, NULL, 1, 1, 'AIX Audit: S_USER_WRITE');

-- TCPIP
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 32, NULL, NULL, 1, 1, 'AIX Audit: TCPIP_connect');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 33, NULL, NULL, 1, 1, 'AIX Audit: TCPIP_data_out');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 34, NULL, NULL, 1, 1, 'AIX Audit: TCPIP_data_in');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 35, NULL, NULL, 1, 1, 'AIX Audit: TCPIP_access');

-- GIP
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 36, NULL, NULL, 1, 1, 'AIX Audit: GIP_W_BDE');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 37, NULL, NULL, 1, 1, 'AIX Audit: GIP_R_BDE');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 38, NULL, NULL, 1, 1, 'AIX Audit: GIP_X_BDE');

-- TAM
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 39, NULL, NULL, 1, 1, 'AIX Audit: TAM_W_BDE');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 40, NULL, NULL, 1, 1, 'AIX Audit: TAM_R_BDE');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 41, NULL, NULL, 1, 1, 'AIX Audit: TAM_X_BDE');

-- File 
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 42, NULL, NULL, 1, 1, 'AIX Audit: FILE_Owner');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 43, NULL, NULL, 1, 1, 'AIX Audit: FILE_Mode');

-- OTHER
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 44, NULL, NULL, 1, 1, 'AIX Audit: AUD_CONFIG_WR');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 45, NULL, NULL, 1, 1, 'AIX Audit: SSH_X_BDE');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 46, NULL, NULL, 1, 1, 'AIX Audit: SCM_R_BDE');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1649, 47, NULL, NULL, 1, 1, 'AIX Audit: KRB_X_BDE');





