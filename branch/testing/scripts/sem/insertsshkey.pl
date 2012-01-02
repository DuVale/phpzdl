#!/usr/bin/perl

sub get_exists {
	my $key = shift;
	return 0 if (!-e "/root/.ssh/authorized_keys");
	open K, "/root/.ssh/authorized_keys" or die $!;
	my @lines = <K>;
	my $exists = 0;
	foreach $line (@lines) {
		chomp($line);
		if ($line eq $key) {
			$exists = 1;
			last;
		}
	}
	close K;
	return $exists;
}

$tmpfile = $ARGV[0];
my $key = "";
if (-e $tmpfile) {
	open F, $tmpfile or die $!;
	my @lines = <F>;
	$key = $lines[0];
	chomp($key);
	close F; 
}

if (!get_exists($key)) {
	system("mkdir /root/.ssh;chmod 700 /root/.ssh") if (!-d "/root/.ssh");
	system("cat /tmp/tmpkey >> /root/.ssh/authorized_keys");
}

if (get_exists($key)) {
	print "OK";
}
