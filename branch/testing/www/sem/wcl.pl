#!/usr/bin/perl
use lib "/usr/share/ossim/include";
use ossim_conf;
use DBI;
use Time::Local;
use File::Basename; 
use Time::localtime;
use File::stat;
use POSIX qw(strftime);
use Data::Dumper;
 
if(!$ARGV[2]){
print "Expecting: start_date end_date\n";
print "Don't forget to escape the strings\n";
exit;
}

%ini = read_ini();
$loc_db = $ini{'main'}{'locate_db'};
$loc_db = "/var/ossim/logs/locate.index" if ($loc_db eq "");

$debug="";
$user = $ARGV[0];
$start = $ARGV[1];
$end = $ARGV[2];
$allowed_sensors = $ARGV[3];
$lastupdate = $ARGV[4];
$debug = $ARGV[5];

# Special case: $user is a HOST -> get count from database stats (Used in Asset Summarized Status REPORT)
if ($user =~ /^\d+\.\d+\.\d+\.\d+$/) {
	# Data Source OSSIM
	my $ossim_type = $ossim_conf::ossim_data->{"ossim_type"};
	my $ossim_name = "ossim";
	my $ossim_host = $ossim_conf::ossim_data->{"ossim_host"};
	my $ossim_port = $ossim_conf::ossim_data->{"ossim_port"};
	my $ossim_user = $ossim_conf::ossim_data->{"ossim_user"};
	my $ossim_pass = $ossim_conf::ossim_data->{"ossim_pass"};
	my $orig_dsn = "dbi:" . $ossim_type . ":" . $ossim_name . ":" . $ossim_host . ":" . $ossim_port . ":";
	my $orig_conn = DBI->connect($orig_dsn, $ossim_user, $ossim_pass) or die "Can't connect to Database\n";
	$ip_filter = "value = '$user'";
	$allowed_sensors =~ s/\,/','/g;
	$start =~ s/\-//g;
	$end =~ s/\-//g;
	$sensor_filter = ($allowed_sensors ne "") ? " AND sensor in ('".$allowed_sensors."')" : "";
	$query = "SELECT sum(counter) as total, max(day) as maxdate FROM sem_stats WHERE day >= '$start' AND day <= '$end' AND (type='src_ip' OR type='dst_ip') AND ($ip_filter)$sensor_filter";
	#print $query."\n";
	my $stm = $orig_conn->prepare($query);
    $stm->execute();
    my $count = 0;
	my $date = "-";
    if (my @row = $stm->fetchrow_array) {
    	$count = $row[0] if ($row[0] > $count);
		$date = $row[1] if ($row[1] =~ /^\d+$/);
    }
	$stm->finish();
    # Disconnect from database
    $orig_conn->disconnect();
	print "$count;$date\n";
	exit;
}

############
###### Only last indexer update date
############
if ($lastupdate eq "lastupdate") {

	$file = $ini{'main'}{'mindex'};
	$file =  "/var/ossim/logs/last/mindex.inx" if ($file eq "");
	$indexer = "";
	if (-e $file) {
		$mtime = gmtime( stat($file)->mtime );
		print $mtime. " UTC\n";
	}

	die(0);
}


############
###### Convert stuff
############

if ($start =~ /(\d+)-(\d+)-(\d+)\s+(\d+):(\d+):(\d+)/) {
	$start_epoch = timegm($6, $5, $4, $3, $2-1, $1);
}
if ($end =~ /(\d+)-(\d+)-(\d+)\s+(\d+):(\d+):(\d+)/) {
	$end_epoch = timegm($6, $5, $4, $3, $2-1, $1);
}

$common_date = `perl return_sub_dates_locate.pl \"$start\" \"$end\"`;
if ($debug ne "") { open (L,">>$debug"); }

#print "perl return_sub_dates.pl $start $end`;
chop($common_date);

%already = ();
$lines = 0;
$sort = ($order_by eq "date") ? "sort" : "sort -r";
$swish = "locate.findutils -d $loc_db $common_date | grep -E \".(log|log.gz)\$\" | egrep '($allowed_sensors)' | php check_perms.php $user | $sort |";
print L "WCL.pl: calling $swish\n" if ($debug ne "");
open (G,$swish);
while ($file=<G>) {
	next if ($file =~ /Warning|\/searches\// || $file eq "");
	chomp($file);
	my @fields = split(/\//,$file);
	my $sdirtime = timegm(0, 0, $fields[7], $fields[6], $fields[5]-1, $fields[4]);
	my $edirtime = timegm(59, 59, $fields[7], $fields[6], $fields[5]-1, $fields[4]);
	print L "WCL.pl: $start <= ".$fields[4]."-".$fields[5]."-".$fields[6]." ".$fields[7]."h <= $end ?: " if ($debug ne "");
	if ($start_epoch<=$edirtime && $end_epoch>=$sdirtime) {
		my $sf = dirname($file)."/../.total_events_".$fields[8];
		my $ac = $fields[7]."-".$fields[6]."-".($fields[5]-1)."-".$fields[4]."-".$fields[8];
		#$sf =~ s/log$/ind/;
		print L "yes $fields[8] += " if ($debug ne "");
		if (!$already{$ac}++) {
			open (F,$sf);
			while (<F>) {
				#if (/^lines\:(\d+)/) {
				#	$lines += $1;
				#}
				if (/^(\d+)/) {
					$lines += $1; 
					print L "$1 = $lines\n" if ($debug ne "");
				}
			}
			close F;
		} else { print L "already\n" if ($debug ne ""); }
	} else { print L "no skip\n" if ($debug ne ""); }
}
close G;
if ($debug ne "") {close L;}

print "$lines\n";

sub read_ini {
	my ($hash,$section,$keyword,$value);
    open (INI, "everything.ini") || die "Can't open everything.ini: $!\n";
    while (<INI>) {
        chomp;
        if (/^\s*\[(\w+)\].*/) {
            $section = $1;
        }
        if (/^\W*(.+?)=(.+?)\W*(#.*)?$/) {
            $keyword = $1;
            $value = $2 ;
            # put them into hash
            $hash{$section}{$keyword} = $value;
        }
    }
    close INI;
    return %hash;
}