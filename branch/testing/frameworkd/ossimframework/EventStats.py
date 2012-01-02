#!/usr/bin/python
#
# License:
#
#    Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2011 AlienVault
#    All rights reserved.
#
#    This package is free software; you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation; version 2 dated June, 1991.
#    You may not use, modify or distribute this program under any other version
#    of the GNU General Public License.
#
#    This package is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with this package; if not, write to the Free Software
#    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#    MA  02110-1301  USA
#
#
# On Debian GNU/Linux systems, the complete text of the GNU General
# Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
# Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#

#
# GLOBAL IMPORTS
#

import os
import sys
import time
import re
import threading
import rrdtool
#
# LOCAL IMPORTS
#

from OssimConf import OssimConf
from OssimDB import OssimDB
import Const
import Util

from Logger import Logger
logger = Logger.logger

class EventStats (threading.Thread) :

    def __init__ (self) :
        self.__conf = None      # ossim configuration values (ossim.conf)
        self.__conn = None      # cursor to ossim database
        threading.Thread.__init__(self)


    def __startup (self) :

        # configuration values
        self.__conf = OssimConf (Const.CONFIG_FILE)

        # database connection
        self.__conn = OssimDB()
        self.__conn.connect ( self.__conf["ossim_host"],
                              self.__conf["ossim_base"],
                              self.__conf["ossim_user"],
                              self.__conf["ossim_pass"])

        self.__snort_conn = OssimDB()
        self.__snort_conn.connect ( self.__conf["snort_host"],
                              self.__conf["snort_base"],
                              self.__conf["snort_user"],
                              self.__conf["snort_pass"])

        # rrd paths
        if self.__conf["rrdtool_path"]:
            Const.RRD_BIN = os.path.join(self.__conf["rrdtool_path"], "rrdtool")


    # close db connection
    def __cleanup (self) :
        self.__conn.close()
        self.__snort_conn.close()



    ########## RRDUpdate functions ###########

    # Update event stats table
    def __update_event_stats(self):
        logger.info("Pre-caching querys.")
        query = "set @sensors = (SELECT COUNT(DISTINCT(sid)) FROM acid_event);"
        self.__snort_conn.exec_query(query)
        query = "set @sensors_total = (SELECT COUNT(DISTINCT(sid)) FROM sensor);"
        self.__snort_conn.exec_query(query)
        query = "set @uniq_events = (SELECT COUNT(DISTINCT plugin_id, plugin_sid) FROM acid_event);"
        self.__snort_conn.exec_query(query)
        #query = "set @categories = (SELECT COUNT(DISTINCT(sig_class_id)) FROM acid_event);"
        query = "set @categories = 0;"
        self.__snort_conn.exec_query(query)
        query = "set @total_events = (SELECT COUNT(*) FROM acid_event);"
        logger.info("Pre-caching: 25% completed.")
        self.__snort_conn.exec_query(query)
        query = "set @src_ips = (SELECT COUNT(DISTINCT(ip_src)) FROM acid_event);"
        self.__snort_conn.exec_query(query)
        query = "set @dst_ips = (SELECT COUNT(DISTINCT(ip_dst)) FROM acid_event);"
        self.__snort_conn.exec_query(query)
        query = "set @uniq_ip_links = (SELECT COUNT(DISTINCT ip_src, ip_dst, ip_proto)  FROM acid_event);"
        self.__snort_conn.exec_query(query)
        query = "set @source_ports = (SELECT COUNT(DISTINCT(layer4_sport)) FROM acid_event);"
        self.__snort_conn.exec_query(query)
        query = "set @dest_ports = (SELECT COUNT(DISTINCT(layer4_dport)) FROM acid_event);"
        self.__snort_conn.exec_query(query)
        logger.info("Pre-caching: 50% completed.")
        query = "set @source_ports_udp = (SELECT COUNT(DISTINCT(acid_event.layer4_sport)) FROM acid_event WHERE acid_event.ip_proto = '17');"
        self.__snort_conn.exec_query(query)
        query = "set @source_ports_tcp = (SELECT COUNT(DISTINCT(acid_event.layer4_sport)) FROM acid_event WHERE acid_event.ip_proto = '6');"
        self.__snort_conn.exec_query(query)
        query = "set @dest_ports_tcp = (SELECT COUNT(DISTINCT(acid_event.layer4_dport)) FROM acid_event WHERE acid_event.ip_proto = '6');"
        self.__snort_conn.exec_query(query)
        query = "set @dest_ports_udp = (SELECT COUNT(DISTINCT(acid_event.layer4_dport)) FROM acid_event WHERE acid_event.ip_proto = '17');"
        self.__snort_conn.exec_query(query)
        logger.info("Pre-caching: 75% completed.")
        query = "set @tcp_events = (SELECT count(*) FROM acid_event WHERE ip_proto=6);"
        self.__snort_conn.exec_query(query)
        query = "set @udp_events = (SELECT count(*) FROM acid_event WHERE ip_proto=17);"
        self.__snort_conn.exec_query(query)
        query = "set @icmp_events = (SELECT count(*) FROM acid_event WHERE ip_proto=1);"
        self.__snort_conn.exec_query(query)
        query = "set @portscan_events = (SELECT count(*) FROM acid_event WHERE ip_proto=255);"
        self.__snort_conn.exec_query(query)
        query = "INSERT INTO event_stats(timestamp, sensors, sensors_total, uniq_events, categories, total_events, src_ips, dst_ips, uniq_ip_links, source_ports, dest_ports, source_ports_udp, source_ports_tcp, dest_ports_udp, dest_ports_tcp, tcp_events, udp_events, icmp_events, portscan_events)  VALUES(NOW(), @sensors, @sensors_total, @uniq_events, @categories, @total_events, @src_ips, @dst_ips, @uniq_ip_links, @source_ports, @dest_ports, @source_ports_udp, @source_ports_tcp, @dest_ports_udp, @dest_ports_tcp, @tcp_events, @udp_events, @icmp_events, @portscan_events);"
        return self.__snort_conn.exec_query(query)


    # get event stats
    def __get_event_stats(self):

        query = "SELECT * FROM event_stats ORDER BY timestamp DESC LIMIT 1"
        return self.__snort_conn.exec_query(query)


    # update simple rrd file
    def update_rrd_simple(self, rrdfile, count):

        timestamp = int(time.time())

        try:
            open(rrdfile)
        except IOError:
            logger.info( "Creating %s.." % (rrdfile))
            rrdtool.create(rrdfile,
                           '-b', str(timestamp-1), '-s300',
                           'DS:ds0:GAUGE:600:0:1000000',
                           'RRA:HWPREDICT:1440:0.1:0.0035:288',
                           'RRA:AVERAGE:0.5:1:800',
                           'RRA:AVERAGE:0.5:6:800',
                           'RRA:AVERAGE:0.5:24:800',
                           'RRA:AVERAGE:0.5:288:800',
                           'RRA:MAX:0.5:1:800',
                           'RRA:MAX:0.5:6:800',
                           'RRA:MAX:0.5:24:800',
                           'RRA:MAX:0.5:288:800')
        else:
            logger.info("Updating %s with value (Count=%s).." \
                % (rrdfile, count))
            try:
                rrdtool.update(rrdfile, str(timestamp) + ":" + \
                            str(count))
            except Exception, e:
                logger.error( "Error updating %s: %s" % (rrdfile, e))



    def run (self) :

        rrd_purge = 0
        rrd_purge_iter = 100
        ndays = 365
        
        while 1:

            try:

                # Read configuration and connect to db in every iteration
                # (in order to update configuration parameter)
                self.__startup()


                #### Event Stats Update ####
                retval = self.__update_event_stats()
                logger.info( "** Update returned **" % retval)

                #### Event Stat RRD Update ####
                try:
                    rrdpath = self.__conf["rrdpath_event_stats"] or \
                        '/var/lib/ossim/rrd/event_stats/'
                    if not os.path.isdir(rrdpath):
                        os.makedirs(rrdpath, 0755)
                    stats = self.__get_event_stats()
                    if len(stats) is 0:
                        logger.info( "Something's wrong with the event_stats table, no data has been returned")
                        time.sleep(float(Const.SLEEP))
                    for key in stats[0].keys():
                        logger.debug("%s:%s" %( key, stats[0][key]))
                        filename = os.path.join(rrdpath, key  + '.rrd')
                        self.update_rrd_simple(filename, stats[0][key])
                except OSError, e:
                    logger.error("Error :%s" % str(e))

                # disconnect from db
                self.__cleanup()

                # sleep to next iteration
                logger.info("** Update finished at %s **" % \
                    time.strftime('%Y-%m-%d %H:%M:%S', 
                                  time.localtime(time.time())))
                logger.info("Next iteration in %d seconds...\n\n" % \
                    (int(Const.SLEEP)))
                sys.stdout.flush()

                # sleep until next iteration
                time.sleep(float(Const.SLEEP))

            except KeyboardInterrupt:
                self.__cleanup()
                sys.exit()

        # never reached..

if __name__ == "__main__":

    eventstats = EventStats()
    eventstats.start()

# vim:ts=4 sts=4 tw=79 expandtab:
