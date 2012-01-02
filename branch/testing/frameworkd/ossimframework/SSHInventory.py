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
import time
#import re
#import threading
#
# LOCAL IMPORTS
#
from OssimDB import OssimDB
from OssimConf import OssimConf
from Logger import Logger
import Const
from Inventory import Inventory
from SSHConnection import SSHConnection
logger = Logger.logger

class SSHInventory():
    _interval = 3600
    
    def __init__(self):
        self._tmp_conf = OssimConf (Const.CONFIG_FILE)
        self.inv = Inventory()
        #Implement cache with timeout?????
        self.cache = []
        #threading.Thread.__init__(self)

    def connectDB(self):
        self.db = OssimDB()
        self.db.connect (self._tmp_conf["ossim_host"],
                         self._tmp_conf["ossim_base"],
                          self._tmp_conf["ossim_user"],
                         self._tmp_conf["ossim_pass"])
    
    def closeDB(self):
        self.db.close()

    def run(self):
        while True:
            self.process()
            time.sleep(self._interval)
    
    def process(self):
        '''
        Check host with local credentials
        '''
        hosts = self.inv.getHostWithCredentials("SSH")

