#!/usr/bin/perl
$|=1;
use Time::Local;

use sigtrap 'handler' => \&cleanAndExit, 'HUP', 'INT', 'QUIT', 'ILL', 'TRAP', 'ABRT', 'KILL', 'TERM', 'STOP';
sub cleanAndExit(){
    exit(1);
}

if(!$ARGV[3]){
	print "Expecting: start end user IP_list\n";
	print "Don't forget to escape the strings\n";
	exit;
}

$user = $ARGV[0];
$start = $ARGV[1];
$end = $ARGV[2];
$ips = $ARGV[3];
$allowed_sensors = $ARGV[4];
$lastupdate = $ARGV[5];

if ($start !~ /^\d+\-\d+\-\d+\s+\d+\:\d+\:\d+$/) {
	print "Parameters error in start date\n";
	exit;
}
if ($end !~ /^\d+\-\d+\-\d+\s+\d+\:\d+\:\d+$/) {
	print "Parameters error in end date\n";
	exit;
}
if ($user !~ /^[a-zA-Z0-9_\-\.\/]+$/) {
	print "Parameters error in user\n";
	exit;
}
if ($ips !~ /^(\d+\.\d+\.\d+\.\d+\,?)+$/) {
	print "Parameters error\n";
	exit;
}

my $cert = (-e "/etc/ossim/remotelogger") ? "-i /etc/ossim/remotelogger" : "";
my @ips_arr = split(/\,/,$ips);
foreach $ip (@ips_arr) {
	if ($ip eq "127.0.0.1") {
		$cmd = "perl wcl.pl '$user' '$start' '$end' '$allowed_sensors' $lastupdate";
	} else {
		$cmd = "ssh -o \"StrictHostKeyChecking=no\" $cert $ip \"cd /usr/share/ossim/www/sem;perl wcl.pl '$user' '$start' '$end' '$allowed_sensors' $lastupdate\"";
	}
	system($cmd);
}