-- Aruba
-- plugin_id: 1623


-- DELETE FROM plugin WHERE id = "1623";
-- DELETE FROM plugin_sid where plugin_id = "1623";

INSERT IGNORE INTO `plugin` (`id` , `type` , `name` , `description` , `source_type` , `vendor`) VALUES (
'1623', '1', 'Aruba', 'Aruba Wireless', 'Wireless Security/Management', 'Aruba Networks'
);

INSERT IGNORE INTO `plugin_sid` (`plugin_id`, `sid`, `reliability`, `priority`, `name`) VALUES
(1623, 1, 1, 1, 'Aruba: stm - Probe Request'),
(1623, 2, 1, 1, 'Aruba: stm - Auth request'),
(1623, 3, 1, 1, 'Aruba: stm - Auth success'),
(1623, 4, 1, 1, 'Aruba: stm - Disassoc from sta'),
(1623, 5, 1, 1, 'Aruba: stm - Deauth from sta'),
(1623, 6, 1, 1, 'Aruba: authmgr - Station Down'),
(1623, 7, 1, 1, 'Aruba: authmgr - Station Up'),
(1623, 8, 1, 1, 'Aruba: sapd - Radio Stats'),
(1623, 9, 1, 1, 'Aruba: stm - Deauth to sta'),
(1623, 10, 1, 1, 'Aruba: stm - Assoc request'),
(1623, 11, 1, 1, 'Aruba: stm - Assoc success'),
(1623, 12, 1, 1, 'Aruba: stm - Assoc failure'),
(1623, 13, 1, 1, 'Aruba: stm - Auth failure'),
(1623, 14, 1, 1, 'Aruba: stm - Source'),
(1623, 15, 1, 1, 'Aruba: sapd - calling remove_pot_sta'),
(1623, 16, 1, 1, 'Aruba: sapd - calling remove_ap'),
(1623, 17, 1, 1, 'Aruba: sapd - calling remove_pot_ap'),
(1623, 18, 1, 1, 'Aruba: sapd - set_mode called for MAC address'),
(1623, 19, 1, 1, 'Aruba: sapd - called for MAC address'),
(1623, 20, 1, 1, 'Aruba: sapd - Adding new Gateway MAC'),
(1623, 21, 1, 1, 'Aruba: sapd - unable to find AP'),
(1623, 22, 1, 1, 'Aruba: mobileip - Received disassociation'),
(1623, 23, 1, 1, 'Aruba: mobileip - Received association'),
(1623, 26, 1, 1, 'Aruba: authmgr - Authentication Successful'),
(1623, 27, 1, 1, 'Aruba: authmgr - Authentication failed'),
(1623, 28, 1, 1, 'Aruba: authmgr - AAA server timeout'),
(1623, 29, 1, 1, 'Aruba: authmgr - Station authenticated'),
(1623, 30, 1, 1, 'Aruba: authmgr - Station deauthenticated'),
(1623, 31, 1, 1, 'Aruba: authmgr - User entry added'),
(1623, 32, 1, 1, 'Aruba: authmgr - User entry deleted'),
(1623, 33, 1, 1, 'Aruba: authmgr - User miss'),
(1623, 34, 1, 1, 'Aruba: stm - Station no authentication/association'),
(1623, 35, 1, 1, 'Aruba: stm - CDR information'),
(1623, 36, 1, 1, 'Aruba: stm - VoIP'),
(1623, 37, 1, 1, 'Aruba: sapd - ADHOC network detected'),
(1623, 38, 1, 1, 'Aruba: sapd - RSTA Type set'),
(1623, 39, 1, 1, 'Aruba: sapd - Wireless bridge detected'),
(1623, 40, 1, 1, 'Aruba: sapd - Intolerance setting detected'),
(1623, 41, 1, 2, 'Aruba: sapd - Disconnect-Station Attack'),
(1623, 42, 1, 2, 'Aruba: sapd - Signature Match detected'),
(1623, 43, 1, 1, 'Aruba: mobileip - Station mobility trail'),
(1623, 44, 1, 1, 'Aruba: authmgr - Selected server'),
(1623, 45, 1, 1, 'Aruba: authmgr - RADIUS reject'),
(1623, 46, 1, 1, 'Aruba: authmgr - User authenticated'),
(1623, 47, 1, 1, 'Aruba: authmgr - User de-authenticated'),
(1623, 48, 1, 1, 'Aruba: sapd - Interfering AP detected'),
(1623, 49, 1, 1, 'Aruba: authmgr - Deny'),
(1623, 50, 1, 1, 'Aruba: authmgr - Permit'),
(1623, 51, 1, 1, 'Aruba: fpcli - User logged in'),
(1623, 52, 1, 1, 'Aruba: fpcli - User logged out'),
(1623, 53, 1, 1, 'Aruba: fpcli - User command'),
(1623, 54, 1, 1, 'Aruba: stm - Blacklist add');
