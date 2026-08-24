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
$form_submitted = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
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
      !filter_var( trim( $_POST['email'] ?? '' ), FILTER_VALIDATE_EMAIL ) )
    $error = 1;

  if( $error == 1 )
    $msg = '<div class="alert alert-danger shadow-sm" role="alert"><i class="fas fa-exclamation-circle mr-2"></i>' . field( $LANG['fields_not_filled'] ) . '</div>';
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
    hd_mail( $_POST['email'], $sub, $mes, "From: {$data['email']}" );

    if( trim( $_POST['replymessage'] ?? '' ) != "" )
    {
      // (time() + 1) makes sure this post follows the previous
      mysql_query( "INSERT INTO {$pre}post ( ticket_id, user_id, date, subject, message ) VALUES ( '$id', '{$_SESSION['user']['id']}', '" . (time( ) + 1) . "', '{$_POST['replysubject']}', '{$_POST['replymessage']}' )" );

      mysql_query( "UPDATE {$pre}ticket SET lastpost = '{$_SESSION['user']['id']}' WHERE ( id = '$id' )" );

      eval( "\$sub = \"{$data['email_ticket_notify_subject']}\";" );
      eval( "\$mes = \"{$data['email_ticket_notify']}\";" );
      hd_mail( $_POST['email'], $sub, $mes, "From: {$data['email']}" );
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
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between mb-4">
  <div><h1 class="h3 mb-1 text-gray-800">Create ticket</h1><p class="text-muted mb-0">Open a support request on behalf of a customer.</p></div>
  <a class="btn btn-light btn-sm shadow-sm mt-3 mt-sm-0" href="browse.php"><i class="fas fa-arrow-left fa-sm mr-1"></i> Browse tickets</a>
</div>
<?php echo $msg ?>
<?php /************************************************************/
if( $success )
{
  echo '<div class="card border-left-success shadow-sm mb-4"><div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between"><div><h2 class="h5 text-gray-800 mb-1"><i class="fas fa-check-circle text-success mr-2"></i>Ticket created</h2><p class="text-muted mb-3 mb-md-0">Ticket <strong>' . field( $ticket ) . '</strong> is ready for your team.</p></div><a class="btn btn-success" href="' . field( $HD_URL_ADMINVIEW ) . '?id=' . urlencode( $ticket ) . '">View ticket <i class="fas fa-arrow-right ml-1"></i></a></div></div>';
}
else
{
/********************************************************** PHP */?>
<form class="admin-ticket-form" action="<?php echo field( $HD_CURPAGE ) ?>" method="post">
  <section class="card shadow-sm mb-4">
        <div class="card-header py-3"><h2 class="h6 m-0 font-weight-bold text-primary"><i class="fas fa-user-circle mr-2"></i>Requester and issue</h2></div>
        <div class="card-body">
          <p class="small text-muted mb-4">Fields marked <span class="text-danger">*</span> are required. The requester will receive the standard ticket confirmation email.</p>
          <div class="form-row">
            <div class="form-group col-md-6"><label for="ticket-name"><?php echo $LANG['field_name'] ?> <span class="text-danger">*</span></label><input class="form-control" id="ticket-name" type="text" name="name" value="<?php echo field( $_POST['name'] ) ?>" required autocomplete="name" autofocus></div>
            <div class="form-group col-md-6"><label for="ticket-email"><?php echo $LANG['field_email'] ?> <span class="text-danger">*</span></label><input class="form-control" id="ticket-email" type="email" name="email" value="<?php echo field( $_POST['email'] ) ?>" required autocomplete="email" inputmode="email"></div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-7"><label for="ticket-department"><?php echo $LANG['field_department'] ?> <span class="text-danger">*</span></label><select class="form-control" id="ticket-department" name="department" required>
<?php /************************************************************/
  $res = mysql_query( "SELECT * FROM {$pre}dept ORDER BY sortnum" );

  while( $row = mysql_fetch_array( $res ) )
  {
    echo "<option value=\"{$row['id']}\" " . (($_POST['department'] == $row['id'] || $_POST['department'] == $row['name']) ? "selected" : "") . ">" . field( $row['name'] ) . "</option>\n";
  }
/********************************************************** PHP */?>
</select>
            </div>
            <div class="form-group col-md-5"><label for="ticket-priority"><?php echo $LANG['field_priority'] ?> <span class="text-danger">*</span></label><select class="form-control" id="ticket-priority" name="priority" required>
              <option value="<?php echo $PRIORITY_LOW ?>" <?php if($_POST['priority'] == $PRIORITY_LOW) echo 'selected' ?>><?php echo $LANG['field_priority_low'] ?></option>
              <option value="<?php echo $PRIORITY_MEDIUM ?>" <?php if($_POST['priority'] == $PRIORITY_MEDIUM) echo 'selected' ?>><?php echo $LANG['field_priority_medium'] ?></option>
              <option value="<?php echo $PRIORITY_HIGH ?>" <?php if($_POST['priority'] == $PRIORITY_HIGH) echo 'selected' ?>><?php echo $LANG['field_priority_high'] ?></option>
            </select></div>
          </div>
          <div class="form-group"><label for="ticket-subject"><?php echo $LANG['field_subject'] ?> <span class="text-danger">*</span></label><input class="form-control" id="ticket-subject" type="text" name="subject" value="<?php echo field( $_POST['subject'] ) ?>" required></div>
          <div class="form-group mb-0"><div class="d-flex justify-content-between"><label for="ticket-message"><?php echo $LANG['field_message'] ?> <span class="text-danger">*</span></label><?php if( $data['tags'] ): ?><a class="small" href="<?php echo field( $HD_URL_TICKET_TAGS ) ?>" target="_blank" rel="noopener">Message tags <i class="fas fa-external-link-alt fa-xs"></i></a><?php endif; ?></div><textarea class="form-control" id="ticket-message" name="message" rows="9" aria-required="true"><?php echo field( $_POST['message'] ) ?></textarea></div>
        </div>
  </section>
  <section class="card shadow-sm mb-4">
    <div class="card-header py-3 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between"><h2 class="h6 m-0 font-weight-bold text-primary"><i class="fas fa-reply mr-2"></i>Initial staff reply</h2><span class="badge badge-light mt-2 mt-sm-0">Optional</span></div>
    <div class="card-body">
      <p class="small text-muted mb-4">Send the customer a first response with the ticket, or leave both fields blank and reply later.</p>
      <div class="form-row">
        <div class="form-group col-lg-4 mb-lg-0"><label for="reply-subject"><?php echo $LANG['field_subject'] ?></label><input class="form-control" id="reply-subject" type="text" name="replysubject" value="<?php echo field( $_POST['replysubject'] ) ?>"><small class="form-text text-muted">Use a concise email subject.</small></div>
        <div class="form-group col-lg-8 mb-0"><div class="d-flex justify-content-between"><label for="reply-message"><?php echo $LANG['field_message'] ?></label><?php if( $data['tags'] ): ?><a class="small" href="<?php echo field( $HD_URL_TICKET_TAGS ) ?>" target="_blank" rel="noopener">Message tags <i class="fas fa-external-link-alt fa-xs"></i></a><?php endif; ?></div><textarea class="form-control" id="reply-message" name="replymessage" rows="7"><?php echo field( $_POST['replymessage'] ) ?></textarea></div>
      </div>
    </div>
  </section>
  <div class="card shadow-sm mb-4 admin-ticket-actions"><div class="card-body d-flex flex-column flex-sm-row align-items-sm-center justify-content-between"><span class="small text-muted mb-3 mb-sm-0"><i class="fas fa-envelope mr-1"></i>The requester will be notified by email.</span><div><button type="reset" class="btn btn-light mr-2">Clear form</button><button type="submit" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Create ticket</button></div></div></div>
</form>
<?php /************************************************************/
}
/********************************************************** PHP */?>

<?php /************************************************************/
include "./include/footer.php";
/********************************************************** PHP */?>
