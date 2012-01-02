<?php
/*****************************************************************************
*
*    License:
*
*   Copyright (c) 2003-2006 ossim.net
*   Copyright (c) 2007-2009 AlienVault
*   All rights reserved.
*
*   This package is free software; you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation; version 2 dated June, 1991.
*   You may not use, modify or distribute this program under any other version
*   of the GNU General Public License.
*
*   This package is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this package; if not, write to the Free Software
*   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
*   MA  02110-1301  USA
*
*
* On Debian GNU/Linux systems, the complete text of the GNU General
* Public License can be found in `/usr/share/common-licenses/GPL-2'.
*
* Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
****************************************************************************/
/**
* Class and Function List:
* Function list:
* Classes list:
*/
require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
require_once ('ossim_db.inc');
$db   = new ossim_db();
$conn = $db->connect();

$user = GET('user');
$pass = GET('pass');
$key = GET('key');
ossim_valid($user, OSS_USER, 'illegal:' . _("user"));
ossim_valid($pass, OSS_ALPHA, OSS_DIGIT, OSS_PUNC_EXT, 'illegal:' . _("pass"));
ossim_valid($key, OSS_ALPHA, OSS_DIGIT, OSS_PUNC_EXT, 'illegal:' . _("key"));
if (ossim_error()) {
    die(ossim_error());
}
// Check if is ADMIN
$list = Session::get_list($conn,"WHERE login='$user' AND pass='".md5($pass)."'");
if (count($list) > 0) {
	$u = $list[0];
	if ($user == ACL_DEFAULT_OSSIM_ADMIN || $u->get_is_admin()) {
		$f = fopen("/tmp/tmpkey","w");
		fputs($f,$key."\n");
		fclose($f);
		$cmd = "sudo /usr/share/ossim/scripts/sem/insertsshkey.pl /tmp/tmpkey";
		$output = explode("\n",`$cmd`);
		unlink("/tmp/tmpkey");
		if ($output[0] == "OK") { echo "OK"; }
	}
} else {
	echo "USERERROR";
}
$db->close($conn);
