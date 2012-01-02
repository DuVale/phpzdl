#!/usr/bin/python
#
# License:
#
#    Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2010 AlienVault
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
import threading 
import re
import socket
import base64


#
# LOCAL IMPORTS
#
from ActionMail import ActionMail
from ActionExec import ActionExec
from ActionSyslog import *
from Logger import Logger
from OssimConf import OssimConf
from OssimDB import OssimDB
import Const
import Util
import re
#
# GLOBAL VARIABLES
#
logger = Logger.logger

class Action(threading.Thread):
    base64field=["username", \
                 "password","filename", \
                 "userdata1","userdata2","userdata3", \
                 "userdata4","userdata5","userdata6", \
                 "userdata7","userdata8","userdata9"] 

    def __init__(self, request):

        self.__request = self.parseRequest(request)
        self.__responses = {}
        self.__conf = OssimConf(Const.CONFIG_FILE)
        self.__db = OssimDB()
        threading.Thread.__init__(self)


    def parseRequest(self, request):
        '''
             build a hash with the request info
        '''
        #
        # request example:
        #
        # event date="2005-06-16 13:06:18" plugin_id="1505" plugin_sid="4"
        # risk="8" priority="4" reliability="10" event_id="297179"
        # backlog_id="13948" src_ip="192.168.1.10" src_port="1765"
        # dst_ip="192.168.1.11" dst_port="139" protocol="6"
        # sensor="192.168.6.64"
        request_hash = {}

        try:
            request_hash['type'] = request.split()[0]
        except IndexError:
            request_hash['type'] = 'unknown'
            logger.warning("Sorry, unknown request type received: %s" % request)
            return {}

        result = re.findall('(\w+)="([^"]+)"', request)
        for i in result:
            if i[0] in Action.base64field:
                try:
                    request_hash[i[0]] = base64.b64decode(i[1])
                except TypeError:
                    logger.warning("Field not in base64: %s = %s" %(i[0],i[1]))
                    request_hash[i[0]] = i[1]
            else:
                request_hash[i[0]] = i[1]
        return request_hash


    def getIpsByNet(self, netname):
        '''
        response-actions stores net names
        '''
        query = "SELECT ips FROM net WHERE name = '%s'" % (netname)
        net_info = self.__db.exec_query(query)

        ips = 'ANY'
        for net in net_info:
            ips = net['ips']

        return ips


    def getActions(self,id):
        '''
        get matched actions from db
        '''

        actions = []

        #
        # ANY: for strings  :'ANY'
        #      for integers : 0
        #

        query = "SELECT action_id FROM policy_actions " +\
                        "WHERE policy_id = %d" % (int(id))
        action_info = self.__db.exec_query(query)

        for action in action_info:
            action_id = action['action_id']
            if actions.count(action_id) == 0:
                actions.append(action_id)

        return actions


    def getResponses(self):
        '''
        fill responses hash with all response db info
        '''
        responses = self.__db.exec_query("SELECT * FROM response")
        for response in responses:
            for item in ("net", "plugin", "port", "action"):
                response[item] = self.__db.exec_query(
                    "SELECT * FROM response_%s WHERE response_id = %d" %\
                        (item, response["id"]))
                response["host"] = self.__db.exec_query(
                    "SELECT * FROM response_host WHERE response_id = %d and _type <> 'sensor'" %\
                        (response["id"]))
            response["sensor"] = self.__db.exec_query(
                    "SELECT %d as response_id, sensor.ip as host, 'sensor' as _type FROM sensor,response_host WHERE response_host.response_id = %d and _type = 'sensor' and response_host.host = sensor.name" %\
                        (response["id"], response["id"]))

        # ensure int datatype for plugin ids and ports
        # NOTE response["plugin"] and response["port"] are not string type
        # they are arrays
        # for item in ("plugin", "port"):
        # response[item] = int(response[item])

        return responses


    def requestRepr(self, request):
        
        temp_str  = " Alert detail: \n"
        for key, value in request.iteritems():
            temp_str += " * %s: \t%s\n" % (key, value)
        return temp_str


    def doAction(self, action_id):

        replaces = {
                'DATE':         self.__request.get('date', ''),
                'PLUGIN_ID':    self.__request.get('plugin_id', ''),
                'PLUGIN_SID':   self.__request.get('plugin_sid', ''),
                'RISK':         self.__request.get('risk', ''),
                'PRIORITY':     self.__request.get('priority', ''),
                'RELIABILITY':  self.__request.get('reliability', ''),
                'SRC_IP':       self.__request.get('src_ip', ''),
                'SRC_PORT':     self.__request.get('src_port', ''),
                'DST_IP':       self.__request.get('dst_ip', ''),
                'DST_PORT':     self.__request.get('dst_port', ''),
                'PROTOCOL':     self.__request.get('protocol', ''),
                'SENSOR':       self.__request.get('sensor', ''),
                'PLUGIN_NAME':  self.__request.get('plugin_id', ''),
                'SID_NAME':     self.__request.get('plugin_sid', ''),
                'USERDATA1':    self.__request.get('userdata1', ''),
                'USERDATA2':    self.__request.get('userdata2', ''),
                'USERDATA3':    self.__request.get('userdata3', ''),
                'USERDATA4':    self.__request.get('userdata4', ''),
                'USERDATA5':    self.__request.get('userdata5', ''),
                'USERDATA6':    self.__request.get('userdata6', ''),
                'USERDATA7':    self.__request.get('userdata7', ''),
                'USERDATA8':    self.__request.get('userdata8', ''),
                'USERDATA9':    self.__request.get('userdata9', ''),
                'FILENAME':     self.__request.get('filename', ''),
                'USERNAME':     self.__request.get('username', ''),
                'PASSWORD':     self.__request.get('password', ''),
                'BACKLOG_ID':   self.__request.get('backlog_id', ''),
                'EVENT_ID':     self.__request.get('event_id', ''),
            }

        query = "SELECT * FROM plugin WHERE id = %d" % int(self.__request['plugin_id'])

        for plugin in self.__db.exec_query(query):
            # should only yield one result anyway
            replaces["PLUGIN_NAME"] = plugin['name']

        query = "SELECT * FROM plugin_sid WHERE plugin_id = %d AND sid = %d" %\
            (int(self.__request['plugin_id']), int(self.__request['plugin_sid']))
        for plugin_sid in self.__db.exec_query(query):
            # should only yield one result anyway
            replaces["SID_NAME"] = plugin_sid['name']

        query = "SELECT * FROM action WHERE id = %d" % (action_id)
        for action in self.__db.exec_query(query):

            logger.info("Successful Response with action: %s" % action['descr'])
            ####################################################################
            # Condition
            ####################################################################

            # get the condition expression
            condition = action['cond']

            # authorized operators
            operators = [
                "+", "-", "*", "/", "%",
                "==", "<=", "<", ">=", ">",
                " and ", " or ", "(", ")", 
                " True ", "False"
            ]

            # only operators and characters in [A-Z0-9_ ]
            condition_tmp = " %s " % condition
            for operator in operators:
                condition_tmp = condition_tmp.replace(operator, " ")
            if not re.match("^[A-Z0-9_ ]+$", condition_tmp):
                logger.warning( ": Illegal character in condition: %s" % condition)
                condition = "False"

            # no function call
            if re.search("[A-Z0-9_]+\s*\(", condition):
                logger.warning(": Illegal function call in condition: %s" % condition)
                condition = "False"

            # replacements
            for key in replaces:
                condition = condition.replace(key, replaces[key])

            # condition evaluation
            try:
                logger.debug(": condition = '%s'" % condition)
                condition = eval(condition)
            except Exception, e:
                logger.debug( ": Condition evaluation failed: %s --> %s" % (condition,str(e)))
                condition = False
            logger.debug( ": eval(condition) = %s" % condition)

            # is the condition True?
            if not condition: continue

            # is the action based on risk increase?
            if int(action['on_risk']) == 1:

                backlog_id = int(self.__request.get('backlog_id', ''))
                risk_old = 0
                risk_new = int(self.__request.get('risk', ''))

                # get the old risk value
                query = "SELECT * FROM action_risk WHERE action_id = %d AND backlog_id = %d" % (int(action_id), int(backlog_id))
                for action_risk in self.__db.exec_query(query):
                    # should only yield one result anyway
                    risk_old = int(action_risk['risk'])
                    break
                else:
                    query = "INSERT INTO action_risk VALUES (%d, %d, %d)" % (
                        int(action_id), int(backlog_id), int(risk_new))
                    logger.debug( ": %s" % query)
                    self.__db.exec_query(query)

                # is there a risk increase?
                logger.debug( ": risk_new > risk_old = %s" % (risk_new > risk_old))
                if risk_new <= risk_old: continue

                # save the new risk value
                query = "UPDATE action_risk SET risk = %d WHERE action_id = %d AND backlog_id = %d" % (
                    int(risk_new), int(action_id), int(backlog_id))
                logger.debug( ": %s" % query)
                self.__db.exec_query(query)

                # cleanup the action_risk table
                query = "DELETE FROM action_risk WHERE backlog_id NOT IN (SELECT id FROM backlog)"
                logger.debug(": %s" % query)
                self.__db.exec_query(query)

            ####################################################################

            # email notification
            if action['action_type'] == 'email':
                query = "SELECT * FROM action_email WHERE action_id = %d" %\
                    (action_id)
                for action_email in self.__db.exec_query(query):
                    email_from = action_email['_from']
                    email_to = action_email['_to'].split(',')
                    email_subject = action_email['subject']
                    email_message = action_email['message']

                    for replace in replaces:
                        if replaces[replace]:
                            email_from = email_from.replace(replace, replaces[replace])
                            for to_mail in email_to:
                                to_mail = to_mail.strip()
                                to_mail = to_mail.replace(replace,\
                                                          replaces[replace])
                            replace_variable=r'\b%s\b'%replace
                            email_subject = re.sub(replace_variable,replaces[replace],email_subject)
                            #email_subject= email_subject.replace(replace, replaces[replace])
                            email_message = re.sub(replace_variable,replaces[replace],email_message)
                            #email_message = email_message.replace(replace, replaces[replace])
                    m = ActionMail()
                    m.sendmail(email_from,
                               email_to,
                               email_subject,
                               email_message +\
                               "\n\n" + self.requestRepr(self.__request))
                    del(m)
                

            # execute external command
            elif action['action_type'] == 'exec':
                query = "SELECT * FROM action_exec WHERE action_id = %d" %\
                    (action_id)
                for action_exec in self.__db.exec_query(query):
                    action = action_exec['command']
                    for replace in replaces:
                        action = action.replace(replace, replaces[replace])
                    c = ActionExec()
                    c.execCommand(action)
                    del(c)

            elif action['action_type'] == 'syslog':
                syslog(self.__request) 
            elif action['action_type'] == 'ticket':
                descr = action['descr']
                plugin_id = int(self.__request.get('plugin_id', ''))
                plugin_sid = int(self.__request.get('plugin_sid', ''))
                title = 'Automatic Incident Ticket'
                namequery ="select if((select name from plugin_sid where plugin_id='%s' and sid='%s')!='',(select name from plugin_sid where plugin_id='%s' and sid='%s')  , 'Automatic Incident Ticket') as name;" % (plugin_id,plugin_sid,plugin_id,plugin_sid)
                data = self.__db.exec_query(namequery)
                if data !=[]:
                    title = data[0]['name']
                regexp = re.compile('(?P<data>.*)##@##(?P<username>.*)')
                matches = regexp.search(descr)
                in_charge = 'admin'
                descr=''
                if matches:
                    in_charge = matches.group('username')
                    descr = matches.group('data')
                get_last_id_query = "select max(id) as id from incident;"
                data = self.__db.exec_query(get_last_id_query)
                if data != []:
                    last_id = data[0]['id'] +1
                    priority = int(self.__request.get('priority', '')) *2
                    insert_query = """insert into incident (id,title,date,ref,type_id,priority,status,last_update,in_charge,submitter,event_start,event_end) values ('%s','%s',now(),'Event','Generic','%s','Open',now(),'%s','admin',now(),now()); """ % (last_id,title,priority,in_charge)
                    self.__db.exec_query(insert_query)
                    logger.debug("Query: %s" % insert_query)
                    get_last_id_query = "select max(id) as id from incident_event;"
                    data = self.__db.exec_query(get_last_id_query)
                    if data != []:
                        src_ip = self.__request.get('src_ip', '')
                        src_port = int(self.__request.get('src_port', ''))
                        dst_ip = self.__request.get('dst_ip', '')
                        dst_port = int(self.__request.get('dst_port', ''))
                        last_id_ie = data[0]['id'] +1
                        insert_incident_event_query = """ insert into incident_event (id,incident_id,src_ips,src_ports,dst_ips,dst_ports) values ('%s','%s','%s','%s','%s','%s'); """ % (last_id_ie,last_id,src_ip,src_port,dst_ip,dst_port) 
                        self.__db.exec_query(insert_incident_event_query)
                        logger.debug("Query2:_ %s" % insert_incident_event_query)
                else:
                    logger.error("Can't insert incident because it can't be retrieved the max id.")
            else:
                logger.error("Invalid action_type: '%s'" % action['action_type'])
    def mailNotify(self):
        '''
        Notify every alarm if email_alert is set
        '''
        email = self.__conf['email_alert']
        emails = self.__conf['email_sender']
        if emails is None or emails == "":
            emails = "ossim@localhost"

        if email is not None and email != "":

            m = ActionMail()
            m.sendmail( self.__conf['email_sender'] , [ self.__conf['email_alert'] ],
                       "Ossim Alert from server '%s'" % (socket.gethostname()),
                       self.requestRepr(self.__request))
            logger.info("Notification sent from %s to %s" % (emails, (self.__conf['email_alert'])))


    def run(self):

        if self.__request != {}:
            
            try:
                if int(self.__request['actions']) < 1:
                    return
            except Exception,e:
                logger.error("Error %s" %str(e))
                return

            if self.__request['type'] == "event":
                self.mailNotify()

            self.__db.connect(self.__conf['ossim_host'],
                              self.__conf['ossim_base'],
                              self.__conf['ossim_user'],
                              self.__conf['ossim_pass'])

            actions = self.getActions(self.__request['policy_id'])
            
            for action in actions:
                self.doAction(action)

            self.__db.close()

# vim:ts=4 sts=4 tw=79 expandtab:
