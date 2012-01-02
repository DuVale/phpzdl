#!/usr/bin/perl
use File::Basename;
use Time::Local;

if(!$ARGV[0]){
print "Must enter one of:\n";
print "all, plugin_id, plugin_sid, time, sensor, src_ip, dst_ip, ftime, src_port, dst_port, data\n\n";
exit;
}
$debug = 0; # 1 for debuging info
$what = $ARGV[0];
$num_lines = $ARGV[1];
$start = $ARGV[2];
$end = $ARGV[3];
$sort_order = $ARGV[4];
$debug = 1 if ($ARGV[5] eq "debug");
%already = ();
%stats = ();
$searchingdate1 = "";
$searchingdate2 = "";
while ($file=<STDIN>) {
	chomp($file);
	my @fields = split(/\//,$file);
	my $sdirtime = timegm(0, 0, $fields[7], $fields[6], $fields[5]-1, $fields[4]);
	my $edirtime = timegm(59, 59, $fields[7], $fields[6], $fields[5]-1, $fields[4]);
	if ($start<=$edirtime && $end>=$sdirtime) {
		$searchingdate1 = $fields[4].$fields[5].$fields[6];
		if ($searchingdate1 ne $searchingdate2) {
			print "Searching in $searchingdate1\n";
			$searchingdate2 = $searchingdate1;
		}
		$sf = dirname($file);
		#$sf =~ s/\/((25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.){3}(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])$//;
		$sf .= "/data.stats";
		#print "Abriendo $sf\n";
		if (!$already{$sf}++) {
			print "Reading $sf\n" if ($debug);
			if ($what eq "all") {
				open (F,$sf);
				while (<F>) {
					#chomp;
	                if (/^(.*?)\:(.*)\:(\d+)/) {
	                    $stats{$1}{$2} += $3;
	                }
				}
				close F;				
			} else {
				#$sf = "grep ^$what '$sf'|" if ($what ne "all");
				open (F,$sf);
				while (<F>) {
					#chomp;
					if (/^$what\:(.*)\:(\d+)/) {
						$stats{$1} += $2 if ($1 ne '0' && $1 ne '253.253.253.253' && $1 ne '0.0.0.0');
					}
				}
				close F;
			}
		}
	}
}
#
@ks = keys (%stats);
if (@ks>0) {
    if ($what eq "all") {
        @ks = keys (%stats);
        foreach $what (@ks) {
            $i=1;
            $max = ($what eq "sensor" || $what eq "plugin_id") ? 100 : 10;
            if ($sort_order eq "asc") {
	            STAT: foreach $value (sort {$stats{$what}{$a}<=>$stats{$what}{$b}} keys (%{$stats{$what}})) {
	                print " $what $stats{$what}{$value} $value\n";
	                last STAT if ($i++>=$max);
	            }
            } else {
	            STAT: foreach $value (sort {$stats{$what}{$b}<=>$stats{$what}{$a}} keys (%{$stats{$what}})) {
	                print " $what $stats{$what}{$value} $value\n";
	                last STAT if ($i++>=$max);
	            }
            }
        }
    } else {
        $i=1;
        if ($sort_order eq "asc") {
	        STAT: foreach $value (sort {$stats{$a}<=>$stats{$b}} @ks) {
	            print " $value $stats{$value}\n";
	            last STAT if ($i++>=$num_lines);
	        }
	     } else {
	        STAT: foreach $value (sort {$stats{$b}<=>$stats{$a}} @ks) {
	            print " $value $stats{$value}\n";
	            last STAT if ($i++>=$num_lines);
	        }	     
	     }
    }
} else {
    print "0 none\n";
}
