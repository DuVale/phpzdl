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
import re
import threading

#
# LOCAL IMPORTS
#
from OssimDB import OssimDB
from OssimConf import OssimConf
from Logger import Logger
import Const
from Inventory import *

logger = Logger.logger

#class OCSInventory(threading.Thread):
class OCSInventory():
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
        self.ossimHosts = self.inv.getListOfHosts()
        #print self.ossimHosts
        ocsHosts = self.getOCSHosts()
        for host in ocsHosts:
            ip = host['ipaddr']
            #Check if host is valid
            if self.inv.validateIp(ip):
                #Check if host exists
                if not ip in self.ossimHosts:
                    #Add host to ossim Database
                    logger.debug("Adding %s" % ip)
                    self.inv.insertHost(ip, None, host['name'], host['description'])
                    mac = self.getMACfromHost(host['id'], ip)
                    self.inv.insertProp(ip, "macAddress", "OCS", mac, None)
                    self.inv.insertProp(ip, "workgroup", "OCS", host['workgroup'], None)
                    self.inv.insertProp(ip, "operating-system", "OCS", host['osname'], None)
                else:
                    #Host previously discovered
                    #OCS has the highest priority to replace properties
                    props = self.inv.getProps(ip)
                    if self.inv.properties["macAddress"] not in props:
                        mac = self.getMACfromHost(host['id'], ip)
                        self.inv.insertProp(ip, "macAddress", "OCS", mac, None)
                    else:
                        mac = self.getMACfromHost(host['id'], ip)
                        self.inv.updateProp(ip, "macAddress", "OCS", mac, None)                
                    if self.inv.properties["workgroup"] not in props:        
                        self.inv.insertProp(ip, "workgroup", "OCS", host['workgroup'], None)
                    else:
                        self.inv.updateProp(ip, "workgroup", "OCS", host['workgroup'], None)
                    
                    #OS
                    cpe = self.inv.generateCPE(host['osname'], host['osversion'], host['oscomments'])
                    if not cpe:
                        cpe = host['oscomments']    
                    if self.inv.properties["operating-system"] not in props:
                        self.inv.insertProp(ip, "operating-system", "OCS", host['osname'], cpe)
                    else:
                        self.inv.updateProp(ip, "operating-system", "OCS", host['osname'], cpe)
    
    def getOCSHosts(self):
        self.connectDB()
        sql = "select id,name,osname,osversion,ipaddr,workgroup,description,oscomments from ocsweb.hardware;"
        data = self.db.exec_query(sql)
        self.closeDB()
        return data
    
    def getSoftware(self, ip):
        pass
        
    def getMACfromHost(self, id, ip):
        self.connectDB()
        sql = "select MACADDR from ocsweb.networks where HARDWARE_ID = %d and IPADDRESS = '%s';" % (id, ip)
        data = self.db.exec_query(sql)
        if data:
            self.closeDB()
            return data[0]["macaddr"]
        
if __name__ == '__main__':
    ocs = OCSInventory()
    ocs.run()
