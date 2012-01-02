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

require_once 'classes/Session.inc';
Session::useractive("session/login.php");
require_once 'classes/Security.inc';
require_once 'classes/WebIndicator.inc';
require_once 'classes/Util.inc';

// Refresh hour
$tz     = Util::get_timezone();
$timetz = gmdate("U")+(3600*$tz);

if ( GET("lasthour") == "1" ) 
{
	echo gmdate("H:i",$timetz);
	exit();
}

require_once ('ossim_conf.inc');
$conf      = $GLOBALS["CONF"];
$version   = $conf->get_conf("ossim_server_version", FALSE);
$prodemo   = ( preg_match("/pro|demo/i",$version) ) ? true : false;

//$ntop_link = $conf->get_conf("ntop_link", FALSE);
ossim_set_lang();

$uc_languages = array(
    "de_DE.UTF-8",
    "de_DE.UTF8",
    "de_DE",
    "en_GB",
    "es_ES",
    "fr_FR",
    "pt_BR"
);
//$sensor_ntop   = parse_url($ntop_link);
$ocs_link      = $conf->get_conf("ocs_link", FALSE); //Policy host
$glpi_link     = $conf->get_conf("glpi_link", FALSE);
$ovcp_link     = $conf->get_conf("ovcp_link", FALSE);  //No se usa

$menu  = array();
$hmenu = array();
/*
$placeholder = gettext("Dashboard");
$placeholder = gettext("Events");
$placeholder = gettext("Monitors");
$placeholder = gettext("Incidents");
$placeholder = gettext("Reports");
$placeholder = gettext("Policy");
$placeholder = gettext("Correlation");
$placeholder = gettext("Configuration");
$placeholder = gettext("Tools");
*/

$placeholder = gettext("Logout");

// Passthrough Vars
$status = "Open";
if (GET('status') != null) $status = GET('status');
/* Menu options */

include ("menu_options.php");

if ( $prodemo ) {
    require_once ('classes/Webinterfaces.inc');
    require_once ('ossim_db.inc');
    
    /* connect to db */
    $dbwebinterfaces    = new ossim_db();
    $connwebinterfaces  = $dbwebinterfaces->connect();

    $webinterfaces_list = Webinterfaces::get_list($connwebinterfaces, "where status=1");
    
    if (count($webinterfaces_list > 0))
    {
        foreach($webinterfaces_list as $webinterface)
        {
			$menu[md5("Remote Interfaces")]["name"] = "Remote Interfaces";
            $menu[md5("Remote Interfaces")][md5($webinterface->get_name())] = array(
                "name"   => $webinterface->get_name()." [".$webinterface->get_ip()."]",
                "id"     => "web_interfaces",
                "target" => "_blank",
                "url"    => $webinterface->get_ip()
            );
        }
    }
    $dbwebinterfaces->close($connwebinterfaces);
}

//Logout
$menu[md5("Logout")]["name"] = "Logout";
$menu[md5("Logout")][]       = "session/login.php?action=logout"; 

$hmenu[md5("Userprofile")][] = array(
    "name" => gettext("My Profile"),
    "id"   => "Userprofile",
    "url"  => ( $opensource ) ? "session/modifyuserform.php?user=".Session::get_session_user()."&frommenu=1" : "acl/users_edit.php?login=".Session::get_session_user()."&frommenu=1",
    "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:my_profile','Help');"
);

// Custom menu options
@include("menu_options_custom.php");

/* Generate reporting server url */

switch ($conf->get_conf("bi_type", FALSE)) 
{
    case "jasperserver":
    default:
        if ($conf->get_conf("bi_host", FALSE) == "localhost") 
		    $bi_host = $_SERVER["SERVER_NAME"];
        else
            $bi_host = $conf->get_conf("bi_host", FALSE);
        
        if (!strstr($bi_host, "http")) 
            $reporting_link = "http://";
        
        $bi_link = $conf->get_conf("bi_link", FALSE);
        $bi_link = str_replace("USER", $conf->get_conf("bi_user", FALSE) , $bi_link);
        $bi_link = str_replace("PASSWORD", $conf->get_conf("bi_pass", FALSE) , $bi_link);
        $reporting_link.= $bi_host;
        $reporting_link.= ":";
        $reporting_link.= $conf->get_conf("bi_port", FALSE);
        $reporting_link.= $bi_link;
}


$option  = GET('hmenu');
$soption = GET('smenu');
$url     = addslashes(GET('url'));


if ($url != "") 
{
	$url_check = preg_replace("/\.php.*/",".php",$url);
	if (!file_exists($url_check)) 
	{
		echo _("Can't access to $url_check for security reasons");
		exit;
	}
} 
        
    
if (empty($option)) {

    $aux_menu = array_slice($menu, 0,1);
	$option = key($aux_menu);

    $aux_menu = array_shift($aux_menu);
    $aux_menu = array_slice($aux_menu, 1,1);
    $soption = key($aux_menu);
    
}
if (empty($soption))
{
    $aux_menu = $menu[$option];
    $aux_menu = array_slice($aux_menu, 1,1);
    $soption = key($aux_menu);
} 

//If we try to load an element of the accordion and we don't have permissions, we show an special error message and the accordion without any item selected
 if(!$menu[$option]){
	$url = 'wrong_access.php';
	$option = '';
	$soption = '';
 }


?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<title>Ossim Menu</title>
<head>
	<link rel="stylesheet" type="text/css" href="style/top.css">
	<link type="text/css" href="style/default/jx.stylesheet.css" rel="stylesheet" />
	<script type="text/javascript" src="js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="js/jquery.jixedbar.js"></script>
	<script type="text/javascript" src="js/accordian.js" ></script>
	<script type="text/javascript">
		var newwindow;
	
		function new_wind(url,name)
		{
			newwindow=window.open(url,name,'height=768,width=1024,scrollbars=yes');
            if (window.focus) {newwindow.focus()}
		}
	
		function fullwin(){
			window.open("index.php","main_window","fullscreen,scrollbars")
		}
	
		$(document).ready(function() {
			new Accordian('basic-accordian',5,'header_highlight');
			$("#side-bar").jixedbar();
			$("#side-bar").css('visibility', 'visible');
			$("#side-bar2").css('visibility', 'visible');
		}); 
		
		function refresh_hour() {
			$.ajax({
				type: "GET",  
				url: "top.php?lasthour=1&bypassexpirationupdate=1",
				success: function(msg) {
						$("#hour").html(msg); 
				}
			});
		}
        
        function newconsole(ip){
            
            if(parent.main.location.search==""){
                sep = "?";
            }else{
                sep = "&";
            }
            
            var newremoteconsole
            var width = 1200;
            var height = 720;
            var left = (screen.width/2)-(width/2);
            var top = (screen.height/2)-(height/2);
            var strWindowFeatures = "menubar=no,location=no,resizable=yes,scrollbars=yes,status=yes, toolbar=no, personalbar=yes, chrome=yes, centerscreen=yes, top="+top+", left="+left+", height="+height+",width="+width;
            newremoteconsole = window.open('https://' + ip + parent.main.location.pathname + parent.main.location.search + sep + '<?php echo ("login=".$_SESSION['_remote_login']) ?>',ip, strWindowFeatures);
            newremoteconsole.focus();
            
        }	
		
	</script>
	
	<style type='text/css'>
		html, body {height:100%;overflow-y:auto;overflow-x:hidden}
		/*#side-bar ul, #side-bar span {visibility: hidden;}*/
		
		#side-bar, #side-bar2 {visibility: hidden;}
	</style>
</head>

<body leftmargin='0' topmargin='0' marginwidth='0' marginheight='0' bgcolor="#D2D2D2">

	<table width="100%" height="100%" border='0' cellpadding='0' cellspacing='0' style="border:1px solid #AAAAAA">

		<tr>
			<td valign="top">

				<table width="100%" border='0' cellpadding='0' cellspacing='0'>
					<!--<tr><td style="background:#678297 url(pixmaps/top/blueshadow.gif) top left" align="center" height="40" class="white"> :: Operation :: </td></tr> -->
					<tr>
						<td>
							<div id="basic-accordian" ><!--Parent of the Accordion-->
				
							<?php
							$moption = $hoption = "";
							//If not url is passed by get, we construct the url of the content to show
                            if ($url == "") {
                                $url = $menu[$option][$soption]["url"];
                            } 
                            $hoption = md5($menu[$option][$soption]["name"]);
                            $moption = md5($menu[$option][$soption]["id"]);
							foreach($menu as $name => $opc) 
							{
								if ($name != md5("Logout"))
								{
									if ( !isset($language) ) 
										$language = "";

									$open   = ($option == $name) ? "header_highlight" : "";
									$txtopc = (in_array($language, $uc_languages)) ? (Util::htmlentities(strtoupper(html_entity_decode(gettext($opc["name"]))))) : gettext($opc["name"]);
							
									?>
									<!--Start of each accordion item-->
									<div id="test<?php echo $name ?>-header" class="accordion_headings <?php echo $open ?>">										
                                        <?php
											//Special case for cloud:
                                            if( $name==md5("My Test") && !Session::am_i_admin() ) {
                                                $local_ip = `grep framework_ip /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`;
                                                $local_ip = trim($local_ip);
                                            
                                                require_once ('ossim_db.inc');
                                                
                                                $db     = new ossim_db();
                                                $conn   = $db->connect();
                                                $query  = "select t.id,t.allowed_sensors from acl_entities e,acl_templates t where t.id=e.template_sensors AND e.admin_user=?";
                                                $params = array(Session::get_session_user());

                                                if (!$rs = & $conn->Execute($query,$params)) {
                                                    print $conn->ErrorMsg();
                                                    exit;
                                                } else {
                                                    $aux = explode(",", trim($rs->fields['allowed_sensors']));
                                                    
                                                    if( in_array($local_ip, $aux) ) {
                                                        $opc["name"] = "mytest_plus";
                                                    }

                                                 $db->close($conn);
                                                }
                                            }
                                        ?>
										&nbsp;<img src="pixmaps/menu/<?php echo str_replace(" ","",strtolower($opc["name"])) ?>.gif" border=0 align="absmiddle"> &nbsp; <?php echo $txtopc ?>
									</div>
                                    
									<!--Prefix of heading (the DIV above this) and content (the DIV below this) to be same... eg. foo-header & foo-content-->
									<?php 
                                    
                                    //If the accordion only has one child, the content will be loaded without click needed
									if ( count($menu[$name]) == 2 && $opc["name"] != "Remote Interfaces" )
									{
                                        $keys        = array_keys($menu[$name]);
                                        $url_hmenu   = $menu[$name][$keys[1]]['id'];
                                        $url_smenu   = $hmenu[md5($url_hmenu)][0]['id'];
										$query_ch    = ( preg_match('/\?/', $menu[$name][$keys[1]]["url"]) ) ? "&" : "?";
										$default_url = $menu[$name][$keys[1]]["url"].$query_ch."hmenu=".md5($url_hmenu)."&smenu=".md5($url_smenu);
									}
									else
										$default_url = "";                      
			
									$div_id  = "test$name-content";
									$div_url = ( $default_url !="" ) ? " url='$default_url'" : "";
									
									?>
									<!--DIV which show/hide on click of header-->
									<div id="<?php echo $div_id?>"<?php echo $div_url?>>

										<!--This DIV is for inline styling like padding...-->
										<div class="accordion_child">
											<table cellpadding='0' cellspacing='0' border='0' width="100%">
												<?php
                                                
												if (is_array($opc)) 
												{
													array_shift($opc);
													foreach($opc as $j => $op)													
													{
                                                        //Special case - Web interfaces
                                                        if($op['id'] == "web_interfaces") {                                                                                                
															$onclick_ref = "newconsole('".$op["url"]."')";
														} 
                                                        else{
															$onclick_ref = "document.location.href='".$_SERVER['SCRIPT_NAME']."?hmenu=".$name."&smenu=".$j."'";
														}
																								
                                                        $txtsopc = (in_array($language, $uc_languages)) ? Util::htmlentities(strtoupper(html_entity_decode($op["name"]))) : $op["name"];
                                                        $lnk     = (count($menu[$name]) == 2 || ($option == $name && $soption == $j)) ? "on" : "";
                                                        
                                                        ?>
                                                        <tr>
                                                             <td>
                                                                 <div class="opc<?php echo $lnk ?>" onclick="<?php echo $onclick_ref ?>">
                                                                    <table cellpadding='0' cellspacing='0' border='0' width="100%">
                                                                         <tr>
                                                                            <td class="cell right"><img src="pixmaps/menu/icon0.gif"/></td>
                                                                            <td class="cell" style='white-space: nowrap;'>
                                                                                <span class="lnk<?php echo $lnk ?>"><?php echo $txtsopc ?></span>
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <?php                                                                                                                                  
													}
                                                    ?>
													<tr><td height='2' bgcolor='#575757'></td></tr>
													<tr><td height='1' bgcolor='#FFFFFF'></td></tr>
                                                    <?php
												}
												?>
											</table>
										</div>
									</div>
					  
									<?php
								}
							}
						?>
							</div>
						</td>
					</tr>
				</table>

				<div id="side-bar">
					<ul>        
						<li title="<?php echo _("User options")?>"><a href="#"><img src="pixmaps/myprofile.png" alt="<?php echo Session::get_session_user()?>" /></a>
							<ul>
								<li title="<?php echo _("Maximize")?>"><a href="#" onClick="fullwin()"><img src="pixmaps/maximize.png" align="absmiddle">&nbsp;&nbsp;&nbsp;<?php echo _("Maximize")?></a></li>
								<li title="<?php echo _("Logout")?>"><a href='javascript:void(0)' onclick="window.parent.document.location.href='session/login.php?action=logout'; return false;"><img src="pixmaps/logout.png" align="absmiddle"/>&nbsp;&nbsp;&nbsp;<?php echo _("Logout")?></a></li>
								<?php
                                if ( $opensource )
                                    $mp_link  = "session/modifyuserform.php?user=".Session::get_session_user();
                                else
                                    $mp_link = "acl/users_edit.php?login=".Session::get_session_user();  

                                $mp_link .= "&hmenu=".md5("Userprofile")."&smenu=".md5("Userprofile")."&frommenu=1";
                                ?>
                                <li title="<?php echo _("My Profile")?>"><a href="<?php echo $mp_link?>" target="main"><img src="pixmaps/myprofile.png" align="absmiddle">&nbsp;&nbsp;&nbsp;<?php echo _("My Profile")?></a></li>
							</ul>
						</li>
					</ul>
					
					<span class="jx-separator-right"></span>
					
					<ul class="jx-bar-button-right">
						<?php 
						if( Session::am_i_admin() ) 
						{ 
							?>
							<li title="<?php echo _("System<br/>Status")?>"><a href="sysinfo/index.php?hmenu=<?php echo md5("Sysinfo")?>&smenu=<?php echo md5("Hardware Info")?>" target="main"><img src="pixmaps/status.png"/></a>&nbsp;</li>
							<?php 
						} 
						?>
					</ul>
					
					<ul class="jx-bar-button-right">
						<?php 
						if(Session::menu_perms("MenuReports", "ReportsHostReport") || Session::am_i_admin()) 
						{ 
							?>
							<li title="<?php echo _("Data Snapshot")?>"><a href="<?php echo "report/host_report.php?hmenu=".md5("Sysinfo")."&smenu=".md5("Sysinfo")."&asset_type=any&asset_key=&start_date=".gmdate("Y-m-d",$timetz-604800)."&end_date=".gmdate("Y-m-d",$timetz) ?>" target="main"><img src="pixmaps/compass.png"/></a></li>
							<?php 
						} 
						?>
					</ul>
				</div>

				<?php
				require_once ('classes/DateDiff.inc');
				require_once ('ossim_db.inc');
				require_once ('ossim_conf.inc');

				//Logged time
                
                $db           = new ossim_db();
				$conn         = $db->connect();
				$all_sessions = Session_activity::get_list($conn," ORDER BY activity desc");
						
				foreach ($all_sessions as $sess) 
				{
					if ($sess->get_id() == session_id()) 
					{
						$ago = str_replace(" ago","",TimeAgo(Util::get_utc_unixtime($conn,$sess->get_logon_date()),Util::get_unixtime_utc()));
					}
				}				
                
                // Active users
                
                $where  = "";
                $ausers = array();

                if  ( Session::am_i_admin() || ($pro && Acl::am_i_proadmin()) )
                {
                    if ( Session::am_i_admin() )
                        $users_list = Session::get_list($conn, "ORDER BY login");
                    else
                        $users_list = Acl::get_my_users($conn,Session::get_session_user());
                    
                    
                    if ( is_array($users_list) && !empty($users_list) )
                    {
                        foreach($users_list as $k => $v)
                            $ausers[] = ( is_object($v) )? $v->get_login() : $v["login"];
                        
                        $where = "WHERE login in ('".implode("','",$ausers)."')";
                    }
                }
                else 
                    $where = "WHERE login = '".Session::get_session_user()."'";

                $users = count( Session_activity::get_list($conn, $where." ORDER BY activity desc") );
                
                
				$db->close($conn);

				// TIME DEBUG
				//var_dump($tz);
				//var_dump(gmdate("U"));
				//var_dump($timetz);
				//var_dump($sess->get_activity());
				//var_dump(Util::get_utc_unixtime($conn,$sess->get_activity()));
				?>

				<div id="side-bar2" class="jx-bottom-bar jx-bar-rounded-bl jx-bar-rounded-br">
					<table style="width:100%">
						<tr>
							<td class="jx-gray">
								<?php 
                                $us_smenu = ( $sstatus ) ? "Sessions" : "Sysinfo";
                                echo  _("User session").": <a href='userlog/opened_sessions.php?hmenu=".md5("Sysinfo")."&smenu=".md5($us_smenu)."' target='main' class='jx-gray-b'>$ago</a>" ?>
								<br/>
								<span style="float:left"><?php echo "<a href='userlog/opened_sessions.php?hmenu=".md5("Sysinfo")."&smenu=".md5($us_smenu)."' target='main' class='jx-gray-b'>$users</a> "._("active users") ?></span>
								<span style="float:right" id="hour"><?php echo gmdate("H:i",$timetz)?></span>
							</td>
						</tr>
					</table>
				</div>
			</td>
		</tr>
	</table>
	
<?php
if ( $url != "" && $url != 'wrong_access.php') 
{

	//Tab option: If the tab is not specified in the url, we set it adding hmenu and smenu
    if ( !preg_match("/hmenu\=/",$url) )
    {
        $url .= ( preg_match("/\?/", $url) ) ? "&" : "?";
        if ( $hoption == md5("Policy") && $moption!= md5("Actions") ) 
            $hoption = $hoption;
        else
            $hoption = $moption;
        
        $url .= "hmenu=".$hoption. "&smenu=".$moption;
        
    } 
        
}


?>

<!-- The url is loaded in the main frame: -->
<script type='text/javascript'> window.open('<?php echo $url ?>', 'main') </script>

<script type='text/javascript'>
	setInterval('refresh_hour()',60000);
</script>
</body>
</html>
