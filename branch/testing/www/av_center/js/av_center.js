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



/******************************************
****************** Tree *******************
*******************************************/

function Tree(b64_tree){
    
    //Singleton pattern
    if( typeof(Tree.instance) !== 'undefined' )
    {
        return Tree.instance;
    }
      
    this.b64_tree      = b64_tree[0]; 
    this.tree_error    = b64_tree[1];
    this.json_tree     = null; 
    this.current_node  = null;
    this.root_node     = null;
    this.layer         = 'srctree';
    Tree.instance      = this;
            
    this.load_tree     = function(){
        this.json_tree = "[" + Base64.decode(this.b64_tree) + "]";
        
        $('#tree_container_bt').append('<div id="'+this.layer+'" style="width:100%;"></div>');
        
        $('#'+this.layer).dynatree({
            minExpandLevel: 2,
            onActivate: function(dtnode) {
                tree.current_node = $('#'+tree.layer).dynatree("getActiveNode");
                section = new Section(tree.current_node, 'home');
                
                if ( tree.tree_error != '' )
                    section.section_error(tree.tree_error);
                else
                    section.load_section('home');    
            },
            children: eval(this.json_tree)
        });
        
        this.root_node = $('#'+this.layer).dynatree("getRoot");
        
    };
    
    this.get_current_uuid  = function(){
        return this.current_node.data.key;
    };
    
    this.get_current_profiles  = function(){
        return this.current_node.data.profiles;
    };
}


/******************************************
*************** Section *******************
*******************************************/


function Section(node, id_section)
{
    this.uuid               = node.data.key;
    this.profiles           = node.data.profiles;
    this.active_section     = id_section;
                  
    this.section_error = function(error_msg) {
        $.ajax({
            type: "POST",
            url: "data/section.php",
            data: "error_msg="+ error_msg,
            beforeSend: function( xhr ) {
                $('#avc_actions').html(Message.show_loading(messages[0], 'text-align:right')); 
            },
            success: function(section_html){
                $('#avc_actions').html(''); 
                               
                var status = section_html.split("###");
                
                if ( status[0] == "section")
                {
                    $('#avc_data').html(status[1]); 
                }
                else
                {
                    $('#avc_data').html("<div style='margin: auto; padding: 30px 0px;'>"+section_html+"</div>");
                }     
            }
        });
    };
            
    
    this.load_section = function(id_section)
    {
        section.active_section = id_section;
        section.set_bc();
    };
    
    this.set_bc = function(){
        
        var data = '';
        
        if ( section.active_section == 'home')
        {
            data = "pre_data="+tree.current_node.data.tooltip+"&";
        }
        
        data += "section="+section.active_section;
        
        $.ajax({
            type: "POST",
            url: "data/sections/get_bc.php",
            data: data,
            beforeSend: function( xhr ) {
                $('#avc_actions').html(Message.show_loading(messages[1], 'text-align:right')); 
            },
            success: function(bc_html){
                                
                //Check expired session                
                var session = new Session(bc_html, '');
                                
                session.check_session_expired();
                if ( session.expired == true )
                    session.redirect();
                
                var status = bc_html.split("###");
                                                
                if ( status[0] != 'error')
                {
                    $('#bc_data').html(bc_html);
                    $('#breadcrumbs').xBreadcrumbs({ collapsible: true });
                                                            
                    $.ajax({
                        type: "POST",
                        url: "data/section.php",
                        data: "uuid="+section.uuid+"&profiles="+ section.profiles+"&section="+id_section,
                        beforeSend: function( xhr ) {
                            $('#avc_actions').html(Message.show_loading(messages[0], 'text-align:right')); 
                        },
                        success: function(section_html){
                            
                            //Check expired session                
                            var session = new Session(section_html, '');
                                            
                            session.check_session_expired();
                            if ( session.expired == true )
                                session.redirect();
                                            
                            $('#avc_actions').html('');                                                  
                           
                            section.load_content(section_html);
                        }
                    });
                }
                else
                {
                    $('#avc_actions').html(''); 
                    section.load_content(bc_html);
                }
            }
        });
    };
    
    this.load_content = function(section_html){
        
        var status = section_html.split("###");
              
        if ( status[0] == "error")
        {
            var height = $('#avc_data').outerHeight();
            $('#avc_data').css('height', height);  
            section_html = status[1];
        }
                      
        $('#avc_data').html(section_html);  
        
        section.post_load();
    };
    
    this.post_load = function(){
    
        $('#avc_data').css('min-height', '500px');
        
        //Hide Tree
        if ( section.active_section == 'home' )
        {
            if ( $("#avc_cmcontainer img").hasClass('show') )
                toggle_tree();
                
            section.real_time();    
        }
    };
    
    
    this.real_time = function(){
        if ( section.active_section == 'home' )
        {
            System_status.real_time_home();
        }
    };
} 


/******************************************
**************** Session ******************
*******************************************/

function Session(data, url)
{
    this.url = ( url == '' ) ? '/ossim/session/login.php' : url;
    this.data    = data;
    this.expired = false;
    this.check_session_expired = function(){
        if ( typeof(data) == 'string' && data.match(/\<meta /i) )
        {
            this.expired = true;
        }
    };
    
    this.redirect = function(url){
        window.parent.document.location.href = '/ossim/session/login.php';
        return;
    }
}


/*******************************************************
*********         Home - System Status          ********
********************************************************/


function System_status(){}

System_status.real_time_home = function(){
    $.ajax({
        type: "POST",
        data: "uuid="+section.uuid+"&id_section=home",
        url: "data/sections/common/real_time.php",
        dataType: "json",
        cache: false,
        beforeSend: function( xhr ) {
            
        },
        success: function(data){
            
            //Check expired session
            var session = new Session(data, '');
            
            session.check_session_expired();
            if ( session.expired == true )
                session.redirect();
                                  
            if ( data.status == 'UP' )
            {
                //Memory - Progress bar
                var r_memory_data = new Array(data.percent_memused, data.memtotal, data.memfree, data.memused);
                System_status.set_pb_mem('r_memory_pbar', 'r_mem_data', r_memory_data);
                
                var s_memory_data = new Array(data.percent_virtualmemused, data.virtualmem, data.virtualmemfree, data.virtualmemused);
                System_status.set_pb_mem('s_memory_pbar', 's_mem_data', s_memory_data);
                
                //Memory - Spark Line
                r_memory_usage.push(data.percent_memused);
                s_memory_usage.push(data.percent_virtualmemused);
                
                if ( r_memory_usage.length > 20 ){
                   
                   r_memory_usage = r_memory_usage.splice(0, 1);
                }
                
                if ( s_memory_usage.length > 20 ){
                   
                   s_memory_usage = s_memory_usage.splice(0, 1);
                }
                                                                               
                $('#r_memory_spark_line').sparkline(r_memory_usage, { width:'120px', height: '30px', chartRangeMin: '0', chartRangeMax: '100'});
                $('#s_memory_spark_line').sparkline(s_memory_usage, { width:'120px', height: '30px', chartRangeMin: '0', chartRangeMax: '100'});
                
                
                //CPU - Progress bar
                var cpu_data = new Array(data.cpu);
                System_status.set_pb_cpu('cpu_pbar', cpu_data);   
                
                cpu_usage.push(data.cpu);
                                              
                if ( cpu_usage.length > 20 ){
                   
                   cpu_usage = cpu_usage.splice(0, 1);
                }
                                                                               
                $('#cpu_spark_line').sparkline(cpu_usage, { width:'120px', height: '30px', chartRangeMin: '0', chartRangeMax: '100'});
                
                
                //Others data
                $('#la_data').html(data.loadaverage);
                $('#rp_data').html(data.running_proc);
                $('#cs_data').html(data.current_sessions);
                                
                setTimeout('System_status.real_time_home();', 3000); 
            }
            else
                section.load_section('home');      
        }
    }); 
};

System_status.set_pb_mem = function(id_bar, id_data, data){
    $('#'+ id_bar + ' .ui-progress').animateProgress(data[0], function(){ $('#' + id_bar + ' .value').html(data[0] + " %")});
    
    $('#'+id_data +' .total').html(data[1]);
    $('#'+id_data +' .free').html(data[2]);
    $('#'+id_data +' .used').html(data[3]);
    
};

System_status.set_pb_cpu = function(id, data){
    $('#'+ id + ' .ui-progress').animateProgress(data[0], function(){ $('#' + id + ' .value').html(data[0] + " %")});
};

System_status.get_last_percentage = function(id){
    var percentage = $('#'+id+' .value').text();
        percentage = percentage.replace(" %", "");
                
    return percentage;
};

System_status.show_pie = function(id, data){
    
    $.jqplot(id, data, {
        grid: {
            drawBorder: false, 
            drawGridlines: false,
            background: 'rgba(255,255,255,0)',
            shadow:false
        },
        seriesColors: ["#4BB2C5","#E9967A"],
        seriesDefaults:{
            renderer:$.jqplot.PieRenderer,
            rendererOptions: {
                diameter: '80',
                showDataLabels: true
                
            }								
        },
        
        legend:{
            show:true,
            placement: 'outside',
            rendererOptions: {
                numberRows: 1
            },
            location:'s',
            marginTop:'-5px',
            marginBottom:'-5px'
            
        }    
    }); 
};





