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
import ldap
import ldap.async

#
# LOCAL IMPORTS
#
from Logger import Logger
logger = Logger.logger

class LDAPConnection:
    def __init__(self, host, user, password, dom, binddn, base_dn):
        self.host = host
        self.user = user
        self.password = password
        self.dom = dom
        self.binddn = binddn
        self.base_dn = base_dn
        self.mystreamList = None
        self.conn = None


    def connect(self):
        self.conn = ldap.initialize('ldap://%s' % self.host)
        self.conn.simple_bind_s(self.binddn, self.password )
        self.mystreamList = ldap.async.List(self.conn,)
    
    def getComputers(self):
        self.mystreamList.startSearch(self.base_dn, ldap.SCOPE_SUBTREE, '(objectClass=Computer)',)
        try:
            partial = self.mystreamList.processResults()
        except ldap.SIZELIMIT_EXCEEDED:
            sys.stderr.write('Warning: Server-side size limit exceeded.\n')
        else:
            if partial:
                sys.stderr.write('Warning: Only partial results received.\n')
        return self.mystreamList.allResults()

if __name__ == "__main__":
    myladpConnection = LDAPConnection('127.0.0.1','youruser','yourpassword','yourdomain','your bind domainname','your base domain name')
    myladpConnection.connect()
    print myladpConnection.getComputers()
