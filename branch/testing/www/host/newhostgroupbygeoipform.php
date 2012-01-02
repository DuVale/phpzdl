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


require_once ('classes/Session.inc');
Session::logcheck("MenuPolicy", "PolicyHosts");

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link type="text/css" rel="stylesheet" href="../style/style.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript">
        
		function select_all(id)
        {
            var value = ( $("#"+id+":checked").length > 0 ) ? "checked" : "";
            
            $("input[type='checkbox']").each(function (index) {
				$(this).attr("checked", value);
            });
        }
		
		function delete_session_data()
		{
			$.ajax({
                type: "POST",
                url: "newhostgroupbygeoip.php",
                data: "delete_session_data=1"
			});
		}	
		
		function enable_button()
		{
			$('#created_selected').removeClass('buttonoff');
			$('#created_selected').attr('disabled', '');
			$('#created_selected').bind('click',  function() { add_selected() });
		}
		
		function disable_button()
		{
			$('#created_selected').unbind('click');
			$('#created_selected').attr('disabled', 'disabled');
			$('#created_selected').addClass('buttonoff');
		}
        
		
		function search_hg_by_geoip()
        {
            $.ajax({
                type: "POST",
                url: "newhostgroupbygeoip.php",
                data: "search=1",
                success: function(html){
                    var status = html.split("###");
                    $("#load_container").hide();
                    if ( status[0] == "error")
                    {
                        $("#hg_data").show();
                        $(".info").html("<div class='ossim_error'><div style='text-align: left; padding: 3px 5px 3px 15px;'>"+status[1]+"</div></div>");
                        $(".info").fadeIn(2000);
                    }
                    else
                    {
                        $("#hg_data").show();
                        $("#hg_data").html(status[1]);
                    }
                }
            });
        }        
        
        function add_selected()
        {
            var countries         = new Array();
            var countries_names   = new Array();
            var checked     = false;
            var cont        = 0;
            
            //Get selected host groups
            $("input[name^='chk_']").each(function (index) {
                checked = $(this).attr("checked");
                if ( checked == true )
                {
                    countries[cont]       = ($(this).attr("id")).replace("chk_", "");
                    countries_names[cont] = ($(this).attr("name")).replace("chk_", "");
                    cont                  = cont + 1;
                }
            });
            
            $('.info_update').remove();
            
            if ( countries.length > 0 )
            {
                disable_button();
                recursive_add_selected(countries,  countries_names);
            }
        }       
        
        function recursive_add_selected(countries, countries_names)
        {
            var img_loading        = "<img src='../pixmaps/loading3.gif' alt='<?php echo _("Loading")?>'/>";
            var first_country      = '';  
            var first_country_name = '';              
            
            if ( countries.length > 0 )
            {
                first_country      = countries.shift();
                first_country_name = countries_names.shift();
            }
            else
            {
                $('#ajax_info').html('');
                enable_button();
                return;
            }
            
            var info = img_loading + "<span style='margin-left: 5px;'><?php echo _("Creating Host group")?> AV_"+first_country_name +" ...</span>";
            $('#ajax_info').html(info);
            
            $.ajax({
                type: "POST",
                url: "newhostgroupbygeoip.php",
                data: "create=1&ccode="+first_country,
                success: function(html){
                    
                    var status  = html.split("###");
                    var id      = 'datahg_'+first_country;
                    var request = false;
                    
                    var cross_id =  'cross_'+first_country;
                    var tick_id  = 'tick_'+first_country;
                                      
                    if ( status[0] == "error")
                    {
                        var cross   =  '<div class="info_update" id="'+cross_id+'" style="clear: both; text-align: left; padding: 10px 0px">\n'
                                            + '<div style="float:left; width: 20px;">\n'  
												+ '<img align="absmiddle" src="../pixmaps/cross.png" alt="<?php echo _("Cross")?>" title="<?php echo _("Host group not created")?>"/>\n'
											+ '</div>\n'
                                            + '<div style="float:left; color: #D8000C; padding: 2px 0px 4px 0px;">'+ status[1] + '</div>\n' +
                                       '</div>\n';
                        
                        $('#'+id).append(cross);
                       
                        request = true;
                    }
                    else if ( status[0] == "OK")
                    {                    
                        var tick = '<div class="info_update" id="'+tick_id+'" style="float: right; margin-right: 5px">\n' +
										'<img src="../pixmaps/tick.png" alt="<?php echo _("Tick")?>" title="<?php echo _("Host group created successfully")?>"/>\n' +									 
                                   '</div>';
                        
                        $('#'+id).append(tick); 
                                              
                        request = true;
                    }
                             
                    
                    if ( request == true )
                    {
                        if ( countries.length > 0 )
                        {
                            recursive_add_selected(countries, countries_names);
                        }
                        else
                        {
                            delete_session_data();
							$('#ajax_info').html('');
                            enable_button();
                        }
                    }
                    else
                    {
                        var data      = ( html == '' ) ? '<?php echo _("Bad AJAX response")?>' : html;
                        var info      = "<div class='ossim_error'>"+data+"</div>";
                        
                        delete_session_data();
						$('#ajax_info').html('');
                        $('#t_avhg_error').show();
                        $('#t_avhg_error').html(info);
                        enable_button();                      
                    }    
                }
            });
        }
		
		
		$(document).ready(function(){
			search_hg_by_geoip();
        });
        
    </script>
                
    <style type='text/css'>
        #container {
            margin: auto; 
            width: 90%;
        }
        
        #load_container {
            margin: auto; 
            width: 90%;
            height: 99%;
        }
        
        #hg_data{
            margin: auto; 
            width: 100%;
            display: none;
        }
        
        #loading {
			position: absolute; 
			margin: auto; 
			text-align: center;
			background: #FFFFFF;
			z-index: 10000;
            top: 40%;
            left: 40%;     
        }
                
		#loading div{
				position: relative;
				top: 40%;
				margin:auto;
		}
		
		#loading div span{
				margin-left: 5px;
				font-weight: bold;      
		}
        
        #info {
            width: 90%;
            margin: auto;
            height: 100px;
            border: solid 1px green;
        }
        
        #t_avhg {
            width: 90%;
            margin: auto;
            background: transparent;
        }
        
        #t_avhg_error{
            width: 90%;
            margin: auto;
            background: transparent;
            display: none;
            margin-top: 30px;
        }
        
        .container_button {
            width: 98%;
            margin: auto;
            text-align: center;
            padding: 10px 0px;
            height: 25px;
        }
        
        .th_chk{ width: 30px; }
        
        .th_hgroups{ width: 170px; }
        
        #container_msg{ 
            width:  90%; 
            clear: both;
            margin: auto;
            height: 40px;
        }
        
        #ajax_info{ 
            width:  100%; 
            padding-top: 20px;
            clear: both;
            margin: auto;
        }
                        
    </style>
        
</head>
<body>

    <?php include ("../hmenu.php"); ?>

    <div id='container'>
        <div id='load_container'>
            <div id='loading'>
                <div>
                    <img src='../pixmaps/loading3.gif' alt='<?php echo _("Loading")?>'/>
                    <span><?php echo _("Searching Host groups by IP Geolocation.  Please wait")?>, ...</span>
                </div>
            </div>
        </div>
        
        <div id='hg_data'>
            <div class='info'></div>
        </div>
    </div>

</body>
</html>