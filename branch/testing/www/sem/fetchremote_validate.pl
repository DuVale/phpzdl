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

$signature = $ARGV[0];
$log_line = $ARGV[1];
$start = $ARGV[2];
$end = $ARGV[3];
$logfile = $ARGV[4];
$server = $ARGV[5];

if ($signature !~ /^[a-zA-Z0-9\/\=\+]*$/) {
	print "Parameters error in signature\n";
	exit;
}
if ($log_line =~ /[\"\']/) {
	print "Parameters error in log_line\n";
	exit;
}
if ($start !~ /^\d+\-\d+\-\d+\s+\d+\:\d+\:\d+$/) {
	print "Parameters error in start date\n";
	exit;
}
if ($end !~ /^\d+\-\d+\-\d+\s+\d+\:\d+\:\d+$/) {
	print "Parameters error in end date\n";
	exit;
}
if ($logfile !~ /.*\.log$/) {
	print "Parameters error in logfile\n";
	exit;
}
if ($server !~ /^\d+\.\d+\.\d+\.\d+$/) {
	print "Parameters error in $server\n";
	exit;
}

my $cert = (-e "/etc/ossim/remotelogger") ? "-i /etc/ossim/remotelogger" : "";
$cmd = "ssh -o \"StrictHostKeyChecking=no\" $cert $server \"cd /usr/share/ossim/www/sem;php validate.php '$signature' '$log_line' '$start' '$end' '$logfile'\"";
system($cmd);
