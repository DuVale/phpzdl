#!/usr/bin/perl
$|=1;
use Time::Local;

use sigtrap 'handler' => \&cleanAndExit, 'HUP', 'INT', 'QUIT', 'ILL', 'TRAP', 'ABRT', 'KILL', 'TERM', 'STOP';
sub cleanAndExit(){
    exit(1);
}

if(!$ARGV[3]){
	print "Expecting: gt cat ip_list\n";
	print "Don't forget to escape the strings\n";
	exit;
}

$gt = $ARGV[0];
$cat = $ARGV[1];
$allowed_sensors = $ARGV[2];
$ips = $ARGV[3];
$tz = $ARGV[4];

if ($ips !~ /^(\d+\.\d+\.\d+\.\d+\,?)+$/) {
	print "Parameters error\n";
	exit;
}
if ($gt !~ /^[a-z]+$/ && $gt !~ /^[a-z]+\_[a-z]+$/) {
	print "Parameters error\n";
	exit;
}
if ($cat !~ /^[a-zA-Z]+\%2C\+\d\d\d\d$/ && $cat !~ /^[a-zA-Z]+\s+\d+\,\s+\d\d\d\d$/ && $cat !~ /^[a-zA-Z]+\,\s\d\d\d\d/ && $cat !~ /^\d\d\d\d$/ && $cat !~/[-+]?\d+(\.\d+)?/ && $cat ne "") {
	print "Parameters error\n";
	exit;
}
if ($tz ne "" && $tz !~ /^[\d\-]+$/) {
	print "Parameters error\n";
	exit;
}

my $cert = (-e "/etc/ossim/remotelogger") ? "-i /etc/ossim/remotelogger" : "";
my @ips_arr = split(/\,/,$ips);
print "{";
$flag = 0;
foreach $ip (@ips_arr) {
	if ($gt eq "panel") {
		$cmd = "ssh -o \"StrictHostKeyChecking=no\" $cert $ip \"cd /usr/share/ossim/www/sem;php remote_panel_graph.php '$cat' '$allowed_sensors'\"";
	} else {
		if ($ip == "127.0.0.1") {
			$cmd = "php forensic_source.php '$gt' '$cat' '$allowed_sensors' '$tz'";
		} else {
			$cmd = "ssh -o \"StrictHostKeyChecking=no\" $cert $ip \"cd /usr/share/ossim/www/sem;php forensic_source.php '$gt' '$cat' '$allowed_sensors' '$tz'\"";
		}
		print "," if ($flag);
		print '"'.$ip.'":';
		$flag = 1;
	}
	system($cmd);
}
print "}\n";
