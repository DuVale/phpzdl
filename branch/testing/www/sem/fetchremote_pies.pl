#!/usr/bin/perl
$|=1;
use Time::Local;

use sigtrap 'handler' => \&cleanAndExit, 'HUP', 'INT', 'QUIT', 'ILL', 'TRAP', 'ABRT', 'KILL', 'TERM', 'STOP';
sub cleanAndExit(){
    exit(1);
}

if(!$ARGV[10]){
	print "Expecting: start end uniqueid user ip_list\n";
	print "Don't forget to escape the strings\n";
	exit;
}

$start = $ARGV[0];
$end = $ARGV[1];
$allowed_sensors = $ARGV[8];
$uniqueid = $ARGV[9];
$user = $ARGV[10];
$ips = $ARGV[11];

if ($ips !~ /^(\d+\.\d+\.\d+\.\d+\,?)+$/) {
	print "Parameters error in ips $uniqueid\n";
	exit;
}
if ($start !~ /^\d+\-\d+\-\d+\s+\d+\:\d+\:\d+$/) {
	print "Parameters error in start\n";
	exit;
}
if ($end !~ /^\d+\-\d+\-\d+\s+\d+\:\d+\:\d+$/) {
	print "Parameters error in end\n";
	exit;
}
if ($uniqueid !~ /^[a-z0-9]+\.[0-9]+$/) {
	print "Parameters error in UniqueID\n";
	exit;
}
if ($user !~ /^[a-zA-Z0-9_\-\.]+$/) {
	print "Parameters error in user\n";
	exit;
}

my $cert = (-e "/etc/ossim/remotelogger") ? "-i /etc/ossim/remotelogger" : "";
my @ips_arr = split(/\,/,$ips);
print "{";
$flag = 0;
foreach $ip (@ips_arr) {
	if ($ip == "127.0.0.1") {
		$cmd = "php pies.php '$start' '$end' '$uniqueid' '$user'";
	} else {
		$cmd = "ssh -o \"StrictHostKeyChecking=no\" $cert $ip \"cd /usr/share/ossim/www/sem;php pies.php '$start' '$end' '$uniqueid' '$user' '$allowed_sensors'\"";
	}
	print "," if ($flag);
	print '"'.$ip.'":';
	system($cmd);
	$flag = 1;
}
print "}\n";
