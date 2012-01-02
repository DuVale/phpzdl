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
import commands
import re
#
# LOCAL IMPORTS
#
#Win32_OperatingSystem
#smb_reg_service_pack.nasl

class WMIConnection:
    def __init__(self, host, user, password):
        self.host = host
        self.user = user
        self.password = password
    
    def execute(self, sql):
        #TODO RETURN ERROR if FAILS
        data = commands.getstatusoutput('wmic -U %s%%%s //%s "%s"' % (self.user, self.password, self.host, sql))
        return data[1]
    
    def getLoggedUsers(self):
        data = self.execute("SELECT * FROM Win32_LoggedOnUSer")
        users = []
        for l in data.split("\n"):
            p = re.compile('.*Win32_Account.Domain=\"(?P<domain>[^\"]+)\",Name=\"(?P<user>[^\"]+)\".*Win32_LogonSession.LogonId=\"(?P<id>[^\"]+)\"')
            m = p.match(l)
            if (m):
                users.append({"domain" : m.group(1), "username" : m.group(2), "logonId" : m.group(3)})
        print users
        return users
    
    def getOSInfo(self):
        data = self.execute("SELECT Caption,ServicePackMajorVersion,ServicePackMinorVersion,Version FROM Win32_OperatingSystem")
        
        
if __name__ == '__main__':
    conn = WMIConnection("192.168.1.135", "Administrador", "temporal")
    print conn.getLoggedUsers()
