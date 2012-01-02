<!DOCTYPE html>

<html class="iphone">
<head>

    <title>Ossim</title>
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black" />  
    <meta name="viewport" content="user-scalable=no; width=device-width; initial-scale=1.0; maximum-scale=1.0;"> 
    <link rel="shortcut icon" href="_/images/favicon.ico">
    <link rel="apple-touch-icon-precomposed" href="_/images/icon-default.png">
    <link rel="apple-touch-startup-image" href="_/images/splash-default.png" />  
    <script src="_/scripts/jquery.min.js"></script>
    <style type="text/css">
        body,html {
    padding: 0;
    margin: 0;
    -webkit-user-select: none;
}
html {
    background-color: rgba(0,0,0,1);
    min-height: 416px;
}
body {
    padding: 10px;
    font: normal 12px/16px Helvetica, Geneva, sans-serif;
    text-align: center;
}

html.desktop {
    background: url(_/images/homepage.jpg) no-repeat center center fixed;
    -webkit-background-size: cover;
    -moz-background-size: cover;
    -o-background-size: cover;
    background-size: cover;
}
html.iphone {
    background-size: 480px 720px;
    background:-webkit-gradient(linear, 0% 80%, 0% 20%, from(#ffffff), to(#d1d1d1));
}

@media only screen and (device-width: 768px) {
  html,body {
    height: 100%;
    width: 100%;
  }
  
  html.iphone {
    background-size: 1024px 1024px;
    background-repeat: repeat;
  }
}

h1#logo {
    display: block;
    height: 97px;
    width: 97px;
    background: transparent url(_/images/logo.png) no-repeat center top;
    background-size: 97px 97px;
    margin: 14px auto;
    overflow: hidden;
}
    h1#logo a {
        display: block;
        padding-top: 97px;
        height: 0;
        line-height: 999px;
        border-radius: 100px;
    }

ul#icons, ul#icons li, ul#icons li a, a.details {
    list-style: none;
    margin: 0;
    padding: 0;
}
ul#icons
{
    display: block;
    position:relative;
    text-align: left;
}
    ul#icons li {
        display: inline-block;
        position: relative;
        margin: 6px 3px;
        width: 66px;
        padding-top: 61px;
    }
        ul#icons li a {
            display: inline-block;
            position: absolute;
            top: 0;
            left: 50%;
            margin-left: -28px;
            background-color: transparent;
            background-position: center top;
            background-repeat: no-repeat;
            background-size: 57px 57px;
            height: 57px;
            width: 57px;
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(0,0,0,1);
        }
        ul#icons li label {
            display: block;
            text-align: center;
            overflow: hidden;
            max-width: 66px;
            text-overflow: ellipsis;
            text-align: center;
            font-weight: bold;
            text-decoration: none;
            color: #232323;
            text-shadow: 0 1px 1px #F2F2F2;
            line-height: 16px;
        }
        
        ul#icons li a.selected {
             -webkit-animation-name: pulse;
             -webkit-animation-duration: 2s;
             -webkit-animation-direction: alternate;
             -webkit-animation-timing-function: ease-in-out;
             -webkit-animation-iteration-count: infinite;
        }
        
        @-webkit-keyframes pulse {
         0% {
           box-shadow: inset 0 0 0 1px rgba(0,0,0,0.3), 0 0 0 3px #b3e955,  0 0 10px 3px #b3e955;
         }
         50% {
           box-shadow: inset 0 0 0 1px rgba(0,0,0,0.3), 0 0 0 3px #b3e955;
         }
         100% {
           box-shadow: inset 0 0 0 1px rgba(0,0,0,0.3), 0 0 0 3px #b3e955,  0 0 10px 3px #b3e955;
         }
        }
a.details {
    margin: 0 auto 30px auto;
}

a.install {
    display: block;
    margin: 10px 0;
    padding: 15px 20px;
    text-shadow: 0 -1px 0 #000;
    color: #adadad;
    line-height: 14px;
    text-decoration: none;
    text-align: center;
    color: #fff;
    font-weight: bold;
    font-size: 14px;
    border: 1px solid #2c170b;
    background: -webkit-linear-gradient(top, rgba(160,100,66,0.8) 0%,rgba(70,40,22,0.8) 50%,rgba(53,29,15,0.8) 50%,rgba(78,46,26,0.8) 100%);
    background-repeat: repeat-x;
    border-radius: 8px;
    -webkit-box-shadow: inset 0 1px 1px rgba(219,140,94,0.5), 0 1px 3px rgba(0,0,0,0.75);
}
h1 {
    display: block;
    font-weight: bold;
    font-size: 12px;
    color: #fff;
    text-align: center;
    text-shadow: 0 1px 1px #000;
}
p {
    font-weight: bold;
    color: #fff;
    text-align: center;
    text-shadow: 0 1px 1px #000;
}
p.desc {
    font-weight: normal;
    font-style: italic;
    margin-bottom: 40px;
}
p.footer a {
    margin-top: 5px;
    font-weight: normal;
    font-style: italic;
    color: rgba(255,255,255,0.6);
    display: inline-block;
    padding: 3px 15px;
    background-color: rgba(0,0,0,0.4);
    border-radius: 12px;
    text-decoration: none;
}
    p.footer a b {
        color: rgba(255,255,255,1);
    }
    
    html.desktop .content {
        border-radius: 12px;
        width:400px;
        position:absolute;
        padding: 250px 15px 15px 15px;
        margin-top: -147px;
        left: 8%;
        top:50%;
        box-sizing: border-box;
        background: transparent url(_/images/homepage-icon.png) center 40px no-repeat;
        background-color: rgba(0,0,0,0.2);
    }
        html.desktop p.footer {
            margin-bottom: 0;
        }
    
html.iphone a#donate {
    display: inline-block;
    overflow: hidden;
    background: transparent url(_/images/donate-button.png) left center no-repeat;
    padding-top: 82px;
    height: 0;
    width: 310px;
    background-size: 300px 82px;
    margin: 10px auto 25px auto;
}

html.desktop a#donate {
    margin-top: 5px;
    font-weight: normal;
    font-style: italic;
    color: rgba(255,255,255,0.6);
    display: inline-block;
    padding: 3px 15px;
    background-color: rgba(0,0,0,0.4);
    border-radius: 12px;
    text-decoration: none;
}

ul#links {
    display: block;
    margin: 15px 0 10px 0;
    padding: 0;
}
    ul#links li {
        display: inline-block;
        overflow: hidden;
        height: 36px;
        width: 136px;
        padding: 0;
        margin: 0;
    }
        ul#links li a {
            display: block;
            overflow: hidden;
            padding-top: 36px;
            height: 0;
            background-color: transparent;
            background-position: top center;
            background-repeat: no-repeat;
            background-size: 136px 36px;
            border-radius: 6px;
        }
        ul#links li.tweet a {
            background-image: url(_/images/tweet-button.png);
        }
        ul#links li.sponsors a {
            background-image: url(_/images/sponsors-button.png);
        }

    </style>
</head>
<body>
    <img src="../pixmaps/top/logo_siem.png" border="0" style="margin:10px 0">
    <ul id="icons">
        <li class="button1"><a href="status" style="background-image: url('../pixmaps/mobile/icon-status.png');"></a><label>Status</label></li>
        <li class="button2"><a href="alarms" style="background-image: url('../pixmaps/mobile/icon-alarm.png');"></a><label>Alarms</label></li>
        <li class="button3"><a href="tickets" style="background-image: url('../pixmaps/mobile/icon-ticket.png');"></a><label>Tickets</label></li>
        <li class="button5"><a href="siemevents" style="background-image: url('../pixmaps/mobile/icon-siem_events.png');"></a><label>SiemEvents</label></li>
        <li class="button6"><a href="logout" style="background-image: url('../pixmaps/mobile/icon-exit.png');"></a><label>Logout</label></li>
    </ul>
    
    <script>
        window.onload = function() {
            setTimeout(function() { window.scrollTo(0, 1) }, 100);
        }
    </script>
</body>
</html>