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
# TODO: move this file to a real config file (/etc/ossim/frameworkd/)
#       and merge necessary variables from framework's ossim.conf
#

# Ossim framework daemon version
VERSION = "3.1"

# default delay between iterations
# overriden with -s option
SLEEP = 300

# default configuration file
# overriden with -c option
CONFIG_FILE = "/etc/ossim/framework/ossim.conf"

# Default asset
ASSET = 2

# Default frameworkd path
FRAMEWORKD_DIR = "/usr/share/ossim-framework/ossimframework/"

# default log directory location
LOG_DIR = "/var/log/ossim/"

# default rrdtool bin path
# overriden if there is an entry at ossim.conf
RRD_BIN = "/usr/bin/rrdtool"

# don't show debug by default
# overriden with -v option
VERBOSE = 0

# default listener port
# overriden with -p option
LISTENER_PORT = 40003

# default listener ip address. Defaults to loopback only
# overriden with -l option
# Specify 0.0.0.0 for "any"
LISTENER_ADDRESS = "0.0.0.0"



# access to ossim-framework through http:// or https://
HTTP_SSL = False

#AES encryption/decrypt file
ENCRYPTION_KEY_FILE = "/etc/ossim/framework/db_encryption_key"

#Apatch - Ntop proxy configuration file template
NTOP_APACHE_PROXY_TEMPLATE = "/etc/ossim/framework/ntop_proxy_apache_template.conf"

#Log file for notifications.
NOTIFICATION_FILE="/var/log/ossim/framework-notifications.log"

#Ntop rewrite defualt config file

NTOP_REWRITE_DEFUALT_CONFIG_FILE= "/etc/apache2/conf.d/default-ntop.conf"

#configuration backups dir.
BACKUPS_DIR ="/etc/ossim/framework/backups/"
NFSEN_CONFIG="/etc/nfsen/nfsen.conf"
NFSEN_MONIT_CONFIG ="/etc/monit/alienvault/nfcapd.monitrc"
# vim:ts=4 sts=4 tw=79 expandtab:
