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
import sys, re
import os
import ConfigParser
#
# LOCAL IMPORTS
#
import Const
from Logger import Logger
logger = Logger.logger
class OssimMiniConf :

    def __init__ (self, config_file=Const.CONFIG_FILE):
        self._conf = {}
        # get config only from ossim.conf file
        # (for example, when you only need database access)
        self._get_conf(config_file)

    def __setitem__ (self, key, item) :
        self._conf[key] = item

    def __getitem__ (self, key) :
        return self._conf.get(key, None)

    def __repr__ (self):
        repr = ""
        for key, item in self._conf.iteritems():
            repr += "%s\t: %s\n" % (key, item)
        return repr


    def _get_conf (self, config_file) :

        # Read config from file
        #
        try:
            config = open(config_file)
        except IOError, e:
            logger.error( "Error opening OSSIM configuration file (%s)" % e)
            sys.exit()
       
        pattern = re.compile("^(\S+)\s*=\s*(\S+)")

        for line in config:
            result = pattern.match(line)
            if result is not None:
                (key, item) = result.groups()
                self[key] = item
       
        config.close()


class OssimConf (OssimMiniConf) :

    def __init__(self, config_file=Const.CONFIG_FILE):
        OssimMiniConf.__init__(self, config_file)
        self.__configfile = config_file
        if os.path.isfile(Const.ENCRYPTION_KEY_FILE):
            config = ConfigParser.ConfigParser()
            config.readfp(open(Const.ENCRYPTION_KEY_FILE))
            self["encryptionKey"] = config.get('key-value', 'key')
        # complete config info from OssimDB
        self._get_db_conf()
        
    def _get_db_conf(self):

        # Now, complete config info from Ossim database
        #
        from OssimDB import OssimDB
        db = OssimDB(config_file=self.__configfile)
        db.connect(self["ossim_host"],
                   self["ossim_base"],
                   self["ossim_user"],
                   self["ossim_pass"])
        query = ''
        useEncryption = False
        if self["encryptionKey"]:
            query = "SELECT * FROM config where conf not like '%%_pass%%'"
            useEncryption = True
        else:
            query = "SELECT * FROM config"
            
        hash = db.exec_query(query)
        for row in hash:
            # values declared at config file override the database ones
            if row["conf"] not in self._conf:
                self[row["conf"]] = row["value"]
                
        #Now read pass
        if useEncryption: 

            hash = db.exec_query("SELECT *, AES_DECRYPT(value,'%s') as dvalue FROM config where conf like '%%_pass%%'" % self["encryptionKey"])
            
            for row in hash:
                # values declared at config file override the database ones
                if row["conf"] not in self._conf:
                    if row["dvalue"] is not None:
                        self[row["conf"]] = row["dvalue"]
                    else:
                        hash = db.exec_query("SELECT * FROM config where conf like '%s'" % row["conf"])
                        self[row["conf"]] = hash[0]["value"]
            
        db.close()


if __name__ == "__main__":
    c = OssimConf(Const.CONFIG_FILE)
    print c


# vim:ts=4 sts=4 tw=79 expandtab:
