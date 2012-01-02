#!/usr/bin/perl
$|=1;
use Time::Local;

if(!$ARGV[1]){
	print "Expecting: IP_list uniqueID\n";
	print "Don't forget to escape the strings\n";
	exit;
}

$ips = $ARGV[0];
$uniqueid = $ARGV[1];

if ($ips !~ /^(\d+\.\d+\.\d+\.\d+\,?)+$/) {
	print "Parameters error\n";
	exit;
}
if ($uniqueid !~ /^[A-Za-z0-9]+\.\d+$/ && $uniqueid !~ /^NOINDEX$/) {
	print "Parameters error\n";
	exit;
}

my $cert = (-e "/etc/ossim/remotelogger") ? "-i /etc/ossim/remotelogger" : "";

my @ips_arr = split(/\,/,$ips);
foreach $ip (@ips_arr) {
	if ($ip eq "127.0.0.1") {
		$cmd = "php killprocess.php '$uniqueid'";
	} else {
		$cmd = "ssh -o \"StrictHostKeyChecking=no\" $cert $ip \"cd /usr/share/ossim/www/sem;php killprocess.php '$uniqueid'\"";
	}
	system($cmd);
}