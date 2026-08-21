<?php 
////////////////////////////////////////////////////////////////////
// LynxHD Formely ColdBrew Help Desk  
// -----------------------------------------------------------------
//
// License info can be found in license.txt.
// You must leave this notice as is.
//
// LynxHD Formely ColdBrew Helpdesk has been modified and mantained by:
//
//      Old Author: James Paige
//      New Author: Trilex Labs
//         Web: http://www.lynxhd.com
// -----------------------------------------------------------------
////////////////////////////////////////////////////////////////////

include "../include/settings.php";
include "../include/include.php";

$HD_CURPAGE = $HD_URL_MASSREPLY;
$_GET['id'] = $_GET['id'] ?? '';
$_GET['tickets'] = $_GET['tickets'] ?? '';

if( $_SESSION['login_type'] == $LOGIN_INVALID )
  Header( "Location: {$HD_URL_LOGIN}?redirect=" . urlencode( $HD_CURPAGE . "?id={$_GET['id']}" ) );

$options = array( "email", "url", "emailheader", "emailfooter", "email_ticket_notify_subject", "email_ticket_notify" );
$data = get_options( $options );

if( isset( $_POST['tickets'] ) )
  $_GET['tickets'] = $_POST['tickets'];

$tickets = explode( ";", $_GET['tickets'] );

$res = mysql_query( "SELECT num FROM {$pre}options WHERE ( name = 'tags' )" );
$row = mysql_fetch_array( $res );
if( !$row )
  $data['tags'] = 0;
else
  $data['tags'] = $row[0];

if( $_POST['cmd'] == "reply" )
{
  $_POST['subject'] = $_POST['subject'] ?? '';
  if( trim( $_POST['message'] ?? '' ) == "" )
    $msg = "<div class=\"normal\"><font color=\"#FF0000\">You must specify a message in your reply (subjects are optional).</font></div><br />";
  else
  {
    $tickets_replied = 0;
    for( $i = 0; $i < count( $tickets ); $i++ )
    {
      $res = mysql_query( "SELECT * FROM {$pre}ticket WHERE ( id = '{$tickets[$i]}' )" );
      $row = mysql_fetch_array( $res );
      if( $row )
      {
        $tickets_replied++;
         
        // Send notification if necessary
        if( $row['notify'] )
        {
          $ticket = $row['ticket_id'];
          $email = $row['email'];
          $name = stripslashes( $row['name'] );
          $subject = stripslashes( $row['subject'] );
          $message = stripslashes( $_POST['message'] );

          eval( "\$sub = \"{$data['email_ticket_notify_subject']}\";" );
          eval( "\$mes = \"{$data['email_ticket_notify']}\";" );
          hd_mail( $row['email'], $sub, $mes, "From: {$data['email']}" );
        }

        mysql_query( "INSERT INTO {$pre}post ( ticket_id, user_id, date, subject, message ) VALUES ( '{$row['id']}', '{$_SESSION['user']['id']}', '" . time( ) . "', '{$_POST['subject']}', '{$_POST['message']}' )" );

        mysql_query( "UPDATE {$pre}ticket SET lastactivity = '" . time( ) . "' WHERE ( id = '{$tickets[$i]}' )" );

        if( ($_POST['save'] ?? '') == "on" )
        {
          if( trim( $_POST['replyname'] ?? '' ) != "" )
          {
            if( get_row_count( "SELECT COUNT(*) FROM {$pre}reply WHERE ( phrase = '{$_POST['replyname']}' && dept_id = '-1' )" ) )
              mysql_query( "UPDATE {$pre}reply SET reply = '{$_POST['message']}' WHERE ( phrase = '{$_POST['replyname']}' )" );
            else
              mysql_query( "INSERT INTO {$pre}reply ( dept_id, reply, phrase ) VALUES ( '-1', '{$_POST['message']}', '{$_POST['replyname']}' )" );
          }
        }
      }
    }

    $msg = "<div class=\"successbox\">Your mass reply has been successfully posted to {$tickets_replied} tickets.</div><br />";
  }
}

include "./include/header.php";
/********************************************************** PHP */?>
<div class="title"><?php echo $script_name ?> Mass Reply</div><br /><?php echo $msg ?>
<div class="card shadow-sm mb-4"><div class="card-body">
<?php /************************************************************/
$res = mysql_query( "SELECT * FROM {$pre}reply WHERE ( dept_id = '-1' )" );
if( mysql_num_rows( $res ) )
{
/********************************************************** PHP */?>
<form class="form-row align-items-end mb-4" name="predefineddelete" action="<?php echo $HD_CURPAGE ?>" method="post">
<input type="hidden" name="id" value="<?php echo $_GET['id'] ?>" />
<input type="hidden" name="replyname" value="" />
<input type="hidden" name="cmd" value="deletereply" />
<div class="form-group col-md-8 mb-2"><label for="predefined-reply">Predefined Reply</label><select class="form-control" id="predefined-reply" name="reply" onchange="document.predefinedreply.message.value = this.options[selectedIndex].value; if( this.options[selectedIndex].value != '' )  { document.predefinedreply.replyname.value = this.options[selectedIndex].text; document.predefineddelete.replyname.value = this.options[selectedIndex].text; } else { document.predefinedreply.replyname.value = ''; document.predefineddelete.replyname.value = ''; }">
<option value="">(None)</option>
<?php /************************************************************/
  while( $row = mysql_fetch_array( $res ) )
    echo "<option value=\"" . field( $row['reply'] ) . "\">" . field( $row['phrase'] ) . "</option>\n";  
/********************************************************** PHP */?>
</select></div>
<div class="form-group col-md-4 mb-2"><input class="btn btn-outline-danger" type="submit" value="Delete Selected Reply" /></div>
</form>
<?php /************************************************************/
}
/********************************************************** PHP */?>
<form name="predefinedreply" action="<?php echo $HD_CURPAGE ?>" method="post">
<input type="hidden" name="cmd" value="reply" />
<input type="hidden" name="tickets" value="<?php echo $_GET['tickets'] ?>" />
<div class="form-group"><label for="mass-subject">Subject</label><input class="form-control" id="mass-subject" type="text" name="subject" value="<?php echo field( $_POST['subject'] ?? '' ) ?>" /></div>
<div class="form-group"><div class="d-flex justify-content-between"><label for="mass-message">Message <span class="text-danger">*</span></label><?php if( $data['tags'] ) echo '<a class="small" href="' . $HD_URL_TICKET_TAGS . '" target="_blank">Message tags</a>'; ?></div><textarea class="form-control" id="mass-message" name="message" rows="8"><?php echo field( $_POST['message'] ?? '' ) ?></textarea></div>
<div class="form-row align-items-center mb-4"><div class="col-auto"><div class="custom-control custom-checkbox"><input class="custom-control-input" id="save-reply" type="checkbox" name="save"><label class="custom-control-label" for="save-reply">Save as a predefined reply</label></div></div><div class="col"><input class="form-control" type="text" name="replyname" placeholder="Reply name"></div></div>
<div class="text-right"><input class="btn btn-light mr-2" type="reset" /><input class="btn btn-primary" type="submit" value="Post Reply" /></div>
</form>
</div></div>
<br />
<?php /************************************************************/
include "./include/footer.php";
/********************************************************** PHP */?>
