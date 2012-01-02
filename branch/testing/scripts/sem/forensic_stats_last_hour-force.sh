#!/bin/bash

LOGS='/var/ossim/logs/'
eval `egrep "^log_dir" /usr/share/ossim/www/sem/everything.ini `
if [ -d $log_dir ];then
	LOGS=$log_dir
fi

cd $LOGS

# check if already running
TEST=`ps ax | grep forensic_stats_last_hour-force | grep -v grep | wc -l`
if [ $TEST = 1 ]
then
    echo "Script $0 is already running: exiting"
    exit
fi

reverse_acum ( )
{
	echo received list $1
IFS=' '
	for a in $1;do
		echo "************ finding in $a"
		# now generate .csv_total_events and .total_events for the directories and subdirectories
		rm -f $a/.csv_total_events 2>/dev/null
		echo "0,0" > $a/.csv_total_events
IFS='
'		
		for i in `find $a -maxdepth 1 -mindepth 1 -type d `;do
	
			echo "Acumulating $i for $a/.csv_total_events"
			name=`echo $i|tr '/' ' ' |egrep -io " [^ ]+ *$"|tr -d ' ' `
			echo $i
	
			tot=0
			if [ -f "$i/.total_events" ];then
				tot=`cat $i/.total_events`
			#else
			#	echo "0" > $i/.total_events
			fi

			echo $name,$tot >> $a/.csv_total_events
		done
		grep -v "0,0" $a/.csv_total_events > /tmp/$$
		mv /tmp/$$ $a/.csv_total_events
		awk -v FS="," '{a=a+$2}END{a=a+0;print a}' $a/.csv_total_events  > $a/.total_events
		# force regenerate all totals
		rm -f $a/.total_events_* $a/.csv_total_events_* $a/data.stats 2>/dev/null
	done
}

update_last_hours ( )
{ 
	hour=$( perl /usr/share/ossim/scripts/sem/get_last_hour.pl "`date +"%F %T"`"|tr "-" "/"|tr -d "\n" )
	hour2=` date +"%F %T"|tr "-" "/"|tr " " "/"|egrep -io "(/*[0-9]+)+"|head -1`

	for b in ./$hour ./$hour2;do
		if [ -d "$b" ];then
			echo "Doing last hours: $b"
			find $b -name "*.log" -type f -exec egrep '^<e' {} \; |wc -l >$b/.total_events
			# force regenerate all totals
			rm -f $b/.total_events_* $b/.csv_total_events_* $b/data.stats 2>/dev/null
		else
			echo " Doing last hours: $b doesn't exist"
		fi
	done
}


first_acum ( )
{
	# Generate first total_events (for .log files)
	echo "Generating total_events for $1/.total_events"
	find $1 -name "*.log" -type f -exec egrep '^<e' {} \; |wc -l >$1/.total_events
	# force regenerate all totals
	rm -f $1/.total_events_* $1/.csv_total_events_* $1/data.stats 2>/dev/null
}

IFS='
'

for s in `find . -mindepth 5 -maxdepth 5 -type d`
do
	echo "55555555555"
	first_acum $s
done

for a in `find . -mindepth 4 -maxdepth 4 -type d`
do
	echo "444444444444"
	first_acum $a
done

update_last_hours

for b in `find . -mindepth 3 -maxdepth 3 -type d|tr "\n" " "`
do
	echo "33333333333"
	reverse_acum $b
done

for c in `find . -mindepth 2 -maxdepth 2 -type d|grep -v searches|tr "\n" " "`
do
	echo "222222222222"
	reverse_acum $c
done

for d in `find . -mindepth 1 -maxdepth 1 -type d|grep -v searches|tr "\n" " "`
do
	echo "111111111111"
	reverse_acum $d
done

for e in `find . -mindepth 0 -maxdepth 0 -type d|grep -v searches|tr "\n" " "`
do
	echo "000000000000"
	reverse_acum $e
done

