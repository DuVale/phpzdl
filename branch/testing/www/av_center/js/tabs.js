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


/*******************************************************
******                Tabs Update                 ******
********************************************************/

function show_proxy_conf()
{
    var proxy = $('#proxy_conf').val();
    
    if ( proxy == 'manual')
        $('#container_proxy').show();
    else
        $('#container_proxy').hide(); 
        
    show_bapply();
}

function show_bapply()
{
    $('#b_apply').show();
    $("#b_apply").bind('click', function()  { update_proxy(); });
}

function update_proxy()
{
    //Unbind function
    var elem   = $('#b_apply')[0];
    var status = $.data(elem, 'status');
    
    if ( status == 'disabled' )
        return false;
    
    disable_button('b_apply', 'click');
   
    $('.status_action').html('');
    
    var uuid = $(layer).dynatree("getActiveNode").data.key;
    
    $.ajax({
        type: "POST",
        url: "data/tabs/update/update_actions.php",
        data: "update_proxy=1" + "&" + $('#form_proxy_conf').serialize()+"&uuid="+uuid,
        beforeSend: function( xhr ) {
            $('#avc_actions').html(Message.show_loading(messages[8], '')); 
        },
        success: function(html){
            
            var session = new Session(html, '');
                
            session.check_session_expired();
            if ( session.expired == true )
                session.redirect();             
            
            $('#avc_actions').html(''); 
            
            var status = html.split("###");
            
            enable_button('b_apply', 'click');
            
            if ( status[0] == "Ok")
            {
                var msg = Message.show_tooltip('success', status[1], 'top: -8px;');
                $('.status_action').html('<div style="text-align:left;">'+msg+'</div>'); 
            }
            else if ( status[0] == "error")
            {
                var msg = Message.show_tooltip('error', status[1], 'top: -8px;');
                $('.status_action').html('<div style="text-align:left;">'+msg+'</div>'); 
            }  
            
            setTimeout('$(".status_action").fadeOut(4000);', 10000);	
        }
    });
}


function show_all_packages(package_name, id, type)
{
    var prefix   = (type == 'alienvault' ) ? "av" : "deb";
    var id_img   = '#ip_img_'+prefix+"_"+id;
    var id_pkg   = '#all_pkgs_'+prefix+"_"+id;
    
    var src  = $(id_img).attr("src");
        
    var src1 = "images/minus-small.png";
    var src2 = "images/plus-small.png";
    
    if (src == src1)
    {
        $(id_pkg).slideUp();
        $(id_img).attr("src", src2);
    }
    else
    {
        $(id_pkg).show();
        
        $(id_img).attr("src", src1);
        
        $.ajax({
            type: "POST",
            url: "data/tabs/update/update_actions.php",
            data: "info_package=1" + "&package_name=" + package_name + "&type=" + type + "&uuid=" + menu.uuid,
            beforeSend: function( xhr ) {
                
                $(id_pkg).html("<td colspan='3'>"+Message.show_loading(messages[0], 'padding: 5px 0px 0px 5px; height: 100px; font-size:11px;')+"</td>"); 
            },
            success: function(html){
                
                var session = new Session(html, '');
                
                session.check_session_expired();
                if ( session.expired == true )
                    session.redirect();             
                
                var status = html.split("###");
                $(id_pkg).html('');
                              
                if ( status[0] == "Ok")
                {
                   $(id_pkg).html("<td colspan='3'>"+status[1]+"</td>");
                }
                else if ( status[0] == "error")
                {
                    var msg = Message.show_tooltip('warning', status[1], 'margin:auto; text-align: center;');
                    $(id_pkg).html("<td colspan='3'><div style='padding: 40px 0px;'>"+msg+"</div></td>"); 
                }  
            }
        });
    }
}











