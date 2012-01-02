#!/usr/bin/perl
# Purpose : Pull event ID list with brute force directly from Microsoft TechNet
#           And generates the SQL statements we need.
# If there's any problem, please contact: nelsonh@infinet.com.tw

use 5.010;
use strict;
use warnings;
use LWP::Simple;

my $plugin_id = 1655;

print <<HEADER;
-- snare-msssis
-- plugin_id: $plugin_id;
DELETE FROM plugin WHERE id = "$plugin_id";
DELETE FROM plugin_sid where plugin_id = "$plugin_id";

INSERT INTO plugin (id, type, name, description) VALUES ($plugin_id, 1, 'snare-msssis', 'MS SQL Server Integration Services');

HEADER

sub get_event_id {
    return unpack("N", pack("B32", substr('0' x 32 . substr(sprintf("%b", shift), -16), -32)));
}

my @sections = get('http://technet.microsoft.com/en-us/library/ms345164.aspx')
  =~ /MTPS_CollapsibleSection.+?tableSection(.+?)<\/table>/sg;

foreach my $table (@sections) {
    foreach my $row ($table =~ /<tr>(.+?)<\/tr>/sg) {
        my @cols = ($row =~ /<td>(.+?)<\/td>/sg)[0, 3];

        if ($cols[0] && $cols[1]) {
            map { s/<.+?>//g } @cols;
            $cols[0] = get_event_id(hex $cols[0]);
            $cols[1] =~ s/'/\\'/g;
            print 'INSERT IGNORE INTO plugin_sid(plugin_id, sid, category_id, class_id, priority, reliability, name) ';
            say "VALUES ($plugin_id, $cols[0], NULL, NULL, 1, 5, 'MS SSIS: $cols[1]');";
        }
    }
}
