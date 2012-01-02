#!/usr/bin/perl
use DBI;

if(!$ARGV[0]){
print "Accepts folder with *log files\n";
exit;
}
$debug = 1; # 1 for debuging info
$folder = $ARGV[0];
$folder =~ s/\/$//;
$qfolder = quotemeta $folder;

#$cmd_years = "ls -1t $folder/*/*/*/*/*/.total_events |";

$year = `date +%Y`;
chomp($year);
$cmd_years = "find $folder/$year/ -name .total_events | egrep '[0-9]{2,3}\.[0-9]' | sort -u |";

open (LS,$cmd_years);
foreach $line (<LS>) {
	chomp ($line);
	if ($line =~ /(\d\d\d\d)\/(\d\d)\/(\d\d)\/(\d\d)\/(.+)\/\.total_events/) {
		my $year = $1;
		my $month = $2;
		my $day = $3;
		my $hour = $4;
		my $sensor = $5;
	
		open (E,$line);
		@val = <E>;
		close E;
	
		$yearly{$sensor}{$year} += int($val[0]);
		$monthly{$sensor}{$year}{$month} += int($val[0]);
		$daily{$sensor}{$year}{$month}{$day} += int($val[0]);
		$hourly{$sensor}{$year}{$month}{$day}{$hour} += int($val[0]);
	
		#print $sensor." $year/$month/$day at $hour hours: $val[0] $line\n";
	}
}
close LS;

# database connect
my $dbhost = `grep ^db_ip= /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`; chomp($dbhost);
$dbhost = "localhost" if ($dbhost eq "");
my $dbuser = `grep ^user= /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`; chomp($dbuser);
my $dbpass = `grep ^pass= /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`; chomp($dbpass);
my $dbh = DBI->connect("DBI:mysql:ossim:$dbhost", $dbuser,$dbpass, {
	PrintError => 0,
	RaiseError => 1,
	AutoCommit => 1 } ) or die("Failed to connect : $DBI::errstr\n");
# create tables if not exists
$sql_update = qq{ CREATE TABLE IF NOT EXISTS `ossim`.`sem_stats_events` (`day` INT( 11 ) NOT NULL ,`sensor` VARCHAR( 15 ) NOT NULL ,`counter` INT( 11 ) NOT NULL ,PRIMARY KEY ( `day` , `sensor` )) };
$sth_update = $dbh->prepare( $sql_update );
$sth_update->execute;
#

foreach $s (keys %yearly) {
	foreach $y (keys %{$yearly{$s}}) {
		print "$folder/".$y."/.total_events_".$s." : ".$yearly{$s}{$y}."\n";
		open (Y,">$folder/".$y."/.total_events_".$s);
		print Y $yearly{$s}{$y}."\n";
		close Y;
		$csv_year = "";
		foreach $m (keys %{$monthly{$s}{$y}}) {
			$csv_year .= "$m,".$monthly{$s}{$y}{$m}."\n" if ($monthly{$s}{$y}{$m} > 0);
			print " $folder/$y/$m/.total_events_".$s." : ".$monthly{$s}{$y}{$m}."\n";
			open (M,">$folder/$y/$m/.total_events_".$s);
			print M $monthly{$s}{$y}{$m}."\n";
			close M;
			$csv_month = "";
			foreach $d (keys %{$daily{$s}{$y}{$m}}) {
				$csv_month .= "$d,".$daily{$s}{$y}{$m}{$d}."\n" if ($daily{$s}{$y}{$m}{$d} > 0);
				print "  $folder/$y/$m/$d/.total_events_".$s." : ".$daily{$s}{$y}{$m}{$d}."\n";
				open (D,">$folder/$y/$m/$d/.total_events_".$s);
				print D $daily{$s}{$y}{$m}{$d}."\n";
				close D;
				# now in BBDD
				$day = "$y$m$d";
				$cnt = $daily{$s}{$y}{$m}{$d};
				$sql_update = qq{ REPLACE INTO sem_stats_events VALUES ('$day', '$s', $cnt) };
				$sth_update = $dbh->prepare( $sql_update );
				$sth_update->execute;
				#
				$csv_day = "";
				foreach $h (keys %{$hourly{$s}{$y}{$m}{$d}}) {
					$csv_day .= "$h,".$hourly{$s}{$y}{$m}{$d}{$h}."\n" if ($hourly{$s}{$y}{$m}{$d}{$h} > 0);
					print "   $folder/$y/$m/$d/$h/.total_events_".$s." : ".$hourly{$s}{$y}{$m}{$d}{$h}."\n";
					open (H,">$folder/$y/$m/$d/$h/.total_events_".$s);
					print H $hourly{$s}{$y}{$m}{$d}{$h}."\n";
					close H;
				}
				open (M,">$folder/".$y."/$m/$d/.csv_total_events_".$s);
				print M $csv_day;
				close M;
			}
			open (M,">$folder/".$y."/$m/.csv_total_events_".$s);
			print M $csv_month;
			close M;
		}
		open (Y,">$folder/".$y."/.csv_total_events_".$s);
		print Y $csv_year;
		close Y;
	}
}
$dbh->disconnect;
