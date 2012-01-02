#!/usr/bin/perl
$|=1;

use sigtrap 'handler' => \&cleanAndExit, 'HUP', 'INT', 'QUIT', 'ILL', 'TRAP', 'ABRT', 'KILL', 'TERM', 'STOP';
sub cleanAndExit(){
    exit(1);
}

if(!$ARGV[4]){
	print "Expecting: logger_source script_name date_from date_to\n";
	print "Don't forget to escape the strings\n";
	exit;
}
$logger_source = $ARGV[0];
$script_name = $ARGV[1];
$NUM_HOSTS = $ARGV[2];
$date_from = $ARGV[3];
$date_to = $ARGV[4];
$range = $ARGV[5];
$filters = $ARGV[6];

if ($logger_source !~ /^(\d+\.\d+\.\d+\.\d+)+$/) {
	print "Parameters error\n";
	exit;
}
if ($script_name ne "AttackedHosts" && $script_name ne "AttackerHosts" && $script_name ne "UsedPorts" && $script_name ne "CollectionSources" && $script_name ne "EventsTrend") {
	print "Parameters error\n";
	exit;
}
if ($NUM_HOSTS !~ /^\d+$/) {
	print "Parameters error\n";
	exit;
}
if ($date_from !~ /[\d\-]+/) {
	print "Parameters error\n";
	exit;
}
if ($date_to !~ /[\d\-]+/) {
	print "Parameters error\n";
	exit;
}
if ($range ne "" && $range !~ /[a-zA-Z]+/) {
	print "Parameters error\n";
	exit;
}
if ($filters ne "" && $filters !~ /^[a-zA-Z0-9\=]$+/) {
	print "Parameters error\n";
	exit;
}

my $cert = (-e "/etc/ossim/remotelogger") ? "-i /etc/ossim/remotelogger" : "";
$cmd = "ssh -o \"StrictHostKeyChecking=no\" $cert $logger_source \"cd /usr/share/ossim/www/report/Logger;php $script_name.php '$NUM_HOSTS' '$date_from' '$date_to' $range $filters\"";
system($cmd);
