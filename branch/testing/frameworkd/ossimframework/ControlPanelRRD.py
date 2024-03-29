#!/usr/bin/python
#
# License:
#
#    Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2010 AlienVault
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
import os, re, rrdtool, sys, threading, time

#
# LOCAL IMPORTS
#
import Const
from Logger import Logger
from OssimConf import OssimConf
from OssimDB import OssimDB
import Util

#
# GLOBAL VARIABLES
#
logger = Logger.logger



class ControlPanelRRD (threading.Thread):

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

        # rrd paths
        if self.__conf["rrdtool_path"]:
            Const.RRD_BIN = os.path.join(self.__conf["rrdtool_path"], "rrdtool")


    # close db connection
    def __cleanup (self) :
        self.__conn.close()



    ########## RRDUpdate functions ###########

    # get hosts c&a
    def __get_hosts(self):

        query = "SELECT * FROM host_qualification where host_ip != '0.0.0.0'"
        return self.__conn.exec_query(query)


    # get nets c&a
    def __get_nets(self):

        query = "SELECT * FROM net_qualification"
        return self.__conn.exec_query(query)


    # get groups c&a
    def __get_groups(self):
    
        query = "SELECT net_group_reference.net_group_name AS group_name,\
          SUM(net_qualification.compromise) AS compromise,\
          SUM(net_qualification.attack) AS attack\
          FROM net_group_reference, net_qualification WHERE\
          net_group_reference.net_name = net_qualification.net_name GROUP BY\
          group_name"
        return self.__conn.exec_query(query)


    # get ossim users
    def __get_users(self):

        query = "SELECT * FROM users"
        return self.__conn.exec_query(query)


    # get users of incidents
    def __get_incident_users(self):

        query = "SELECT in_charge FROM incident_ticket GROUP BY in_charge;"
        return self.__conn.exec_query(query)

    # get business processes members
    def __get_bp_members(self):

        query = "SELECT * FROM bp_member_status"
        return self.__conn.exec_query(query)

    # get global c&a as sum of hosts c&a
    def get_global_qualification(self, allowed_nets):
        
        MIN_GLOBAL_VALUE = 0.0001 # set to 0.0001 by alexlopa (sure?)

        compromise = attack = MIN_GLOBAL_VALUE

        for host in self.__get_hosts():
            if Util.isIpInNet(host["host_ip"], allowed_nets) or not allowed_nets:
                threshold_c = Util.getHostThreshold(self.__conn, host["host_ip"],"C")
                threshold_a = Util.getHostThreshold(self.__conn, host["host_ip"],"A")
                asset = Util.getHostAsset(self.__conn, host["host_ip"])

                if asset is False:
                    net = Util.getClosestNet(self.__conn, host["host_ip"])
                    asset = Util.getNetAsset(self.__conn, net)

                if host["compromise"] > (threshold_c * asset):
                    compromise += (threshold_c * asset)

                else:
                    compromise += int(host["compromise"])

                if host["attack"] > (threshold_c * asset):
                    attack += (threshold_a * asset)

                else:
                    attack += int(host["attack"])

        if compromise < MIN_GLOBAL_VALUE:
            compromise = MIN_GLOBAL_VALUE

        if attack < MIN_GLOBAL_VALUE:
            attack = MIN_GLOBAL_VALUE

        return (compromise, attack)


    # get level (0 or 100) using c&a and threshold
    def get_level_qualification(self, user, c, a):

        compromise = attack = 1

        if float(c) > float(self.__conf["threshold"]):
            compromise = 0
        if float(a) > float(self.__conf["threshold"]):
            attack = 0

        return (100*compromise, 100*attack)


    # get user incident count
    def get_incidents (self, user):

        status = {}
        query = "SELECT count(*) as count, status FROM incident_ticket WHERE in_charge = \"%s\" GROUP BY status" % user
        hash = self.__conn.exec_query(query)

        for row in hash: # Should be only one anyway
            status[row["status"]] = row["count"]

        return status


    # update rrd files with new C&A values
    def update_rrd(self, rrdfile, compromise, attack):

        timestamp = int(time.time())

        try:
            open(rrdfile)

        except IOError:
            logger.error("Creating %s.." % (rrdfile))
# This needs some checking, we don't need HWPREDICT here and I got some probs
# on MacosX (def update_rrd_simple) so I removed aberrant behaviour detection.
            try:
                rrdtool.create(rrdfile,
                               '-b', str(timestamp-1), '-s300',
                               'DS:ds0:GAUGE:600:0:1000000',
                               'DS:ds1:GAUGE:600:0:1000000',
                               'RRA:AVERAGE:0.5:1:800',
                               'RRA:HWPREDICT:1440:0.1:0.0035:288',
                               'RRA:AVERAGE:0.5:6:800',
                               'RRA:AVERAGE:0.5:24:800',
                               'RRA:AVERAGE:0.5:288:800',
                               'RRA:MAX:0.5:1:800',
                               'RRA:MAX:0.5:6:800',
                               'RRA:MAX:0.5:24:800',
                               'RRA:MAX:0.5:288:800')
            except Exception, e:
                logger.warning(": Error creating %s.." % (rrdfile))
                return

        logger.info("Updating %s with values (C=%s, A=%s).." % (rrdfile, str(compromise), str(attack)))

            # RRDs::update("$dotrrd", "$time:$inlast:$outlast");
        # It may fail here, I don't know if it only happens on MacosX but this
        # does solve it. (DK 2006/02)
        # Usually update fails after creation rrd
        # Fix : create and update can't be done on the same second
        #  so create one second in the past (LL 2007/05)
        try:
            rrdtool.update(rrdfile, str(timestamp) + ":" + \
                            str(compromise) + ":" +\
                            str(attack))
        except Exception, e:
            logger.error("Error updating %s: %s" % (rrdfile, e))


    # update incident rrd files with a new incident count
    def update_rrd_simple(self, rrdfile, count):

        timestamp = int(time.time())

        try:
            open(rrdfile)

        except IOError:
            logger.error(": Creating %s.." % (rrdfile))
            rrdtool.create(rrdfile,
                           '-b', str(timestamp), '-s300',
                           'DS:ds0:GAUGE:600:0:1000000',
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
                % (rrdfile, str(count)))

            try:
                rrdtool.update(rrdfile, str(timestamp) + ":" + \
                            str(count))

            except Exception, e:
                logger.error("Error updating %s: %s" % (rrdfile, e))


    # purge rrd files older than n days
    def purge_old_rrd_files(self, rrdpath, ndays):

        # Time n days ago
        secs = int(time.time()) - (ndays * 24 * 60 * 60)

        try:
            for file in os.listdir(rrdpath):
                if file.endswith(".rrd"):
                    rrd_file = os.path.join(rrdpath, file)

                    try:
                        statinfo = os.stat(rrd_file)

                        if statinfo.st_mtime < secs:
                            logger.info("Purging %s because it's %d days old" \
                              % (rrd_file, int((secs - statinfo.st_mtime)/(24*60*60)) + ndays))

                            try:
                                os.remove(rrd_file)

                            except Exception, e:
                                logger.error("Error removing %s: %s" % (rrdfile, e))

                    except Exception, e:
                        logger.error("Error getting stat for %s: %s" % (rrdfile, e))

        except Exception, e:
            logger.error("Error purging in %s: %s" % (rrdpath, e))



    ########## ControlPanel functions ###########

    # update c&a and c&a date in database
    def update_db_control_panel(self, type, info, id, range):

        query = """
            DELETE FROM control_panel 
                WHERE id = '%s' AND rrd_type = '%s' AND time_range = '%s'
            """ % (id, type, range)

        self.__conn.exec_query(query)

        # If host and max_c = max_a = 0 don't update
        if not (type == 'host' and info["max_c"] == 0 and info["max_a"] == 0):

            query = """
                INSERT INTO control_panel 
                (id, rrd_type, time_range, max_c, max_a, max_c_date, max_a_date)
                VALUES ('%s', '%s', '%s', %f, %f, '%s', '%s')
                """ % (id, type, range, info["max_c"], info["max_a"],
                       info["max_c_date"], info["max_a_date"])

            self.__conn.exec_query(query)

            logger.info("Updating %s (%s):    \tC=%f, A=%f" % \
                (id, range, info["max_c"], info["max_a"]))

            # TODO: necessary??
            sys.stdout.flush()


    # return a tuple with C & A and the date of C and A max values
    def get_rrd_max(self, start, end, rrd_file):

        rrd_info = {}        
        max_c = max_a = 0.0
        max_c_date = max_a_date = c_date = a_date = None
        
        # execute rrdtool fetch and obtain max c & a date
        cmd = "%s fetch %s MAX -s %s -e %s" % \
            (Const.RRD_BIN, rrd_file, start, end)
        output = os.popen(cmd)
        pattern = "(\d+):\s+(\S+)\s+(\S+)"

        for line in output.readlines():
            result = re.findall(pattern, line)

            if result != []:
                (date, compromise, attack) = result[0]

                if compromise not in ("nan", "", "-nan") and attack not in ("nan", "", "-nan"):
                    if Util.getLocaleFloat(compromise) > max_c:
                        c_date = date
                        max_c = Util.getLocaleFloat(compromise)

                    if Util.getLocaleFloat(attack) > max_a:
                        a_date = date
                        max_a = Util.getLocaleFloat(attack)

        output.close()

        # convert date to datetime format
        if c_date is not None:
            max_c_date = time.strftime('%Y-%m-%d %H:%M:%S',
                time.localtime(float(c_date)))

        else:
            max_c_date = time.strftime('%Y-%m-%d %H:%M:%S',
                time.localtime(0))

        if a_date is not None:
            max_a_date = time.strftime('%Y-%m-%d %H:%M:%S',
                time.localtime(float(a_date)))

        else:
            max_a_date = time.strftime('%Y-%m-%d %H:%M:%S',
                time.localtime(0))
        
        rrd_info["max_c"] = max_c
        rrd_info["max_c_date"] = max_c_date
        rrd_info["max_a"] = max_a
        rrd_info["max_a_date"] = max_a_date

        return rrd_info


    # Update global level using level rrd
    # level is calculated as level average in a range
    def update_control_panel_level(self, rrd_file, user):

        rrd_name = os.path.basename(rrd_file.split(".rrd")[0])

        # It's a dictionary => no sorted iteration
        range2date = {
            "day"  : "N-1D",
            "week" : "N-7D",
            "month": "N-1M",
            "year" : "N-1Y",
        }

        pattern = "(\d+):\s+(\S+)\s+(\S+)"

        # calculate average for day, week, month and year levels
        # with no special sort (range2date is a dictionary)
        for range_key, range_value in range2date.iteritems():

            output = os.popen("%s fetch %s AVERAGE -s %s -e N" % \
                 (Const.RRD_BIN, rrd_file, range_value))

            C_level = A_level = count = 0
            for line in output.readlines():
                result = re.findall(pattern, line)
                if result != []:
                    (date, compromise, attack) = tuple(result[0])
                    if compromise not in ("nan", "", "-nan") \
                      and attack not in ("nan", "", "-nan"):
                        C_level += Util.getLocaleFloat(compromise)
                        A_level += Util.getLocaleFloat(attack)
                    else: # when there isn't data we suppose the level is 100
                        C_level += 100
                        A_level += 100
                    count += 1

            output.close

            if count == 0: # if there isn't data we suppose the level is 100
                C_level = 100
                A_level = 100
            else: # when there isn't data we suppose the level is 100
                C_level = C_level / count
                A_level = A_level / count

            query = """
                UPDATE control_panel 
                    SET c_sec_level = %f, a_sec_level = %f
                    WHERE id = 'global_%s' and time_range = '%s'
            """ % (C_level, A_level, user, range_key)

            logger.info("Updating %s (%s):  C=%s%%, A=%s%%" % \
                (rrd_name, range_key, str(C_level), str(A_level)))

            self.__conn.exec_query(query)


    # update control_panel table with rrd values, propagating maximal c&a
    def update_control_panel_max(self, rrd_file, type):

        try:

            rrd_name = os.path.basename(rrd_file.split(".rrd")[0])
            rrd_info = {}
            # It's a dictionary => no sorted iteration
            range2date = {
                "day"  : "N-1D",
                "week" : "N-7D",
                "month": "N-1M",
                "year" : "N-1Y",
            }
            # It's a list => sorted iteration
            ranges = ["day","week","month","year"]

            # For every range
            for range_key, range_value in range2date.iteritems():
                # Get MAX values
                rrd_info[range_key]   = self.get_rrd_max(range_value, "N", rrd_file)

            rrd_info_p = {
               "max_c"     : -1,
               "max_a"     : -1,
               "max_c_date": None,
               "max_a_date": None,
            }
            # We need a sorted iteration (day -> week -> month -> year) to
            # propagate maximal c&a
            for range in ranges:
                # Check previous range maximal compromise to avoid incoherence in returned values by rrdfetch
                if rrd_info[range]["max_c"] <= rrd_info_p["max_c"]:
                    rrd_info[range]["max_c"] = rrd_info_p["max_c"]
                    rrd_info[range]["max_c_date"] = rrd_info_p["max_c_date"]
                else:
                    rrd_info_p["max_c"] = rrd_info[range]["max_c"]
                    rrd_info_p["max_c_date"] = rrd_info[range]["max_c_date"]

                # Check previous range maximal attack to avoid incoherence in returned values by rrdfetch
                if rrd_info[range]["max_a"] <= rrd_info_p["max_a"]:
                    rrd_info[range]["max_a"] = rrd_info_p["max_a"]
                    rrd_info[range]["max_a_date"] = rrd_info_p["max_a_date"]
                else:
                    rrd_info_p["max_a"] = rrd_info[range]["max_a"]
                    rrd_info_p["max_a_date"] = rrd_info[range]["max_a_date"]

                # Update db with MAX values
                self.update_db_control_panel(type, rrd_info[range], rrd_name, range)

        except Exception, e:
            logger.error("Unexpected exception in update_control_panel_max:", e)


    # Delete hosts from control_panel when they're too old
    def delete_from_control_panel(self, type, range):

        interval = 1
        myrange = range
        # Compatibility with MySQL < 5.0.0
        # 'week' keyboard is new in mysql5
        if range == "week":
            myrange = "day"
            interval = 7

        query = """
            DELETE FROM control_panel
                WHERE rrd_type = '%s' AND time_range = '%s'
                    AND (max_c_date is NULL OR
                         max_c_date<=SUBDATE(now(),INTERVAL %i %s))
                    AND (max_a_date is NULL OR
                         max_a_date<=SUBDATE(now(),INTERVAL %i %s))
                   """ % (type, range, interval, myrange, interval, myrange)

        self.__conn.exec_query(query)


    def run (self) :

        rrd_purge = 0
        rrd_purge_iter = 100
        ndays = 365
        
        while 1:

            try:

                # Read configuration and connect to db in every iteration
                # (in order to update configuration parameter)
                self.__startup()


                #### RRDUpdate ####

                ### business processes
                try:
                    rrdpath = self.__conf["rrdpath_bps"] or \
                        '/var/lib/ossim/rrd/business_processes/'
                    if not os.path.isdir(rrdpath):
                        os.makedirs(rrdpath, 0755)
                    for bp_member in self.__get_bp_members():
                        filename = os.path.join(rrdpath,
                                                bp_member['measure_type'] +\
                                                '-' +\
                                                bp_member['member'] +\
                                                '.rrd')
                        self.update_rrd_simple(filename, bp_member['severity'])
                except OSError, e:
                    logger.error(e)

                ### incidents
                try:
                    rrdpath = self.__conf["rrdpath_incidents"] or \
                        '/var/lib/ossim/rrd/incidents/'
                    if not os.path.isdir(rrdpath):
                        os.makedirs(rrdpath, 0755)
                    for user in self.__get_incident_users():
                        incidents = self.get_incidents(user["in_charge"])
                        for type in incidents:
                            filename = os.path.join(rrdpath, "incidents_" + user["in_charge"] + "_" +  type + ".rrd")
                            self.update_rrd_simple(filename, incidents[type])
                except OSError, e:
                    logger.error(e)

                ### hosts
                try:
                    rrdpath = self.__conf["rrdpath_host"] or \
                        '/var/lib/ossim/rrd/host_qualification/'
                    if not os.path.isdir(rrdpath):
                        os.makedirs(rrdpath, 0755)
                    for host in self.__get_hosts():
                        filename = os.path.join(rrdpath, host["host_ip"] + ".rrd")
                        if host.has_key("compromise") and host.has_key("attack"):
                            self.update_rrd(filename, host["compromise"], host["attack"])
                            self.update_control_panel_max(filename, "host")
                        else:
                            logger.error("RDD File:%s malformated" % filename)
                except OSError, e:
                    logger.error(e)

                ### nets
                try:
                    rrdpath = self.__conf["rrdpath_net"] or \
                        '/var/lib/ossim/rrd/net_qualification/'
                    if not os.path.isdir(rrdpath):
                        os.makedirs(rrdpath, 0755)
                    for net in self.__get_nets():
                        filename = os.path.join(rrdpath, net["net_name"] + ".rrd")
                        if net.has_key("compromise") and net.has_key("attack"):
                            self.update_rrd(filename, net["compromise"], net["attack"])
                            self.update_control_panel_max(filename, "net")
                        else:
                            logger.error("RDD File:%s malformated" % filename)
                except OSError, e:
                    logger.error(e)

                ### groups
                try:
                    rrdpath = self.__conf["rrdpath_net"] or \
                        '/var/lib/ossim/rrd/net_qualification/'
                    if not os.path.isdir(rrdpath):
                        os.makedirs(rrdpath, 0755)
                    for group in self.__get_groups():
                        filename = os.path.join(rrdpath, "group_" + group["group_name"] + ".rrd")
                        if group.has_key("compromise") and group.has_key("attack"):  
                            self.update_rrd(filename, group["compromise"], group["attack"])
                            self.update_control_panel_max(filename, "group")
                        else:
                            logger.error("RDD File:%s malformated" % filename)
                except OSError, e:
                    logger.error(e)

                ### global & level
                try:
                    rrdpath = self.__conf["rrdpath_global"] or \
                        '/var/lib/ossim/rrd/global_qualification/'
                    if not os.path.isdir(rrdpath):
                        os.makedirs(rrdpath, 0755)
                    rrdpath_level = self.__conf["rrdpath_level"] or \
                        '/var/lib/ossim/rrd/level_qualification/'
                    if not os.path.isdir(rrdpath_level):
                        os.makedirs(rrdpath_level, 0755)
                    for user in self.__get_users():

                        # ** FIXME **
                        # allow all nets if user is admin
                        # it's ugly, I know..
                        if user['login'] == 'admin':
                            user['allowed_nets'] = ''

                        ### global
                        filename = os.path.join(rrdpath,
                                                "global_" + user["login"] + ".rrd")
                        (compromise, attack) = \
                            self.get_global_qualification(user["allowed_nets"])
                        self.update_rrd(filename, compromise, attack)
                        self.update_control_panel_max(filename, "global")

                        ### level
                        filename_level = os.path.join(rrdpath_level,
                                                "level_" + user["login"] + ".rrd")
                        (c_percent, a_percent) = \
                            self.get_level_qualification(user["login"], compromise, attack)
                        self.update_rrd(filename_level, c_percent, a_percent)
                        self.update_control_panel_level(filename_level, user["login"])

                except OSError, e:
                    logger.error(e)


                #### ControlPanel ####

                # clean up host's rrds
                for range in ["day", "week", "month", "year"]:
                    self.delete_from_control_panel("host", range)

                # Purge rrd files older than n days, each 100 iters
                if rrd_purge == 0:
                    logger.info("Purging rrd files older than %s days" % str(ndays))
                    rrd_purge = rrd_purge_iter
                    for rrdpath in [
                                    self.__conf["rrdpath_incidents"],
                                    self.__conf["rrdpath_host"],
                                    self.__conf["rrdpath_net"],
                                    self.__conf["rrdpath_global"],
                                    self.__conf["rrdpath_level"]
                                   ]:
                        self.purge_old_rrd_files(rrdpath, ndays)

                rrd_purge = rrd_purge - 1

                # disconnect from db
                self.__cleanup()

                # sleep to next iteration
                logger.info("** Update finished at %s **" % \
                    time.strftime('%Y-%m-%d %H:%M:%S', 
                                  time.localtime(time.time())))

                logger.info("Next iteration in %d seconds..." % int(Const.SLEEP))

                # TODO: necessary ??
                sys.stdout.flush()

                # sleep until next iteration
                time.sleep(float(Const.SLEEP))

            except KeyboardInterrupt:
                self.__cleanup()
                sys.exit()

        # never reached..

# vim:ts=4 sts=4 tw=79 expandtab:
