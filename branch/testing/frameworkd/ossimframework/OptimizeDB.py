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
import sys
import time
import random
import threading
#
# LOCAL IMPORTS
#
from OssimConf import OssimConf
from OssimDB import OssimDB
import Const

class OptimizeDB (threading.Thread):

    def __init__ (self) :
        self.__conf = None
        self.__conn = None
        self.__sleep = 86400
        self.__rand = 60
        threading.Thread.__init__(self)

    def __startup (self) :
        # configuration values
        self.__conf = OssimConf (Const.CONFIG_FILE)

        # database connection
        self.__conn = OssimDB()

        self.__rand = random.randrange(60, 300)

    def __cleanup (self) :
        self.__conn.close()

    def optimize (self, host, base, user, password):
        self.__conn.connect (host , base, user, password)

        print __name__, "Optimizing tables for database: %s" % base;
        query = "SHOW TABLES;";
        try:
            hash = self.__conn.exec_query(query)
        except Exception, e:
            print __name__, \
                ': Error executing query (%s) """%s"""' % (query, e)
            return []

        hash = self.__conn.exec_query(query)

        for row in hash:
            print __name__, \
                "Optimizing %s.%s" % (base, row["tables_in_" + base])
            query = "OPTIMIZE TABLE `%s`;" % row["tables_in_" + base]
            hash = self.__conn.exec_query(query)



    def run (self):
        self.__startup()


        print __name__, ": Waiting %d seconds for first database optimization...\n\n" % (int(self.__rand))
        time.sleep(float(self.__rand))

        while 1:

            try:

                self.optimize ( self.__conf["ossim_host"], self.__conf["ossim_base"], self.__conf["ossim_user"], self.__conf["ossim_pass"])
                self.optimize ( self.__conf["snort_host"], self.__conf["snort_base"], self.__conf["snort_user"], self.__conf["snort_pass"])
                self.optimize ( self.__conf["phpgacl_host"], self.__conf["phpgacl_base"], self.__conf["phpgacl_user"], self.__conf["phpgacl_pass"])


                # sleep to next iteration
                print __name__, ": ** Database Optimization finished at %s **" % time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(time.time()))
                print __name__, ": Next iteration in %d seconds...\n\n" % (int(self.__sleep))
                sys.stdout.flush()

                time.sleep(float(self.__sleep))

            except KeyboardInterrupt:
                sys.exit()
      



if __name__ == "__main__" :
    optimizedb = OptimizeDB()
    optimizedb.run()

# vim:ts=4 sts=4 tw=79 expandtab:
