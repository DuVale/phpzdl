#!/usr/bin/env python
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
import time
import signal
import threading
import socket
import codecs
#
# LOCAL IMPORTS
#
from Config import Conf, Plugin, Aliases, CommandLineOptions
from ParserLog import ParserLog
from Watchdog import Watchdog
from Logger import Logger
from Output import Output
from Stats import Stats
from Conn import ServerConn, IDMConn, FrameworkConn
from Exceptions import AgentCritical
from ParserUnifiedSnort import ParserUnifiedSnort
from ParserDatabase import ParserDatabase
from ParserWMI import ParserWMI
from ParserSDEE import ParserSDEE
from ParserRemote import ParserRemote
from ParserUtil import HostResolv
from ParserFtp import ParserFTP
import re
#import pdb
#
# GLOBAL VARIABLES
#
logger = Logger.logger



class Agent:

    def __init__( self ):

        # parse command line options
        self.options = CommandLineOptions().get_options()
        # read configuration
        self.conf = Conf()
        if self.options.config_file:
            conffile = self.options.config_file

        else:
            conffile = self.conf.DEFAULT_CONFIG_FILE

        self.conf.read( [conffile], False )

        # aliases
        aliases = Aliases()
        aliases.read( [os.path.join( os.path.dirname( conffile ), "aliases.cfg" )], False )
        local_aliases_fn = os.path.join( os.path.dirname( conffile ), "aliases.local" )
        #if aliases.local exists, after we've loaded aliases default file, 
        #we load aliases.local
        if os.path.isfile( local_aliases_fn ):
            logger.info( "Reading local aliases file: %s" % local_aliases_fn )
            aliases.read( local_aliases_fn, False )
        # list of plugins and total number of rules within them
        self.plugins = []
        self.nrules = 0

        for name, path in self.conf.hitems( "plugins" ).iteritems():
            if os.path.exists( path ):
                plugin = Plugin()

                #Check if unicode support is needed.
                ff = open ( path, 'r' )
                bom = ff.read( 4 )
                withunicode = False

                if bom.startswith( codecs.BOM_UTF8 ):
                    logger.info( "Plugin configuration file: %s is encoded as utf-8, all regular expressions will be compiled as unicode" % path )
                    withunicode = True
                ff.close()

                # Now read the config file
                plugin.read( path, withunicode )
                if not plugin.get_validConfig():
                    logger.error("Invalid plugin. Please check it :%s"%path)
                    continue

                #check if custom plugin configuration exist
                custompath = "%s.local" % path
                if os.path.exists( custompath ):
                    logger.warning( "Loading custom configuration for plugin: %s" % custompath )
                    custom_plug = Plugin()
                    custom_plug.read( custompath, withunicode, False )
                    for item in custom_plug.hitems("DEFAULT"):
                        new_value = custom_plug.get("DEFAULT", item)
                        old_value = plugin.get("DEFAULT", item)
                        if new_value != old_value:
                            plugin.set( "DEFAULT", item, new_value)
                            logger.warning( "Loading custon value for %s--->%s. New value: %s - Old value: %s" % ( "DEFAULT", item, new_value, old_value ) )
                    for section in custom_plug.sections():
                        for item in custom_plug.hitems(section):
                            if not plugin.has_section(section):
                                plugin.add_section(section)
                            new_value = custom_plug.get(section, item)
                            if plugin.has_section(section):
                                old_value = plugin.get(section, item)
                            else:
                                old_value = ""

                            if new_value != old_value:
                                plugin.set( section, item, new_value )
                                logger.warning( "Loading custon value for %s--->%s. New value: %s - Old value: %s" % ( section, item, new_value, old_value ) )
                self.nrules += len( plugin.sections() ) \
                               - plugin.sections().count( 'translation' ) \
                               - 1 # [config]

                plugin.set( "config", "name", name )
                plugin.set( "config", "unicode_support", str( withunicode ) )
                plugin.replace_aliases( aliases )
                plugin.replace_config( self.conf )
                self.plugins.append( plugin )
                self.nrules += len( plugin.sections() ) \
                               - plugin.sections().count( 'translation' ) \
                               - 1 # [config]
                if plugin.has_option('config','custom_functions_file'):
                    self.__readCustomPluginFunctions(plugin,plugin.get('config','custom_functions_file'))
            else:
                logger.error( "Unable to read plugin configuration (%s) at (%s)" % ( name, path ) )

        HostResolv.loadHostCache()
        self.detector_objs = []
        self.watchdog = None
        self.shutdown_running = False
        self.__outputServerConneciton = None
        self.__outputIDMConnection = None
        self.__frameworkConnection = None
        self.__keep_working = True
        self.__checkThread = None
        self.__stop_server_counter = 9999
        self.__pluginStopEvent = threading.Event()

    def __readCustomPluginFunctions(self,plugin, custom_plugin_functions_file):
        pid = plugin.get("DEFAULT", "plugin_id")
        logger.info("Loading custom plugin functions for pid: %s" % pid)
        f = open(custom_plugin_functions_file, 'rb')
        lines = f.read()
        result = re.findall("Start Function\s+(\w+)\n(.*?)End Function", lines, re.M | re.S)
        function_list = {}
        for name,function in result:
            logger.info("Loading function: %s" % name)
            try:
                exec function.strip() in function_list
                function_name = "%s_%s" % (name,pid)
                logger.info("Adding function :%s" % function_name)
                setattr(Plugin, function_name, function_list[name])
            except Exception,e:
                logger.error("Custom function error: %s" % str(e))




    def setShutDownRunning( self, value ):
        self.shutdown_running = value


    def getShutDownRunning( self ):
        return self.shutdown_running


    def init_logger( self ):
        """Initiate the logger. """

        # open file handlers (main and error logs)
        if self.conf.has_option( "log", "file" ):
            Logger.add_file_handler( self.conf.get( "log", "file" ) )

        if self.conf.has_option( "log", "error" ):
            Logger.add_error_file_handler( self.conf.get( "log", "error" ) )

        if self.conf.has_option( "log", "syslog" ):
            if ( self.conf.get( "log", "syslog" ) ):
                Logger.add_syslog_handler( ( self.conf.get( "log", "syslog" ), 514 ) )

        # adjust verbose level
        verbose = self.conf.get( "log", "verbose" )
        if self.options.verbose is not None:
            # -v or -vv command line argument
            #  -v -> self.options.verbose = 1
            # -vv -> self.options.verbose = 2
            for i in range( self.options.verbose ):
                verbose = Logger.next_verbose_level( verbose )

        Logger.set_verbose( verbose )


    def init_stats( self ):
        '''
            Initialize Stats 
        '''
        Stats.startup()

        if self.conf.has_section( "log" ):
            if self.conf.has_option( "log", "stats" ):
                Stats.set_file( self.conf.get( "log", "stats" ) )


    def init_output( self ):
        '''
            Initialize Outputs
        '''

        printEvents = True

        if self.conf.has_section( "output-properties" ):
            printEvents = self.conf.getboolean( "output-properties", "printEvents" )
        Output.print_ouput_events( printEvents )


        if self.conf.has_section( "output-plain" ):
            if self.conf.getboolean( "output-plain", "enable" ):
                Output.add_plain_output( self.conf )

        # output-server is enabled in connect_server()
        # if the connection becomes availble

        if self.conf.has_section( "output-csv" ):
            if self.conf.getboolean( "output-csv", "enable" ):
                Output.add_csv_output( self.conf )

        if self.conf.has_section( "output-db" ):
            if self.conf.getboolean( "output-db", "enable" ):
                Output.add_db_output( self.conf )
        
    def connect_framework( self ):
        '''
            Connect to framewokd
        '''

        frmk_tmp_id, frmk_tmp_ip, frmk_tmp_port = self.__outputServerConneciton.get_framework_data()
        tryConnect = False
        if self.__frameworkConnection is None:
            self.__frameworkConnection = FrameworkConn( self.conf, frmk_tmp_id, frmk_tmp_ip, frmk_tmp_port )
        elif not self.__frameworkConnection.frmk_alive():
            tryConnect = True

        if tryConnect:
            if self.__frameworkConnection.connect( attempts = 3, waittime = 30 ):
                logger.info( "Control Framework (%s:%s) is now enabled!" % ( frmk_tmp_ip, frmk_tmp_port ) )
                self.__frameworkConnection.frmk_control_messages()


    def check_pid( self ):
        """Check if a running instance of the agent already exists. """

        pidfile = self.conf.get( "daemon", "pid" )

        # check for other ossim-agent instances when not using --force argument
        if self.options.force is None and os.path.isfile( pidfile ):
            raise AgentCritical( "There is already a running instance" )

        # remove ossim-agent.pid file when using --force argument
        elif os.path.isfile( pidfile ):
            try:
                os.remove( pidfile )

            except OSError, e:
                logger.warning( e )


    def createDaemon( self ):
        """Detach a process from the controlling terminal and run it in the
        background as a daemon.

        Note (DK): Full credit for this daemonize function goes to Chad J. Schroeder.
        Found it at ASPN http://aspn.activestate.com/ASPN/Cookbook/Python/Recipe/278731
        Please check that url for useful comments on the function.
        """

        # Install a handler for the terminate signals
        signal.signal( signal.SIGTERM, self.terminate )

        # -d command-line argument
        if self.options.daemon:
            self.conf.set( "daemon", "daemon", "True" )

        if self.conf.getboolean( "daemon", "daemon" ) and \
            self.options.verbose is None:
            logger.info( "Forking into background.." )

            UMASK = 0
            WORKDIR = "/"
            MAXFD = 1024
            REDIRECT_TO = "/dev/null"

            if ( hasattr( os, "devnull" ) ):
                REDIRECT_TO = os.devnull

            try:
                pid = os.fork()

            except OSError, e:
                raise Exception, "%s [%d]" % ( e.strerror, e.errno )
                sys.exit( 1 )

            # check if we are the first child
            if ( pid == 0 ):
                os.setsid()

                # attempt to fork a second child
                try:
                    pid = os.fork()   # Fork a second child.

                except OSError, e:
                    raise Exception, "%s [%d]" % ( e.strerror, e.errno )
                    sys.exit( 1 )

                # check if we are the second child
                if ( pid == 0 ):
                    os.chdir( WORKDIR )
                    os.umask( UMASK )

                # otherwise exit the parent (the first child of the second child)
                else:
                    open( self.conf.get( "daemon", "pid" ), 'w' ).write( "%d" % pid )
                    os._exit( 0 )

            # otherwise exit the parent of the first child
            else:
                os._exit( 0 )

            import resource         # Resource usage information.
            maxfd = resource.getrlimit( resource.RLIMIT_NOFILE )[1]
            if ( maxfd == resource.RLIM_INFINITY ):
                maxfd = MAXFD

            for fd in range( 0, maxfd ):
                try:
                    os.close( fd )

                except OSError:      # ERROR, fd wasn't open to begin with (ignored)
                    pass

            os.open( REDIRECT_TO, os.O_RDWR ) # standard input (0)
            os.dup2( 0, 1 )                   # standard output (1)
            os.dup2( 0, 2 )                   # standard error (2)
            return( 0 )


    def init_plugins( self ):

        for plugin in self.plugins:
            if plugin.get( "config", "type" ) == "detector":
                if plugin.get( "config", "source" ) == "log":
                    parser = ParserLog( self.conf, plugin, None )
                    parser.start()
                    self.detector_objs.append( parser )

                elif plugin.get( "config", "source" ) == "snortlog":
                    parser = ParserUnifiedSnort( self.conf, plugin, None )
                    parser.start()
                    self.detector_objs.append( parser )

                elif plugin.get( "config", "source" ) == "database":
                    parser = ParserDatabase( self.conf, plugin, None )
                    parser.start()
                    self.detector_objs.append( parser )

                elif plugin.get( "config", "source" ) == "wmi":
                    #line_cnt = 0
                    try:
                        credentials = open( plugin.get( "config", "credentials_file" ), "rb" )
                    except:
                        logger.warning( "Unable to load wmi credentials file %s, disabling wmi collection." % ( plugin.get( "config", "credentials_file" ) ) )
                        plugin.set( "config", "enable", "no" )
                        continue
                    for row in credentials:
                        creds = row.split( "," )
                        # TODO: Check for shell escape chars in host, user and pass that could break this
                        parser = ParserWMI( self.conf, plugin, None, creds[0], creds[1], creds[2] )
                        parser.start()
                        self.detector_objs.append( parser )

                elif plugin.get( "config", "source" ) == "sdee":
                    try:
                        credentials = open( plugin.get( "config", "credentials_file" ), "rb" )
                    except:
                        logger.warning( "Unable to load sdee credentials file, falling back to old behaviour" )
                        parser = ParserSDEE( self.conf, plugin, None )
                        parser.start()
                        self.detector_objs.append( parser )
                    else:
                        for row in credentials:
                            creds = row.split( "," )
                            # TODO: Check for shell escape chars in host, user and pass that could break this
                            parser = ParserSDEE( self.conf, plugin, None, creds[0], creds[1], creds[2].rstrip() )
                            parser.start()
                            self.detector_objs.append( parser )

                elif plugin.get( "config", "source" ) == "remote-log":
                    parser = ParserRemote( self.conf, plugin, None )
                    logger.info( "Starting remote ssh parser" )
                    parser.start()
                    self.detector_objs.append( parser )
                elif plugin.get( "config", "source" ) == "ftp":
                    parser = ParserFTP(self.conf,plugin,None)
                    parser.start()
                    self.detector_objs.append(parser)
            
        logger.info( "%d detector rules loaded" % ( self.nrules ) )


    def init_watchdog( self ):
        '''
            Starts Watchdog thread
        '''
        if self.conf.getboolean( "watchdog", "enable" ):
            self.watchdog = Watchdog( self.conf, self.plugins )
            self.watchdog.start()


    def terminate( self, sig, params ):
        '''
            Handle terminate signal
        '''
        if not self.getShutDownRunning():
            logger.info( "WARNING: Shutdown received! - Processing it ...!" )
            self.shutdown()
        else:
            logger.info( "WARNING: Shutdown received! - We can't process it because another shutdonw process is running!" )


    def shutdown( self ):
        '''
            Handles shutdown signal. Stop all threads, plugist, closes connections...
        '''
        #Disable Ctrl+C signal.
        signal.signal( signal.SIGINT, signal.SIG_IGN )
        logger.info( "Shutdown in process..." )
        self.setShutDownRunning( True )
        Watchdog.setShutdownRunning( True )
        self.__keep_working = False
        self.__pluginStopEvent.set()
        logger.info( "Waiting for check thread.." )
        if self.__checkThread is not None:
            self.__checkThread.join(1)
        # Remove the pid file
        pidfile = self.conf.get( "daemon", "pid" )
        if os.path.exists( pidfile ):
            f = open( pidfile )
            pid_from_file = f.readline()
            f.close()

            try:
                # don't remove the ossim-agent.pid file if it 
                # belongs to other ossim-agent process
                if pid_from_file == str( os.getpid() ):
                    os.remove( pidfile )

            except OSError, e:
                logger.warning( e )


        # output plugins
        Output.shutdown()

        # parsers
        for parser in self.detector_objs:
            if hasattr( parser, 'stop' ):
                parser.stop()
        #Stop server connection.
        if self.__outputServerConneciton is not None:
            self.__outputServerConneciton.close()
        #Stop IDM connection
        if self.__outputIDMConnection is not None:
            self.__outputIDMConnection.close()
        #Stop framework connection
        if self.__frameworkConnection is not None:
            self.__frameworkConnection.close()
        # execution statistics        
        Stats.shutdown()
        if Stats.dates['startup']:
            Stats.stats()
        # Watchdog
        if self.watchdog:
            self.watchdog.shutdown()
        self.setShutDownRunning( False )


    def waitforever( self ):
        '''
            Wait forever agent loop
        '''
        timer = 0

        while self.__keep_working:
            time.sleep(1)
            timer += 1

            if timer >= 30:
                Stats.log_stats()
                timer = 0


    def __readOuptutServer( self ):
        ''' Read the ouptput server list, if exists'''
        if self.conf.has_section( "output-server" ):
            if self.conf.getboolean( "output-server", "enable" ):
                server_ip = self.conf.get( "output-server", "ip" )
                server_port = self.conf.get( "output-server", "port" )
                server_priority = 0
                allow_frmk_data = True
                sendEvents = True
                framework_data = False
                framework_ip = ""
                framework_port = 0
                framework_hostname = ""
                if self.conf.has_section( "control-framework" ):
                    framework_data = True
                    framework_ip = self.conf.get( "control-framework", "ip" )
                    framework_port = self.conf.get( "control-framework", "port" )
                    framework_hostname = socket.gethostname()
                self.__outputServerConneciton = ServerConn( server_ip, server_port, server_priority, allow_frmk_data, sendEvents, self.plugins, self.__pluginStopEvent )
                Output.add_server_output(self.__outputServerConneciton)
                if framework_data:
                    self.__outputServerConneciton.set_framework_data( framework_hostname, \
                                                           framework_ip, \
                                                           framework_port )
    def __readOutputIDM( self ):
        if self.conf.has_section( "output-idm" ):
            if self.conf.getboolean( "output-idm", "enable" ):
                idm_ip = self.conf.get( "output-idm", "ip" )
                idm_port = self.conf.get( "output-idm", "port" )
                logger.info("IDM conn: %s:%s" %(idm_ip,idm_port))
                self.__outputIDMConnection = IDMConn( idm_ip, idm_port )
                Output.add_idm_output(self.__outputIDMConnection)

    def __check_server_status( self ):
        '''
            Check if there is any server, with the max priority (temporal priority),  alive.
            If yes and the temporal priority  is greater than current priority, we've to change the priority, if no, we do nothing
        '''
        #Default values
        timeBeetweenChecks = 2.0
        maxStopCounter = 2.0
        #poolInterval = 15.0
        if self.conf.has_section( "output-properties" ):
            if self.conf.get( "output-properties", "timeBeetweenChecks" ) != "":
                try:
                    timeBeetweenChecks = float( self.conf.get( "output-properties", "timeBeetweenChecks" ) )
                except ValueError:
                    timeBeetweenChecks = 2.0
            if self.conf.get( "output-properties", "maxStopCounter" ) != "":
                try:
                    maxStopCounter = float( self.conf.get( "output-properties", "maxStopCounter" ) )
                except ValueError:
                    maxStopCounter = 5.0

        logger.info( "Check status configuration: Time between checks: %s - max stop counter: %s" % ( timeBeetweenChecks, maxStopCounter ) )
        #priority 0,1,2,3,4,5
        while self.__keep_working:
            if self.__outputIDMConnection is not None and not self.__outputIDMConnection.get_is_alive():
                self.__outputIDMConnection.connect()
            if not self.__outputServerConneciton.get_is_alive():
                self.__stop_server_counter +=1
                
            
            if self.__stop_server_counter >= maxStopCounter:
                logger.info( "Server %s:%s has reached %s stops, trying to reconnect!" % ( self.__outputServerConneciton.get_server_ip(), self.__outputServerConneciton.get_server_port(), maxStopCounter ) )
                self.__outputServerConneciton.connect( attempts = 3, waittime = 10 )
                Stats.server_reconnect( self.__outputServerConneciton.get_server_ip() )
                self.__stop_server_counter = 0
                if self.__keep_working:
                    time.sleep(3)
            if self.__outputServerConneciton.get_is_alive():
                self.connect_framework()
            if self.__keep_working:
                time.sleep( timeBeetweenChecks )
        logger.info("Check thread finish!")
            


    def main( self ):
        try:
            self.check_pid()
            self.createDaemon()
            self.init_logger()
            self.init_output()
            self.init_stats()
            self.__readOuptutServer()
            self.__readOutputIDM()
            self.__checkThread = threading.Thread( target = self.__check_server_status, args = () )
            self.__checkThread.start()
            self.init_plugins()
            self.init_watchdog()
            self.waitforever()

        except KeyboardInterrupt:
            if not self.getShutDownRunning() :
                logger.info( "WARNING! Ctrl+C received! shutting down" )
                self.shutdown()
            else:
                logger.info( "WARNING! Ctrl+C received! Shutdown signal ignored -- Another shutdown process running." )

        except AgentCritical, e:
            logger.critical( e )
            if not self.getShutDownRunning():
                self.shutdown()
                logger.info( "WARNING! Exception captured, shutdowning!" )
            else:
                logger.info( "WARNING! Exception captured! Shutdown signal ignored -- Another shutdown process running" )

        except Exception, e:
            logger.error( "Unexpected exception: " + str( e ) )

            # print trace exception
            import traceback
            traceback.print_exc()

            # print to error.log too
            if self.conf.has_option( "log", "error" ):
                fd = open( self.conf.get( "log", "error" ), 'a+' )
                traceback.print_exc( file = fd )
                fd.close()


if __name__ == "__main__":
#    sys.setcheckinterval(-1)
    a = Agent()
    a.main()

    print "Bye!"
    pid = os.getpid()
    os.kill(pid, signal.SIGKILL)

# vim:ts=4 sts=4 tw=79 expandtab:
