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

$HD_CURPAGE = $HD_URL_EMAILS;

if( $_SESSION['login_type'] == $LOGIN_INVALID )
  Header( "Location: {$HD_URL_LOGIN}?redirect=" . urlencode( $HD_CURPAGE ) );

$global_priv = get_row_count( "SELECT COUNT(*) FROM {$pre}privilege WHERE ( user_id = '{$_SESSION['user']['id']}' && dept_id = '0' )" );
if( !$global_priv )
  Header( "Location: {$HD_URL_LOGIN}?redirect=" . urlencode( $HD_CURPAGE ) );

$options = array( "emailheader", "emailfooter", "email_ticket_notify", "email_ticket_notify_subject", "email_ticket_created", "email_ticket_created_subject", "email_ticket_survey_subject", "email_ticket_survey", "email_notify_create_subject", "email_notify_create", "email_notify_reply_subject", "email_notify_reply", "email_notifysms_create_subject", "email_notifysms_create", "email_notifysms_reply_subject", "email_notifysms_reply", "email_ticket_lookup", "email_ticket_lookup_subject" );

if( isset( $_POST['emailheader'] ) )
{
  for( $i = 0; $i < count( $options ); $i++ )
  {
    $exists = get_row_count( "SELECT COUNT(*) FROM {$pre}options WHERE ( name = '{$options[$i]}' )" );
    if( $exists )
      mysql_query( "UPDATE {$pre}options SET text = '" . $_POST[$options[$i]] . "' WHERE ( name = '{$options[$i]}' )" );
    else
      mysql_query( "INSERT INTO {$pre}options ( name, text ) VALUES ( '{$options[$i]}', '" . $_POST[$options[$i]] . "' )" );
  }
}

$_POST = get_options( $options );

include "./include/header.php";
/********************************************************** PHP */?>

<div class="title">Customize Emails</div><br /><?php echo $msg ?>
  <div class="clean-gray">
    Below you can customize the emails sent to customers, as well as the header and footer
    used on all emails.
  </div>
<br />
<div id="container">
	<h1>Customize Emails</h1>
<form class="wufoo" action="<?php echo $HD_CURPAGE ?>" method="post">
<input type="hidden" name="cmd" value="add" />
	<ul>
      <div class="clean-gray">
        The following will be prepended (header) and appended (footer) to most all emails sent
        by the help desk.
      </div>
<li>
	   <label class="desc">Header:</label>
    <div>
    	<textarea class="field textarea medium" name="emailheader" rows="5" cols="40"><?php echo field( $_POST['emailheader'] ) ?></textarea>
	</div>
</li>
    <li>
	   <label class="desc">Footer:</label>
    <div>
    	<textarea class="field textarea medium" name="emailfooter" rows="5" cols="40"><?php echo field( $_POST['emailfooter'] ) ?></textarea>
	</div>
</li>
    <h1>- Ticket Creation Email -</h1> 
      <div class="clean-gray">
        This is the email sent when a customer creates a new ticket.
      </div>
  <li>
	   <label class="desc">Subject:</label>
    <div>
    	<input class="field text medium" type="text" name="email_ticket_created_subject" size="30" value="<?php echo field( $_POST['email_ticket_created_subject'] ) ?>" />
	</div>
</li>
 <li>
	   <label class="desc">Message:</label>
    <div>
    	<textarea class="field textarea medium" name="email_ticket_created" rows="5" cols="40"><?php echo field( $_POST['email_ticket_created'] ) ?></textarea>
	</div>
</li>
    <h1>- Ticket Notification Email -</h1> 
      <div class="clean-gray">
        This is the email sent to a customer when his/her ticket has been replied to.
      </div>
<li>
	   <label class="desc">Subject:</label>
    <div>
    	<input class="field text medium" type="text" name="email_ticket_notify_subject" size="30" value="<?php echo field( $_POST['email_ticket_notify_subject'] ) ?>" />
	</div>
</li>
    <li>
	   <label class="desc">Message:</label>
    <div>
    	<textarea class="field textarea medium" name="email_ticket_notify" rows="5" cols="40"><?php echo field( $_POST['email_ticket_notify'] ) ?></textarea>
	</div>
</li>
	<h1>- Ticket Lookup Email -</h1> 
      <div class="clean-gray">
        This is the email sent to a customer when tickets all tickets are requested via email.
      </div>
<li>
	   <label class="desc">Subject:</label>
    <div>
    	<input class="field text medium" type="text" name="email_ticket_lookup_subject" size="30" value="<?php echo field( $_POST['email_ticket_lookup_subject'] ) ?>" />
	</div>
</li>
   <li>
	   <label class="desc">Message:</label>
    <div>
    	<textarea class="field textarea medium" name="email_ticket_lookup" rows="5" cols="40"><?php echo field( $_POST['email_ticket_lookup'] ) ?></textarea>
	</div>
</li>
    <h1>- User Notification (Ticket Created) -</h1>  
      <div class="clean-gray">
        This is the notification email sent to a user when a customer creates a ticket.
      </div>
	<li>
	   <label class="desc">Subject:</label>
    <div>
    	<input class="field text medium" type="text" name="email_notify_create_subject" size="30" value="<?php echo field( $_POST['email_notify_create_subject'] ) ?>" />
	</div>
</li>
    <li>
	   <label class="desc">Message:</label>
    <div>
    	<textarea class="field textarea medium" name="email_notify_create" rows="5" cols="40"><?php echo field( $_POST['email_notify_create'] ) ?></textarea>
	</div>
</li>
    <h1>- User Notification (Ticket Reply) -</h1>  
      <div class="clean-gray">
        This is the notification email sent to a user when a customer replies to a ticket.
      </div>
	 <li>
	   <label class="desc">Subject:</label>
    <div>
    	<input class="field text medium" type="text" name="email_notify_reply_subject" size="30" value="<?php echo field( $_POST['email_notify_reply_subject'] ) ?>" />
	</div>
</li>
   <li>
	   <label class="desc">Message:</label>
    <div>
    	<textarea class="field textarea medium" name="email_notify_reply" rows="5" cols="40"><?php echo field( $_POST['email_notify_reply'] ) ?></textarea>
	</div>
</li>
    <h1>- SMS User Notification (Ticket Created) -</h1>  
      <div class="clean-gray">
        This is the notification email sent to a user's SMS email when a customer creates a ticket.
      </div>
<li>
	   <label class="desc">Subject:</label>
    <div>
    	<input class="field textarea medium" type="text" name="email_notifysms_create_subject" size="30" value="<?php echo field( $_POST['email_notifysms_create_subject'] ) ?>" />
	</div>
</li>
   <li>
	   <label class="desc">Message:</label>
    <div>
    	<textarea class="field textarea medium" name="email_notifysms_create" rows="5" cols="40"><?php echo field( $_POST['email_notifysms_create'] ) ?></textarea>
	</div>
</li>

    <h1>- SMS User Notification (Ticket Reply) -</h1>  
      <div class="clean-gray">
        This is the notification email sent to a user's SMS email when a customer replies to a ticket.
      </div>
<li>
	   <label class="desc">Subject:</label>
    <div>
    	<input class="field text medium" type="text" name="email_notifysms_reply_subject" size="30" value="<?php echo field( $_POST['email_notifysms_reply_subject'] ) ?>" />
	</div>
</li>
    <li>
	   <label class="desc">Message:</label>
    <div>
    	<textarea class="field textarea medium" name="email_notifysms_reply" rows="5" cols="40"><?php echo field( $_POST['email_notifysms_reply'] ) ?></textarea>
	</div>
</li>
    <h1>- Ticket Survey Email -</h1>  
      <div class="clean-gray">
        This is the email sent to a customer when a survey is requested.
      </div>
<li>
	   <label class="desc">Subject:</label>
    <div>
    	<input class="field text medium" type="text" name="email_ticket_survey_subject" size="30" value="<?php echo field( $_POST['email_ticket_survey_subject'] ) ?>" />
	</div>
</li>
    <li>
	   <label class="desc">Message:</label>
    <div>
    	<textarea class="field textarea medium" name="email_ticket_survey" rows="5" cols="40"><?php echo field( $_POST['email_ticket_survey'] ) ?></textarea>
	</div>
</li>
    <div class="buttons">
    <button type="submit" class="positive">Update</button>
	<button type="reset" class="negative">Reset</button>
</div>
</form>
</div>
<br />
<?php /************************************************************/
include "./include/footer.php";
/********************************************************** PHP */?>