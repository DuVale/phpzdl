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

#include <time.h>

#include "sim-event.h"
#include "sim-util.h"
#include "os-sim.h"
#include "sim-text-fields.h"
#include <config.h>
#include <pcre.h>
#include <time.h>
#include <math.h>
#include <string.h> //strlen()
#include <assert.h>

enum
{
  DESTROY, LAST_SIGNAL
};

static gpointer parent_class = NULL;
static gint sim_server_signals[LAST_SIGNAL] =
  { 0 };

/* GType Functions */

static void
sim_event_impl_dispose(GObject *gobject)
{
  G_OBJECT_CLASS(parent_class)->dispose(gobject);
}

static void
sim_event_impl_finalize(GObject *gobject)
{
  int i;
  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,"sim_event_impl_finalize");
  SimEvent *event = (SimEvent *) gobject;

  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_event_impl_finalize: Id %u, Sid %u, Cid %u", event->id,
      event->snort_sid, event->snort_cid);

  if (event->sensor)
  {
    g_free(event->sensor);
    event->sensor = NULL;
  }
  if (event->device)
  {
    g_free(event->device);
    event->device = NULL;
  }
  if (event->interface)
  {
    g_free(event->interface);
    event->interface = NULL;
  }
  if (event->src_ia)
    gnet_inetaddr_unref(event->src_ia);
  if (event->dst_ia)
    gnet_inetaddr_unref(event->dst_ia);
  if (event->data)
  {
    g_free(event->data);
    event->data = NULL;
  }
  if (event->time_str)
  {
    g_free(event->time_str);
    event->time_str = NULL;
  }
  if (event->role)
    g_free(event->role);
  if (event->log)
  {
    g_free(event->log);
    event->log = NULL;
  }
  if (event->sql_text_fields)
  {
    g_free(event->sql_text_fields);
    event->sql_text_fields = NULL;
  }
  if (event->value)
  {
    g_free(event->value);
    event->value = NULL;
  }

  g_free (event->rep_act_src);
  g_free (event->rep_act_dst);

  for (i = 0; i < N_TEXT_FIELDS; i++)
  {
    if(event->textfields[i])
    {
      g_free(event->textfields[i]);
      event->textfields[i] = NULL;
    }
  }
  if (event->buffer)
  {
    g_free(event->buffer);
    event->buffer = NULL;
  }

  if (event->plugin_sid_name)
  {
    g_free(event->plugin_sid_name);
    event->plugin_sid_name =  NULL;
  }
  if (event->packet)
  {
      g_object_unref(event->packet);
  }

  if (event->backlog_list)
    {
      GList *node;
      for (node = g_list_first(event->backlog_list); node != NULL; node
          = g_list_next(node))
        {
          g_object_unref(G_OBJECT(node->data));
        }
      g_list_free(event->backlog_list);
    }
  G_OBJECT_CLASS(parent_class)->finalize(gobject);
}

static void
sim_event_class_init(SimEventClass * klass)
{
  GString *st;
  int i;
  GObjectClass *object_class = G_OBJECT_CLASS(klass);
  st = g_string_new("");
  assert (st != NULL);

  parent_class = g_type_class_ref(G_TYPE_OBJECT);

  object_class->dispose = sim_event_impl_dispose;
  object_class->finalize = sim_event_impl_finalize;
  /* Create a string with the sql text fields, for insert sentences. Becasuse the fields are
   defined at compile time, we create this string only one time, when the class is initialize */
  for (i = 0; i < N_TEXT_FIELDS; i++)
    {
      g_string_append_printf(st, "%s%s", sim_text_field_get_name(i), (i
          != (N_TEXT_FIELDS - 1)) ? "," : "");
    }
  klass->sql_text_fields = g_string_free(st, FALSE); /* This string is never deleted, but is OK*/

}

static void
sim_event_instance_init(SimEvent *event)
{
  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_event_instance_init");
  int i;

  event->id = 0;
  event->id_tmp = 0;
  event->snort_sid = 0;
  event->snort_cid = 0;
  event->signature = 0xdeadbeef;

  event->type = SIM_EVENT_TYPE_NONE;

  event->time = 0;
  event->time_str = NULL;
  event->diff_time = 0;
  event->tzone = 0;

  event->sensor = NULL;
  event->device = NULL;
  event->interface = NULL;

  event->plugin_id = 0;
  event->plugin_sid = 0;
  event->plugin_sid_name = NULL;

  event->plugin = NULL;
  event->pluginsid = NULL;

  event->protocol = SIM_PROTOCOL_TYPE_NONE;
  event->src_ia = NULL;
  event->dst_ia = NULL;
  event->src_port = 0;
  event->dst_port = 0;

  event->src_home_net_searched = FALSE;
  event->src_home_net = FALSE;
  event->dst_home_net_searched = FALSE;
  event->dst_home_net = FALSE;

  event->condition = SIM_CONDITION_TYPE_NONE;
  event->value = NULL;
  event->interval = 0;

  event->alarm = FALSE;
  event->priority = 0;
  event->reliability = 0;
  event->asset_src = 2; //can't be changed to 0: among other things, event_directive won't work then!
  event->asset_dst = 2;
  event->risk_c = 0;
  event->risk_a = 0;

  event->data = NULL;
  event->log = NULL;

  event->match = FALSE;
  event->matched = FALSE;
  event->count = 0;
  event->level = 1;
  event->backlog_id = 0;

  event->rserver = FALSE;
  event->store_in_DB = TRUE; //we want to store everything by default

  event->is_correlated = FALSE; //local mode
	event->correlation = EVENT_MATCH_NOTHING;
  event->is_priority_set = FALSE;
  event->is_reliability_set = FALSE;

  event->data_storage = NULL;

  event->role = NULL;
  event->policy = NULL;
  /*
   event->filename = NULL;
   event->username = NULL;
   event->password = NULL;
   event->userdata1 = NULL;
   event->userdata2 = NULL;
   event->userdata3 = NULL;
   event->userdata4 = NULL;
   event->userdata5 = NULL;
   event->userdata6 = NULL;
   event->userdata7 = NULL;
   event->userdata8 = NULL;
   event->userdata9 = NULL;
   */

  event->rep_act_src = NULL;
  event->rep_act_dst = NULL;
  event->rep_prio_src = 0;
  event->rep_prio_dst = 0;
  event->rep_rel_src = 0;
  event->rep_rel_dst = 0;

  for (i = 0; i < N_TEXT_FIELDS; i++)
    {
      event->textfields[i] = NULL;
      event->isTextMatched[i] = FALSE;
    }
  uuid_clear(event->uuid);
  event->buffer = NULL;
  event->packet = NULL;

  event->backlog_list = NULL;

}

/* Public Methods */

GType
sim_event_get_type(void)
{
  static GType object_type = 0;

  if (!object_type)
    {
      static const GTypeInfo type_info =
        { sizeof(SimEventClass), NULL, NULL,
            (GClassInitFunc) sim_event_class_init, NULL, NULL, /* class data */
            sizeof(SimEvent), 0, /* number of pre-allocs */
            (GInstanceInitFunc) sim_event_instance_init, NULL /* value table */
        };

      g_type_init();

      object_type = g_type_register_static(G_TYPE_OBJECT, "SimEvent",
          &type_info, 0);
    }

  return object_type;
}

/*
 *
 *
 *
 *
 */
SimEvent*
sim_event_new(void)
{
  SimEvent *event = NULL;

  event = SIM_EVENT (g_object_new (SIM_TYPE_EVENT, NULL));

  return event;
}

/*
 *
 *
 *
 *
 */
SimEvent*
sim_event_new_from_type(SimEventType type)
{
  SimEvent *event = NULL;

  event = SIM_EVENT (g_object_new (SIM_TYPE_EVENT, NULL));
  event->type = type;

  return event;
}

/*
 *
 *
 *
 *
 */
SimEventType
sim_event_get_type_from_str(const gchar *str)
{
  g_return_val_if_fail(str, SIM_EVENT_TYPE_NONE);

  if (!g_ascii_strcasecmp(str, SIM_DETECTOR_CONST))
    return SIM_EVENT_TYPE_DETECTOR;
  else if (!g_ascii_strcasecmp(str, SIM_MONITOR_CONST))
    return SIM_EVENT_TYPE_MONITOR;

  return SIM_EVENT_TYPE_NONE;
}

gchar*
sim_event_get_str_from_type(SimEventType type)
{
  if (type == SIM_EVENT_TYPE_DETECTOR)
    return (g_ascii_strdown(SIM_DETECTOR_CONST, strlen(SIM_DETECTOR_CONST)));
  else if (type == SIM_EVENT_TYPE_MONITOR)
    return (g_ascii_strdown(SIM_MONITOR_CONST, strlen(SIM_MONITOR_CONST)));

  return NULL;
}

/*
 *
 *
 *
 *
 */
SimEvent*
sim_event_clone(SimEvent *event)
{
  SimEvent *new_event;
  int i;

  new_event = SIM_EVENT (g_object_new (SIM_TYPE_EVENT, NULL));
  new_event->id = event->id;
  new_event->snort_sid = event->snort_sid;
  new_event->snort_cid = event->snort_cid;

  new_event->type = event->type;

  new_event->time = event->time;

  (event->sensor) ? new_event->sensor = g_strdup(event->sensor) : NULL;
  (event->device) ? new_event->device = g_strdup(event->device) : NULL;
  (event->interface) ? new_event->interface = g_strdup(event->interface) : NULL;

  new_event->plugin_id = event->plugin_id;
  new_event->plugin_sid = event->plugin_sid;

  new_event->plugin = event->plugin;
  new_event->pluginsid = event->pluginsid;
  (event->plugin_sid_name) ? new_event->plugin_sid_name = g_strdup(
      event->plugin_sid_name) : NULL;

  new_event->protocol = event->protocol;
  (event->src_ia) ? new_event->src_ia = gnet_inetaddr_clone(event->src_ia)
      : NULL;
  (event->dst_ia) ? new_event->dst_ia = gnet_inetaddr_clone(event->dst_ia)
      : NULL;
  new_event->src_port = event->src_port;
  new_event->dst_port = event->dst_port;

  new_event->condition = event->condition;
  (event->value) ? new_event->value = g_strdup(event->value) : NULL;
  new_event->interval = event->interval;

  new_event->alarm = event->alarm;
  new_event->priority = event->priority;
  new_event->reliability = event->reliability;
  new_event->asset_src = event->asset_src;
  new_event->asset_dst = event->asset_dst;
  new_event->risk_c = event->risk_c;
  new_event->risk_a = event->risk_a;

  if (event->role)
    {
      new_event->role = g_new0(SimRole, 1);
      new_event->role->correlate = event->role->correlate;
      new_event->role->cross_correlate = event->role->cross_correlate;
      new_event->role->store = event->role->store;
      new_event->role->qualify = event->role->qualify;
      new_event->role->resend_event = event->role->resend_event;
      new_event->role->resend_alarm = event->role->resend_alarm;
    }

  new_event->log = g_strdup (event->log);
  new_event->rep_act_src = g_strdup (event->rep_act_src);
  new_event->rep_act_dst = g_strdup (event->rep_act_dst);
  new_event->rep_prio_src = event->rep_prio_src;
  new_event->rep_prio_dst = event->rep_prio_dst;
  new_event->rep_rel_src = event->rep_rel_src;
  new_event->rep_rel_dst = event->rep_rel_dst;

  for (i = 0; i < N_TEXT_FIELDS; i++)
    {
      if (event->textfields[i] != NULL)
        new_event->textfields[i] = g_strdup(event->textfields[i]);
      else
        new_event->textfields[i] = NULL;
    }
  /*
   (event->filename) ? new_event->filename = g_strdup (event->filename) : NULL;
   (event->username) ? new_event->username = g_strdup (event->username) : NULL;
   (event->password) ? new_event->password = g_strdup (event->password) : NULL;
   (event->userdata1) ? new_event->userdata1 = g_strdup (event->userdata1) : NULL;
   (event->userdata2) ? new_event->userdata2 = g_strdup (event->userdata2) : NULL;
   (event->userdata3) ? new_event->userdata3 = g_strdup (event->userdata3) : NULL;
   (event->userdata4) ? new_event->userdata4 = g_strdup (event->userdata4) : NULL;
   (event->userdata5) ? new_event->userdata5 = g_strdup (event->userdata5) : NULL;
   (event->userdata6) ? new_event->userdata6 = g_strdup (event->userdata6) : NULL;
   (event->userdata7) ? new_event->userdata7 = g_strdup (event->userdata7) : NULL;
   (event->userdata8) ? new_event->userdata8 = g_strdup (event->userdata8) : NULL;
   (event->userdata9) ? new_event->userdata9 = g_strdup (event->userdata9) : NULL;
   */

  (event->buffer) ? new_event->buffer = g_strdup(event->buffer) : NULL;
  uuid_generate(new_event->uuid);

  return new_event;
}

/*
 *
 *
 *
 *
 */
void
sim_event_print(SimEvent *event)
{
  gchar time[TIMEBUF_SIZE];
  gchar *timestamp = time;
  gchar *ip;
  int i;

  g_return_if_fail(event);
  g_return_if_fail(SIM_IS_EVENT (event));

  g_print("event");

  switch (event->type)
    {
  case SIM_EVENT_TYPE_DETECTOR:
    g_print(" type=\"D\"");
    break;
  case SIM_EVENT_TYPE_MONITOR:
    g_print(" type=\"M\"");
    break;
  case SIM_EVENT_TYPE_NONE:
    g_print(" type=\"N\"");
    break;
    }

  g_print(" id=\"%d\"", event->id);

  if (event->time_str)
    timestamp = event->time_str;
  else if (event->time)
    strftime (timestamp, TIMEBUF_SIZE, "%F %T", gmtime ((time_t *) &event->time));

  g_print(" alarm=\"%d\"", event->alarm);

  if (event->sensor)
    g_print(" sensor=\"%s\"", event->sensor);
  if (event->device)
    g_print(" device=\"%s\"", event->device);

  if (event->interface)
    g_print(" interface=\"%s\"", event->interface);

  if (event->plugin_id)
    g_print(" plugin_id=\"%d\"", event->plugin_id);
  if (event->plugin_sid)
    g_print(" plugin_sid=\"%d\"", event->plugin_sid);

  if (event->protocol)
    g_print(" protocol=\"%d\"", event->protocol);

  if (event->src_ia)
    {
      ip = gnet_inetaddr_get_canonical_name(event->src_ia);
      g_print(" src_ia=\"%s\"", ip);
      g_free(ip);
    }
  if (event->src_port)
    g_print(" src_port=\"%d\"", event->src_port);
  if (event->dst_ia)
    {
      ip = gnet_inetaddr_get_canonical_name(event->dst_ia);
      g_print(" dst_ia=\"%s\"", ip);
      g_free(ip);
    }
  if (event->dst_port)
    g_print(" dst_port=\"%d\"", event->dst_port);

  if (event->condition)
    g_print(" condition=\"%d\"", event->condition);
  if (event->value)
    g_print(" value=\"%s\"", event->value);
  if (event->interval)
    g_print(" ineterval=\"%d\"", event->interval);

  if (event->priority)
    g_print(" priority=\"%d\"", event->priority);
  if (event->reliability)
    g_print(" reliability=\"%d\"", event->reliability);
  if (event->asset_src)
    g_print(" asset_src=\"%d\"", event->asset_src);
  if (event->asset_dst)
    g_print(" asset_dst=\"%d\"", event->asset_dst);
  if (event->risk_c)
    g_print(" risk_c=\"%lf\"", event->risk_c);
  if (event->risk_a)
    g_print(" risk_a=\"%lf\"", event->risk_a);

  if (event->snort_sid)
    g_print(" sid =\"%d\"", event->snort_sid);
  if (event->snort_cid)
    g_print(" cid =\"%d\"", event->snort_cid);

  if (event->data)
    g_print(" data=\"%s\"", event->data);

	if (event->rep_prio_src)
      g_message (" rep_prio_src=\"%u\"", event->rep_prio_src);

	if (event->rep_prio_dst)
      g_message (" rep_prio_dst=\"%u\"", event->rep_prio_dst);

	if (event->rep_rel_src)
      g_message (" rep_rel_src=\"%u\"", event->rep_rel_src);

	if (event->rep_rel_dst)
      g_message (" rep_rel_dst=\"%u\"", event->rep_rel_dst);

	if (event->rep_act_src)
      g_message (" rep_act_src=\"%s\"", event->rep_act_src);

	if (event->rep_act_dst)
      g_message (" rep_act_dst=\"%s\"", event->rep_act_dst);

  for (i = 0; i < N_TEXT_FIELDS; i++)
    {
      if (event->textfields[i] != NULL)
        {
          g_printf(" %s=\"%s\"", sim_text_field_get_name(i),
              event->textfields[i]);
        }

    }

  if (!uuid_is_null(event->uuid))
    {
      gchar uuidtext[37];
      uuid_unparse_upper(event->uuid, uuidtext);
      g_message(" uuid=\"%s\"", uuidtext);
    }
  g_print("\n");
}

/*
 *
 *
 *
 *
 */
gchar*
sim_event_get_insert_clause(SimEvent *event)
{
  gchar time[TIMEBUF_SIZE];
  gchar *timestamp = time;
  gchar *query;
  gint c;
  gint a;
  gchar uuidtext[37];
  GString *st;
  int i;

  gchar * e_rep_act_src = NULL, * e_rep_act_dst = NULL;
  gchar *e_fields[N_TEXT_FIELDS];

  g_return_val_if_fail(event, NULL);
  g_return_val_if_fail(SIM_IS_EVENT (event), NULL);

  c = rint(event->risk_c);
  a = rint(event->risk_a);

  if (c < 0)
    c = 0;
  else if (c > 10)
    c = 10;
  if (a < 0)
    a = 0;
  else if (a > 10)
    a = 10;

  if(event->time_str)
    timestamp = event->time_str;
  else
    strftime (timestamp, TIMEBUF_SIZE, "%F %T", gmtime ((time_t *) &event->time));

  if (!uuid_is_null(event->uuid))
    {
      uuid_unparse_upper(event->uuid, uuidtext);
    }
  else
    {
      uuidtext[0] = '\0';
    }

  if (event->rep_act_src){
    e_rep_act_src = g_new0 (gchar,strlen(event->rep_act_src)*2+1);
    gda_connection_escape_string (sim_database_get_conn (ossim.dbossim),event->rep_act_src,e_rep_act_src);	
  }
  if (event->rep_act_dst){
    e_rep_act_dst = g_new0 (gchar,strlen(event->rep_act_dst)*2+1);
    gda_connection_escape_string (sim_database_get_conn (ossim.dbossim),event->rep_act_dst,e_rep_act_dst);	
  }

  /* Escape de character data*/
  /* ossimdb */
  for (i = 0; i < N_TEXT_FIELDS; i++)
    {
      if (event->textfields[i] != NULL)
        {
          e_fields[i] = g_new0(gchar, strlen(event->textfields[i]) * 2 + 1);
          gda_connection_escape_string(sim_database_get_conn(ossim.dbossim),
              event->textfields[i], e_fields[i]);
        }
      else
        {
          e_fields[i] = NULL;
        }
    }

  st = g_string_new("INSERT INTO event "
    "(id, timestamp, tzone, sensor, interface, type, plugin_id, plugin_sid, "
    "protocol, src_ip, dst_ip, src_port, dst_port, "
    "event_condition, value, time_interval, "
    "priority, reliability, asset_src, asset_dst, risk_c, risk_a, alarm, "
    "snort_sid, snort_cid, rep_prio_src, rep_prio_dst, rep_rel_src, rep_rel_dst, rep_act_src, rep_act_dst, uuid ");
  for (i = 0; i < N_TEXT_FIELDS; i++)
    {
      g_string_append_printf(st, ",%s", sim_text_field_get_name(i));
    }
  g_string_append_printf(st, ") VALUES  (%d, '%s', %4.2f, '%s', '%s', %d, %d, %d,"
    " %d, %u, %u, %d, %d, %d, '%s', %d, %d, %d, %d, %d, %d, %d, %d, %u, %u, "
    " %u, %u, %u, %u , '%s' ,'%s','%s' ", 
                         event->id, 
                         timestamp,
                         event->tzone,
                         (event->sensor) ? event->sensor : "", 
                         (event->interface) ? event->interface : "",
                         event->type,
                         event->plugin_id, event->plugin_sid, event->protocol,
                         (event->src_ia) ? sim_inetaddr_ntohl(event->src_ia) : -1,
                         (event->dst_ia) ? sim_inetaddr_ntohl(event->dst_ia) : -1,
                         event->src_port, event->dst_port, event->condition,
                         (event->value) ? event->value : "", event->interval, event->priority,
                         event->reliability, event->asset_src, event->asset_dst, c, a,
                         event->alarm, event->snort_sid, event->snort_cid,
                         event->rep_prio_src,
                         event->rep_prio_dst,
                         event->rep_rel_src,
                         event->rep_rel_dst,
                         (event->rep_act_src) ? e_rep_act_src : "",
                         (event->rep_act_dst) ? e_rep_act_dst : "",
                         (uuid_is_null(event->uuid) != 1) ? uuidtext : "");

  for (i = 0; i < N_TEXT_FIELDS; i++)
    {
      g_string_append_printf(st, ",'%s'",
          event->textfields[i] != NULL ? e_fields[i] : "");
    }
  g_string_append(st, ");\n");

  g_free (e_rep_act_src);
  g_free (e_rep_act_dst);

  /* Free memory*/
  for (i = 0; i < N_TEXT_FIELDS; i++)
    {
      g_free(e_fields[i]);
    }

  return g_string_free(st, FALSE);
}

/*
 *
 *
 *
 *
 */
gchar*
sim_event_get_update_clause(SimEvent *event)
{
  gchar    time[TIMEBUF_SIZE];
	gchar   *timestamp=time;
  gchar *query;
  gint c;
  gint a;

  g_return_val_if_fail(event, NULL);
  g_return_val_if_fail(SIM_IS_EVENT (event), NULL);

  c = rint(event->risk_c);
  a = rint(event->risk_a);

  if (c < 0)
    c = 0;
  else if (c > 10)
    c = 10;
  if (a < 0)
    a = 0;
  else if (a > 10)
    a = 10;

  if(event->time_str)
    timestamp=event->time_str;
	else
    strftime (timestamp, TIMEBUF_SIZE, "%F %T", gmtime ((time_t *) &event->time));

  query = g_strdup_printf(
      "UPDATE event SET timestamp='%s', sensor='%s', interface='%s', "
        "type=%d, plugin_id=%d, plugin_sid=%d, "
        "protocol=%d, src_ip=%u, dst_ip=%u, src_port=%d, dst_port=%d, "
        "event_condition=%d, value='%s', time_interval=%d, "
        "priority=%d, reliability=%d, asset_src=%d, asset_dst=%d, "
        "risk_c=%d, risk_a=%d, alarm=%d, "
        "snort_sid=%u, snort_cid=%u "
        " WHERE id=%u", timestamp, (event->sensor) ? event->sensor : "",
      (event->interface) ? event->interface : "", event->type,
      event->plugin_id, event->plugin_sid, event->protocol,
      (event->src_ia) ? sim_inetaddr_ntohl(event->src_ia) : -1,
      (event->dst_ia) ? sim_inetaddr_ntohl(event->dst_ia) : -1,
      event->src_port, event->dst_port, event->condition,
      (event->value) ? event->value : "", event->interval, event->priority,
      event->reliability, event->asset_src, event->asset_dst, c, a,
      event->alarm, event->snort_sid, event->snort_cid, event->id);

  return query;
}

static gchar *
sim_event_get_text_escape_fields_values(SimEvent *event)
{
  int i;
  gchar *e_fields[N_TEXT_FIELDS];
  GString * st;
  GdaConnection *conn;
  conn = sim_database_get_conn(ossim.dbossim);
  st = g_string_new("");
  if (st == NULL)
    return NULL; // I prefer an assert here. If no memory, we must explicit print the warning and die!
  for (i = 0; i < N_TEXT_FIELDS; i++)
    {
      if (event->textfields[i] != NULL)
        {
          e_fields[i] = g_new0(gchar, strlen(event->textfields[i]) * 2 + 1);
          gda_connection_escape_string(conn, event->textfields[i], e_fields[i]);
          g_string_append_printf(st, "'%s'%s", e_fields[i], i != (N_TEXT_FIELDS
              - 1) ? "," : "");
          g_free(e_fields[i]);
        }
      else
        g_string_append_printf(st, "'%s'%s", "", i != (N_TEXT_FIELDS - 1) ? ","
            : "");

    }
  return g_string_free(st, FALSE);
}

/*
 *
 *
 *
 *
 */
gchar*
sim_event_get_replace_clause(SimEvent *event)
{
  gchar    time[TIMEBUF_SIZE];
	gchar   *timestamp=time;
  gchar *query;
  gint c;
  gint a;
  int i;
  /* Temporal HACK */

  gchar uuidtext[37];
  gchar *values;
  gchar * e_rep_act_src = NULL, * e_rep_act_dst = NULL;

  g_return_val_if_fail(event, NULL);
  g_return_val_if_fail(SIM_IS_EVENT (event), NULL);

  c = rint(event->risk_c);
  a = rint(event->risk_a);

  if (c < 0)
    c = 0;
  else if (c > 10)
    c = 10;
  if (a < 0)
    a = 0;
  else if (a > 10)
    a = 10;

  if(event->time_str)
    timestamp=event->time_str;
	else
    strftime (timestamp, TIMEBUF_SIZE, "%F %T", gmtime ((time_t *) &event->time));

  if (event->rep_act_src){
    e_rep_act_src = g_new0 (gchar,strlen(event->rep_act_src)*2+1);
    gda_connection_escape_string (sim_database_get_conn (ossim.dbossim),event->rep_act_src,e_rep_act_src);	
  }
  if (event->rep_act_dst){
    e_rep_act_dst = g_new0 (gchar,strlen(event->rep_act_dst)*2+1);
    gda_connection_escape_string (sim_database_get_conn (ossim.dbossim),event->rep_act_dst,e_rep_act_dst);	
  }


  uuid_unparse_upper(event->uuid, uuidtext);
  values = sim_event_get_text_escape_fields_values(event);
  query
      = g_strdup_printf(
          "REPLACE INTO event "
            "(id, timestamp, sensor, interface, type, plugin_id, plugin_sid, "
            "protocol, src_ip, dst_ip, src_port, dst_port, "
            "event_condition, value, time_interval, "
            "priority, reliability, asset_src, asset_dst, risk_c, risk_a, alarm, "
            "snort_sid, snort_cid, uuid, rep_prio_src, rep_prio_dst, rep_rel_src, "
            "rep_rel_dst, rep_act_src, rep_act_dst, %s) "
            " VALUES  (%d, '%s', '%s', '%s', %d, %d, %d,"
            " %d, %u, %u, %d, %d, %d, '%s', %d, %d, %d, %d, %d, %d, %d, %d, %u, %u,'%s',"
            " %u, %u, %u, %u, '%s', '%s', %s)",
          sim_event_get_sql_fields(), event->id, timestamp,
          (event->sensor) ? event->sensor : "",
          (event->interface) ? event->interface : "", event->type,
          event->plugin_id, event->plugin_sid, event->protocol,
          (event->src_ia) ? sim_inetaddr_ntohl(event->src_ia) : -1,
          (event->dst_ia) ? sim_inetaddr_ntohl(event->dst_ia) : -1,
          event->src_port, event->dst_port, event->condition,
          (event->value) ? event->value : "", event->interval, event->priority,
          event->reliability, event->asset_src, event->asset_dst, c, a,
          event->alarm, event->snort_sid, event->snort_cid,
          (!uuid_is_null(event->uuid) ? uuidtext : ""),
          event->rep_prio_src, event->rep_prio_dst,
          event->rep_rel_src, event->rep_rel_dst,
          (event->rep_act_src) ? e_rep_act_src : "",
          (event->rep_act_dst) ? e_rep_act_dst : "", values);

  g_free(values);
  g_free (e_rep_act_src);
  g_free (e_rep_act_dst);

  return query;
}

/*
 *
 *
 *
 *
 */
gchar*
sim_event_get_alarm_insert_clause(SimEvent *event)
{
  gchar    time[TIMEBUF_SIZE];
	gchar   *timestamp=time;
  gchar *query;
  gint c;
  gint a;
  gchar uuidtext[37];
  gchar uuidtext_backlog[37];
  g_return_val_if_fail(event, NULL);
  g_return_val_if_fail(SIM_IS_EVENT (event), NULL);

  if (event->risk_c < 0)
    event->risk_c = 0;
  else if (event->risk_c > 10)
    event->risk_c = 10;

  if (event->risk_a < 0)
    event->risk_a = 0;
  else if (event->risk_a > 10)
    event->risk_a = 10;

  c = rint(event->risk_c);
  a = rint(event->risk_a);

  if(event->time_str)
    timestamp=event->time_str;
	else
    strftime (timestamp, TIMEBUF_SIZE, "%F %T", gmtime ((time_t *) &event->time));

  uuid_unparse_upper(event->uuid, uuidtext);
  uuid_unparse_upper(event->uuid_backlog, uuidtext_backlog);
  query
      = g_strdup_printf(
          "REPLACE INTO alarm "
            "(event_id, backlog_id, timestamp, plugin_id, plugin_sid, "
            "protocol, src_ip, dst_ip, src_port, dst_port, "
            "risk, snort_sid, snort_cid,uuid_backlog,uuid_event) "
            " VALUES  ('%u', '%u', '%s', %d, %d, %d, %u, %u, %d, %d, %d, %u, %u,'%s','%s')",
          event->id, event->backlog_id, timestamp, event->plugin_id,
          event->plugin_sid, event->protocol,
          (event->src_ia) ? sim_inetaddr_ntohl(event->src_ia) : -1,
          (event->dst_ia) ? sim_inetaddr_ntohl(event->dst_ia) : -1,
          event->src_port, event->dst_port, (a > c) ? a : c, event->snort_sid,
          event->snort_cid,
          (!uuid_is_null(event->uuid_backlog) ? uuidtext_backlog : ""),
          (!uuid_is_null(event->uuid) ? uuidtext : ""));

  return query;
}

/*
 *
 *
 *
 *
 */
gchar*
sim_event_to_string(SimEvent *event)
{
  GString *str;
  gchar *ip;
  gchar * base64;
  gint base64_len;
  gchar uuidtext[37];
  int i;
  g_return_if_fail(event);
  g_return_if_fail(SIM_IS_EVENT (event));
  gchar *e_filename = NULL, *e_username = NULL, *e_password = NULL;
  gchar *e_userdata1 = NULL, *e_userdata2 = NULL, *e_userdata3 = NULL, *e_userdata4 = NULL;
  gchar *e_userdata5 = NULL, *e_userdata6 = NULL, *e_userdata7 = NULL, *e_userdata8 = NULL;
  gchar *e_userdata9 = NULL, *e_data = NULL, *e_log = NULL;
  str = g_string_new("event ");

  g_string_append_printf(str, "id=\"%u\" ", event->id);
  g_string_append_printf(str, "alarm=\"%d\" ", event->alarm);

  gchar *aux = sim_event_get_str_from_type(event->type);
  if (aux)
  {
    g_string_append_printf(str, "type=\"%s\" ", aux);
    g_free(aux);
  }

  g_string_append_printf(str, "date=\"%u\" ", event->time);
  g_string_append_printf(str, "tzone=\"%4.2f\" ", event->tzone);

  if (event->time_str)
    g_string_append_printf(str, "fdate=\"%s\" ", event->time_str);

  if (event->plugin_id)
    g_string_append_printf(str, "plugin_id=\"%d\" ", event->plugin_id);

  if (event->plugin_sid)
    g_string_append_printf(str, "plugin_sid=\"%d\" ", event->plugin_sid);

  if (event->src_ia)
  {
    ip = gnet_inetaddr_get_canonical_name(event->src_ia);
    g_string_append_printf(str, "src_ip=\"%s\" ", ip);
    g_free(ip);
  }

  if (event->src_port)
    g_string_append_printf(str, "src_port=\"%d\" ", event->src_port);

  if (event->dst_ia)
  {
    ip = gnet_inetaddr_get_canonical_name(event->dst_ia);
    g_string_append_printf(str, "dst_ip=\"%s\" ", ip);
    g_free(ip);
  }

  if (event->dst_port)
    g_string_append_printf(str, "dst_port=\"%d\" ", event->dst_port);

  if (event->sensor)
    g_string_append_printf(str, "sensor=\"%s\" ", event->sensor);
  if (event->device)
    g_string_append_printf(str, "device=\"%s\" ", event->device);

  if (event->interface)
    g_string_append_printf(str, "interface=\"%s\" ", event->interface);

  if (event->protocol)
  {
    gchar *value = sim_protocol_get_str_from_type(event->protocol);
    g_string_append_printf(str, "protocol=\"%s\" ", value);
    g_free(value);
  }

  if (event->condition)
  {
    gchar *value = sim_condition_get_str_from_type(event->condition);
    g_string_append_printf(str, "condition=\"%s\" ", value);
    g_free(value);
  }
  if (event->value)
    g_string_append_printf(str, "value=\"%s\" ", event->value);
  if (event->interval)
    g_string_append_printf(str, "interval=\"%d\" ", event->interval);

  if (event->is_priority_set)
    g_string_append_printf(str, "priority=\"%d\" ", event->priority);
  if (event->is_reliability_set)
    g_string_append_printf(str, "reliability=\"%d\" ", event->reliability);
  if (event->asset_src)
    g_string_append_printf(str, "asset_src=\"%d\" ", event->asset_src);
  if (event->asset_dst)
    g_string_append_printf(str, "asset_dst=\"%d\" ", event->asset_dst);
  if (event->risk_c)
    g_string_append_printf(str, "risk_a=\"%lf\" ", event->risk_a);
  if (event->risk_a)
    g_string_append_printf(str, "risk_c=\"%lf\" ", event->risk_c);

  if (event->snort_sid)
    g_string_append_printf(str, "snort_sid=\"%u\" ", event->snort_sid);
  if (event->snort_cid)
    g_string_append_printf(str, "snort_cid=\"%u\" ", event->snort_cid);

  //  if (event->data)
  //    g_string_append_printf(str, "data=\"%s\" ", event->data);
  if (event->log && (base64_len = strlen(event->log)))
  {
    base64 = g_base64_encode(event->log, base64_len);
    assert(base64!=NULL);
    g_string_append_printf(str, "log=\"%s\" ", base64);
    g_free(base64);
  }

  if (event->rep_prio_src)
    g_string_append_printf(str, "rep_prio_src=\"%u\" ", event->rep_prio_src);

  if (event->rep_prio_dst)
    g_string_append_printf(str, "rep_prio_dst=\"%u\" ", event->rep_prio_dst);

  if (event->rep_rel_src)
    g_string_append_printf(str, "rep_rel_src=\"%u\" ", event->rep_rel_src);

  if (event->rep_rel_dst)
    g_string_append_printf(str, "rep_rel_dst=\"%u\" ", event->rep_rel_dst);

  if (event->rep_act_src && (base64_len = strlen(event->rep_act_src)))
  {
    base64 = g_base64_encode( (guchar*)event->rep_act_src, base64_len);
    assert (base64 != NULL);
    g_string_append_printf(str, "rep_act_src=\"%s\" ", base64);
    g_free(base64);
  }

  if (event->rep_act_dst && (base64_len = strlen(event->rep_act_dst)))
  {
    base64 = g_base64_encode( (guchar*)event->rep_act_dst, base64_len);
    assert (base64 != NULL);
    g_string_append_printf(str, "rep_act_dst=\"%s\" ", base64);
    g_free(base64);
  }

  //g_string_append_printf(str, "log=\"%s\" ", event->log);
  for (i = 0; i < N_TEXT_FIELDS; i++)
  {
    if ((event->textfields[i] != NULL) && (base64_len = strlen(event->textfields[i])))
    {
      base64 = g_base64_encode(event->textfields[i], base64_len);
      assert(base64!=NULL);
      g_string_append_printf(str, "%s=\"%s\" ", sim_text_field_get_name(i), base64);
      g_free(base64);
    }
  }

  if (!uuid_is_null(event->uuid))
  {
    uuid_unparse_upper(event->uuid, uuidtext);
    g_string_append_printf(str, "uuid=\"%s\" ", uuidtext);
  }

  if (event->packet)
    if (event->packet->payloadlen > 0)
    {
      gchar *payload;
      payload = sim_bin2hex(event->packet->payload, event->packet->payloadlen);
      g_string_append_printf(str, "payload=\"%s\" ", payload);
      g_free(payload);
    }

  g_string_append_printf(str, "\n");

  return g_string_free(str, FALSE);
}

/*
 * Returns TRUE if the event is one of the "special" events: MAC, OS, Service or HIDS
 */
gboolean
sim_event_is_special(SimEvent *event)
{
  if ((event->plugin_id == 1512) || (event->plugin_id == 1511)
      || (event->plugin_id == 1516) || (event->plugin_id == 4001))
    return TRUE;
  else
    return FALSE;
}
/*
 * FIXME: This function will remove some things from the event, like SQL injection and so on.
 * At this moment, it just substitute ";" with "," from event->data. The reason is that the call to GDA function
 * wich is supposed to do just one query gda_connection_execute_non_query(), in fact accept 
 * multiple queries (as tells the GDA source in gda_connection_execute_command() comments. And
 * that queries are supposed to be separated by ';'
 * 
 * This is a FIXME because we have to analize much more in depth the event.
 */
void
sim_event_sanitize(SimEvent *event)
{
  g_return_if_fail(event);
  g_return_if_fail(SIM_IS_EVENT (event));

  //sim_string_remove_char (event->data, ';');
  //sim_string_remove_char (event->log, ';');
  return;
  /*
   sim_string_substitute_char (event->data, ';', ',');
   sim_string_substitute_char (event->log, ';', ',');
   sim_string_substitute_char (event->userdata1, ';', ',');

   sim_string_substitute_char (event->data, '\'', ',');
   sim_string_substitute_char (event->log, '\'', ',');
   sim_string_substitute_char (event->userdata1, '\'', ',');
   sim_string_substitute_char (event->userdata2, '\'', ',');
   sim_string_substitute_char (event->userdata3, '\'', ',');
   sim_string_substitute_char (event->userdata4, '\'', ',');
   sim_string_substitute_char (event->userdata5, '\'', ',');
   sim_string_substitute_char (event->userdata6, '\'', ',');
   sim_string_substitute_char (event->userdata7, '\'', ',');
   sim_string_substitute_char (event->userdata8, '\'', ',');
   sim_string_substitute_char (event->userdata9, '\'', ',');
   */
}

/*
 * This query will insert event into the event_tmp table. This is needed for the dinamic event viewer
 * in the framework.
 *
 */
gchar*
sim_event_get_insert_into_event_tmp_clause(SimEvent *event)
{
  gchar    time[TIMEBUF_SIZE];
	gchar   *timestamp=time;
  gchar *query;
  gint c;
  gint a;
  gchar uuidtext[37];
  GString *st;
  int i;
  gchar *e_fields[N_TEXT_FIELDS];
  g_return_val_if_fail(event, NULL);
  g_return_val_if_fail(SIM_IS_EVENT (event), NULL);

  c = rint(event->risk_c);
  a = rint(event->risk_a);

  if (c < 0)
    c = 0;
  else if (c > 10)
    c = 10;
  if (a < 0)
    a = 0;
  else if (a > 10)
    a = 10;

  if(event->time_str)
    timestamp=event->time_str;
	else
    strftime (timestamp, TIMEBUF_SIZE, "%F %T", gmtime ((time_t *) &event->time));

  if (!uuid_is_null(event->uuid))
    {
      uuid_unparse_upper(event->uuid, uuidtext);
    }
  else
    {
      uuidtext[0] = '\0';
    }
  for (i = 0; i < N_TEXT_FIELDS; i++)
    {
      if (event->textfields[i] != NULL)
        {
          e_fields[i] = g_new0(gchar, strlen(event->textfields[i]) * 2 + 1);
          gda_connection_escape_string(sim_database_get_conn(ossim.dbossim),
              event->textfields[i], e_fields[i]);
        }
      else
        {
          e_fields[i] = NULL;
        }
    }

  st = g_string_new("INSERT INTO event_tmp "
    "(id, timestamp, sensor, interface, type, plugin_id, plugin_sid, "
    "protocol, src_ip, dst_ip, src_port, dst_port, "
    "event_condition, value, time_interval, "
    "priority, reliability, asset_src, asset_dst, risk_c, risk_a, alarm, "
    "snort_sid, snort_cid, uuid ");
  for (i = 0; i < N_TEXT_FIELDS; i++)
    {
      g_string_append_printf(st, ",%s", sim_text_field_get_name(i));
    }
  g_string_append_printf(st, " VALUES  (%d, '%s', '%s', '%s', %d, %d, %d,"
    " %d, %u, %u, %d, %d, %d, '%s', %d, %d, %d, %d, %d, %d, %d, %d, %u, %u, "
    " '%s','%s'", event->id, event->time_str, (event->sensor) ? event->sensor
      : "", (event->interface) ? event->interface : "", event->type,
      event->plugin_id, event->plugin_sid, event->protocol,
      (event->src_ia) ? sim_inetaddr_ntohl(event->src_ia) : -1,
      (event->dst_ia) ? sim_inetaddr_ntohl(event->dst_ia) : -1,
      event->src_port, event->dst_port, event->condition,
      (event->value) ? event->value : "", event->interval, event->priority,
      event->reliability, event->asset_src, event->asset_dst, c, a,
      event->alarm, event->snort_sid, event->snort_cid,
      (uuid_is_null(event->uuid) != 1) ? uuidtext : "");
  for (i = 0; i < N_TEXT_FIELDS; i++)
    {
      g_string_append_printf(st, ",%s",
          event->textfields[i] != NULL ? e_fields[i] : "");
    }
  g_string_append(st, ")\n");
  /*
   query = g_strdup_printf ("INSERT INTO event_tmp "
   "(id, timestamp, sensor, interface, type, plugin_id, plugin_sid, plugin_sid_name, "
   "protocol, src_ip, dst_ip, src_port, dst_port, "
   "priority, reliability, asset_src, asset_dst, risk_c, risk_a, alarm, "
   "filename, username, password, userdata1, userdata2, userdata3, userdata4, userdata5, userdata6, userdata7, userdata8, userdata9,uuid)"
   " VALUES  (%d, '%s', '%s', '%s', %d, %d, %d, '%s',"
   " %d, %u, %u, %d, %d, "
   " %d, %d, %d, %d, %d, %d, %d,"
   " '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s','%s')",
   event->id_tmp,
   timestamp,
   (event->sensor) ? event->sensor : "",
   (event->interface) ? event->interface : "",
   event->type,
   event->plugin_id,
   event->plugin_sid,
   event->plugin_sid_name,
   event->protocol,
   (event->src_ia) ? sim_inetaddr_ntohl (event->src_ia) : -1,
   (event->dst_ia) ? sim_inetaddr_ntohl (event->dst_ia) : -1,
   event->src_port,
   event->dst_port,
   event->priority,
   event->reliability,
   event->asset_src,
   event->asset_dst,
   c, a,
   event->alarm,
   event->filename,
   event->username,
   event->password,
   event->userdata1,
   event->userdata2,
   event->userdata3,
   event->userdata4,
   event->userdata5,
   event->userdata6,
   event->userdata7,
   event->userdata8,
   event->userdata9,
   (!uuid_is_null(event->uuid))? uuidtext:"");*/
  for (i = 0; i < N_TEXT_FIELDS; i++)
    {
      g_free(e_fields[i]);
    }

  return g_string_free(st, FALSE);
}

void
sim_event_add_backlog_ref_ul(SimEvent *event, GObject *directive)
{
  g_return_if_fail(event != NULL);
  g_return_if_fail(directive != NULL);
  g_return_if_fail(SIM_IS_EVENT (event));
  g_return_if_fail(SIM_IS_DIRECTIVE (directive));
  event->backlog_list = g_list_prepend(event->backlog_list, g_object_ref(
      G_OBJECT(directive)));
}

const gchar *
sim_event_get_sql_fields(void)
{
  gchar *temp = NULL;
  GType type = sim_event_get_type();
  SimEventClass *klass = (SimEventClass*) g_type_class_ref(type);
  temp = klass->sql_text_fields;
  g_type_class_unref(klass);
  return temp;

}

// vim: set tabstop=2:

