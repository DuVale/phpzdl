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
# Some maintenance tasks for the Nagios config related to HOST SERVICES
#

#
# GLOBAL IMPORTS
#
import os, re, subprocess, threading, time

#
# LOCAL IMPORTS
#
import Const
from Logger import Logger
from NagiosMisc import nagios_host, nagios_host_service, nagios_host_group_service, nagios_host_group
from OssimDB import OssimDB
from OssimConf import OssimConf
import Util

#
# GLOBAL VARIABLES
#
logger = Logger.logger
looped = 0



class NagiosManager:
    def __init__(self, conf):
        logger.debug("Initialising Nagios Manager.")
        self.__nagios = None
        self.__conf = conf

    def process(self, message):
        logger.debug("Nagios Manager: Processing: %s" % message)

        response = ""
        action = Util.get_var("action=\"([a-z]+)\"", message)

        if action == "add":
            type = Util.get_var("type=\"([a-zA-Z]+)\"", message)

            logger.debug("TYPE: %s" % type)
            if type == "host":
                liststring = Util.get_var("list=\"[^\"]+\"", message)
                logger.debug("STRING: %s" % liststring)
                list = liststring.split("|")
                logger.debug("LIST: %s" % list)
                for host in list:
                    host_data = re.match(r"^\s*(list=\")*(?P<ip>([0-9]{1,3}\.){3}[0-9]{1,3})\;(?P<hostname>[\w_\-\s.]+)\s*\"*$", host)

                    if host_data.group('ip') != [] and host_data.group('hostname') != []:
                        hostname = host_data.group('hostname')
                        ip = host_data.group('ip')

                        logger.debug("Adding hostname \"%s\" with ip \"%s\" to nagios" % (hostname, ip))
                        nh = nagios_host(ip, ip, "", self.__conf)
                        nh.write()

            if type == "hostgroup":
                name = Util.get_var("name=\"([\w_\-\s.]+)\"", message)
                liststring = Util.get_var("list=\"([^\"]+)\"", message)
                list = liststring.split(",")
                logger.debug("LISTSTRING: %s" % liststring)
                hosts = ""
                for host in list:
                    #host_data=re.match(r"^\s*(list=\")*(?P<ip>([0-9]{1,3}\.){3}[0-9]{1,3})\s*\"*$",host)
                    #To support nagios name
                            
                    host_data = re.match("(?P<ip>[^;]+);(?P<name>[^$]+)$", host)
                    if host_data and host_data.group('ip') != []:
                        ip = host_data.group('ip')
                        hName = host_data.group('name')

                        if hosts == "":
                            hosts = ip
                            hgName = hName
                        else:
                            hosts = ip + "," + hosts
                            hgName = hName + "," + hgName

                        logger.debug("Adding host \"%s\" with ip \"%s\" needed by group_name %s to nagios" % (hName, ip, name))
                        nh = nagios_host(ip, ip, "", self.__conf)
                        nh.write()

                    else:
                        logger.warning("Nagios format error in message: %s" % message)
                        return

                if hosts != "":
                    logger.debug("Adding %s to nagios" % (name))
                    logger.debug("LIST: %s" % (hgName))
                    nhg = nagios_host_group(name, name, hosts, self.__conf)
                    nhg.write()

                else:
                    logger.debug("Invalid hosts list... not adding %s to nagios" % (name))

            action = "reload"

        if action == "del":
            type = Util.get_var("type=\"([a-zA-Z]+)\"", message)

            if type == "host":
                ip = Util.get_var("list=\"\s*(([0-9]{1,3}\.){3}[0-9]{1,3})\s*\"", message)
                ip = ip[0]

                if ip != "":
                    logger.debug("Deleting hostname \"%s\" from nagios" % (ip))
                    nh = nagios_host(ip, ip, "", self.__conf)
                    nh.delete_host()

            if type == "hostgroup":
                name = Util.get_var("name=\"([\w_\-.]+)\"", message)

                logger.debug("Deleting hostgroup_name \"%s\" from nagios" % (name))

                nhg = nagios_host_group(name, name, "", self.__conf)
                nhg.delete_host_group()

            action = "reload"

        else: 
            logger.warning("Unknown action: %s" % action)


        if action == "restart" or action == "reload":
            if self.__nagios == None:
                self.__nagios = DoNagios()

            self.__nagios.make_nagios_changes()
            self.__nagios.reload_nagios()

        # send back our response
        return response



class DoNagios(threading.Thread):
    _interval = 600                 # intervals

    def test_create_dir(self, path):
        if not os.path.exists(path):
            os.makedirs(path)


    def __init__(self):
        self._tmp_conf = OssimConf (Const.CONFIG_FILE)
        threading.Thread.__init__(self)
        self._active_hosts = {} #key = ip value=hostname
        self.test_create_dir(self._tmp_conf['nagios_cfgs'])
        self.test_create_dir(os.path.join(self._tmp_conf['nagios_cfgs'], "hosts"))
        self.test_create_dir(os.path.join(self._tmp_conf['nagios_cfgs'], "host-services"))
        self.test_create_dir(os.path.join(self._tmp_conf['nagios_cfgs'], "hostgroups"))
        self.test_create_dir(os.path.join(self._tmp_conf['nagios_cfgs'], "hostgroup-services"))


    def run(self):
        global looped

        if looped == 0:
            self.loop()
            looped = 1

        else:
            logger.debug("Ignoring additional instance.")


    def loop(self):
        while True:
            logger.debug("Looking for new services to add")
            self.make_nagios_changes()

            # sleep until the next round
            logger.debug("Sleeping until the next round in %ss" % self._interval)
            time.sleep(self._interval)

    def load_active_hosts(self):
        '''
        Loads those host that has nagios active and almost one service active
        '''
        query = "select h.ip, h.hostname from host h, host_scan hs,host_services hss where inet_aton(h.ip) = hss.ip and inet_aton(h.ip)=hs.host_ip and hss.nagios=1 and (hss.protocol =1 or hss.protocol=0 or hss.protocol=6 or hss.protocol=17) and hs.plugin_id=2007 group by ip;"
        db = OssimDB()
        db.connect (self._tmp_conf["ossim_host"],
                self._tmp_conf["ossim_base"],
                self._tmp_conf["ossim_user"],
                self._tmp_conf["ossim_pass"])
        data = db.exec_query(query)
        self._active_hosts.clear()
        for host in data:
            hostip = host['ip']
            hostname = host['hostname']
            self._active_hosts[hostip] = hostname
        db.close()

    def get_services_by_hosts(self, hostip):
        query = 'select inet_ntoa(hss.ip) as ip, h.hostname as hostname, hss.port as port, hss.protocol as protocol,hss.service as service,hss.service_type as service_type from host h, host_services hss where hss.ip=inet_aton("%s") and (hss.protocol =1 or hss.protocol=0 or hss.protocol=6 or hss.protocol=17) and nagios=1 and inet_ntoa(hss.ip) = h.ip;' % hostip
        db = OssimDB()
        db.connect (self._tmp_conf["ossim_host"],
                self._tmp_conf["ossim_base"],
                self._tmp_conf["ossim_user"],
                self._tmp_conf["ossim_pass"])
        data = db.exec_query(query)
        db.close()
        return data

    def get_host_groups(self):
        query = 'select name from host_group'
        db = OssimDB()
        db.connect (self._tmp_conf["ossim_host"],
                self._tmp_conf["ossim_base"],
                self._tmp_conf["ossim_user"],
                self._tmp_conf["ossim_pass"])
        data = db.exec_query(query)
        db.close()
        return data

    def get_hostlist_from_hg(self,hgname):
        query="select host_ip from host_group_reference where host_group_name = '%s'" % hgname
        db = OssimDB()
        db.connect (self._tmp_conf["ossim_host"],
                self._tmp_conf["ossim_base"],
                self._tmp_conf["ossim_user"],
                self._tmp_conf["ossim_pass"])
        data = db.exec_query(query)
        db.close()
        return data
    def make_nagios_changes(self):
        path = os.path.join(self._tmp_conf['nagios_cfgs'], "host-services")
        pattern = re.compile("(?P<kk>^[\w\-]+)$")
        for fi in os.listdir(path):
            os.remove(os.path.join(path, fi))

        path = os.path.join(self._tmp_conf['nagios_cfgs'], "hostgroup-services")

        for fi in os.listdir(path):
            os.remove(os.path.join(path, fi))

        path = os.path.join(self._tmp_conf['nagios_cfgs'], "hostgroups")

        for fi in os.listdir(path):
            os.remove(os.path.join(path, fi))
        self.load_active_hosts()
        services_by_host_dic = {}
        for host_ip, hostname in self._active_hosts.iteritems():
            nh = nagios_host(host_ip, hostname, "", self._tmp_conf)
            nh.write()
            services_by_host = self.get_services_by_hosts(host_ip)
            for service in services_by_host:
                service_type =service['service_type']
                if not pattern.match(service_type):
                    logger.warning('Invalid service type: %s' % service_type)
                    continue
                protocol = service['protocol']
                hostname = service['hostname']
                port = service['port']
                if not services_by_host_dic.has_key(service_type):
                    services_by_host_dic[service_type] = []
                if hostname not in services_by_host_dic[service_type]:
                    services_by_host_dic[service_type].append(hostname)
                k = nagios_host_service(service_type,protocol,hostname,port,"120", self._tmp_conf)
                k.write()
        for key, value in services_by_host_dic.iteritems():
            if key=='unknown':
                continue
            members = ','.join(value)
            hg = nagios_host_group_service(key,key,members,self._tmp_conf)
            hg.write()
        data = self.get_host_groups()
        for hg in data:
            name = hg['name']
            data =self.get_hostlist_from_hg(name)
            host_list = []
            if len(data)>0:
                for host in data:
                    if self._active_hosts.has_key(host['host_ip']):
                        host_list.append(self._active_hosts[host['host_ip']])
            if len(host_list)> 0:
                hosts_list_str = ','.join(host_list)
                nhg = nagios_host_group(name, name, hosts_list_str, self._tmp_conf)
                nhg.write()
        logger.debug("Changes where applied! Reloading Nagios config.")
        
        self.reload_nagios()

#        port = None
#        db = OssimDB()
#        db.connect (self._tmp_conf["ossim_host"],
#                self._tmp_conf["ossim_base"],
#                self._tmp_conf["ossim_user"],
#                self._tmp_conf["ossim_pass"])
#        # protocols numbers:
#        # /etc/protocols
#        query="select port from host_services where (protocol=6 or protocol=0 or protocol=1 or protocol=17) and nagios=1 group by port"
#        services=db.exec_query(query)
#
#        path = os.path.join(self._tmp_conf['nagios_cfgs'], "host-services")
#
#        for fi in os.listdir(path):
#            os.remove(os.path.join(path, fi))
#
#        path = os.path.join(self._tmp_conf['nagios_cfgs'], "hostgroup-services")
#
#        for fi in os.listdir(path):
#            os.remove(os.path.join(path, fi))
#
#        path = os.path.join(self._tmp_conf['nagios_cfgs'], "hostgroups")
#
#        for fi in os.listdir(path):
#            os.remove(os.path.join(path, fi))
#
#
#        i = 0
#        services_hosts_dict = {} #key = service_type value = host list.
#        nagios_host_build = []
#        for port in services:
#            i+=1
#            query = "select DISTINCT h.ip, hs.service_type from host_services hs, host_scan h_sc, host h where (hs.protocol=6 or hs.protocol=0 or hs.protocol=1 or hs.protocol=17) and hs.port=%s and hs.ip=h_sc.host_ip and h_sc.plugin_id=2007 and hs.nagios=1 and h.ip=inet_ntoa(hs.ip) order by h.ip" % port['port']
#            hosts = db.exec_query(query)
#            list = ""
#            for row in hosts:
#                hostip = row['ip']
#                host_service = row['service_type']
#                if services_hosts_dict.has_key(host_service):
#                    if hostip not in services_hosts_dict[host_service]:
#                         services_hosts_dict[host_service].append(hostip)
#                else:
#                    services_hosts_dict[host_service] = []
#                    services_hosts_dict[host_service].append(hostip)
#                #create host configuration
#                if hostip not in nagios_host_build:
#                    nagios_host_build.append(hostip)
#        for host_service, host_list in services_hosts_dict.iteritems():
#            if len(host_list)>0:
#                str_list =  ",".join(host_list)
#                cmd = "check_tcp!%d" % port['port']
#                if host_service in  ['ping','PING']:
#                    cmd = "check_ping!100.0,20%!500,60%"
#                k = nagios_host_service(host_service,str_list, port['port'],"120", self._tmp_conf)
#                k.write()
#                hg = nagios_host_group_service(self.serv_port(port['port']),self.serv_name(port['port']),str_list,self._tmp_conf)
#                hg.write()
#        query = "select name from host_group;"
#        host_groups = db.exec_query(query)
#        for hg in host_groups:
#            query  = "select host_ip from host_group_reference where host_group_name = '%s' and host_ip in (select inet_ntoa(host_ip) from host_scan where host_scan.plugin_id=2007);" % hg['name']
#            hosts = db.exec_query(query)
#            hosts_by_group_list = []
#            hosts_list_str = ""
#            for host in hosts:
#                if host['host_ip'] not in hosts_by_group_list:
#                    hosts_by_group_list.append(host['host_ip'])
#                if host['host_ip'] not in nagios_host_build:
#                    nagios_host_build.append(host['host_ip'])
#            hosts_list_str = ",".join(hosts_by_group_list)
#            if len(hosts_by_group_list) > 0:
#                nhg = nagios_host_group(hg['name'], hg['name'], hosts_list_str, self._tmp_conf)
#                nhg.write()
#        for host_ip in nagios_host_build:
#            nh = nagios_host(hostip, hostip, "", self._tmp_conf)
#            nh.write()
#        #if port is not None and port in services:
#        logger.debug("Changes where applied! Reloading Nagios config.")
#        self.reload_nagios()
#
#        db.close()


    def reload_nagios(self):

        # catch the process output for logging purposes
        process = subprocess.Popen(self._tmp_conf['nagios_reload_cmd'], stdout=subprocess.PIPE, stderr=subprocess.PIPE, shell=True)
        (pid, exit_status) = os.waitpid(process.pid, 0)
        output = process.stdout.read().strip() + process.stderr.read().strip()

        # show command output if return code indicates error
        if exit_status != 0:
            logger.error(output)


    def port_to_service(self, number):
        f = open("/etc/services")
        #Actually we only look for tcp protocols here
        regexp_line = r'^(?P<serv_name>[^\s]+)\s+%d/tcp.*' % number
        try:
            service = re.compile(regexp_line)
            for line in f:
                serv = service.match(line)

                if serv != None:
                    return serv.groups()[0]

        finally:
            f.close()


    def serv_name(self, port):
        return "%s_Servers" % (self.port_to_service(port)) 


    def serv_port(self, port):
        return "port_%d_Servers" % port





