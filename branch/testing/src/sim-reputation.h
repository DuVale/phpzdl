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

#ifndef __SIM_REPUTATION_H__
#define __SIM_REPUTATION_H__ 1

#include <glib.h>
#include <glib-object.h>

#ifndef __SIM_EVENT_H__
#include "sim-event.h"
#endif

#include "sim-radix.h"
#include "sim-unittesting.h"

#define SIM_REPUTATION_PLUGIN_ID 3000
#define SIM_REPUTATION_DEFAULT_SID 1

#ifdef __cplusplus
extern "C" {
#endif /* __cplusplus */

typedef struct _SimReputationData {
  gchar *ip;
  guint reliability;
  guint priority;
  gchar *activities;
  // TODO: For a second version of reputation, we should split activities into
  // a list/Tree, so then we can also ask for an activity in the correlation engine
} SimReputationData;

#define SIM_TYPE_REPUTATION                  (sim_reputation_get_type ())
#define SIM_REPUTATION(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_REPUTATION, SimReputation))
#define SIM_REPUTATION_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_REPUTATION, SimReputationClass))
#define SIM_IS_REPUTATION(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_REPUTATION))
#define SIM_IS_REPUTATION_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_REPUTATION))
#define SIM_REPUTATION_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_REPUTATION, SimReputationClass))

G_BEGIN_DECLS

typedef struct _SimReputation        SimReputation;
typedef struct _SimReputationClass   SimReputationClass;
typedef struct _SimReputationPrivate SimReputationPrivate;

struct _SimReputation {
  GObject parent;

  SimReputationPrivate *_priv;
};

struct _SimReputationClass {
  GObjectClass parent_class;
};

GType                   sim_reputation_get_type    (void);
SimReputation*          sim_reputation_new         (gchar *);

SimRadix*                sim_reputation_get_ipv4tree    (SimReputation *);
void                    sim_reputation_set_ipv4tree    (SimReputation *, SimRadix *);

SimRadix*                  sim_reputation_get_ipv6tree    (SimReputation *);
void                    sim_reputation_set_ipv6tree    (SimReputation *, SimRadix *);

SimReputationData*
sim_reputation_search_best_key_IPV6(SimReputation *, SimRadixKey *);

SimReputationData*
sim_reputation_search_exact_key_IPV6(SimReputation *, SimRadixKey *);

SimReputationData*
sim_reputation_search_best_key_IPV4(SimReputation *, SimRadixKey *);

SimReputationData*
sim_reputation_search_exact_key_IPV4(SimReputation *, SimRadixKey *);


void sim_reputation_match_event (SimReputation * reputation, SimEvent *event);
void sim_reputation_debug_print (SimReputation *);
void sim_reputation_data_destroy(SimReputationData *data);
void sim_reputation_user_data_update(void *old, void *new_data);
void sim_reputation_user_data_print(void *ud);
gboolean sim_reputation_entry_skip(gchar *ip);
struct in_addr *sim_reputation_get_IPV4_addr(const gchar *ascii_ip);
struct in6_addr *sim_reputation_get_IPV6_addr(const gchar *ascii_ip);
gboolean sim_reputation_to_IPV4(gchar *ip);
gboolean sim_reputation_to_IPV6(gchar *ip);
SimReputationData* sim_reputation_data_create(gchar *ip, gchar *reliability, gchar *priority, gchar *activities);
gboolean sim_reputation_add_entry(SimReputation *reputation, gchar *ip, gchar *reliability, gchar *priority, gchar *activities);
void sim_reputation_print(SimReputation *reputation); 
G_END_DECLS

#ifdef __cplusplus
}
#endif /* __cplusplus */

#ifdef USE_UNITTESTS
void sim_radix_register_tests(SimUnittesting *);
#endif

#endif /* __SIM_REPUTATION_H__ */

// vim: set tabstop=2:
