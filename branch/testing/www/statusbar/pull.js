var   refresh = false,
      move_next = false,
      move_prev = false;
      
var PULL = function() {
  var content,
      pullToRefresh,
      refreshing,
      refreshr,
      contentStartY,
      contentStartX,
      success, 
      start, 
      cancel,
      startY,
      startX,
      track = false;

  var removeTransition = function() {
    content.style['-webkit-transition-duration'] = 0;
  };

  return {
    init: function(o) {
      content = document.getElementById('content');
      pullToRefresh = document.getElementById('pull_to_refresh');
      refreshing = document.getElementById('refreshing');
      refreshr = document.getElementById('refreshr');
      success = o.success;
      start = o.start;
      cancel = o.cancel;

      content.addEventListener('touchstart', function(e) {
        e.preventDefault();
        contentStartY = parseInt(content.style.top);
        contentStartX = parseInt(content.style.left);
        startY = e.touches[0].screenY;
        startX = e.touches[0].screenX;
      });

      content.addEventListener('touchend', function(e) {
        if(move_next || move_prev) {
          content.style['-webkit-transition-duration'] = '.5s';
          content.style.top = '0px';
          content.style.left = (move_next) ? '-25px' : '25px';

          pullToRefresh.style.display = 'none';
          refreshr.style.left = (move_next) ? '295px' : '0px';
          refreshr.style.display = '';

          success(function() { // pass down done callback
            //pullToRefresh.style.display = '';
            //refreshing.style.display = 'none';
            //content.style.top = '0';
            //content.style.left = '0';
            //content.addEventListener('transitionEnd', removeTransition);
          });
          
          move_next = false;
          move_prev = false;
        } else if(refresh) {
          content.style['-webkit-transition-duration'] = '.5s';
          content.style.top = '30px';
          content.style.left = '0px';
          
          pullToRefresh.style.display = 'none';
          refreshing.style.display = '';

          success(function() { // pass down done callback
            //pullToRefresh.style.display = '';
            //refreshing.style.display = 'none';
            //content.style.top = '0';
            //content.style.left = '0';
            //content.addEventListener('transitionEnd', removeTransition);
          });

          refresh = false;
        } else if(track) {
          content.style['-webkit-transition-duration'] = '.25s';
          content.style.top = '0';
          content.style.left = '0';
          content.addEventListener('transitionEnd', removeTransition);

          cancel();
        }

        track = false;
      });

      content.addEventListener('touchmove', function(e) {
        var move_to_y = contentStartY - (startY - e.changedTouches[0].screenY);
        var move_to_x = contentStartX - (startX - e.changedTouches[0].screenX); //alert(contentStartX+" "+startX+" "+e.changedTouches[0].screenX);
        if(move_to_y > 0 || move_to_x > 0) track = true; // start tracking if near the top 
        content.style.top = move_to_y + 'px';
        content.style.left = move_to_x + 'px';

		// Y refresh page
        if(move_to_y > 25) {
          refresh = true;
        } else {
          //if (move_to_y < 0) content.style.top = '0';
          content.style['-webkit-transition'] = '';
          refresh = false;
        }

		// X move next page
        if(move_to_x < -25) {
          move_next = true;
        } else if(move_to_x > 25) {
          move_prev = true;
        } else {
          content.style['-webkit-transition'] = '';
          move_next = false;
          move_prev = false;
        }
        
      });
    }
  };
}();
