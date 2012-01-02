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

#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <unistd.h>
#include <signal.h>
#include <execinfo.h>
#include <os-sim.h>
#include <glib.h>
#include <glib/gstdio.h>


//Show resources
#include <sys/resource.h>


#include "sim-debug.h"
#include "sim-log.h"
#include "os-sim.h"

// Global variables
extern SimMain ossim;
extern SimCmdArgs simCmdArgs;

/**
 * sim_debug_init_signals:
 *
 * System signal handlers
 */
void sim_debug_init_signals (void)
{
  signal (SIGINT, sim_debug_on_signal);
  signal (SIGHUP, sim_debug_on_signal);
  signal (SIGQUIT, sim_debug_on_signal);
  signal (SIGABRT, sim_debug_on_signal);
  signal (SIGILL, sim_debug_on_signal);
  signal (SIGBUS, sim_debug_on_signal);
  signal (SIGFPE, sim_debug_on_signal);
  signal (SIGSEGV, sim_debug_on_signal);
  signal (SIGTERM, sim_debug_on_signal);
  signal (SIGPIPE, sim_debug_on_signal);
  signal (SIM_SIGNAL_DEBUG_ON, sim_debug_on_signal);
  signal (SIM_SIGNAL_DEBUG_OFF, sim_debug_on_signal);
  signal (SIM_SIGNAL_STATS_ON, sim_debug_on_signal);
  signal (SIM_SIGNAL_STATS_OFF, sim_debug_on_signal);
  signal (SIM_SIGNAL_SHOW_RESOURCE_USAGE,sim_debug_on_signal);
}

/**
 * sim_debug_print_bt:
 *
 */
void
sim_debug_print_bt ()
{
	gint fd, nptrs;
	gpointer buffer [100];
	
	fd = g_open (SIM_DEBUG_ERR_FILE, O_RDWR | O_CREAT | O_TRUNC, S_IRUSR | S_IWUSR);
	nptrs = backtrace (buffer, 100);
	backtrace_symbols_fd (buffer, nptrs, fd);

	return;
}

/**
 * sim_debug_sim_debug_on_signal:
 * @signum:
 *
 */
void 
sim_debug_on_signal (gint signum)
{
	g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "%s: process %d got signal %d", __func__, getpid(), signum);

  switch (signum)
  {
    case SIGHUP: //FIXME: reload directives, policy, and so on.
      // reopen log file
      sim_log_reopen();
      break;
    case SIGPIPE:
      g_message("Error: SIGPIPE in comms");
      break;
    case SIGFPE:
    case SIGILL:
    case SIGABRT:
    case SIGSEGV:
      sim_debug_print_bt();
      sim_debug_terminate(signum, TRUE);
      break;
    case SIGQUIT:
      sim_debug_terminate(signum, TRUE);
      break;
    case SIGTERM:
    case SIGINT:
      sim_debug_terminate(signum, FALSE);
      break;
    case SIGBUS:
      break;
    case SIM_SIGNAL_DEBUG_ON:
      g_message("DEBUG signal on!");
      ossim.log.level = G_LOG_LEVEL_DEBUG;
      break;
    case SIM_SIGNAL_DEBUG_OFF:
      g_message("DEBUG signal off!");
      ossim.log.level = G_LOG_LEVEL_MESSAGE;
      break;
    case SIM_SIGNAL_STATS_ON:
      g_message("STATS signal on!");
      simCmdArgs.dvl = SIM_SHOW_STATS_ON;
      break;
    case SIM_SIGNAL_STATS_OFF:
      g_message("STATS signal off!");
      simCmdArgs.dvl = SIM_SHOW_STATS_OFF;
      break;

    case SIM_SIGNAL_SHOW_RESOURCE_USAGE:
      sim_debug_show_resources();
      break;
  }
}
/*
 * Show resources..
 */
void sim_debug_show_resources(void)
{
  int process= RUSAGE_SELF;
  struct rusage usage;
  struct rusage *p=&usage;
  int ret=getrusage(process,p);

  g_message(" Total amount of user time used:                   %8d  %8d\n",  p->ru_utime.tv_sec,p->ru_utime.tv_usec   );
  g_message(" Total amount of system time used:                 %8d  %8d\n",  p->ru_stime.tv_sec,p->ru_stime.tv_usec   );
  g_message(" Amount of sharing of text segment memory  with other processes:     %8d\n",  p->ru_ixrss           );
  g_message(" Amount of data segment memory used (kilobyte-seconds):          %8d\n",  p->ru_idrss           );
  g_message(" Amount of stack memory used (kilobyte-seconds):         %8d\n",  p->ru_isrss           );
  g_message(" Number of soft page faults (i.e. those serviced by reclaiming a page from the list of pages awaiting reallocation:                  %8d\n",  p->ru_minflt          );
  g_message(" Number of hard page faults (i.e. those that required I/O):                      %8d\n",  p->ru_majflt          );
  g_message(" Number of times a process was swapped out of physical memory:                           %8d\n",  p->ru_nswap           );
  g_message(" Number of input operations via the file system.Note: This and `ru_oublock' do not include operations with the cache:          %8d\n",  p->ru_inblock         );
  g_message(" Number of output operations via the file system          %8d\n",  p->ru_oublock         );
  //g_message(" /* # of characters read/written */     %8d\n",  p->ru_ioch            );
  g_message(" Number of IPC messages sent                    %8d\n",  p->ru_msgsnd          );
  g_message(" Number of IPC messages received                %8d\n",  p->ru_msgrcv          );
  g_message(" Number of signals delivered                 %8d\n",  p->ru_nsignals        );
  g_message(" Number of voluntary context switches:       %8d\n",  p->ru_nvcsw           );
  g_message(" Number of involuntary context switches                     %8d\n",  p->ru_nivcsw          );

}
/**
 * sim_debug_terminate:
 *
 */
void
sim_debug_terminate (gint signum, gboolean core)
{
  unlink(OS_SIM_RUN_DIR);
  
  if (core)
	{
		signal(signum, SIG_DFL);
    kill(getpid(), signum);
	}
	else
		exit(EXIT_SUCCESS);
}

/**
 * sim_debug_output
 *
 * print a trace log
*/

#define TXT_BUF_SIZE 4096

void sim_debug_output (const char *format,void *p,const char *file,const char *func,unsigned int line, ...){
	va_list args;
	int len,len1;
	char tempbuf[TXT_BUF_SIZE];
	char *buf;
	char *pbuf = NULL;
	if	 (ossim.log.level == G_LOG_LEVEL_DEBUG){
	 	 va_start (args, line);
	 	 len = snprintf (NULL,0,"%p:%s:%s:%u => ",p,file,func,line);
   	 len1 =vsnprintf (NULL,0,format,args);
	 	 va_end (args);
	 	 if ((len+len1+2)<(TXT_BUF_SIZE)){
	 	 	buf = tempbuf;
	 	 }else{
	 	 	if (( pbuf = buf = malloc (len+len1+2)) == NULL){
	 	 		g_warning ("Can't alloc memory to send log message\n");
	 	 		abort ();
	 	 	}
	 	 }
	 	 if (buf != NULL){
	 	 	len = snprintf (buf,len+1,"%p:%s:%s:%u => ", p, file, func, line);
	 	 	va_start (args,line);
	 	 	len1 = vsnprintf (&buf[len],len1+1,format,args);
	 	 	//g_message (">>>> len:%u len1:%u",len,len1);
	 	 	//buf[len1+len] = '\0';
	 	 	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,buf);
	 	 }
	 	 if (pbuf)
	 	 	free (pbuf);
	 	 
	 	 va_end (args);
		}
}
