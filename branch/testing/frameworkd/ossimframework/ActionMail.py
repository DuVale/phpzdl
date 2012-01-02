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
import smtplib
from email.mime.text import MIMEText
#
# LOCAL IMPORTS
#
from Logger import Logger
#
# GLOBAL VARIABLES
#
logger = Logger.logger

class ActionMail:

    def __init__(self):
        
        self.__smtp = smtplib.SMTP()


    def sendmail(self, sender, recipients, subject, message):
    
        # Create a text/plain message
        msg = MIMEText(message, 'plain', 'latin-1')

        msg['Subject'] = subject
        msg['From'] = sender
        msg['To'] = ", ".join(recipients)

        # Send the message via our own SMTP server.
        try:
            logger.info("Trying to send mail...Connection to the SMTP server..")
            self.__smtp.connect()
            logger.info("Sending mail...")
            self.__smtp.sendmail(sender, recipients, msg.as_string())
        except Exception, e:
            logger.error("An error occurred by sending email: %s" % str(e))
        self.__smtp.close()


if __name__ == "__main__":

    m = ActionMail()

    email_sender = " Gil <dgil@ossim.net>"
    email_recipients = [ "Cristobal Rosa <crosa@alienvault.com>" ]
    email_subject = "Test message from Ossim frameworkd"
    email_message = "test.\r\ntest."

    m.sendmail(email_sender, email_recipients, email_subject, email_message)

# vim:ts=4 sts=4 tw=79 expandtab:
