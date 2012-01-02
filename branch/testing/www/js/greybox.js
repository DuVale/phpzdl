/* Greybox Redux
 * Required: http://jquery.com/
 * Written by: John Resig
 * Based on code by: 4mir Salihefendic (http://amix.dk)
 * License: LGPL (read more in LGPL.txt)
 * 2009-05-1 modified by jmalbarracin. Added GB_TYPE. Fixed  total document width/height
 * 2009-06-4 modified by jmalbarracin. Support of width %, height %
 * 2009-09-19 Added maximized window
 */

var GB_DONE   = false;
var GB_TYPE   = ''; // empty or "w"
var GB_HEIGHT = 400;
var GB_WIDTH  = 400;
var GB_SCROLL_DIFF = (navigator.appVersion.match(/MSIE/)) ? 1 : ((navigator.appCodeName.match(/Mozilla/)) ? 17 : 17 );
//var GB_HDIFF = (navigator.appVersion.match(/MSIE/)) ? 12 : ((navigator.appCodeName.match(/Mozilla/)) ? 42 : 18 );
var GB_HDIFF = 5;
var GB_SLEEP = (navigator.appVersion.match(/MSIE/)) ? 1000 : 0;
var GB_URL_AUX = "";
var GB_MOVE = false;
var GB_RESIZE = false;
var GB_X=0, GB_Y=0, GB_OFFSET = false;
var GB_RWIDTH=0, GB_RHEIGHT=0, GB_IWIDTH=0, GB_IHEIGHT=0;
var GB_MIN_HEIGHT =200;
var GB_MIN_WIDTH = 300;

function GB_show(caption, url, height, width) {
  GB_HEIGHT = height || 400;
  GB_WIDTH = width || 400;
  GB_URL_AUX = url;

  if(!GB_DONE) {
    $(document.body).append("<style> div {overflow:hidden}  iframe {overflow-x:scroll; overflow-y:scroll;} </style>");
	$(document.body).append("<div id='GB_overlay" + GB_TYPE + "'></div><div id='GB_window' width='"+GB_WIDTH+"' height='"+GB_HEIGHT+"'><div id='GB_head'><div id='GB_caption'></div><div id='GB_table'><table><tr><td><img src='/ossim/pixmaps/theme/close.png' id='GB_closeimg' alt='Close' title='Close'></td></tr></table></div></div><div id='GB_resize' style='position: absolute; bottom: 0; right: 0; width: 25px; height:25px; background:url(/ossim/pixmaps/theme/resize.png) no-repeat bottom right'></div></div>");
	$("#GB_closeimg").click(GB_hide);
	$("#GB_maximg").click(GB_maximize);
	$("#GB_overlay" + GB_TYPE).click(GB_hide);
	$(window).resize(GB_position);
	GB_DONE = true;
  }
  $("#GB_frame").remove();
  $("#GB_caption").html(caption);
  $("#GB_overlay" + GB_TYPE).show();
  GB_position();

  $("#GB_window").show();  

  $("#GB_head").hover(function (e) {
    if ($.browser.mozilla) $('body').css('cursor','-moz-grab');
	else $('body').css('cursor','url(/ossim/pixmaps/theme/grab.cur),auto');  
  }).mouseout(function (e) {
    $('body').css('cursor','default');  
  }).mousedown(function (e) {
    GB_MOVE = true;
    GB_X = e.pageX; GB_Y = e.pageY;
    GB_OFFSET = $("#GB_window").offset();
    if ($.browser.mozilla) $('body').css('cursor','-moz-grab');
	else $('body').css('cursor','url(/ossim/pixmaps/theme/grab.cur),auto');  
    noSelect();
  }).mouseup(function (e) {
    GB_MOVE = false;
    if ($.browser.mozilla) $('body').css('cursor','-moz-grab');
	else $('body').css('cursor','url(/ossim/pixmaps/theme/grab.cur),auto');    
    noSelect(false);
  });
  $("body").mousemove(function (e) {
    if (GB_MOVE) {
        if ($.browser.mozilla) $('body').css('cursor','-moz-grabbing');
        else $('body').css('cursor','url(/ossim/pixmaps/theme/grabbing.cur),auto');
        var dif_x = GB_X-e.pageX;
        var dif_y = GB_Y-e.pageY;
        $("#GB_window").css({top:GB_OFFSET.top-dif_y,left:GB_OFFSET.left-dif_x});
    }
    if(GB_RESIZE){
        var dif_x = GB_X-e.pageX;
        var dif_y = GB_Y-e.pageY;
        
        var aux_y = ((GB_RHEIGHT - dif_y - 34) > GB_MIN_HEIGHT) ? (GB_RHEIGHT - dif_y) : GB_MIN_HEIGHT + 34;
        var aux_x = ((GB_RWIDTH - dif_x) > GB_MIN_WIDTH) ? (GB_RWIDTH - dif_x) : GB_MIN_WIDTH;
        

        $("#GB_window").css({height: aux_y, width: aux_x});
           
        var aux_y = ((GB_IHEIGHT - dif_y) > GB_MIN_HEIGHT) ? (GB_IHEIGHT - dif_y) : GB_MIN_HEIGHT;
        var aux_x = ((GB_IWIDTH - dif_x) > GB_MIN_WIDTH) ? (GB_IWIDTH - dif_x) : GB_MIN_WIDTH;

        $("#GB_frame").css({height: aux_y, width: aux_x});

      
    }
  });
    //<img src='/ossim/pixmaps/theme/resize.png' title='resize' id='GB_closeimg' style='position: absolute; bottom: 0; right: 0;' />
  if (GB_SLEEP>0) sleep(GB_SLEEP);
  $("#GB_window").append("<iframe id='GB_frame' name='GB_frame' src='"+url+"' style='overflow:scroll;' scrolling='yes' frameborder='0'></iframe>");
  
  $("#GB_frame").css({height: GB_HEIGHT-39, width: $("#GB_window").width()});
  
  $("#GB_resize").hover(function (e) {
    $('body').css('cursor','se-resize');       
  }).mouseout(function (e) {
    $('body').css('cursor','default');  
  }).mousedown(function (e) {
    GB_RESIZE = true;
    GB_X = e.pageX; GB_Y = e.pageY;
    GB_RWIDTH  = $("#GB_window").width();
    GB_RHEIGHT = $("#GB_window").height();
    GB_IWIDTH  = $("#GB_frame").width(); 
    GB_IHEIGHT = $("#GB_frame").height();   
    noSelect();
    console.log($("#GB_frame"));
    console.log(GB_RWIDTH+";"+GB_RHEIGHT+";"+GB_IWIDTH+";"+GB_IHEIGHT+";");
  }).mouseup(function (e) {
    GB_RESIZE = false;
    noSelect(false);    
  });
  
  
}

function GB_show_nohide(caption, url, height, width) {
  GB_HEIGHT = height || 400;
  GB_WIDTH = width || 400;
  GB_URL_AUX = url;

  if(!GB_DONE) {
	$(document.body).append("<div id='GB_overlay" + GB_TYPE + "'></div><div id='GB_window'><div id='GB_head'><div id='GB_caption'></div><div id='GB_table'><table><tr><td><img src='/ossim/pixmaps/theme/close.png' id='GB_closeimg' alt='Close' title='Close'></td></tr></table></div></div></div>");
	$("#GB_overlay" + GB_TYPE).click(GB_onlyhide);
	$("#GB_closeimg").click(GB_onlyhide);
	$(window).resize(GB_position);
	GB_DONE = true;
  }
  $("#GB_frame").remove();
  $("#GB_caption").html(caption);
  $("#GB_overlay" + GB_TYPE).show();
  GB_position();

  $("#GB_window").show();

  if (GB_SLEEP>0) sleep(GB_SLEEP);
  $("#GB_window").append("<iframe id='GB_frame' name='GB_frame' src='"+url+"' frameborder='0'></iframe>");
}

function sleep(milliseconds) {
  var start = new Date().getTime();
  for (var i = 0; i < 1e7; i++) {
    if ((new Date().getTime() - start) > milliseconds){
      break;
    }
  }
}

function GB_onlyhide() {
  $("#GB_window,#GB_overlay" + GB_TYPE).hide();
  if (typeof(GB_onclose) == "function") GB_onclose(GB_URL_AUX);
}

function GB_hide() {
  $("#GB_window,#GB_overlay" + GB_TYPE).hide();
  if (typeof(GB_onclose) == "function") GB_onclose(GB_URL_AUX);
}

function GB_maximize() {
  $("#GB_window,#GB_overlay" + GB_TYPE).hide();
  if (typeof(GB_onclose) == "function") GB_onclose(GB_URL_AUX);
  window.open(GB_URL_AUX, '', 'fullscreen=yes,scrollbars=yes');
}

function GB_position() {
  var de = document.documentElement;
  // total document width
  var w = document.body.scrollWidth
  if (self.innerWidth > w) w = self.innerWidth;
  if (de && de.clientWidth > w) w = de.clientWidth;
  if (document.body.clientWidth > w) w = document.body.clientWidth;
  
  w = w - GB_SCROLL_DIFF; 
    
  // total document height
  var h = document.body.scrollHeight
  if ((self.innerHeight+window.scrollMaxY) > h) h = self.innerHeight+window.scrollMaxY;
  if (de && de.clientHeight > h) h = de.clientHeight;
  if (document.body.clientHeight > h) h = document.body.clientHeight;
  
  $("#GB_overlay" + GB_TYPE).css({width:(w)+"px",height:(h)+"px"});
   
  var sy_correction = (navigator.appVersion.match(/MSIE/)) ? 30 : 0;  
  var sy = document.documentElement.scrollTop || document.body.scrollTop - sy_correction;
  var ww = (typeof(GB_WIDTH) == "string" && GB_WIDTH.match(/\%/)) ? GB_WIDTH : GB_WIDTH+"px";
  var wp = (typeof(GB_WIDTH) == "string" && GB_WIDTH.match(/\%/)) ? w*(GB_WIDTH.replace(/\%/,''))/100 : GB_WIDTH;
  
  var hw = (typeof(GB_HEIGHT) == "string" && GB_HEIGHT.match(/\%/)) ? GB_HEIGHT- GB_HDIFF : (GB_HEIGHT- GB_HDIFF)+"px";
  var hy = (typeof(GB_HEIGHT) == "string" && GB_HEIGHT.match(/\%/)) ? (document.body.clientHeight-document.body.clientHeight*(GB_HEIGHT.replace(/\%/,''))/100)/2 : 32;
  
  $("#GB_window").css({ width: ww, height: hw, left: ((w - wp)/2)+"px", top: (sy+hy)+"px" });
  $("#GB_frame").css("height",hw);
}

function noSelect(p) { //no select plugin by me :-)

    if (p == null) 
        prevent = true;
    else
        prevent = p;

    if (prevent) {
    
            if ($.browser.msie||$.browser.safari) $('body').bind('selectstart',function(){return false;});
            else if ($.browser.mozilla) 
                {
                    $('body').css('MozUserSelect','none');
                    $('body').trigger('focus');
                }
            else if ($.browser.opera) $('body').bind('mousedown',function(){return false;});
            else $('body').attr('unselectable','on');
        
    } else {

            if ($.browser.msie||$.browser.safari) $('body').unbind('selectstart');
            else if ($.browser.mozilla) $('body').css('MozUserSelect','inherit');
            else if ($.browser.opera) $('body').unbind('mousedown');
            else $('body').removeAttr('unselectable','on');
    
    }
} //end noSelect
    