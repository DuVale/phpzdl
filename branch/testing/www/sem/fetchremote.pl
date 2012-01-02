#!/usr/bin/perl
$|=1;
use Time::Local;
use POSIX;
my @pids;

use sigtrap 'handler' => \&cleanAndExit, 'HUP', 'INT', 'QUIT', 'ILL', 'TRAP', 'ABRT', 'KILL', 'TERM', 'STOP';
sub cleanAndExit(){
	foreach my $pid (@pids) {
		kill -9, $pid;
	}
    exit(1);
}

if(!$ARGV[10]){
	print "Expecting: start_date end_date query start_line num_lines order_by operation cache_file idsession user IP_list\n";
	print "Don't forget to escape the strings\n";
	exit;
}

$start = $ARGV[0];
$end = $ARGV[1];
$query = $ARGV[2];
$start_line = $ARGV[3];
$num_lines = $ARGV[4];
$order_by = $ARGV[5];
$operation = $ARGV[6];
$cache_file = $ARGV[7];
$allowed_sensors = $ARGV[8];
$idsesion = $ARGV[9];
$user = $ARGV[10];
$ips = $ARGV[11];

if ($start !~ /^\d+\-\d+\-\d+\s+\d+\:\d+\:\d+$/) {
	print "Parameters error in start date\n";
	exit;
}
if ($end !~ /^\d+\-\d+\-\d+\s+\d+\:\d+\:\d+$/) {
	print "Parameters error in end date\n";
	exit;
}
if ($query ne "" && $query !~ /^[%a-zA-Z0-9#\r\n\.,:@\_\-\/\?&\!\=\s\[\]\)\(\'"\;\*\+]+$/) {
	print "Parameters error in query\n";
	exit;
}
if ($start_line !~ /^[0-9]+$/) {
	print "Parameters error in start_line\n";
	exit;
}
if ($num_lines !~ /^[0-9]+$/) {
	print "Parameters error in num_lines\n";
	exit;
}
if ($order_by !~ /^[a-zA-Z0-9\_\-\s]+$/) {
	print "Parameters error in order_by\n";
	exit;
}
if ($operation !~ /^[a-zA-Z_]+$/) {
	print "Parameters error in operation\n";
	exit;
}
if ($cache_file ne "" && $cache_file ne "none" && $cache_file !~ /^[a-zA-Z0-9\_\-\s\/]+\.cache$/) {
	print "Parameters error in cache file\n";
	exit;
}
if ($idsesion !~ /^[A-Za-z0-9]+\.\d+$/ && $idsesion !~ /^NOINDEX$/) {
	print "Parameters idsession error\n";
	exit;
}
if ($user !~ /^[a-zA-Z0-9_\-\.]+$/) {
	print "Parameters error in user\n";
	exit;
}
if ($ips !~ /^(\d+\.\d+\.\d+\.\d+\,?)+$/) {
	print "Parameters error in IPs\n";
	exit;
}

$query =~ s/\'/'\\''/g;

my @ips_arr = split(/\,/,$ips);
foreach $ip (@ips_arr) {
    my $pid = fork();    
    if ($pid == 0) { # child
        #print "Connecting $ip\n";
        if ($ip eq "127.0.0.1") {
            $cmd = "cd /usr/share/ossim/www/sem;perl fetchall.pl '$start' '$end' '$query' $start_line $num_lines $order_by $operation $cache_file '$allowed_sensors' $idsesion $user";
        } else {
            if (-e "/etc/ossim/remotelogger") {
            	$cmd = "ssh -i /etc/ossim/remotelogger -o \"StrictHostKeyChecking=no\" root@".$ip." \"cd /usr/share/ossim/www/sem;perl fetchall.pl '$start' '$end' '$query' $start_line $num_lines $order_by $operation $cache_file '$allowed_sensors' $idsesion $ip\"";
            } else {
            	$cmd = "ssh -o \"StrictHostKeyChecking=no\" $ip \"cd /usr/share/ossim/www/sem;perl fetchall.pl '$start' '$end' '$query' $start_line $num_lines $order_by $operation $cache_file '$allowed_sensors' $idsesion $ip\"";
            }
        }
        #print "$cmd\n"; exit; 
        system($cmd);
        exit(0);
    }
    push @pids, $pid;
}
# Waiting childrens
foreach my $pid (@pids) {
    waitpid $pid, 0;
}
