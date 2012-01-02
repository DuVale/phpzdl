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

import socket
import threading
import time
import os
import Const, Util
from OssimDB import OssimDB
from OssimConf import OssimConf

from Logger import Logger
logger = Logger.logger
_CONF = OssimConf(Const.CONFIG_FILE)
_DB = OssimDB()
_DB.connect(_CONF['ossim_host'],
            _CONF['ossim_base'],
            _CONF['ossim_user'],
            _CONF['ossim_pass'])
'''

Plugin Return Code    Service State    Host State
                0        OK                UP
                1        WARNING           UP or DOWN/UNREACHABLE*
                2        CRITICAL          DOWN/UNREACHABLE
                3        UNKNOWN           DOWN/UNREACHABLE

'''
class NagiosMkLiveManager(threading.Thread):
    '''
    Nagios mk_livestatus wrapper
    #hostgroup column member_with_state: A list of all host names that are members of the hostgroup together with state and has_been_checked
    '''
    NAGIOS_SOCKET_PATH = "/var/lib/nagios3/rw/live"
    HOST_STATES = {0: "UP",1:"DOWN",2:"DOWN",3:"DOWN"}
    HOST_STATES_SEVERITY = {0: 0,1:10,2:10,3:10}
    #SERVICE_STATES = {0: "UP",1:"WARNING",2:"CRITICAL",3:"UNKNOWN"}
    HOST_STATE_UP = 0
    HOST_STATE_WARNING = 1
    HOST_STATE_CRITICAL = 2
    HOST_STATE_UNKNOWN = 3
    HOST_QUERY_COLUMNS_COUNT = 6
    INDX_HOST_QUERY_HOSTIP = 0
    INDX_HOST_QUERY_HOSTNAME = 1
    INDX_HOST_QUERY_HOSTSTATE = 2
    INDX_HOST_QUERY_HOST_NUMSERVICES = 3
    INDX_HOST_QUERY_HOST_NUMSERVICESHARDWARN = 4
    INDX_HOST_QUERY_HOST_NUMSERVICESHARDCRIT = 5
    '''
    TODO:
    (18:48:51) Pablo Catalina: (((W*0.5)+C)/float(SRV))*10
    (18:49:05) Pablo Catalina: Columns: name address state num_services_hard_ok num_services_hard_warn num_services_hard_crit num_services_hard_unknown
    '''
    def __init__(self):
        '''
        Constructor
        '''
        self.nagios_querys = {"hosts" : "GET hosts\nColumns: address name state num_services num_services_hard_warn num_services_hard_crit\nOutputFormat: python\n",
#                              "services": "GET services\nColumns:check_command host_name description state\nOutputFormat: python\n",
#                              "hostgroups": "GET hostgroups\nColumns: name num_hosts num_hosts_down num_hosts_pending num_hosts_unreach num_hosts_up num_services num_services_hard_crit num_services_hard_ok num_services_hard_unknown num_services_hard_warn\nOutputFormat: python\n"
                              "hostgroups": "GET hostgroups\nColumns: name members_with_state \nOutputFormat: python\n"                              
#                              "hostgroups": "GET hostgroups\n",
#                              "servicegroups":"GET servicegroups\n",
#                              "contactgroups": "GET contactgroups\n",
#                              "servicesbygroup": "GET servicesbygroup\n",
#                              "servicesbyhostgroup": "GET servicesbyhostgroup\n",
#                              "hostsbygroup": "GET hostsbygroup\n",
#                              "contacts": "GET contacts\n",
#                              "commands":"GET commands\n",
#                              "timeperiods" : "GET timeperiods\n",
#                              "downtimes": "GET downtimes\n",
                              #"comments": "GET comments\n",
#                              "status": "GET status\nColumns: program_version\n",
                              #"columns": "GET columns\n"
                         }
        self.connection = None
        self.host_list = []
        self.host_list_groups=[]
        self.interval = 5
        threading.Thread.__init__(self)

    def getData(self,cmd):
        data = []
        #Do not use isfile
        if os.path.exists(NagiosMkLiveManager.NAGIOS_SOCKET_PATH):
            try:
                self.connection = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)
                self.connection.connect(NagiosMkLiveManager.NAGIOS_SOCKET_PATH)
                self.connection.send(cmd)
                self.connection.shutdown(socket.SHUT_WR)
                data = self.connection.recv(100000000)
                self.connection.close()
            except socket.error:
                logger.error("Can't connect with nagios mklivestatus socket")
            except Exception,e:
                logger.error("An error occurred while connecting with mklivestatus socket: %s" % str(e))
        else:
            logger.warning( "%s doesn't exists. MkLiveStatus doesn't work" % NagiosMkLiveManager.NAGIOS_SOCKET_PATH)
        return data


    def loadDBData(self):
        query_host = "SELECT distinct(member) FROM bp_asset_member where member_type='host';"
        members = _DB.exec_query(query_host)
        del self.host_list[:]
        for host_member in members:
            self.host_list.append(host_member['member'])
        query_host_group = "select name from host_group;"
        members = _DB.exec_query(query_host_group)
        del self.host_list_groups[:]
        for host_group in members:
            self.host_list_groups.append(host_group['name'])

    def get_hostSeverity(self,host_data):
        host_ip = host_data[NagiosMkLiveManager.INDX_HOST_QUERY_HOSTIP]
        host_name = host_data[NagiosMkLiveManager.INDX_HOST_QUERY_HOSTNAME]
        try:
            host_state = float(host_data[NagiosMkLiveManager.INDX_HOST_QUERY_HOSTSTATE])
        except ValueError:
            logger.info("Invalid host state value: %s --> %s" % (host_ip,host_data[NagiosMkLiveManager.INDX_HOST_QUERY_HOSTSTATE]))
            return 0
        try:
            host_nservices = float(host_data[NagiosMkLiveManager.INDX_HOST_QUERY_HOST_NUMSERVICES])
        except ValueError:
            logger.info("Invalid host_nservices value: %s --> %s" % (host_ip,host_data[NagiosMkLiveManager.INDX_HOST_QUERY_HOST_NUMSERVICES]))
            return 0
        try:
            host_nservices_warn = float(host_data[NagiosMkLiveManager.INDX_HOST_QUERY_HOST_NUMSERVICESHARDWARN])
        except ValueError:
            logger.info("Invalid host_nservices_warn value: %s --> %s" % (host_ip,host_data[NagiosMkLiveManager.INDX_HOST_QUERY_HOST_NUMSERVICESHARDWARN]))
            return 0
        try:
            host_nservices_crit = float(host_data[NagiosMkLiveManager.INDX_HOST_QUERY_HOST_NUMSERVICESHARDCRIT])
        except ValueError:
            logger.info("Invalid host_nservices_crit value: %s --> %s" % (host_ip,host_data[NagiosMkLiveManager.INDX_HOST_QUERY_HOST_NUMSERVICESHARDCRIT]))
            return 0
        severity = 0
        if host_state == NagiosMkLiveManager.HOST_STATE_UP and host_nservices > 0:
            severity = (((host_nservices_warn*0.5)+host_nservices_crit)/host_nservices)*10
        elif host_state == NagiosMkLiveManager.HOST_STATE_UP and host_nservices == 0:
            severity = 0
        else:
            severity = 10
        return int(severity)
    def updateHostAvailability(self):
        data  = self.getData(self.nagios_querys['hosts'])
        if data!= []:
            data= eval(data)
            for host_data in data:
                if len(host_data) == NagiosMkLiveManager.HOST_QUERY_COLUMNS_COUNT:
                    host_ip = host_data[NagiosMkLiveManager.INDX_HOST_QUERY_HOSTIP]
                    severity = self.get_hostSeverity(host_data)
                    if host_ip in self.host_list:
                        query = "DELETE FROM bp_member_status WHERE member = '%s' and measure_type = '%s';" % (host_ip,'host_availability') 
                        _DB.exec_query(query)
                        query = "INSERT INTO bp_member_status (member, status_date, measure_type, severity) VALUES('%s', now(), '%s', %d);" % (host_ip,'host_availability',severity)
                        _DB.exec_query(query)
                        logger.info("Updating bp_member_status -> member:%s measure_type: %s severity:%s" % (host_ip,'host_availability',severity))
    def updataHostGroups(self):
        data  = self.getData(self.nagios_querys['hostgroups'])
        if data != []:
            data= eval(data) 
            for hostgroup_data in data:
                if len (hostgroup_data) == 2:
                    host_group_name = hostgroup_data[0]
                    if host_group_name in self.host_list_groups:#host group name
                        for host in hostgroup_data[1]:
                            if len(host) == 3:
                                if host[1] != 0 and host[2] == 1:#host state
                                    query = "DELETE FROM bp_member_status WHERE member = '%s' and measure_type = '%s';" % (host_group_name,'host_group_availability') 
                                    _DB.exec_query(query)
                                    query = "INSERT INTO bp_member_status (member, status_date, measure_type, severity) VALUES('%s', now(), '%s', %d);" % (host_group_name,'host_group_availability', NagiosMkLiveManager.HOST_STATES_SEVERITY [host[1]])
                                    _DB.exec_query(query)
                                    logger.info("Updating bp_member_status -> member:%s measure_type: %s severity:%s" % (host_group_name,'host_group_availability',NagiosMkLiveManager.HOST_STATES_SEVERITY [host[1]]))


    def run(self):
        while True:
            self.loadDBData()
            self.updateHostAvailability()
            self.updataHostGroups()
            time.sleep(self.interval)
if __name__ == "__main__":
    ng = NagiosMkLiveManager()
    ng.start()
