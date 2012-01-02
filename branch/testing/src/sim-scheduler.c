/*
License:

   Copyright (c) 2003-2006 ossim.net
   Copyright (c) 2007-2009 AlienVault
   All rights reserved.

   This package is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; version 2 dated June, 1991.
   You may not use, modify or distribute this program under any other version
   of the GNU General Public License.

   This package is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this package; if not, write to the Free Software
   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
   MA  02110-1301  USA


On Debian GNU/Linux systems, the complete text of the GNU General
Public License can be found in `/usr/share/common-licenses/GPL-2'.

Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
*/

#include <unistd.h>

#include "os-sim.h"
#include "sim-scheduler.h"
#include "sim-container.h"
#include "sim-config.h"
#include "sim-directive.h"
#include "sim-command.h"
#include "sim-server.h"
#include "sim-session.h"
#include <config.h>
#include "sim-enums.h"

extern SimMain  ossim;
extern int  aux_received_messages;
#define TIME_LAPSE 10
#define MYSQL_NO_ROW_MODIFICATION_ERROR 1065
enum 
{
  DESTROY,
  LAST_SIGNAL
};

/* FIXME: this struct is not used anywhere*/
struct SimSchedulerTask {
  gint     id;
  gchar   *name;
  gint     timer;
};
/**/


struct _SimSchedulerPrivate {
  SimConfig      *config;

  gint            timer;

  GList          *tasks;
};

static gpointer parent_class = NULL;
static gint sim_container_signals[LAST_SIGNAL] = { 0 };

static time_t       last = 0;
static time_t       timer = 0;

//the following used in sim_scheduler_task_store_event_number_at_5min()
static time_t       event_last = 0;
static time_t       event_timer = 300; //FIXME: A variable in config may be more friendly than this...

//used in sim_scheduler_show_stats()
static time_t       db_last = 0;
static guint        events_before = 0;
static guint        sim_before = 0;
/* GType Functions */

static void 
sim_scheduler_impl_dispose (GObject  *gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void 
sim_scheduler_impl_finalize (GObject  *gobject)
{
  SimScheduler *sch = SIM_SCHEDULER (gobject);

  g_free (sch->_priv);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_scheduler_class_init (SimSchedulerClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_peek_parent (class);

  object_class->dispose = sim_scheduler_impl_dispose;
  object_class->finalize = sim_scheduler_impl_finalize;
}

static void
sim_scheduler_instance_init (SimScheduler *scheduler)
{
  scheduler->_priv = g_new0 (SimSchedulerPrivate, 1);

  scheduler->_priv->config = NULL;

  scheduler->_priv->timer = 30;

  scheduler->_priv->tasks = NULL;
}

/* Public Methods */

GType
sim_scheduler_get_type (void)
{
  static GType object_type = 0;
 
  if (!object_type)
  {
    static const GTypeInfo type_info = {
              sizeof (SimSchedulerClass),
              NULL,
              NULL,
              (GClassInitFunc) sim_scheduler_class_init,
              NULL,
              NULL,                       /* class data */
              sizeof (SimScheduler),
              0,                          /* number of pre-allocs */
              (GInstanceInitFunc) sim_scheduler_instance_init,
              NULL                        /* value table */
    };
    
    g_type_init ();
                                                                                                                             
    object_type = g_type_register_static (G_TYPE_OBJECT, "SimScheduler", &type_info, 0);
  }
                                                                                                                             
  return object_type;
}

/*
 *
 *
 *
 *
 */
SimScheduler*
sim_scheduler_new (SimConfig    *config)
{
  SimScheduler *scheduler = NULL;

  g_return_val_if_fail (config, NULL);
  g_return_val_if_fail (SIM_IS_CONFIG (config), NULL);

  scheduler = SIM_SCHEDULER (g_object_new (SIM_TYPE_SCHEDULER, NULL));
  scheduler->_priv->config = config;

  return scheduler;
}

/*
 * Recover the host and net levels of C and A
 * 
 */
void
sim_scheduler_task_calculate (SimScheduler  *scheduler,
                              gpointer       data)
{
  gint           recovery;
  
  recovery = sim_container_db_get_recovery (ossim.container, ossim.dbossim);
  sim_container_set_host_levels_recovery (ossim.container, ossim.dbossim, recovery);
  sim_container_set_net_levels_recovery (ossim.container, ossim.dbossim, recovery);
}

/*
 *
 *
 *
 */
void
sim_scheduler_task_correlation (SimScheduler  *scheduler,
                                                                                                                                gpointer       data)
{
  g_return_if_fail (scheduler != NULL);
  g_return_if_fail (SIM_IS_SCHEDULER (scheduler));

  sim_scheduler_backlogs_time_out (scheduler);
}

/*
 * Although this function is executed each second or so, only
 * do its job (executing other functions) each "interval" seconds approximately.
 *
 */
void
sim_scheduler_task_execute_at_interval (SimScheduler  *scheduler,
                                                                    gpointer       data)
{
  SimConfig     *config;
  GTimeVal       curr_time;

  g_return_if_fail (scheduler != NULL);
  g_return_if_fail (SIM_IS_SCHEDULER (scheduler));

  g_get_current_time (&curr_time);

  if (curr_time.tv_sec < (last + timer))
    return;

  last = curr_time.tv_sec;
  config = scheduler->_priv->config;

  timer = config->scheduler.interval; //interval is 15 by default in config.xml

        //Functions to execute (some of them just if DB is local).:
        if (sim_database_is_local (ossim.dbossim))
        {
                sim_scheduler_task_calculate (scheduler, data);//do the net and host level recovering
                sim_scheduler_task_GDAErrorHandling();  //do a GDA check to test if everything goes fine.
        }
  sim_scheduler_task_rservers (SIM_SCHEDULER_STATE_NORMAL); //(Re)connect with Main Server/s & HA servers

//  sim_scheduler_task_rservers (scheduler); //(Re)connect with the other HA Server.

}

/*
 * Although this function is executed each second or so, only
 * do its job (store how much events of each kind has arrived to the server)
 * each 5 minutes
 */
void
sim_scheduler_task_store_event_number_at_5min (SimScheduler  *scheduler)
{
  GTimeVal       curr_time;

  g_return_if_fail (scheduler != NULL);
  g_return_if_fail (SIM_IS_SCHEDULER (scheduler));

  g_get_current_time (&curr_time);

  if (curr_time.tv_sec < (event_last + event_timer))
    return;

  event_last = curr_time.tv_sec;

  //storing events:
        G_LOCK (s_mutex_sensors);
        
        GList *list;

  list = sim_container_get_sensors_ul(ossim.container);
  SimSensor *sensor;
  while (list)
  {
    sensor = (SimSensor *) list->data;
          sim_container_db_update_sensor_events_number (ossim.container, ossim.dbossim, sensor); //store in DB!
                sim_sensor_reset_events_number (sensor); //reset memory 
                
                list = list->next;                      
        }   


        G_UNLOCK (s_mutex_sensors);


}



/*
 * this function go through the last gda errors and print it.
 * may be this is not the best place to put this function, but...
 * its called from sim_scheduler_task_calculate() (each 15 seconds)
 */
void
sim_scheduler_task_GDAErrorHandling (void)
{
  GList         *list = NULL;
  GList         *node;
  GdaError      *error;
  GdaConnection *conn;

  conn = (GdaConnection *) sim_database_get_conn(ossim.dbossim);

  list = (GList *) gda_connection_get_errors (conn);
      
//  if (!list)
//    g_log (g_log_domain, g_log_level_debug, "gda ok");

  for (node = g_list_first (list); node != NULL; node = g_list_next (node))
  {
    error = (GdaError *) node->data;
                /*
                 * Filter the 1065 error:
                 * if an update or a replace sentece doesn't modify any row, the MySQL server returns the
                 * 1065 error (Query is empty, but the execution _is correct_)
                 */
                if (error!=NULL && gda_error_get_number(error)!=MYSQL_NO_ROW_MODIFICATION_ERROR){
            g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "error in gdaconnection:");
          if (error)
                                g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "error no: %d \ndesc: %s \nsource: %s \nsqlstate: %s", gda_error_get_number (error), gda_error_get_description (error), gda_error_get_source (error), gda_error_get_sqlstate (error));
                }
  }
  gda_connection_clear_error_list(conn); 
}

/*
 *      thread call from sim_scheduler_task_rservers()
 */
static gpointer
sim_scheduler_session (gpointer data)
{
  SimSession  *session = (SimSession *) data;
  SimCommand  *command = NULL;
                             
  g_return_val_if_fail (session, NULL);
  g_return_val_if_fail (SIM_IS_SESSION (session), NULL);

  g_message ("New session (Remote Servers)");

  command = sim_command_new ();
  command->id = 1;
  command->type = SIM_COMMAND_TYPE_CONNECT;
  command->data.connect.type = session->type;
                
        SimServer       *server = (SimServer *) sim_session_get_server (session);
        command->data.connect.hostname = sim_server_get_name (server);  //this is the name of this server, to send it to the master server
                                                                                                                                                                                                                                                                        //so he knows who we are. Or to send it to the HA server.
  if (sim_session_write (session, command))
        {
                if (!sim_session_must_close (session))
                {
                  sim_session_read (session);
                }

                //When the session stops reading data, we must close the conn and re-connect 
                //(in sim_scheduler_task_rservers() )
          if (sim_server_remove_session (ossim.server, session))
                {
                        g_message ("Remove Session (Remote Servers)");
                        g_object_unref (session);
                }
                else
                        g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_scheduler_session: Error removing session: %x", session);
                
        }  
  return NULL;
}


/*
 * This function will open the connection against all the servers from which OSSIM gets information, hosts, networks,
 * (called "rservers" in config.xml) and commands from the "Main Server", identified as primary. If connection is lost,
 * this will try to reopen it.
 */
gboolean
sim_scheduler_task_rservers (SimSchedulerState state)
{
  GThread  *thread;
  GList    *list;
        gboolean exist_rserver;

  list = ossim.config->rservers;

  if (list != NULL)
        {
    g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_scheduler_task_rservers: there are some rservers");
                exist_rserver = TRUE;
        }
        else
                exist_rserver = FALSE;

        
        //may be that the connection with the master servers has been lost. Reopen if needed...
        while (list)
  {
    SimConfigRServer *rserver = (SimConfigRServer*) list->data;

                //This is usefull when we are connecting to the primary master server to load DB data from it (in sim_container_start_temp_listen())
                //we only want to activate the primary master server session, not other master server sessions or HA servers.
                if (state == SIM_SCHEDULER_STATE_INITIAL)       
                        if (!rserver->primary)
                        {
                                list = list->next;
                                continue;
                        }


                SimSessionType  sesstype;               
                SimServer                               *srv;
                if (rserver->is_HA_server)
                {
                        sesstype = SIM_SESSION_TYPE_HA;
                        srv = ossim.HA_server;

                        //sim_server_send_keepalive(); //just a reminder of the next step to programming.
                }
                else
                {                       
                        sesstype = SIM_SESSION_TYPE_SERVER_UP;          
                        srv = ossim.server;
                }
                
    g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_scheduler_task_rservers, sim_scheduler_backlogs_time_out: backlogs %d", g_list_length (list));
                //if the rservers defined aren't active, we've to activate them to accept instructions from
                //them (remember, rservers are the servers "up" in the multiserver architecture) or to accept 
                //data from an HA server. A rserver can be also another HA server in the same level than this one.
                if (!sim_server_get_session_by_ia (srv, sesstype, rserver->ia))
                {
                  rserver->socket = gnet_tcp_socket_new (rserver->ia); //connect to its master server or to the HA server.

                        if (rserver->socket)
                        {
                                SimSession *session = sim_session_new (G_OBJECT (srv), ossim.config, rserver->socket);                                  
                                sim_session_set_hostname (session, rserver->name);
                                sim_session_set_is_initial (session, state);
                                if (!sim_session_must_close (session))
                                {
                                        session->type = sesstype;
                            sim_server_append_session (srv, session);
                                  g_message ("Connecting to remote server: %s\n", rserver->name);
                                        /* session thread */
                                        thread = g_thread_create(sim_scheduler_session, session, FALSE, NULL);
                                  g_return_if_fail (thread);
                                }
                                else
                                {
                                        g_object_unref (session);
                            g_message ("Session Removed: error");                               
                                }
                        }           
                  else
            {
                    if (!rserver->is_HA_server)                                                 
                      g_message ("Error connecting to remote server %s with ip %s,\n please check the server's config.xml file or the connection\n", rserver->name, rserver->ip);                                                       
                                else    //this server takes the control; the other server is down.
                                {
                                        
                      g_message ("HA remote server %s with ip %s is down. This is now the active server.\n", rserver->name, rserver->ip);                                                       
                                }
                                        
            }
                        
                }
    list = list->next;
                
  }

        return exist_rserver;
}

/*
 * main scheduler loop wich decides what should run in a specific moment
 *
 */
void
sim_scheduler_run (SimScheduler *scheduler)
{
  GTimeVal      curr_time;
  GThread *thread;

  g_return_if_fail (scheduler != NULL);
  g_return_if_fail (SIM_IS_SCHEDULER (scheduler));

  g_get_current_time (&curr_time);

  if (!last)
    last = curr_time.tv_sec;

        if (!event_last)
                event_last = curr_time.tv_sec;

  while (TRUE)
  {
    sleep (1);
                if (sim_database_is_local (ossim.dbossim)) //this functions has no sense if the DB is not local
                {
            sim_scheduler_task_correlation (scheduler, NULL); //removes backlog entries when needed
            sim_scheduler_show_stats (scheduler); //NOTE: comment or uncomment this if you want to see statistics
                        sim_scheduler_task_store_event_number_at_5min (scheduler); //stores the event number each 5 minutes (I know, this is a bad style, I'm sorry)
                }
    sim_scheduler_task_execute_at_interval (scheduler, NULL);//execute some tasks in the time interval defined in config.xml
  }
}

/*
 * show the stats message.
 */
void
sim_scheduler_show_stats (SimScheduler *scheduler)
{
  GTimeVal      curr_time;
  gint          events_now = 0;     // events in DB in this moment
  static gint   total_db_old = 0;

  g_return_if_fail (SIM_IS_SCHEDULER (scheduler));
  g_get_current_time (&curr_time);

  if (curr_time.tv_sec < (db_last + TIME_LAPSE))
    return;

  db_last = curr_time.tv_sec;

  events_now = sim_container_get_events_count (ossim.container);
  if(!total_db_old)
    total_db_old = events_now;

  guint eps = (events_now - events_before) / TIME_LAPSE;
  guint eps_sim = (sim_organizer_get_total_events(ossim.organizer) - sim_before) / TIME_LAPSE;
  //this if needed for the first event
  if (events_before == 0)
  {
    eps = 0;
  }
  if (simCmdArgs.dvl == SIM_SHOW_STATS_ON)
  {
    gint nbacklogs = sim_container_get_nbacklogs(ossim.container);
    gint queued_events = sim_container_get_events_to_organizer(ossim.container);
    gint popped_events = sim_organizer_get_total_events(ossim.organizer);
    gint total_sessions = sim_server_get_session_count(ossim.server);
    gint total_active_sessions = sim_server_get_session_count_active(ossim.server);
    g_message("RcvMsgs: %d [SIM queued: %u, popped: %u, eps: %u][session %u/%u] [backlogs: %u]",
      aux_received_messages,
      queued_events,
      popped_events,
      (eps_sim > 0 ? eps_sim:0),
      total_sessions,
      total_active_sessions,
      nbacklogs);
  }
  else
  {
    g_message("Events in DB: %d", events_now);
  }
  events_before = events_now;
  sim_before = sim_organizer_get_total_events (ossim.organizer);
}

/*
 *
 *
 *
 *
 */
void
sim_scheduler_backlogs_time_out (SimScheduler  *scheduler)
{
  SimConfig     *config;
  GList         *list;
  GList                                 *removes = NULL; //here will be append all the events so we can delete it in the second "while".

  g_return_if_fail (scheduler);
  g_return_if_fail (SIM_IS_SCHEDULER (scheduler));

  config = scheduler->_priv->config;

  g_mutex_lock (ossim.mutex_backlogs);
  list = sim_container_get_backlogs_ul (ossim.container);
  if (list)
    g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_scheduler_backlogs_time_out: backlogs %d", g_list_length (list));
  else
    g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_scheduler_backlogs_time_out: list is NULL");
  
  while (list)
  {
    SimDirective *backlog = (SimDirective *) list->data;

    if (sim_directive_is_time_out (backlog)) //the directive has ended. Its 'timeouted'
      removes = g_list_append (removes, backlog);

          list = list->next;
  }

  list = removes;
  while (list)
  {
    SimDirective *backlog = (SimDirective *) list->data;
    sim_container_remove_backlog_ul (ossim.container, backlog);
                //FIXME: Why does this need to be commented? AR.
    //sim_container_db_delete_backlog_ul (ossim.container, ossim.dbossim, backlog);
    g_object_unref (backlog);
    list = list->next;
  }

        if (removes)
                g_list_free (removes);

  g_mutex_unlock (ossim.mutex_backlogs);
}
// vim: set tabstop=2:
