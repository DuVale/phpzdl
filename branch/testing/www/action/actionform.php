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
* - message_ok()
* - email_form()
* - exec_form()
* - submit()
* Classes list:
*/
require_once ('classes/Session.inc');
require_once 'ossim_db.inc';
require_once 'classes/Action.inc';
require_once 'classes/Action_type.inc';
require_once 'classes/Security.inc';

Session::logcheck("MenuIntelligence", "PolicyActions");

function submit() {
    ?>
    <tr>
        <td align="center" style="border-bottom: medium none; padding: 10px;" colspan="2">
            <input type="hidden" name="withoutmenu" value="<?php echo GET('withoutmenu')?>">
            <input type="button" class="button" id='send' value="<?php echo _("Update");?>" onclick="remove_success_message();submit_form();" /> 
        </td>
    </tr>
    <?php
}

function email_form($action) {
    ?>
    <tr class="temail"><td colspan="2" class="nobborder">&nbsp;</td></tr>
    <tr class="temail">
        <th><label for='email_from'><?php echo gettext("From:"); ?></label></th> 
        <td class="left nobborder">
            <input value="<?php echo ((is_null($action)) ? "":$action->get_from()); ?>" class="vfield" name="email_from" id="email_from" type="text" size="60"/>
            <span style="padding-left: 3px;">*</span>
        </td>
    </tr>
    <tr class="temail">
        <th><label for='email_to'><?php echo gettext("To:"); ?></label></th>
        <td class="left nobborder">
            <input value="<?php echo ((is_null($action)) ? "":$action->get_to());?>" class="vfield" name="email_to" id="email_to" type="text" size="60"/>
            <span style="padding-left: 3px;">*</span>
        </td>
    </tr>
    <tr class="temail">
        <th><label for='email_subject'><?php echo gettext("Subject:"); ?></label></th> 
        <td class="left nobborder">
            <input value="<?php echo ((is_null($action)) ? "":$action->get_subject()); ?>" name="email_subject" id="email_subject" class="vfield" type="text" size="60" />
            <span style="padding-left: 3px;">*</span>
        </td>
    </tr>
    <tr class="temail">
        <th class="lth"><label for='email_message'><?php echo gettext("Message:"); ?></label></th>
        <td class="left nobborder">
        <textarea name="email_message" id="email_message" class="vfield"><?php echo ((is_null($action)) ? "":$action->get_message()); ?></textarea>
        <span style='vertical-align: top;padding-left: 3px;'>*</span></td>
    </tr>
    <?php
}

function exec_form($action) {
    ?>
    <tr class="texec"><td colspan="2" class="nobborder">&nbsp;</td></tr>
    <tr class="texec">
        <th><label for="exec_command"><?php echo gettext("Command:"); ?></label></th>
        <td class="nobborder left">
            <input value="<?php echo ((is_null($action)) ? "":$action->get_command()); ?>" class="vfield" name="exec_command" id="exec_command" type="text" size="60" />
            <span style='vertical-align: top;padding-left: 3px;'>*</span>
        </td>
    </tr>
    <?php
}

function ticket_form($action) 
{
	GLOBAL $conn;
	$users     = Session::get_users_to_assign($conn);
	$entities  = Session::get_entities_to_assign($conn);
     
    
    ?>
	<tr class="tticket"><td colspan="2" class="nobborder">&nbsp;</td></tr>
	<tr class="tticket">
		<th><label for="ticket_command"><?php echo gettext("In Charge:"); ?></label></th>
		<td class="nobborder left">
			<table cellspacing="0" cellpadding="0" class="transparent">
                <tr>
                    <td class="nobborder"><span><?php echo _("User:");?></span></td>
                    <td class="nobborder left">
                        <select name="transferred_user" id="user" onchange="switch_user('user');return false;">
                            <?php
                            $num_users = 0;
                                                                                    
                            foreach( $users as $k => $v )
                            {
                                $login = $v->get_login();
                                $options .= "<option value='$login'".($action==$login ? " selected": "").">$login</option>\n";
                                $num_users++;
                            }
                            
                            if ($num_users == 0)
                                echo "<option value='' style='text-align:center !important;'>- "._("No users found")."- </option>";
                            else
                            {
                                echo "<option value='' style='text-align:center !important;' selected='selected'>- "._("Select one user")." -</option>\n";
                                echo $options;
                            }
                            ?>
                        </select>
                    </td>
                
                <?php 
                if ( !empty($entities) ) 
                { 
                    ?>
                    <td class="nobborder" nowrap='nowrap'><span style='margin-right: 3px;'><?php echo _("OR")." "._("Entity:");?></span></td>
                    <td class="nobborder">
                        <select name="transferred_entity" id="entity" onchange="switch_user('entity');return false;">
                            <?php				
                            if (count($entities) == 0)
                                echo "<option value='' style='text-align:center !important;'>- "._("No entities found")." -</option>";
                            else
                                echo "<option value='' style='text-align:center !important;'>- "._("Select one entity")." -</option>\n";
                            
                            foreach ( $entities as $k => $v ) 
                                echo "<option value='$k'".($action==$k ? " selected": "").">$v</option>";
                            ?>
                        </select>
                    </td>
                    <?php 
                } 
                ?>
                </tr>
			</table>
		</td>
	</tr>
    <?php
}


if ( isset($_SESSION['_actions']) )
{
    $action_id      = $_SESSION['_actions']['action_id'];
    $action_type    = $_SESSION['_actions']['action_type'];
    $descr          = $_SESSION['_actions']['descr'];
    $cond           = $_SESSION['_actions']['cond'];
    $on_risk        = $_SESSION['_actions']['on_risk'];
    $email_from     = $_SESSION['_actions']['email_from'];
    $email_to       = $_SESSION['_actions']['email_to'];
    $email_subject  = $_SESSION['_actions']['email_subject'];
    $email_message  = $_SESSION['_actions']['email_message'];
    $exec_command   = $_SESSION['_actions']['exec_command'];
    unset($_SESSION['_actions']);
}
else 
{
    $action_id = REQUEST('id');
    ossim_valid($action_id, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Action id"));

    if (ossim_error()) {
        die(ossim_error());
    }

    $db = new ossim_db();
    $conn = $db->connect();


    if (is_array($action_list = Action::get_list($conn, "WHERE id = '$action_id'"))) {
        $action = $action_list[0];
        
    }

    if( !is_null($action) ) 
    {
        $action_type = $action->get_action_type();
        $cond        = htmlspecialchars($action->get_cond());
        $on_risk     = $action->is_on_risk();
        if (REQUEST('descr')) $description = $descr;
        else $description = $action->get_descr();
    }
    else 
    {
        $action_type = "";
        $cond        = "True";
        $on_risk     = 0;
        $description = "";
    }
}

$update  = intval(GET('update'));

$style_success = "style='display: none;'";

if ( $update == 1 ) 
{
    $success_message = gettext("Action successfully updated");
    $style_success   = "style='display: block;text-align:center;'"; 
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title><?=_("OSSIM Framework")?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <link rel="stylesheet" type="text/css" href="../style/style.css"/>
    <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
    <script type="text/javascript" src="../js/ajax_validator.js"></script>
    <script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
    <script type="text/javascript" src="../js/utils.js"></script>
    <script type="text/javascript" src="../js/messages.php"></script>

    <script type='text/javascript'>
       
        <?php $defaultcond = htmlspecialchars("RISK>=1"); ?>
    
        function changecond() {
            $('#condition').hide();
            if ($('#only').attr('checked')==false) {
                $('#cond').val("True");
                $('#on_risk').attr('checked', false);
            } else {
                $('#cond').val("<?=$defaultcond?>");
                $('#on_risk').attr('checked', false);
            }
        }
        function activecond() {
            $('#condition').show();
            $('#only').attr('checked',false);
            $('#cond').val("True");
            $('#on_risk').attr('checked', false);
        }
        
        function changeType(){
            if($('#action_type').val()=='exec') {
                $('.texec').show('');
                $('.tticket').hide();
                $('.temail').hide('');
                $('#email_from').removeClass('req_field');
                $('#email_to').removeClass('req_field');
                $('#email_subject').removeClass('req_field');
                $('#email_message').removeClass('req_field');
                $('#exec_comand').addClass('req_field');
                
            }
            else if ($('#action_type').val()=='email') {
                $('.temail').show('');
                $('.tticket').hide();
                $('.texec').hide('');
                $('#email_from').addClass('req_field');
                $('#email_to').addClass('req_field');
                $('#email_subject').addClass('req_field');
                $('#email_message').addClass('req_field');
                $('#exec_comand').removeClass('req_field');
            }
            else if ($('#action_type').val()=='ticket') {
                $('.temail').hide('');
                $('.tticket').show();
                $('.texec').hide('');
                $('#email_from').removeClass('req_field');
                $('#email_to').removeClass('req_field');
                $('#email_subject').removeClass('req_field');
                $('#email_message').removeClass('req_field');
                $('#exec_comand').removeClass('req_field');
            }        
            else {
                $('.temail').hide('');
                $('.tticket').hide();
                $('.texec').hide('');
                $('#email_from').removeClass('req_field');
                $('#email_to').removeClass('req_field');
                $('#email_subject').removeClass('req_field');
                $('#email_message').removeClass('req_field');
                $('#exec_comand').removeClass('req_field');
            }
        }
        
        function switch_user(select)
        {
            if( select=='entity' && $('#transferred_entity').val()!=''){
                $('#user').val('');
            }
            else if (select=='user' && $('#transferred_user').val()!=''){
                $('#entity').val('');
            }
        }  
        
        function remove_success_message()
        {
            if ( $('#success_message').length == 1 )
                $("#success_message").remove();
        }
        $(document).ready(function() {
            $('li span').bind('click', function() {
                if($('#descr').val()=='') {
                    $('#descr').val($(this).text());
                }
                else {
                    $('#descr').val($('#descr').val() + ' ' + $(this).text());
                }
            } );
            
            $('textarea').elastic();
            $('.vfield').bind('blur', function() {
                validate_field($(this).attr("id"), "modifyactions.php?action_type="+$('#action_type').val());
            });
            <?php
            if($action_type == "exec") {?>
                $('.temail').hide();
                $('.tticket').hide();
                $('.texec').show();
            <?php
            }
            else if ($action_type == "email") {
            ?>
                $('.texec').hide();
                $('.tticket').hide();
                $('.temail').show();
            <?php
            }
            else if ($action_type == "ticket") {
            ?>
                $('.texec').hide();
                $('.tticket').show();
                $('.temail').hide();
            <?php
            }
            else {
            ?>
                $('.temail').hide();
                $('.tticket').hide();
                $('.texec').hide();
            <?php
            }?>
        });
        
    </script>
  
    <style type='text/css'>
        #table_form {
            width: 750px;
        }
        #table_form th {
            width: 250px !important;
            padding: 5px 0px 5px 0px;
        }
        #table_form th.lth {
            background-position:top;
        }
        input[type='text'], input[type='password'], select, textarea {width: 95%; height: 18px;}
        textarea { height: 95px; }
        label {border: none; cursor: default;}
        .bold {font-weight: bold;}
        div.bold {line-height: 18px;}
        a {cursor:pointer;}
        li span{ color:#17457C; cursor:pointer;}
    </style>
</head>
<body>

<?php
if (GET('withoutmenu') != "1") 
	include ("../hmenu.php"); 

?>

<table align="center" id="table_form">
    <tr>
        <td colspan="2" style="text-align: left" class="nobborder">
            <?php echo gettext("You can use the following keywords within any field which will be get substituted by it's matching value upon action execution") . ":";?>    
            <table width="90%" align="center" style="border-width: 0px">
                <tr>
                    <td style="text-align: left" valign="top" class="nobborder">
                        <ul> 
                            <li><span>DATE</span></li>
                            <li><span>PLUGIN_ID</span></li>
                            <li><span>PLUGIN_SID</span></li>
                            <li><span>RISK</span></li>
                            <li><span>PRIORITY</span></li>
                            <li><span>RELIABILITY</span></li>
                            <li><span>SRC_IP_HOSTNAME</span></li>
                            <li><span>DST_IP_HOSTNAME</span></li>
                            <li><span>SRC_IP</span></li>
                            <li><span>DST_IP</span></li>
                            <li><span>SRC_PORT</span></li>
                            <li><span>DST_PORT</span></li>
                            <li><span>PROTOCOL</span></li>
                            <li><span>SENSOR</span></li>
                            <li><span>BACKLOG_ID</span></li>
                        </ul>
                    </td>
                    
                    <td style="text-align: left" valign="top" class="nobborder">
                        <ul> 
                            <li><span>EVENT_ID</span></li>
                            <li><span>PLUGIN_NAME</span></li>
                            <li><span>SID_NAME</span></li>
                            <li><span>USERNAME</span></li>
                            <li><span>PASSWORD</span></li>
                            <li><span>FILENAME</span></li>
                            <li><span>USERDATA1</span></li>
                            <li><span>USERDATA2</span></li>
                            <li><span>USERDATA3</span></li>
                            <li><span>USERDATA4</span></li>
                            <li><span>USERDATA5</span></li>
                            <li><span>USERDATA6</span></li>
                            <li><span>USERDATA7</span></li>
                            <li><span>USERDATA8</span></li>
                            <li><span>USERDATA9</span></li>
                        </ul>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <div id='success_message' class='ossim_success' <?php echo $style_success ?>><?php echo $success_message;?></div>
    <div id='info_error' class='ossim_error' style='display:none;'></div>

    <form method="post" action="modifyactions.php" id="new_action" name="new_action">
        <input type="hidden" name="id" value="<?php echo ((is_null($action)) ? "":$action->get_id()); ?>" />
        <input type="hidden" name="action" value="<?php echo (($action_id=="") ? "new" : "edit"); ?>" />
    <tr>
        <th><label for='descr'><?php echo gettext("Description"); ?></label></th>
        <td class="left nobborder">
            <textarea name="descr" id="descr" class="vfield"><?php echo $description ?></textarea>
        </td>
    </tr>
    
    <tr>
        <th><label for='action_type'><?=_("Type")?></label></th>
        <td class="left nobborder">
            <?php
            if(!is_null($action)) 
            {
                ?>
                <input type="hidden" name="action_type" id="action_type" class="vfield req_field" value="<?php echo $action_type ?>" />
                <b><?php echo $action_type ?></b>
                <?php
            }
            else 
            {
                ?>
                <select name="action_type" id="action_type" onChange="changeType()">
                    <option value=""> -- <?php echo gettext("Select an action type"); ?> -- </option>
                    <?php
                    if (is_array($action_type_list = Action_type::get_list($conn))) 
                    {
                        foreach($action_type_list as $action_type_aux) 
                        {
                            ?>
                            <option value="<?php echo $action_type_aux->get_type() ?>"
                                <?php
                            if ($action_type == $action_type_aux->get_type()) 
                                echo " selected='selected' ";
                            ?>>
                                <?php echo _($action_type_aux->get_descr()); ?>
                            </option>
                            <?php
                        }
                    }
                    ?>
                </select>
                <?php 
            }
            ?>
        </td>
    </tr>

    <tr>
        <td class="nobborder">&nbsp;</td>
        <td style="text-align:center;padding-left:14px;" class="nobborder">
            <input type="checkbox" id="only" name="only" onclick="changecond()" <?=($cond == $defaultcond) ? "checked" : ""?>> <?=_("Only if this is an alarm")?> <a href="javascript:;" onclick="activecond()" >[<?=_("Define logical condition")?>]</a>
        </td>
    </tr>
  
    <tr id="condition" <?=(in_array($cond,array($defaultcond,'','True'))) ? "style='display:none'" : ""?>>
        <th>
            <label for='cond'>
            <?php echo _("Condition")?>
            </label>
        </th>
        <td style="text-align: center" class="noborder">
            <table class="noborder">
                <tr>
                    <td class="noborder left" width="115">
                        <?php echo gettext('Python boolean expression') ?>:&nbsp;
                    </td>
                    
                    <td class="left noborder">
                        <input type="text" id="cond" name="cond" size="55" class="vfield" value="<?php echo $cond ?>">
                    </td>
                </tr>
                
                <tr>
                    <td class="noborder left">
                        <label for="on_risk"><?php echo gettext('Only on risk increase') ?>:&nbsp;</label>
                    </td>
                    <td class="noborder" style="text-align: left">
                        <input type="checkbox" id="on_risk" name="on_risk" <?php if ($on_risk == "1") echo "checked" ?>>
                    </td>
                </tr>
            </table>
        </td>  
    </tr>
  
    <?php

    if(!is_null($action)) 
    {
        if ($action_type=="email")
            email_form($action->get_action($conn));
        if($action_type=="exec")
            exec_form($action->get_action($conn));
        if($action_type=="ticket")
            ticket_form($action->get_action($conn));
    }
    else
    {
        email_form(NULL);
        exec_form(NULL);
        ticket_form(NULL);
    }

    submit();

    ?>
    </form>
</table>

<p align="center" style="font-style: italic;"><?php echo _("Values marked with (*) are mandatory"); ?></p>

</body>
</html>

<?php $db->close($conn); ?>
