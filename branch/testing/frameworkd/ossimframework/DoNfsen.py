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
import sys
from datetime import date, datetime
import signal
from tempfile import mkstemp

#
# LOCAL IMPORTS
#
import Const
from Logger import Logger
from OssimDB import OssimDB
from OssimConf import OssimConf, OssimMiniConf
import Util
import re
from shutil import copy 
import os
#
# GLOBAL VARIABLES
#
logger = Logger.logger
class NfsenErros():
    Nfsen_ok = 0
    Nfsen_error_makeBackupDirError = 1
    Nfsen_error_invalidSensorname = 2
    Nfsen_error_invalidPort = 3
    Nfsen_error_invalidType = 4
    Nfsen_error_duplicateSensorname = 5
    Nfsen_error_sensornotfound = 6
    def __init__(self):
        self.__error_msgs = {}
        self.__error_msgs [1] = "It can't make backups directory:%s"
        self.__error_msgs [2] = "Invalid Sensor Name: %s"
        self.__error_msgs [3] = "Invalid Port : %s"
        self.__error_msgs [4] = "Invalid flow type: %s, allow valid types(netflow|sflow)"
        self.__error_msgs[5] = "Sensor '%s' already exists."
        self.__error_msgs[6] = "Sensor '%s' not found!"
    def getErrorString(self, nerror, msg):
        errorstring = ""
        if self.__error_msgs.has_key(nerror):
            errorstring = "errno=\"%d\" msg=\"%s\" ackend\n" % (int(nerror), self.__error_msgs[nerror] % msg)
        else:
            errorstring = "errno=\"%d\" msg=\"%s\" ackend\n" % (int(nerror), msg)
        return errorstring

class NfsenSensor():
    def __init__(self, sensorname, port, color, type):
        self.__sensorname = sensorname
        self.__port = port
        self.__color = color
        if type == "" or not type:
            self.__type = 'netflow'
        else:
            self.__type = type

    def checkString(self):
        return "if failed port %s type UDP then restart\n" % self.port
    def get_sensorname(self):
        return self.__sensorname


    def get_port(self):
        return self.__port


    def get_color(self):
        return self.__color


    def get_type(self):
        return self.__type


    def set_sensorname(self, value):
        self.__sensorname = value


    def set_port(self, value):
        self.__port = value


    def set_color(self, value):
        self.__color = value


    def set_type(self, value):
        self.__type = value


    def del_sensorname(self):
        del self.__sensorname


    def del_port(self):
        del self.__port


    def del_color(self):
        del self.__color


    def del_type(self):
        del self.__type

    def __repr__(self):
        return "Sensor: %s port: %s color:%s type:%s" % (self.__sensorname, self.__port, self.__color, self.__type)
    sensorname = property(get_sensorname, set_sensorname, del_sensorname, "sensorname's docstring")
    port = property(get_port, set_port, del_port, "port's docstring")
    color = property(get_color, set_color, del_color, "color's docstring")
    type = property(get_type, set_type, del_type, "type's docstring")
class NfsenPlugin():
    def __init__(self, profiles, module):
        self.__profiles = profiles
        self.__module = module

    def get_profiles(self):
        return self.__profiles


    def get_module(self):
        return self.__module


    def set_profiles(self, value):
        self.__profiles = value


    def set_module(self, value):
        self.__module = value


    def del_profiles(self):
        del self.__profiles


    def del_module(self):
        del self.__module

    def __repr__(self):
        return "Plugin module:%s, profiles: %s" % (self.__module, self.__profiles)
    profiles = property(get_profiles, set_profiles, del_profiles, "profiles's docstring")
    module = property(get_module, set_module, del_module, "module's docstring")
class NfsenMonitConf():
    def __init__(self):
        self.__configurationfile = "/etc/monit/alienvault/nfcapd.monitrc"
        self.__sensorshash = {}
    def setNfsenSensors(self, sensorhash):
        self.__sensorshash = sensorhash
    def write(self):
        #Before do nothing, it do a backup
        backup = Const.BACKUPS_DIR + os.path.basename(self.__configurationfile)
        copy(self.__configurationfile, backup)
        logger.info("Doing backup for file :%s in %s " % (self.__configurationfile, backup))
        fd, tempfile = mkstemp()
        logger.info("Making new monit nfsend config using tmp file: %s" % tempfile)
        newfile = open(tempfile, 'w')
        header = """check process nfsen with pidfile /var/nfsen/run/nfsend.pid
        start program = "/etc/init.d/nfsen restart"
        stop program = "/etc/init.d/nfsen stop\n"""
        newfile.write(header)
        for sensorname, sensor in self.__sensorshash.iteritems():
            checkstr = sensor.checkString()
            newfile.write(checkstr)
        newfile.close()
        copy(tempfile,self.__configurationfile)
        return NfsenErros.Nfsen_ok
    def doRollback(self):
        backup = Const.BACKUPS_DIR + os.path.basename(self.__configurationfile)
        copy(backup, self.__configurationfile)
        return NfsenErros.Nfsen_ok
class NfsenConf():
    def __init__(self, filename):
        self.__monitConf = NfsenMonitConf()
        self.__filename = filename
        self.__sensors = {}
        self.__plugins = {}
        self.__variables = {}
        self.__pluginconf = []
    def addSensor(self, sensor):
        if not self.__sensors.has_key(sensor.get_sensorname()):
            self.__sensors[sensor.get_sensorname()] = sensor
            return True
        return False
    def removeSensor(self, sensorname):
        if not self.__sensors.has_key(sensorname):
            return NfsenErros.Nfsen_error_sensornotfound
        del self.__sensors[sensorname]
        return NfsenErros.Nfsen_ok
    def write(self):
        #Before do nothing, it do a backup
        backup = Const.BACKUPS_DIR + os.path.basename(self.__filename)
        copy(self.__filename, backup)
        logger.info("Doing backup for file :%s in %s " % (self.__filename, backup))
        self.__monitConf.setNfsenSensors(self.__sensors)
        errorn = self.__monitConf.write()
        if errorn != NfsenErros.Nfsen_ok:
            return errorn
        errorn = NfsenErros.Nfsen_ok
        fd, tempfile = mkstemp()
        logger.info("Making new nfsend config using tmp file: %s" % tempfile)
        newfile = open(tempfile, 'w')
        header = """##############################
#
# NfSen master config file
#
# $Id: %s %s %s $
#
# Configuration of NfSen:
# Set all the values to fit your NfSen setup and run the 'install.pl'
# script from the nfsen distribution directory.
#
# The syntax must conform to Perl syntax.
#
##############################
        \n""" % ('nfsen_ossim_config', datetime.now(), "Alienvault")
        newfile.write(header)
        for vname, value in self.__variables.iteritems():
            newfile.write("$%s=%s\n" % (vname.strip(), value))
        sources_headers = """# Netflow sources
# Define an ident string, port and colour per netflow source
#
# Required parameters:
#    ident   identifies this netflow source. e.g. the router name, 
#            Upstream provider name etc.
#    port    nfcapd listens on this port for netflow data for this source
#                        set port to '0' if you do not want a collector to be started
#    col     colour in nfsen graphs for this source
#
# Optional parameters
#    type    Collector type needed for this source. Can be 'netflow' or 'sflow'. Default is netflow
#        optarg  Optional args to the collector at startup
#
# Syntax: 
#         'ident' => { 'port' => '<portnum>', 'col' => '<colour>', 'type' => '<type>' }
# Ident strings must be 1 to 19 characters long only, containing characters [a-zA-Z0-9_].

#%sources = (
#    'upstream1'    => { 'port'    => '9995', 'col' => '#0000ff', 'type' => 'netflow' },
#    'peer1'        => { 'port'    => '9996', 'col' => '#ff0000' },
#);\n"""
        newfile.write(sources_headers)
        sensorstr = "%sources = (\n"
        for sensorname, sensorobj in self.__sensors.iteritems():
            sensorstr += "     '%s'        => { 'port'    => '%s', 'col' => '%s', 'type' => '%s' },\n" % (sensorobj.get_sensorname(), sensorobj.get_port(), sensorobj.get_color(), sensorobj.get_type())
        sensorstr += ");\n"
        newfile.write(sensorstr)
        pluginstr = "@plugins = (\n"
        for pmodule, pluginobj in self.__plugins.iteritems():
            pluginstr += "    [ '%s',     '%s' ],\n" % (pluginobj.get_profiles(), pluginobj.get_module())
        pluginstr += ");\n"
        newfile.write(pluginstr)
        newfile.write("%PluginConf = (")
        for line in self.__pluginconf:
            newfile.write(line)
        newfile.write(");\n")
        newfile.close()
        copy(tempfile,self.__filename)
        return errorn
    def readConf(self):
        config = open(self.__filename, 'r')
        nfsen_variables_hash = {}
        stringvariablepattern = re.compile('^\$(?P<vname>\w+)\s*=\s*[\"\'](?P<vvalue>[a-zA-Z0-9_\-\/\${}\@\.]+)[\"|\'];')
        integervariablepattern = re.compile('^\$(?P<vname>\w+)\s*=\s*(?P<vvalue>\d+);')
        plugin_pattern = re.compile("\s+\[\s*\'(?P<profile>[^']+)\'\s*,\s*\'(?P<module>\S+)\'")
        sensor_pattern = re.compile("\s*'(?P<ident>[a-zA-Z0-9_]{1,19})'\s*=>\s*\{\s*'port'\s*=>\s*'(?P<port>\d+)'\s*,\s*'col'\s*=>\s*'(?P<color>#[a-fA-F0-9]{6})'\s*(,\s*'type'\s*=>\s*'(?P<type>\S+)')?\s*\}")
        data = config.readlines()
        nlines = len(data)
        readedlines = 0
        while readedlines < nlines:
            line = data[readedlines]
            if line.startswith('#'):
                readedlines = readedlines + 1
                continue
            elif line.startswith('$'):
                result = stringvariablepattern.match(line)
                match = False
                if result:
                    self.__variables[result.groupdict()['vname']] = result.groupdict()['vvalue'] 
                    match = True 
                if not match:
                    result = integervariablepattern.match(line)
                    if result:
                        match = True
                        self.__variables[result.groupdict()['vname']] = result.groupdict()['vvalue']
            elif line.startswith('%sources'):
                #Read sensors:
                linebuffer = []
                readedlines = readedlines + 1
                line = data[readedlines]
                while not line.startswith(');'):
                    linebuffer.append(line)
                    readedlines = readedlines + 1
                    result = sensor_pattern.match(line)
                    if result:
                        self.__sensors[result.groupdict()['ident']] = NfsenSensor(result.groupdict()['ident'], result.groupdict()['port'], result.groupdict()['color'], result.groupdict()['type'])
                    line = data[readedlines]
                readedlines = readedlines - 1
            elif line.startswith('@plugins'):
                #Read sensors:
                linebuffer = []
                readedlines = readedlines + 1
                line = data[readedlines]
                match = False
                while not line.startswith(');'):
                    linebuffer.append(line)
                    readedlines = readedlines + 1
                    result = plugin_pattern.match(line)
                    if result:
                        self.__plugins[result.groupdict()['module']] = NfsenPlugin(result.groupdict()['profile'], result.groupdict()['module'])
                    line = data[readedlines]
                readedlines = readedlines - 1
            elif line.startswith('%PluginConf'):
                readedlines = readedlines + 1
                line = data[readedlines]
                match = False
                while not line.startswith(');'):
                    self.__pluginconf.append(line)
                    readedlines = readedlines + 1
                    line = data[readedlines]
                readedlines = readedlines - 1
            readedlines = readedlines + 1;
    def doRollback(self):
        backup = Const.BACKUPS_DIR + os.path.basename(self.__filename)
        copy(backup, self.__filename)
        self.__monitConf.doRollback()
        return NfsenErros.Nfsen_ok
class NfsenManager:
    '''
        Example comands:
        nfsen action="addsensor" sensorname="yoursensor" port="2345" type="netflow" color="#0000ff"\n
        nfsen action="addsensorlist" sensors="sensor1|1234|netflow|#0000ff,sensor1|12434|netflow|#0001ff"\n
        nfsen action="delsensor" sensorname="yoursensor"\n
        nfsen action="stop"\n
        nfsen action="start"\n
        nfsen action="restart"\n
        NOTES: 
        type, Can be 'netflow' or 'sflow'. Default is netflow
        Ident strings must be 1 to 19 characters long only, containing characters [a-zA-Z0-9_].
    '''
    def __init__(self, conf):
        logger.debug("Initialising NFSen Manager.")
        self.__nfsenConfigfile = Const.NFSEN_CONFIG
        self.__nfsen = None
        self.__conf = conf
        self.__nfsenconfloaded = False
        self.__NfsenError = NfsenErros()
        self.__NfsenConf = NfsenConf(self.__nfsenConfigfile)


    def process(self, message):
        response = ""
        if not self.__nfsenconfloaded:
            self.__NfsenConf.readConf()
            self.__nfsenconfloaded = True
        if not os.path.exists(Const.BACKUPS_DIR):
            try:
                os.makedirs(Const.BACKUPS_DIR)
            except OSError, e:
                logger.error("It can't make dirs: %s" % str(e))
                response += self.__NfsenError.getErrorString(NfsenErros.Nfsen_error_makeBackupDirError, str(e))
                return response
        logger.debug("Nfsen Manager: Processing: %s" % message)
        nfsend_restart_required = False
        nfsend_djob = ''
        action = Util.get_var("action=\"([a-z]+)\"", message)
        if action == "addsensor":
            logger.info("Add nfsen sensor")
            sensorname = Util.get_var("sensorname=\"([a-zA-Z0-9_]{1,19})\"", message)
            color = Util.get_var("color=\"(#[a-fA-F0-9_]{6})\"", message)
            port = Util.get_var("port=\"(6553[0-5]|655[0-2]\d|65[0-4]\d\d|6[0-4]\d{3}|[1-5]\d{4}|[1-9]\d{0,3}|0)\"", message)
            type = Util.get_var("type=\"(netflow|sflow)\"", message)
            if sensorname == "":
                response += self.__NfsenError.getErrorString(NfsenErros.Nfsen_error_invalidSensorname, sensorname) 
                return response
            if port == "":
                response += self.__NfsenError.getErrorString(NfsenErros.Nfsen_error_invalidPort, port)
                return response
            if type == "":
                response += self.__NfsenError.getErrorString(NfsenErros.Nfsen_error_invalidSensorname, type)
                return response
            logger.info("Nfsen sensor: %s port:%s type:%s color:%s" % (sensorname, port, type, color))
            nwsensor = NfsenSensor(sensorname, port, color, type)
            if self.__NfsenConf.addSensor(nwsensor):
                errorn = self.__NfsenConf.write()
                if errorn == NfsenErros.Nfsen_ok:
                    nfsend_djob = 'restart'
                    response += ' action="addsensor" sensorname="%s" color="%s" port="%s" type="%s" errno="0" error="Success." ackend\n' % (sensorname, color, port, type)
                else:
                    response += self.__NfsenError.getErrorString(errorn, "")
            else:
                response += ' action="addsensor" sensorname="%s" color="%s" port="%s" type="%s" errno="1" error="Sensor already exists." ackend\n' % (sensorname, color, port, type)
        elif action == "addsensorlist":
            logger.info("Adding sensor list...")
            sensors = Util.get_var('sensors="([^"]+)"', message)
            sensors = sensors.split(',')
            for sensordata in sensors:
                if sensordata != '':
                    data = sensordata.split('|')
                    if len(data) == 4:
                        sensorname = data[0]
                        sensorport = data[1]
                        sensortype = data[2]
                        sensorcolor = data[3]
                        if not re.match("[a-zA-Z0-9_]{1,19}", sensorname):
                            response += self.__NfsenError.getErrorString(NfsenErros.Nfsen_error_invalidSensorname, sensorname)
                            return response
                        if not re.match("(6553[0-5]|655[0-2]\d|65[0-4]\d\d|6[0-4]\d{3}|[1-5]\d{4}|[1-9]\d{0,3}|0)", sensorport):
                            response += self.__NfsenError.getErrorString(NfsenErros.Nfsen_error_invalidPort, sensorport)
                            return response
                        if not re.match("(netflow|sflow)", sensortype):
                            response += self.__NfsenError.getErrorString(NfsenErros.Nfsen_error_invalidSensorname, sensortype)
                            return response
                        if not re.match("(#[a-fA-F0-9_]{6})", sensorcolor):
                            #default color
                            sensorcolor = "#0000FF"
                        newsensor = NfsenSensor(sensorname, sensorport, sensorcolor, sensortype)
                        if not  self.__NfsenConf.addSensor(newsensor):
                            response += self.__NfsenError.getErrorString(NfsenErros.Nfsen_error_duplicateSensorname, sensorname)
                            return response
            errorn = self.__NfsenConf.write()
            if errorn == NfsenErros.Nfsen_ok:
                nfsend_djob = 'restart'
                response += ' action="addsensorlist" errno="0" error="Success." ackend\n'
            else:
                response += self.__NfsenError.getErrorString(errorn, "")
        elif action == "delsensor":
            logger.info("removing sensor")
            sensorname = Util.get_var("sensorname=\"([a-zA-Z0-9_]{1,19})\"", message)
            if sensorname == "":
                response += self.__NfsenError.getErrorString(NfsenErros.Nfsen_error_invalidSensorname, sensorname)
                return response
            errno = self.__NfsenConf.removeSensor(sensorname)
            if errno != NfsenErros.Nfsen_ok:
                response += self.__NfsenError.getErrorString(errno, sensorname)
                return response
            errorn = self.__NfsenConf.write()
            if errorn == NfsenErros.Nfsen_ok:
                nfsend_djob = 'restart'
                response += ' action="delsensor" errno="0" error="Success." ackend\n'
            else:
                response += self.__NfsenError.getErrorString(errorn, "")
        elif action == "delsensorlist":
            logger.info("removing sensor list")
            sensors = Util.get_var('sensors="([^"]+)"', message)
            sensors = sensors.split(',')
            for sensordata in sensors:
                if sensordata != '':
                    if not re.match("[a-zA-Z0-9_]{1,19}", sensordata):
                        response += self.__NfsenError.getErrorString(NfsenErros.Nfsen_error_invalidSensorname, sensorname)
                        return response
                    errno = self.__NfsenConf.removeSensor(sensordata)
                    if errno != NfsenErros.Nfsen_ok:
                        response += self.__NfsenError.getErrorString(errno, sensordata)
                        return response
            errorn = self.__NfsenConf.write()
            if errorn == NfsenErros.Nfsen_ok:
                nfsend_djob = 'restart'
                response += ' action="delsensorlist" errno="0" error="Success." ackend\n'
            else:
                response += self.__NfsenError.getErrorString(errorn, "")
        elif action == "start":
            logger.info("Del nfsen sensor")
            nfsend_djob = 'start'
        elif action == "stop":
            logger.info("Del nfsen sensor")
            nfsend_djob = 'stop'
        elif action == "restart":
            logger.info("Del nfsen sensor")
            nfsend_djob = 'restart'
        else: 
            logger.info("Invalid action for nfsen")

        if nfsend_djob in ['start', 'stop', 'restart']:
            if self.__nfsen == None:
                self.__nfsen = DoNfsen()
                self.__nfsen.start()
            self.__nfsen.dojob(nfsend_djob)

        # send back our response
        return response


class DoNfsen(threading.Thread):

    def __init__(self):
        threading.Thread.__init__(self)
        self.__nfsend_service = "/etc/init.d/nfsen"
        self.__monit_service = "/etc/init.d/monit"

    def run(self):
        while True:
            time.sleep(1)
    def dojob(self, operation):
        if operation == "start" or operation == "restart":
            # catch the process output for logging purposes
            cmd = self.__nfsend_service + " restart"
            process = subprocess.Popen(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, shell=True)
            (pid, exit_status) = os.waitpid(process.pid, 0)
            output = process.stdout.read().strip() + process.stderr.read().strip()
            # show command output if return code indicates error
            if exit_status != 0:
                logger.error(output)
            else:
                logger.info("Nfsen restarted succesfully")
            cmd = self.__monit_service + " restart"
            process = subprocess.Popen(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, shell=True)
            (pid, exit_status) = os.waitpid(process.pid, 0)
            output = process.stdout.read().strip() + process.stderr.read().strip()
            # show command output if return code indicates error
            if exit_status != 0:
                logger.error(output)
            else:
                logger.info("Monit restarted succesfully")
        elif operation == "stop":
            # catch the process output for logging purposes
            cmd = self.__nfsend_service + " stop"
            process = subprocess.Popen(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, shell=True)
            (pid, exit_status) = os.waitpid(process.pid, 0)
            output = process.stdout.read().strip() + process.stderr.read().strip()
            # show command output if return code indicates error
            if exit_status != 0:
                logger.error(output)
            else:
                logger.info("Nfsend stopped succesfully")


def killme(sig, params):
    pid = os.getpid()
    os.kill(pid, signal.SIGTERM)
if __name__ == "__main__":
    signal.signal(signal.SIGINT, killme)
    conf = OssimMiniConf (config_file=Const.CONFIG_FILE)
    nn = NfsenManager(conf)
    #Test addsensor
    #cmd = 'nfsen action="addsensor" sensorname="yoursensor" port="2345" type="netflow" color="#0000ff"\n'
    #print nn.process(cmd)
    #time.sleep(2)
    #Test add duplicate sensor
    #cmd = 'nfsen action="addsensor" sensorname="yoursensor" port="2345" type="netflow" color="#0000ff"\n'
    #print nn.process(cmd)
    # Test add sensor list.
    cmd = 'nfsen action="addsensorlist" sensors="sensor1|777|netflow|#1000ff,sensor2|666|netflow|#0001ff,"\n'
    print nn.process(cmd)
    #Test del sensor
#    cmd = 'nfsen action="delsensor" sensorname="sensor"\n'
#    print nn.process(cmd)
#    cmd = 'nfsen action="delsensor" sensorname="sensor1"\n'
#    print nn.process(cmd)
#    cmd = 'nfsen action="delsensorlist" sensors="sensor1,sensor2"\n'
#    print nn.process(cmd)
    while True:
        time.sleep(1)
