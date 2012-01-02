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
import os
from threading import Lock

try:
    import MySQLdb
    import MySQLdb.cursors 
    import _mysql_exceptions
except ImportError:
    print "You need python mysqld module installed"
    sys.exit()

#
# LOCAL IMPORTS
#

from OssimConf import OssimConf,OssimMiniConf
import Const
from Logger import Logger

logger = Logger.logger


class OssimDB:

    def __init__ (self, config_file=Const.CONFIG_FILE) :
        self.conn = None
        self.conf = OssimMiniConf(config_file)
        self.connected = False
        self.mutex = Lock()


    def connect (self, host, db, user, passwd=""):
        self.connected = False
        try:
            if passwd is None:
                passwd = ""
            self.conn = MySQLdb.connect(host=host, user=user, passwd=passwd, db=db,cursorclass=MySQLdb.cursors.DictCursor)
            self.connected = True
        except Exception, e:
            logger.error(" Can't connect to database (%s@%s) error: %s" % (user, host, e))
        self._host = host
        self._db = db
        self._user = user
        self._password = passwd
        return self.connected

    # execute query and return the result in a hash
    def exec_query (self, query) :
        self.mutex.acquire()
        arr = []
        max_retries = 3
        retries = 0
        retry_query = False
        cursor = None
        continu_working = True
        while continu_working:
            try:
                if not self.connected or self.conn is None:
                    self.connect(self._host, self._db, self._user, self._password)
                else:
                   cursor = self.conn.cursor()
                   cursor.execute(query)
                   arr  = cursor.fetchall()
                   continu_working = False
                   cursor.close()
            except _mysql_exceptions.OperationalError, e:
                self.close()
            except Exception, e:
                logger.error('Error executing query:\n----> %s \n----> [%s]' % (query, e))
                self.close()
            retries = max_retries + 1
            if retries >= max_retries:
                continu_working = False
        self.mutex.release()
        if not arr:
            arr =[]
        #We must return a hash table for row:
        return arr

    def close (self):
            try:
                self.conn.close()
            except _mysql_exceptions.ProgrammingError,e:
                pass
            finally:
                self.conn = None
            self.connected = False


if __name__ == "__main__" :
    tmp_conf = OssimConf (Const.CONFIG_FILE)
    db = OssimDB()
    db.connect(tmp_conf["ossim_host"],
                tmp_conf["ossim_base"],
                tmp_conf["ossim_user"],
                tmp_conf["ossim_pass"])
    hash = db.exec_query("select * from config")
    for row in hash: 
        print row
    db.close()

# vim:ts=4 sts=4 tw=79 expandtab:
