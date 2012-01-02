#!/usr/bin/perl
# Purpose : Pull event ID list with brute force directly from THE MSDN ABYSS(TM)
#           And generates the SQL statements we need.
#           This is for MS SQL Server 2008 R2.
# If there's any problem, please contact: nelsonh@infinet.com.tw

use 5.010;
use strict;
use warnings;
use LWP::Simple;

my $plugin_id = 1654;

print <<HEADER;
-- MSSQLServer
-- plugin_id: $plugin_id;
DELETE FROM plugin WHERE id = "$plugin_id";
DELETE FROM plugin_sid where plugin_id = "$plugin_id";

INSERT INTO plugin (id, type, name, description) VALUES ($plugin_id, 1, 'snare-mssql', 'MS SQL Server');

HEADER

my $base_url = 'http://msdn.microsoft.com/en-us/library/cc645603.aspx';
(my $main_page) = get($base_url) =~ /title="System Error Messages"(.+?)alt="Separator"/s;

foreach my $url ($main_page =~ /<a href="(.+?)"/g) {
    (my $content) = get($url) =~ /<table>(.+?)<\/table>/;
    foreach my $row ($content =~ /<tr>(.+?)<\/tr>/g) {
        my @cols = ($row =~ /<p>(.+?)<\/p>/g)[0, 2, 3];
        map { s/<.+?>//g } @cols;
        $cols[2] =~ s/'/\\'/g;
        if ($cols[1] eq 'Yes') {
            print 'INSERT IGNORE INTO plugin_sid(plugin_id, sid, category_id, class_id, priority, reliability, name) ';
            say "VALUES ($plugin_id, $cols[0], NULL, NULL, 1, 5, 'MSSQLServer: $cols[2]');";
        }
    }
}
