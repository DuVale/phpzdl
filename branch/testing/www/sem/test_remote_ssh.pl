#!/usr/bin/perl
$|=1;
$ip = $ARGV[0];
if ($ip !~ /^\d+\.\d+\.\d+\.\d+$/) {
	exit;
}
if ($ip eq "127.0.0.1") {
	print "OK\n";
	exit;
}
if (-e "/etc/ossim/remotelogger") {
	$cmd = 'ssh -q -o "BatchMode=yes" -o "StrictHostKeyChecking=no" -o "ConnectTimeout=5" -i /etc/ossim/remotelogger root@'.$ip.' "echo 2>&1" && echo "OK" || echo "NOK" |';
} else {
	$cmd = 'ssh -q -o "BatchMode=yes" -o "StrictHostKeyChecking=no" -o "ConnectTimeout=5" root@'.$ip.' "echo 2>&1" && echo "OK" || echo "NOK" |';
}
open(S,$cmd);
while(<S>) {
	chomp;
	next if $_ eq "";
	print "$_\n";
}
close(S);
