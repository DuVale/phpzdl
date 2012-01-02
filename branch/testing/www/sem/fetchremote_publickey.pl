#!/usr/bin/perl
$|=1;
use Time::Local;

if(!$ARGV[0]){
	print "Expecting: SENSOR IP\n";
	print "Don't forget to escape the strings\n";
	exit;
}

$server = $ARGV[0];

if ($server !~ /^\d+\.\d+\.\d+\.\d+$/) {
	print "Parameters error in $server\n";
	exit;
}

if (-e "/etc/ossim/remotelogger") {
	$cmd = "ssh -i /etc/ossim/remotelogger -o \"StrictHostKeyChecking=no\" root@".$server." \"cd /usr/share/ossim/www/sem;php get_publickey.php\"";
} else {
	$cmd = "ssh -o \"StrictHostKeyChecking=no\" $server \"cd /usr/share/ossim/www/sem;php get_publickey.php\"";
}
system($cmd);
