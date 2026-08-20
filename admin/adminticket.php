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

$HD_CURPAGE = $HD_URL_ADMINTICKET;

if( $_SESSION['login_type'] == $LOGIN_INVALID )
  Header( "Location: {$HD_URL_LOGIN}?redirect=" . urlencode( $HD_CURPAGE ) );

$options = array( "header", "footer", "logo", "title", "background", "outsidebackground", "border", "topbar", "menu", "styles", "email", "url", "emailheader", "emailfooter", "tags", "email_ticket_created", "email_ticket_created_subject", "email_ticket_notify", "email_ticket_notify_subject" );
$data = get_options( $options );

if( isset( $_GET['subject'] ) )
  $_POST['subject'] = $_GET['subject'];
if( isset( $_GET['department'] ) )
  $_POST['department'] = $_GET['department'];

$success = 0;
$form_submitted = isset( $_POST['name'] );
$msg = "";
$ticket_form_defaults = array(
  "name" => "",
  "email" => "",
  "department" => "",
  "subject" => "",
  "message" => "",
  "priority" => $PRIORITY_LOW,
  "replysubject" => "",
  "replymessage" => ""
);
foreach( $ticket_form_defaults as $key => $value )
  if( !isset( $_POST[$key] ) )
    $_POST[$key] = $value;

if( $form_submitted )
{
  $error = 0;

  if( trim( $_POST['name'] ?? '' ) == "" ||
      trim( $_POST['subject'] ?? '' ) == "" ||
      trim( $_POST['message'] ?? '' ) == "" ||
      !eregi( "^[_a-z0-9-]+(\.[_a-z0-9-]+)*@([0-9a-z](-?[0-9a-z])*\.)+[a-z]{2}([zmuvtg]|fo|me)?$", $_POST['email'] ) )
    $error = 1;

  if( $error == 1 )
    $msg = "<div class=\"normal\"><font color=\"#FF0000\">{$LANG['fields_not_filled']}</font></div><br />";
  else
  {
    $ticket = strtoupper( base_convert( time( ), 10, 16 ) );
    if( get_row_count( "SELECT COUNT(*) FROM {$pre}ticket WHERE ( ticket_id = '$ticket' )" ) )
    {
      $res = mysql_query( "SELECT ticket_id FROM {$pre}ticket ORDER BY ticket_id DESC LIMIT 1" );
      $row = mysql_fetch_array( $res );
      $ticket = strtoupper( base_convert( base_convert( $row[0], 16, 10 ) + 1, 10, 16 ) );
    }

    $res = mysql_query( "SELECT name, text FROM {$pre}options WHERE ( name LIKE 'custom%' )" );
    $custom = "";
    while( $row = mysql_fetch_array( $res ) )
      $custom .= addslashes( $row['text'] ) . "\n" . ( $_POST[$row['name']] ?? "" ) . "\n";

    mysql_query( "INSERT INTO {$pre}ticket ( ticket_id, dept_id, email, name, subject, date, status, notify, priority, custom, lastactivity ) VALUES ( '$ticket', '{$_POST['department']}', '{$_POST['email']}', '{$_POST['name']}', '{$_POST['subject']}', '" . time( ) . "', '$HD_STATUS_OPEN', '1', '{$_POST['priority']}', '$custom', '" . time( ) . "' )" );

    $id = mysql_insert_id( );

    mysql_query( "INSERT INTO {$pre}post ( ticket_id, user_id, date, subject, message ) VALUES ( '$id', '-1', '" . time( ) . "', '{$_POST['subject']}', '{$_POST['message']}' )" );

    $subject = $_POST['subject'];
    $message = $_POST['replymessage'];
    $res = mysql_query( "SELECT name FROM {$pre}dept WHERE ( id = '{$_POST['department']}' )" );
    $row = mysql_fetch_array( $res );
    $department = $row[0];

    eval( "\$sub = \"{$data['email_ticket_created_subject']}\";" );
    eval( "\$mes = \"{$data['email_ticket_created']}\";" );
    mail( $_POST['email'], $sub, $mes, "From: {$data['email']}" );

    if( trim( $_POST['replymessage'] ?? '' ) != "" )
    {
      // (time() + 1) makes sure this post follows the previous
      mysql_query( "INSERT INTO {$pre}post ( ticket_id, user_id, date, subject, message ) VALUES ( '$id', '{$_SESSION['user']['id']}', '" . (time( ) + 1) . "', '{$_POST['replysubject']}', '{$_POST['replymessage']}' )" );

      mysql_query( "UPDATE {$pre}ticket SET lastpost = '{$_SESSION['user']['id']}' WHERE ( id = '$id' )" );

      eval( "\$sub = \"{$data['email_ticket_notify_subject']}\";" );
      eval( "\$mes = \"{$data['email_ticket_notify']}\";" );
      mail( $_POST['email'], $sub, $mes, "From: {$data['email']}" );
    }

    $autoreply = "";
    $res = mysql_query( "SELECT reply, phrase FROM {$pre}reply WHERE ( dept_id = '0' || dept_id = '{$_POST['department']}' )" );
    while( $row = mysql_fetch_array( $res ) )
    {
      if( $row['phrase'] == "" )
      {
        $autoreply = "{$row['reply']}\n\n";
        break;
      }
      else if( strstr( strtoupper( $_POST['subject'] ), strtoupper( $row['phrase'] ) ) )
      {
        $autoreply = "{$row['reply']}\n\n";
        break;
      }
    }

    $success = 1;
  }
}

/********************************************************** PHP */?>

<?php 
include "./include/header.php";
?>
<div class="title">Staff Ticket Creation</div><br /><?php echo $msg ?>
  <div class="clean-gray">
    Use this form to create a ticket for a user. (For instance when a staff member
    is helping a user over the phone and needs to create a ticket for that user.)  Simply
    fill out the ticket form below and optionally give the ticket a reply.  You can
    reply to the ticket later if you omit one now.
  </div>
  <br />
<?php /************************************************************/
if( $success )
{
  echo "The ticket <a href=\"{$HD_URL_ADMINVIEW}?id=$ticket\">$ticket</a> has been created.<br /><br />";
}
else
{
/********************************************************** PHP */?>
<div id="container">
	<h1>Ticket Information</h1>
<form class="wufoo" action="<?php echo $HD_CURPAGE ?>" method="post">
<ul>
<li>
	   <label class="desc"><?php echo $LANG['field_name'] ?><span class="req">*</span></label>
    <div>
    	<input class="field text medium" type="text" name="name" value="<?php echo field( $_POST['name'] ) ?>" size="30" />   
	</div>
</li>
<li>
	   <label class="desc"><?php echo $LANG['field_email'] ?><span class="req">*</span></label>
    <div>
    	<input class="field text medium" type="text" name="email" value="<?php echo field( $_POST['email'] ) ?>" size="30" />	
	</div>
</li>
<li>
	   <label class="desc"><?php echo $LANG['field_department'] ?><span class="req">*</span></label>
	   <span>
<select class="field select" name="department">
<?php /************************************************************/
  $res = mysql_query( "SELECT * FROM {$pre}dept ORDER BY sortnum" );

  while( $row = mysql_fetch_array( $res ) )
  {
    echo "<option value=\"{$row['id']}\" " . (($_POST['department'] == $row['id'] || $_POST['department'] == $row['name']) ? "selected" : "") . ">" . field( $row['name'] ) . "</option>\n";
  }
/********************************************************** PHP */?>
</select>
	   </span>
</li>
<li>
	   <label class="desc"><?php echo $LANG['field_subject'] ?><span class="req">*</span></label>
    <div>
    	<input class="field text medium" type="text" name="subject" value="<?php echo field( $_POST['subject'] ) ?>" size="30" />	
	</div>
</li>
<li>
	   <label class="desc"><?php echo $LANG['field_message'] ?><span class="req">*</span></label>
	   <?php if( $data['tags'] ) echo "<br /><div class=\"normal\"><font size=\"-2\"><b>You can use <a href=\"$HD_URL_TICKET_TAGS\" target=\"_blank\">message tags</a></b></font></div><img src=\"./images/blank.gif\" width=\"1\" height=\"5\" /><br />"; ?>
    <div>
	<textarea class="field textarea medium" name="message" rows="8" cols="45"><?php echo field( $_POST['message'] ) ?></textarea>	
	</div>
</li>
<li>
	   <label class="desc"><?php echo $LANG['field_priority'] ?><span class="req">*</span></label>
		<span>
<select class="field select" name="priority">
	<option value="<?php echo $PRIORITY_LOW ?>"><?php echo $LANG['field_priority_low'] ?></option>
	<option value="<?php echo $PRIORITY_MEDIUM ?>"><?php echo $LANG['field_priority_medium'] ?></option>
	<option value="<?php echo $PRIORITY_HIGH ?>"><?php echo $LANG['field_priority_high'] ?></option>
</select>
		</span>
</li>
<br />
<h1>Reply Information</h1>
<li>
	   <label class="desc"><?php echo $LANG['field_subject'] ?></label>
    <div>
    	<input class="field text medium" type="text" name="replysubject" value="<?php echo field( $_POST['replysubject'] ) ?>" size="30" />	
	</div>
</li>
<li>
	   <label class="desc"><?php echo $LANG['field_message'] ?></label>
	   <?php if( $data['tags'] ) echo "<br /><div class=\"normal\"><font size=\"-2\"><b>You can use <a href=\"$HD_URL_TICKET_TAGS\" target=\"_blank\">message tags</a></b></font></div><img src=\"./images/blank.gif\" width=\"1\" height=\"5\" /><br />"; ?>
	   <div>
	   <textarea class="field textarea medium" name="replymessage" rows="8" cols="45"><?php echo field( $_POST['replymessage'] ) ?></textarea>
	   </div>
</li>
<div class="buttons">
    <button type="submit" class="positive">Create Ticket</button>
	<button type="reset" class="negative">Reset</button>
</div>
</form>
</div>
<?php /************************************************************/
}
/********************************************************** PHP */?>

<?php /************************************************************/
include "./include/footer.php";
/********************************************************** PHP */?>
