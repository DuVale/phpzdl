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

function enable_button(id, action)
{
    var elem = $('#'+id)[0];
    $.data(elem, 'status', 'enabled');
    $('#'+id).removeClass('buttonoff');
    $('#'+id).attr('disabled', '');
}

function disable_button(id, action)
{
    var elem = $('#'+id)[0];
    $.data(elem, 'status', 'disabled');
    $('#'+id).attr('disabled', 'disabled');
    $('#'+id).addClass('buttonoff');
}

function toggle_tree()
{
    $("#avc_clcontainer").toggle();
    
    if ( $("#avc_cmcontainer img").hasClass('show') )
    {
        $("#avc_cmcontainer").html("<img src='images/right_arrow.png' class='hide' title='"+labels[0]+"' alt='"+labels[0]+"'/>");
    }
    else
    {
        $("#avc_cmcontainer").html("<img src='images/left_arrow.png' class='show' title='"+labels[1]+"' alt='"+labels[1]+"'/>");
    }
}

/*******************************************************
*********                Messages               ********
********************************************************/

function Message(){}

Message.show_tooltip = function(type, msg, style){
    
    var st_class = 'avc_tooltip_error';
    var img      = 'avc_error.png';
    
    switch (type){
            
        case 'info':
           st_class = 'avc_tooltip_info';
           img      = 'avc_info.png';
        break;
        
        case 'success':
           st_class = 'avc_tooltip_success';
           img      = 'avc_success.png';
        break;
        
        case 'warning':
           st_class = 'avc_tooltip_warning';
           img      = 'avc_warning.png';
        break;
        
        default:
           st_class = 'avc_tooltip_error';
           img      = 'avc_error.png';
    
    } 
    
    var html = "<div id='avc_tooltip' class='"+st_class+"' style='"+style+"'>"
                + "<img src='/ossim/av_center/images/"+img+"'/>" + msg  + 
               "</div>";
          
    return html;
};
   
Message.show_message = function(type, msg, style){
    
    var st_class = 'ossim_error';
          
    switch (type){
            
        case 'error':
           st_class = 'ossim_error';
        break;
        
        case 'info':
           st_class = 'ossim_info';
        break;
        
        case 'success':
           st_class = 'ossim_success';
        break;
        
        case 'warning':
           st_class = 'ossim_alert';
        break;
        
        default:
           st_class = 'ossim_error';
    } 
    
    var html = "<div class='"+st_class+"' style='"+style+"'>"
                    + msg  + 
               "</div>";
          
    return html;
};

Message.show_loading = function(msg, style){
    
    var st_class  = '';
    var div_style = ( style == '' ) ? "text-align: left; padding-left: 5px;" : style;
    
    var html = "<div style='"+div_style+"'>" +
                    "<img src='/ossim/pixmaps/loading3.gif' border='0' align='abs_middle'/>" +
                    "<span style='margin-left: 8px'>"+ msg + "</span>" + 
               "</div>";
          
    return html;
};








   
