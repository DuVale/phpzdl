<?php
$host_report_menu_flag = true; // To know when is already loaded
?>
<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
<link href="../style/jquery.contextMenu.css" rel="stylesheet" type="text/css" />
<script src="../js/jquery.contextMenu.js" type="text/javascript"></script>

<? if (!$noready) { ?>
<script type="text/javascript">
    function load_inframe(url) {
        if (typeof(top.frames['main']) != 'undefined') {
            top.frames['main'].document.location.href = url;
        } else {
            document.location.href = url;
        }
    }
	function load_contextmenu() {
		$('.HostReportMenu').contextMenu({
				menu: 'myMenu'
			},
				function(action, el, pos) {
                    if (action=='filter_rse') {
                        var aux = $(el).attr('id2').split(/;/);  
                        url="../forensics/base_qry_main.php?clear_allcriteria=1&hmenu=Forensics&smenu=Forensics&time_range=all&clear_criteria=time&search=1&sensor=&search_str="+aux[0]+"+AND+"+aux[1]+"&submit=Src+or+Dst+IP&ossim_risk_a=+";
                        load_inframe(url);
                    }
                    else if(action=='filter_rsfe') {
                        var aux = $(el).attr('id2').split(/;/);
                        url="../forensics/base_qry_main.php?clear_allcriteria=1&hmenu=Forensics&smenu=Forensics&time_range=all&clear_criteria=time&search=1&sensor=&search_str="+aux[0]+"+AND+"+aux[1]+"&submit=Src+or+Dst+IP&sip=&plugin=&ossim_risk_a=+&category%5B0%5D=3&category%5B1%5D=";
                        load_inframe(url);
                    }
					else if (action=='filter') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						url = "../forensics/base_qry_main.php?new=2&hmenu=Forensics&smenu=Forensics&num_result_rows=-1&submit=Query+DB&current_view=-1&ip_addr_cnt=1&sort_order=time_d&ip_addr%5B0%5D%5B0%5D=+&ip_addr%5B0%5D%5B1%5D=ip_both&ip_addr%5B0%5D%5B2%5D=%3D&ip_addr%5B0%5D%5B3%5D="+ip+"&ip_addr%5B0%5D%5B8%5D=+";
						;
                       load_inframe(url);
					} else if (action=='filter_src') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						url = "../forensics/base_qry_main.php?new=2&hmenu=Forensics&smenu=Forensics&num_result_rows=-1&submit=Query+DB&current_view=-1&ip_addr_cnt=1&sort_order=time_d&ip_addr%5B0%5D%5B0%5D=+&ip_addr%5B0%5D%5B1%5D=ip_src&ip_addr%5B0%5D%5B2%5D=%3D&ip_addr%5B0%5D%5B3%5D="+ip+"&ip_addr%5B0%5D%5B8%5D=+";
                       load_inframe(url);
					} else if (action=='filter_dst') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						url = "../forensics/base_qry_main.php?new=2&hmenu=Forensics&smenu=Forensics&num_result_rows=-1&submit=Query+DB&current_view=-1&ip_addr_cnt=1&sort_order=time_d&ip_addr%5B0%5D%5B0%5D=+&ip_addr%5B0%5D%5B1%5D=ip_dst&ip_addr%5B0%5D%5B2%5D=%3D&ip_addr%5B0%5D%5B3%5D="+ip+"&ip_addr%5B0%5D%5B8%5D=+";
                        load_inframe(url);
					} else if (action=='edit') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						url = "../host/modifyhostform.php?hmenu=Assets&smenu=Hosts&ip="+ip;
                        load_inframe(url);
					} else if (action=='unique') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						url = "../forensics/base_stat_alerts.php?clear_allcriteria=1&sort_order=occur_d&hmenu=Forensics&smenu=Forensics&ip_addr_cnt=1&ip_addr%5B0%5D%5B0%5D=+&ip_addr%5B0%5D%5B1%5D=ip_both&ip_addr%5B0%5D%5B2%5D=%3D&ip_addr%5B0%5D%5B3%5D="+ip+"&ip_addr%5B0%5D%5B8%5D=+";
                       load_inframe(url);
					} else if (action=='info') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						url = "../forensics/base_stat_ipaddr.php?ip="+ip+"&netmask=32";
                        load_inframe(url);
					} else if (action=='tickets') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						url = "../incidents/index.php?status=Open&hmenu=Tickets&smenu=Tickets&with_text="+ip;
                        load_inframe(url);
					} else if (action=='alarms') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						url = "../control_panel/alarm_console.php?hide_closed=1&hmenu=Alarms&smenu=Alarms&src_ip="+ip+"&dst_ip="+ip;
                        load_inframe(url);
					} else if (action=='sem') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						url = "../sem/index.php?hmenu=SEM&smenu=SEM&query="+ip;
                        load_inframe(url);
					} else if (action=='report') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						var hostname = aux[1];
						var url = "../report/host_report.php?asset_type=host&asset_key="+ip+"&greybox=0";
						if (hostname == ip) var title = "Host Report: "+ip;
						else var title = "Host Report: "+hostname+"("+ip+")";
						//GB_show(title,url,'90%','95%');
                       load_inframe(url);
						//var wnd = window.open(url,'hostreport_'+ip,'fullscreen=yes,scrollbars=yes,location=no,toolbar=no,status=no,directories=no');
						wnd.focus()
					} else if (action=='search') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						url = "../inventorysearch/userfriendly.php?hmenu=Assets&smenu=AssetSearch&ip="+ip;
                        load_inframe(url);
					} else if (action=='vulns') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						url = "../vulnmeter/index.php?hmenu=Vulnerabilities&smenu=Vulnerabilitie&sortby=t1.results_sent+DESC%2C+t1.name+DESC&submit=Find&type=hn&value="+ip;
                        load_inframe(url);
					} else if (action=='kndb') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						url = "../repository/index.php?hmenu=Repository&smenu=Repository&search_bylink="+ip;
                        load_inframe(url);
					} else if (action=='ntop') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						url = "/ntop/"+ip+".html";
						var wnd = window.open(url,'htop_'+ip,'scrollbars=yes,location=no,toolbar=no,status=no,directories=no');
					} else if (action=='flows_rse') {
						var aux = $(el).attr('id2');
						if (aux!='') {
							var aux2 = aux.split(/;/);
							var ip = aux2[0]; var ip2 = aux2[1];
							url = "../nfsen/index.php?tab=2&hmenu=Network&smenu=Network&ip="+ip+"&ip2="+ip2;
						} else {
							var aux = $(el).attr('id').split(/;/);
							var ip = aux[0];							
							url = "../nfsen/index.php?tab=2&hmenu=Network&smenu=Network&ip="+ip;
						}						
                        load_inframe(url);
					} else if (action=='flows') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						url = "../nfsen/index.php?tab=2&hmenu=Network&smenu=Network&ip="+ip;
                        load_inframe(url);
					} else if (action=='nagios') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						url = "/secured_nagios3/cgi-bin/status.cgi?host="+ip;
						var wnd = window.open(url,'nagios_'+ip,'scrollbars=yes,location=no,toolbar=no,status=no,directories=no');
					} else if (action=='whois') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						url = "http://whois.domaintools.com/"+ip;
						var wnd = window.open(url,'whois_'+ip,'scrollbars=yes,location=no,toolbar=no,status=no,directories=no');
					}
				}
			);
		$('.NetReportMenu').contextMenu({
				menu: 'myMenuNet'
			},
				function(action, el, pos) {
					var aux = $(el).attr('id').split(/;/);
					var ip = aux[0];
					var hostname = aux[1];
					var url = "../report/host_report.php?asset_type=net&asset_key="+hostname+"&greybox=0";
					if (hostname == ip) var title = "Host Report: "+ip;
					else var title = "Network Report: "+hostname+"("+ip+")";
					//GB_show(title,url,'90%','95%');
					var wnd = window.open(url,'hostreport_'+ip,'fullscreen=yes,scrollbars=yes,location=no,toolbar=no,status=no,directories=no');
					wnd.focus()
				}
			);
		$('.trlnka').contextMenu({
			menu: 'myMenuSid'
		},
			function(action, el, pos) {
				var aux = $(el).attr('id').split(/;/);
				var plugin_id = aux[0];
				var plugin_sid = aux[1];
				var url = "../policy/insertsid.php?plugin_id="+plugin_id+"&plugin_sid="+plugin_sid;
				var title = "Select a DS Group";
				if (typeof(GB_show) == "undefined") {
					document.location.href = url;
				} else {
					GB_show(title,url,'650','65%');
				}
				//var wnd = window.open(url,title,'fullscreen=yes,scrollbars=yes,location=no,toolbar=no,status=no,directories=no');
				//wnd.focus()
			}
		);
		$('.MapMenu').contextMenu({
			menu: 'myMenuMap'
		},
			function(action, el, pos) {
				var aux = $(el).attr('id').split(/;/);
				var plugin_id = aux[0];
				var plugin_sid = aux[1];
				var url = "../risk_maps/wizard.php";
				var title = "New Indicator";
				GB_show(title,url,'450','650');
				//var wnd = window.open(url,title,'fullscreen=yes,scrollbars=yes,location=no,toolbar=no,status=no,directories=no');
				//wnd.focus()
			}
		);
	}
	$(document).ready(function(){
		load_contextmenu();
		if (typeof postload == 'function') postload();
	});
</script>
<? } ?>
<ul id="myMenu" class="contextMenu">
<? if ($ipsearch) { ?>
<li class="search"><a href="#filter"><?=_("All events from this host")?></a></li>
<li class="search"><a href="#filter_src"><?=_("Events as source")?></a></li>
<li class="search"><a href="#filter_dst"><?=_("Events as destination")?></a></li>
<li class="info"><a href="#info"><?=_("Stats and Info")?></a></li>
<li class="search"><a href="#unique"><?=_("Analyze Asset")?></a></li>
<?php } ?>
<li class="report"><a href="#report"><?=_("Asset Report")?></a></li>
<li class="assetsearch"><a href="#search"><?=_("Asset Search")?></a></li>
<li class="edit"><a href="#edit"><?=_("Configure Asset")?></a></li>
<li class="whois"><a href="#whois"><?=_("Whois")?></a></li>
<li class="tickets"><a href="#tickets"><?=_("Tickets")?></a></li>
<li class="alarms"><a href="#alarms"><?=_("Alarms")?></a></li>
<?
if (!isset($conf)) {
	require_once ('ossim_conf.inc');
	$conf = $GLOBALS["CONF"];
}
$version = $conf->get_conf("ossim_server_version", FALSE);
$opensource = (!preg_match("/.*pro.*/i",$version) && !preg_match("/.*demo.*/i",$version)) ? true : false;
if (!$opensource) { ?>
<li class="sem"><a href="#sem"><?=_("Log")?></a></li>
<? } ?>
<? if (!$ipsearch) { ?>
<li class="sim"><a href="#filter"><?=_("Security Events")?></a></li>
<li class="sim"><a href="#filter_rse"><?=_("Related Security Events")?></a></li>
<li class="sim"><a href="#filter_rsfe"><?=_("Related Security Firewall Events")?></a></li>
<? } ?>
<li class="vulns"><a href="#vulns"><?=_("Vulnerabilities")?></a></li>
<li class="kndb"><a href="#kndb"><?=_("Knownledge DB")?></a></li>
<li class="ntop"><a href="#ntop"><?=_("Net Profile")?></a></li>
<li class="flows"><a href="#flows"><?=_("Traffic")?></a></li>
<li class="flows"><a href="#flows_rse"><?=_("Related Traffic")?></a></li>
<li class="nagios"><a href="#nagios"><?=_("Availability")?></a></li>
</ul>
<ul id="myMenuNet" class="contextMenu">
<li class="report"><a href="#report"><?=_("Network Report")?></a></li>
</ul>
<ul id="myMenuSid" class="contextMenu">
<li class="editds"><a href="#addsid"><?=_("Add this Event Type to a DS Group")?></a></li>
</ul>
<ul id="myMenuMap" class="contextMenu">
<li class="edit"><a href="#newindicator"><?=_("Create a new indicator here")?></a></li>
</ul>
