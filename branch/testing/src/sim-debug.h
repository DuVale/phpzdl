/*
 License:

 Copyright (c) 2003-2006 ossim.net
 Copyright (c) 2007-2011 AlienVault
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

#ifndef __SIM_DEBUG_H__
#define __SIM_DEBUG_H__ 1 

#define       SIM_DEBUG_ERR_FILE        "/var/log/ossim/server.err"

G_BEGIN_DECLS

#define SIM_SIGNAL_STATS_ON    45
#define SIM_SIGNAL_STATS_OFF   46
#define SIM_SIGNAL_DEBUG_ON    47
#define SIM_SIGNAL_DEBUG_OFF   48
#define SIM_SIGNAL_SHOW_RESOURCE_USAGE  49

void  sim_debug_init_signals(void);
void  sim_debug_on_signal(gint signum);
void  sim_debug_terminate(gint signum, gboolean core);
void sim_debug_show_resources(void);

void sim_debug_output (const char *format,void *p,const char *file,const char *func,unsigned int line, ...);

G_END_DECLS

#endif
