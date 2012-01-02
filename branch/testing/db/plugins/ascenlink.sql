-- Ascenlink
-- Xtera's WAN Load Balancer
-- plugin_id: 1660 & 1661

-- TODO: s/1660/<real-plugin-id>

-- DELETE FROM plugin WHERE id="1660" OR id="1661";
-- DELETE FROM plugin_sid WHERE plugin_id="1660" OR plugin_id="1661";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES 
    (1660, 1, 'Ascenlink-network', 'Ascenlink WAN Balancer -- Network');
INSERT IGNORE INTO plugin (id, type, name, description) VALUES 
    (1661, 1, 'Ascenlink-system', 'Ascenlink WAN Balancer -- System');

INSERT IGNORE INTO `plugin_sid`
    (`plugin_id`, `sid`, `priority`, `reliability`, `name`) VALUES

    (1660, 0, 1, 1, 'Ascenlink: ICMP echo reply'),
    (1660, 3, 1, 1, 'Ascenlink: ICMP dest unreachable'),
    (1660, 4, 1, 1, 'Ascenlink: ICMP source quench'),
    (1660, 5, 1, 1, 'Ascenlink: ICMP redirect'),
    (1660, 8, 1, 1, 'Ascenlink: ICMP echo'),
    (1660, 9, 1, 1, 'Ascenlink: ICMP advertisement'),
    (1660, 10, 1, 1, 'Ascenlink: ICMP solicitation'),
    (1660, 11, 1, 1, 'Ascenlink: ICMP time exceeded'),
    (1660, 12, 1, 1, 'Ascenlink: ICMP ip header bad'),
    (1660, 13, 1, 1, 'Ascenlink: ICMP timestamp request'),
    (1660, 14, 1, 1, 'Ascenlink: ICMP timestamp reply'),
    (1660, 15, 1, 1, 'Ascenlink: ICMP information request'),
    (1660, 16, 1, 1, 'Ascenlink: ICMP information reply'),
    (1660, 17, 1, 1, 'Ascenlink: ICMP address mask request'),
    (1660, 18, 1, 1, 'Ascenlink: ICMP address mask reply'),

    (1660, 20, 1, 1, 'Ascenlink: ICMP protocol'),
    (1660, 21, 1, 1, 'Ascenlink: GRE (Generic Route Encapsulation) protocol'),

    (1660, 25, 1, 1, 'Ascenlink: UDP protocol'),
    (1660, 30, 1, 1, 'Ascenlink: TCP protocol'),

    (1660, 41, 1, 1, 'Ascenlink: DNS protocol'),
    (1660, 42, 1, 1, 'Ascenlink: FTP protocol'),
    (1660, 43, 1, 1, 'Ascenlink: H323 protocol'),
    (1660, 44, 1, 1, 'Ascenlink: HTTP protocol'),
    (1660, 45, 1, 1, 'Ascenlink: HTTPS protocol'),
    (1660, 46, 1, 1, 'Ascenlink: IMAP protocol'),
    (1660, 47, 1, 1, 'Ascenlink: NTP protocol'),
    (1660, 48, 1, 1, 'Ascenlink: POP3 protocol'),
    (1660, 49, 1, 1, 'Ascenlink: RDP protocol'),
    (1660, 50, 1, 1, 'Ascenlink: SMTP protocol'),
    (1660, 51, 1, 1, 'Ascenlink: SNMP protocol'),
    (1660, 52, 1, 1, 'Ascenlink: SSH protocol'),
    (1660, 53, 1, 1, 'Ascenlink: TELNET protocol'),

    (1660, 80, 1, 1, 'Ascenlink: pcAnywhere-D protocol'),
    (1660, 81, 1, 1, 'Ascenlink: pcAnywhere-S protocol'),

    (1661, 101, 1, 1, 'Ascenlink: User logged in'),
    (1661, 102, 1, 1, 'Ascenlink: User logged out'),
    (1661, 103, 1, 1, 'Ascenlink: User password'),
    (1661, 104, 1, 1, 'Ascenlink: Failed to browse'),
    (1661, 105, 1, 1, 'Ascenlink: Failed to push'),
    (1661, 106, 5, 8, 'Ascenlink: WLAN link failure'),
    (1661, 107, 1, 1, 'Ascenlink: WLAN link recovery'),
    (1661, 108, 1, 1, 'Ascenlink: Settings applied for page'),
    (1661, 109, 1, 1, 'Ascenlink: System reboot'),
    (1661, 110, 1, 1, 'Ascenlink: Pushing log is finished');


