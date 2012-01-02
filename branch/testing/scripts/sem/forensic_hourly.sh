#!/bin/bash

if pidof -x $(basename $0) > /dev/null; then
	for p in $(pidof -x $(basename $0)); do
    		if [ $p -ne $$ ]; then
      			echo "Script $0 is already running: exiting"
			# exit 0, so that cron does not send an email.
			exit 0
    		fi
	done
fi

# extract logs dir from ini
LOGS='/var/ossim/logs/'
eval `egrep "^indexer" /usr/share/ossim/www/sem/everything.ini `
eval `egrep "^log_dir" /usr/share/ossim/www/sem/everything.ini `
if [ -d $log_dir ];then
	LOGS=$log_dir
fi

# --force command line option forces recalculation all stats files from last hour new logs
if [ "$1" != "--force" ];then
	# alienvault indexer
	if [ -e $indexer ]; then
		ulimit -s 262144
		YESTERDAY=`date --date='last day' "+%Y/%m/%d/"`
		TODAY=`date "+%Y/%m/%d/"`
		HOUR=`date "+%H"`
		if [ $HOUR > 0 ]; then
			$indexer -mm $LOGS$TODAY
		else
			$indexer -mm $LOGS$YESTERDAY
			$indexer -mm $LOGS$TODAY
		fi
	fi
    cd /usr/share/ossim/scripts/sem/ && sh /usr/share/ossim/scripts/sem/forensic_stats_last_hour.sh
    if [ ! -e $indexer ]; then
    	cd /usr/share/ossim/scripts/sem/ && perl /usr/share/ossim/scripts/sem/generate_stats.pl $LOGS
    fi
else
	# alienvault indexer
	if [ -e $indexer ]; then
		$indexer -R $LOGS
	fi
    cd /usr/share/ossim/scripts/sem/ && sh /usr/share/ossim/scripts/sem/forensic_stats_last_hour-force.sh
    if [ ! -e $indexer ]; then
    	cd /usr/share/ossim/scripts/sem/ && perl /usr/share/ossim/scripts/sem/generate_stats.pl $LOGS force
    fi
fi

# generate totals by sensors
cd /usr/share/ossim/scripts/sem/ && perl /usr/share/ossim/scripts/sem/gen_sensor_totals.pl $LOGS

# generate stats into mysql table to logger report facility
# not needed now!
#cd /usr/share/ossim/scripts/sem/ && perl /usr/share/ossim/scripts/sem/generate_sem_stats.pl $LOGS

# update index file
cd /usr/share/ossim/scripts/sem/ && sh /usr/share/ossim/scripts/sem/update_db.sh

# latest mindex.inx update
touch /var/ossim/logs/latest_update
