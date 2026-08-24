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
$mail_warning = '';
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

// include.php escapes legacy request values for interpolated SQL. This page
// uses prepared statements, so restore the original text before validating or
// saving it. Without this, apostrophes and rich-text markup are corrupted.
foreach( $_POST as $key => $value )
  if( is_string($value) )
    $_POST[$key] = stripslashes($value);

$current_user_id = (int)($_SESSION['user']['id'] ?? 0);
$has_global_access = !empty($_SESSION['user']['admin']) || get_row_count( "SELECT COUNT(*) FROM {$pre}privilege WHERE ( user_id = '$current_user_id' && dept_id = '0' )" );
$department_query = $has_global_access
  ? "SELECT id, name FROM {$pre}dept ORDER BY sortnum, name"
  : "SELECT DISTINCT dept.id, dept.name FROM {$pre}dept AS dept LEFT JOIN {$pre}privilege AS priv ON (priv.dept_id = dept.id && priv.user_id = '$current_user_id') WHERE (dept.id = 0 || priv.user_id = '$current_user_id') ORDER BY dept.sortnum, dept.name";
$department_result = mysql_query( $department_query );
$allowed_departments = array();
if( $department_result )
  while( $department_row = mysql_fetch_array( $department_result ) )
    $allowed_departments[(int)$department_row['id']] = $department_row['name'];

if( $_POST['department'] === '' && $allowed_departments )
  $_POST['department'] = (string)array_key_first( $allowed_departments );

$custom_fields = array();
$custom_field_result = mysql_query( "SELECT id, dept_id, name, required FROM {$pre}field ORDER BY dept_id, id" );
if( $custom_field_result )
  while( $custom_field_row = mysql_fetch_array( $custom_field_result ) )
    $custom_fields[] = $custom_field_row;

if( empty($_SESSION['admin_ticket_csrf']) )
  $_SESSION['admin_ticket_csrf'] = bin2hex( random_bytes( 32 ) );

function admin_ticket_has_content( $value )
{
  $plain = html_entity_decode( strip_tags( (string)$value ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
  return trim( str_replace( array("\xc2\xa0", '&nbsp;'), ' ', $plain ) ) !== '';
}

if( $form_submitted )
{
  $_POST['name'] = trim( (string)$_POST['name'] );
  $_POST['email'] = trim( (string)$_POST['email'] );
  $_POST['subject'] = trim( (string)$_POST['subject'] );
  $department_id = filter_var( $_POST['department'], FILTER_VALIDATE_INT, array('options' => array('min_range' => 0)) );
  $priority = filter_var( $_POST['priority'], FILTER_VALIDATE_INT );
  $errors = array();

  if( !isset($_POST['csrf_token']) || !hash_equals( $_SESSION['admin_ticket_csrf'], (string)$_POST['csrf_token'] ) )
    $errors[] = 'Your form session expired. Reload the page and try again.';
  if( $_POST['name'] === '' )
    $errors[] = 'Enter the requester name.';
  else if( strlen($_POST['name']) > 20 )
    $errors[] = 'The requester name must be 20 characters or fewer.';
  if( !filter_var( $_POST['email'], FILTER_VALIDATE_EMAIL ) || strlen($_POST['email']) > 255 )
    $errors[] = 'Enter a valid requester email address.';
  if( $_POST['subject'] === '' )
    $errors[] = 'Enter a ticket subject.';
  else if( strlen($_POST['subject']) > 255 )
    $errors[] = 'The ticket subject must be 255 characters or fewer.';
  if( !admin_ticket_has_content( $_POST['message'] ) )
    $errors[] = 'Enter a ticket message.';
  if( $department_id === false || !array_key_exists((int)$department_id, $allowed_departments) )
    $errors[] = 'Choose a department you are allowed to use.';
  if( !in_array($priority, array($PRIORITY_LOW, $PRIORITY_MEDIUM, $PRIORITY_HIGH), true) )
    $errors[] = 'Choose a valid priority.';
  if( strlen((string)$_POST['replysubject']) > 255 )
    $errors[] = 'The reply subject must be 255 characters or fewer.';

  $custom = '';
  if( $department_id !== false )
  {
    foreach( $custom_fields as $custom_field )
    {
      if( (int)$custom_field['dept_id'] !== 0 && (int)$custom_field['dept_id'] !== (int)$department_id )
        continue;
      $custom_value = trim( (string)($_POST['custom_' . $custom_field['id']] ?? '') );
      if( $custom_field['required'] && $custom_value === '' )
        $errors[] = 'Complete the required custom field: ' . $custom_field['name'] . '.';
      $custom .= $custom_field['name'] . "\n" . $custom_value . "\n";
    }
  }

  if( $errors )
    $msg = '<div class="alert alert-danger shadow-sm" role="alert"><div class="font-weight-bold mb-1"><i class="fas fa-exclamation-circle mr-2"></i>The ticket was not created.</div><ul class="mb-0 pl-4"><li>' . implode('</li><li>', array_map('field', $errors)) . '</li></ul></div>';
  else
  {
    $db = $GLOBALS['_lynxhd_mysql_connection'] ?? null;
    $created_ticket_db_id = 0;
    try
    {
      if( !($db instanceof mysqli) )
        throw new RuntimeException('Database connection is unavailable.');

      $ticket = strtoupper( dechex(time()) . bin2hex(random_bytes(2)) );
      $now = time();
      $notify = 1;
      $requester_email = $_POST['email'];
      $requester_name = $_POST['name'];
      $ticket_subject = $_POST['subject'];
      $ticket_message = $_POST['message'];
      $cc = '';
      $ticket_stmt = mysqli_prepare( $db, "INSERT INTO {$pre}ticket (ticket_id, dept_id, email, name, subject, date, status, notify, priority, custom, lastactivity, cc) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)" );
      if( !$ticket_stmt )
        throw new RuntimeException('Could not prepare the ticket record.');
      mysqli_stmt_bind_param( $ticket_stmt, 'sisssiiiisis', $ticket, $department_id, $requester_email, $requester_name, $ticket_subject, $now, $HD_STATUS_OPEN, $notify, $priority, $custom, $now, $cc );
      if( !mysqli_stmt_execute($ticket_stmt) )
        throw new RuntimeException('Could not save the ticket record.');
      $created_ticket_db_id = mysqli_insert_id( $db );
      mysqli_stmt_close( $ticket_stmt );
      if( !$created_ticket_db_id )
        throw new RuntimeException('The database did not return a ticket ID.');

      $customer_user_id = -1;
      $post_stmt = mysqli_prepare( $db, "INSERT INTO {$pre}post (ticket_id, user_id, date, subject, message) VALUES (?, ?, ?, ?, ?)" );
      if( !$post_stmt )
        throw new RuntimeException('Could not prepare the customer message.');
      mysqli_stmt_bind_param( $post_stmt, 'iiiss', $created_ticket_db_id, $customer_user_id, $now, $ticket_subject, $ticket_message );
      if( !mysqli_stmt_execute($post_stmt) )
        throw new RuntimeException('Could not save the customer message.');
      mysqli_stmt_close( $post_stmt );

      $has_initial_reply = admin_ticket_has_content( $_POST['replymessage'] );
      if( $has_initial_reply )
      {
        $reply_time = $now + 1;
        $reply_subject = trim( (string)$_POST['replysubject'] );
        $reply_message = $_POST['replymessage'];
        $reply_stmt = mysqli_prepare( $db, "INSERT INTO {$pre}post (ticket_id, user_id, date, subject, message) VALUES (?, ?, ?, ?, ?)" );
        if( !$reply_stmt )
          throw new RuntimeException('Could not prepare the initial reply.');
        mysqli_stmt_bind_param( $reply_stmt, 'iiiss', $created_ticket_db_id, $current_user_id, $reply_time, $reply_subject, $reply_message );
        if( !mysqli_stmt_execute($reply_stmt) )
          throw new RuntimeException('Could not save the initial reply.');
        mysqli_stmt_close( $reply_stmt );

        $last_post_stmt = mysqli_prepare( $db, "UPDATE {$pre}ticket SET lastpost = ? WHERE id = ?" );
        if( !$last_post_stmt )
          throw new RuntimeException('Could not prepare the ticket activity update.');
        mysqli_stmt_bind_param( $last_post_stmt, 'ii', $current_user_id, $created_ticket_db_id );
        if( !mysqli_stmt_execute($last_post_stmt) )
          throw new RuntimeException('Could not update the ticket activity.');
        mysqli_stmt_close( $last_post_stmt );
      }
    }
    catch( Throwable $exception )
    {
      $database_error_number = $db instanceof mysqli ? mysqli_errno($db) : 0;
      if( $created_ticket_db_id )
      {
        $cleanup_post = mysqli_prepare( $db, "DELETE FROM {$pre}post WHERE ticket_id = ?" );
        if( $cleanup_post ) { mysqli_stmt_bind_param($cleanup_post, 'i', $created_ticket_db_id); mysqli_stmt_execute($cleanup_post); mysqli_stmt_close($cleanup_post); }
        $cleanup_ticket = mysqli_prepare( $db, "DELETE FROM {$pre}ticket WHERE id = ?" );
        if( $cleanup_ticket ) { mysqli_stmt_bind_param($cleanup_ticket, 'i', $created_ticket_db_id); mysqli_stmt_execute($cleanup_ticket); mysqli_stmt_close($cleanup_ticket); }
      }
      error_log( 'Admin ticket creation failed: ' . $exception->getMessage() . ' Database error: ' . ($db instanceof mysqli ? mysqli_error($db) : 'no connection') );
      $error_reference = $database_error_number ? ' (database error ' . $database_error_number . ')' : '';
      $msg = '<div class="alert alert-danger shadow-sm" role="alert"><i class="fas fa-exclamation-circle mr-2"></i>The ticket could not be saved: ' . field($exception->getMessage()) . field($error_reference) . ' No partial ticket was kept.</div>';
    }

    if( $created_ticket_db_id && $msg === '' )
    {
      unset( $_SESSION['admin_ticket_csrf'] );
      $department = $allowed_departments[(int)$department_id];
      $name = $_POST['name'];
      $email = $_POST['email'];
      $subject = $_POST['subject'];
      $message = $_POST['replymessage'];
      $autoreply = '';
      $reply_department = (int)$department_id;
      $reply_result = mysql_query( "SELECT reply, phrase FROM {$pre}reply WHERE (dept_id = '0' || dept_id = '$reply_department')" );
      if( $reply_result )
        while( $reply_row = mysql_fetch_array($reply_result) )
        {
          if( $reply_row['phrase'] === '' || stripos($_POST['subject'], $reply_row['phrase']) !== false )
          {
            $autoreply = $reply_row['reply'] . "\n\n";
            break;
          }
        }

      $template_variables = array(
        'ticket' => $ticket,
        'department' => $department,
        'name' => $name,
        'email' => $email,
        'subject' => $subject,
        'message' => $message,
        'autoreply' => $autoreply
      );
      $confirmation_subject = render_email_template( $data['email_ticket_created_subject'], $template_variables, $data );
      $confirmation_message = render_email_template( $data['email_ticket_created'], $template_variables, $data );
      $mail_error = '';
      if( !hd_mail($_POST['email'], $confirmation_subject, $confirmation_message, "From: {$data['email']}", $mail_error) )
        $mail_warning = 'The ticket was saved, but the confirmation email could not be sent.';

      if( $has_initial_reply )
      {
        $reply_email_subject = render_email_template( $data['email_ticket_notify_subject'], $template_variables, $data );
        $reply_email_message = render_email_template( $data['email_ticket_notify'], $template_variables, $data );
        $reply_mail_error = '';
        if( !hd_mail($_POST['email'], $reply_email_subject, $reply_email_message, "From: {$data['email']}", $reply_mail_error) )
          $mail_warning = 'The ticket and initial reply were saved, but one or more notification emails could not be sent.';
      }
      $success = 1;
    }
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
  if( $mail_warning !== '' )
    echo '<div class="alert alert-warning shadow-sm"><i class="fas fa-envelope-open-text mr-2"></i>' . field($mail_warning) . '</div>';
}
else
{
/********************************************************** PHP */?>
<form class="admin-ticket-form" action="<?php echo field( $HD_CURPAGE ) ?>" method="post">
  <input type="hidden" name="csrf_token" value="<?php echo field($_SESSION['admin_ticket_csrf']) ?>">
  <?php if(!$allowed_departments): ?><div class="alert alert-warning shadow-sm"><i class="fas fa-exclamation-triangle mr-2"></i>No ticket departments are available to your account. Ask an administrator to assign department access.</div><?php endif; ?>
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
<?php foreach($allowed_departments as $allowed_department_id => $allowed_department_name): ?><option value="<?php echo (int)$allowed_department_id ?>" <?php echo ((string)$_POST['department'] === (string)$allowed_department_id || $_POST['department'] === $allowed_department_name) ? 'selected' : '' ?>><?php echo field($allowed_department_name) ?></option><?php endforeach; ?>
</select>
            </div>
            <div class="form-group col-md-5"><label for="ticket-priority"><?php echo $LANG['field_priority'] ?> <span class="text-danger">*</span></label><select class="form-control" id="ticket-priority" name="priority" required>
              <option value="<?php echo $PRIORITY_LOW ?>" <?php if($_POST['priority'] == $PRIORITY_LOW) echo 'selected' ?>><?php echo $LANG['field_priority_low'] ?></option>
              <option value="<?php echo $PRIORITY_MEDIUM ?>" <?php if($_POST['priority'] == $PRIORITY_MEDIUM) echo 'selected' ?>><?php echo $LANG['field_priority_medium'] ?></option>
              <option value="<?php echo $PRIORITY_HIGH ?>" <?php if($_POST['priority'] == $PRIORITY_HIGH) echo 'selected' ?>><?php echo $LANG['field_priority_high'] ?></option>
            </select></div>
          </div>
          <?php if($custom_fields): ?><div class="form-row admin-ticket-custom-fields" id="ticket-custom-fields"><?php foreach($custom_fields as $custom_field): $custom_name = 'custom_' . $custom_field['id']; ?><div class="form-group col-md-6 ticket-custom-field" data-department="<?php echo (int)$custom_field['dept_id'] ?>"><label for="<?php echo field($custom_name) ?>"><?php echo field($custom_field['name']) ?><?php if($custom_field['required']): ?> <span class="text-danger">*</span><?php endif; ?></label><input class="form-control" id="<?php echo field($custom_name) ?>" type="text" name="<?php echo field($custom_name) ?>" value="<?php echo field($_POST[$custom_name] ?? '') ?>" data-required="<?php echo $custom_field['required'] ? '1' : '0' ?>"></div><?php endforeach; ?></div><?php endif; ?>
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
  <div class="card shadow-sm mb-4 admin-ticket-actions"><div class="card-body d-flex flex-column flex-sm-row align-items-sm-center justify-content-between"><span class="small text-muted mb-3 mb-sm-0"><i class="fas fa-envelope mr-1"></i>The requester will be notified by email.</span><div><button type="reset" class="btn btn-light mr-2">Clear form</button><button type="submit" class="btn btn-primary" <?php echo $allowed_departments ? '' : 'disabled' ?>><i class="fas fa-plus mr-1"></i>Create ticket</button></div></div></div>
</form>
<?php if($custom_fields): ?><script>
document.addEventListener('DOMContentLoaded', function () {
  var department = document.getElementById('ticket-department');
  var fields = Array.prototype.slice.call(document.querySelectorAll('.ticket-custom-field'));
  function updateCustomFields() {
    fields.forEach(function (wrapper) {
      var input = wrapper.querySelector('input');
      var visible = wrapper.getAttribute('data-department') === '0' || wrapper.getAttribute('data-department') === department.value;
      wrapper.hidden = !visible;
      input.disabled = !visible;
      input.required = visible && input.getAttribute('data-required') === '1';
    });
  }
  department.addEventListener('change', updateCustomFields);
  updateCustomFields();
});
</script><?php endif; ?>
<?php /************************************************************/
}
/********************************************************** PHP */?>

<?php /************************************************************/
include "./include/footer.php";
/********************************************************** PHP */?>
