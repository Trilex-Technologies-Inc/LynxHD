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
include "./include/settings.php";
include "./include/include.php";

$HD_CURPAGE = $HD_URL_TICKET_HOME;

$options = array( "header", "footer", "logo", "title", "background", "outsidebackground", "border", "topbar", "menu", "styles", "email", "url", "emailheader", "emailfooter", "tags", "email_ticket_created", "email_ticket_created_subject", "email_notify_create_subject", "email_notify_create", "email_notify_reply_subject", "email_notify_reply", "floodcontrol", "email_notifysms_create_subject", "email_notifysms_create", "email_notifysms_reply_subject", "email_notifysms_reply", "cc" );
$data = get_options( $options );

if( isset( $_GET['subject'] ) )
  $_POST['subject'] = $_GET['subject'];
if( isset( $_GET['department'] ) || isset( $_POST['department'] ) )
{
  if( isset( $_GET['department'] ) )
    $res = mysql_query( "SELECT name, id FROM {$pre}dept WHERE ( id = '{$_GET['department']}' || name = '{$_GET['department']}' )" );
  else
    $res = mysql_query( "SELECT name, id FROM {$pre}dept WHERE ( id = '{$_POST['department']}' || name = '{$_POST['department']}' )" );
  
  $row = mysql_fetch_array( $res );
  if( $row )
  {
    $dept_id = $row['id'];
    $dept_name = $row['name'];
  }
}

$success = 0;

if( isset( $_POST['name'] ) )
{
  $_POST['email'] = $_POST['email'] ?? '';
  $_POST['department'] = $_POST['department'] ?? '';
  $_POST['priority'] = $_POST['priority'] ?? $PRIORITY_MEDIUM;
  $_POST['cc'] = $_POST['cc'] ?? '';
  $error = 0;
  $captcha_valid = isset($_SESSION['vihash'], $_POST['key'])
    && hash_equals($_SESSION['vihash'], md5((string) $_POST['key'] . 'mySecRetkEy'));
  unset($_SESSION['vihash']);

  if( trim( $_POST['name'] ?? '' ) == "" ||
      trim( $_POST['subject'] ?? '' ) == "" ||
      trim( $_POST['message'] ?? '' ) == "" ||
      !$captcha_valid ||
      !eregi( "^[_a-z0-9-]+(\.[_a-z0-9-]+)*@([0-9a-z](-?[0-9a-z])*\.)+[a-z]{2,}([zmuvtg]|fo|me)?$", $_POST['email'] ) )
    $error = 1;

  if( !$error )
  {
    $res = mysql_query( "SELECT * FROM {$pre}field WHERE ( dept_id = '0' || dept_id = '$dept_id' )" );
    while( $row = mysql_fetch_array( $res ) )
    {
      if( $row['required'] && trim( $_POST[$row['id']] ?? '' ) == "" )
      {
        $error = 1;
        break;
      }
    }
  }

  if( $error == 1 )
    $msg = "<div class=\"alert alert-danger\" role=\"alert\">{$LANG['fields_not_filled']}</div>";
  else
  {
    // Determine if this user is banned
    $remote_address = $_SERVER['REMOTE_ADDR'] ?? '';
    if( get_row_count( "SELECT COUNT(*) FROM {$pre}options WHERE ( (name = 'banned_emails' && text LIKE '%{$_POST['email']}%') || (name = 'banned_ips' && text LIKE '%$remote_address%') ) " ) )
    {
      echo $LANG['banned'];
      exit;
    }

    // Checks for a duplicate ticket if flood control is enabled
    if( $data['floodcontrol'] )
    {
      $res_check = mysql_query( "SELECT id, ticket_id FROM {$pre}ticket WHERE ( name = '{$_POST['name']}' && email = '{$_POST['email']}' && subject = '{$_POST['subject']}' )" );
      while( $row_check = mysql_fetch_array( $res_check ) )
      {
        $res_check_post = mysql_query( "SELECT message FROM {$pre}post WHERE ( ticket_id = '{$row_check['id']}' && user_id = '-1' ) ORDER BY date LIMIT 1" );
        $row_check_post = mysql_fetch_array( $res_check_post );

        if( trim( $row_check_post['message'] ) == trim( stripslashes( $_POST['message'] ?? '' ) ) )
        {
          Header( "Location: {$HD_URL_TICKET_VIEW}?id={$row_check['ticket_id']}&email={$_POST['email']}" );
          exit;
        }
      }
    }

    $ticket = new_ticket_id( );

    $res = mysql_query( "SELECT * FROM {$pre}field WHERE ( dept_id = '0' || dept_id = '$dept_id' )" );
    $custom = "";
    while( $row = mysql_fetch_array( $res ) )
      $custom .= addslashes( $row['name'] ) . "\n" . ($_POST[$row['id']] ?? '') . "\n";

    mysql_query( "INSERT INTO {$pre}ticket ( ticket_id, dept_id, email, name, subject, date, status, notify, priority, custom, lastactivity, cc ) VALUES ( '$ticket', '{$_POST['department']}', '{$_POST['email']}', '{$_POST['name']}', '{$_POST['subject']}', '" . time( ) . "', '$HD_STATUS_OPEN', '" . (($_POST['notify'] ?? '') == "on" ? "1" : "0") . "', '{$_POST['priority']}', '$custom', '" . time( ) . "', '" . ($_POST['cc'] ?? '') . "' )" );

    $id = mysql_insert_id( );

    mysql_query( "INSERT INTO {$pre}post ( ticket_id, user_id, date, subject, message, ip ) VALUES ( '$id', '-1', '" . time( ) . "', '{$_POST['subject']}', '{$_POST['message']}', '$remote_address' )" );

    $res = mysql_query( "SELECT name FROM {$pre}dept WHERE ( id = '{$_POST['department']}' )" );
    $row = mysql_fetch_array( $res ) ?: array( 0 => '' );
    $department = $row[0];

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

    $email = stripslashes( $_POST['email'] );
    $name = stripslashes( $_POST['name'] );

    eval( "\$email_subject = \"{$data['email_ticket_created_subject']}\";" );
    eval( "\$email_message = \"{$data['email_ticket_created']}\";" );
    hd_mail( $_POST['email'], $email_subject, $email_message, "From: {$data['email']}" );

    // Notification messages
    $res_user = mysql_query( "SELECT DISTINCT user.email, user.sms FROM {$pre}user AS user, {$pre}privilege AS priv WHERE ( user.id = priv.user_id && (priv.dept_id = '0' || priv.dept_id = '{$_POST['department']}') && user.notify & {$HD_NOTIFY_CREATION} > '0' )" );
    while( $row_user = mysql_fetch_array( $res_user ) )
    {
      $message = stripslashes( $_POST['message'] );

      eval( "\$email_subject = \"{$data['email_notify_create_subject']}\";" );
      eval( "\$email_message = \"{$data['email_notify_create']}\";" );
      hd_mail( $row_user['email'], $email_subject, $email_message, "From: {$data['email']}" );

      if( trim( $row_user['sms'] ) != "" )
      {
        eval( "\$email_subject = \"{$data['email_notifysms_create_subject']}\";" );
        eval( "\$email_message = \"{$data['email_notifysms_create']}\";" );
        hd_mail( $row_user['sms'], $email_subject, $email_message, "From: {$data['email']}" );
      }
    }

    $success = 1;
  }
}      

if( trim( $data['header'] ) == "" )
{
/********************************************************** PHP */?>
<?php 
include "./include/header.php";
?>
<?php /************************************************************/
}
else
  eval( "?> {$data['header']} <?php" );
/********************************************************** PHP */?>
<?php if (trim($data['styles']) !== ''): ?>
<style><?php echo $data['styles'] ?></style>
<?php endif; ?>
<?php echo $msg ?? '' ?>
<?php /************************************************************/
if( $success )
{
  echo '<div class="text-center py-4"><div class="success-icon mb-3" aria-hidden="true">&#10003;</div><div class="alert alert-success d-inline-block mb-0" role="alert">';
  eval( "echo \"{$LANG['ticket_created']}\";" );
  echo '</div></div>';
}
else if( !isset( $dept_id ) )
{
/********************************************************** PHP */?>
<div class="mb-4">
  <span class="badge text-bg-primary mb-3">Step 1 of 2</span>
  <h2 class="h3 mb-2"><?php echo $LANG['select_department'] ?></h2>
  <p class="text-secondary mb-0">Choose the team that best matches your request.</p>
</div>
<form action="<?php echo $HD_CURPAGE ?>" method="get">
  <div class="row g-3 mb-4">
<?php /************************************************************/
  $res = mysql_query( "SELECT * FROM {$pre}dept WHERE ( !(options & {$HD_DEPARTMENT_INVISIBLE}) ) ORDER BY sortnum" );

  while( $row = mysql_fetch_array( $res ) )
  {
    $selected_department = $_POST['department'] ?? $_GET['department'] ?? '';
    $checked = ($selected_department == $row['id'] || $selected_department == $row['name']) ? ' checked' : '';
    echo '<div class="col-md-6">';
    echo '<label class="department-option card h-100 border shadow-sm p-3">';
    echo '<span class="d-flex align-items-start gap-3">';
    echo '<input class="form-check-input mt-1" type="radio" name="department" value="' . field($row['id']) . '" required' . $checked . '>';
    echo '<span><strong class="d-block text-body">' . field($row['name']) . '</strong>';
    if (trim($row['description']) !== '')
      echo '<small class="text-secondary">' . field($row['description']) . '</small>';
    echo '</span></span></label></div>';
  }
/********************************************************** PHP */?>
  </div>
  <div class="d-flex justify-content-end">
    <button type="submit" class="btn btn-primary btn-lg">Continue <span aria-hidden="true">&rarr;</span></button>
  </div>
</form>
<?php /************************************************************/
}
else
{
/********************************************************** PHP */?>

<div class="mb-4">
  <span class="badge text-bg-primary mb-3">Step 2 of 2</span>
  <h2 class="h3 mb-2"><?php echo $LANG['create_new_ticket'] ?></h2>
  <p class="text-secondary mb-0">Fields marked with <span class="text-danger">*</span> are required.</p>
</div>
<form action="<?php echo $HD_CURPAGE ?>" method="post" class="row g-4">
  <input type="hidden" name="department" value="<?php echo field($dept_id) ?>">
  <div class="col-md-6">
    <label class="form-label" for="ticket-name"><?php echo $LANG['field_name'] ?> <span class="text-danger">*</span></label>
    <input class="form-control form-control-lg" id="ticket-name" type="text" name="name" value="<?php echo field($_POST['name'] ?? '') ?>" required autocomplete="name">
  </div>
  <div class="col-md-6">
    <label class="form-label" for="ticket-email"><?php echo $LANG['field_email'] ?> <span class="text-danger">*</span></label>
    <input class="form-control form-control-lg" id="ticket-email" type="email" name="email" value="<?php echo field($_POST['email'] ?? '') ?>" required autocomplete="email">
  </div>
<?php /************************************************************/
  if( $data['cc'] )
  {
/********************************************************** PHP */?>
  <div class="col-12">
    <label class="form-label" for="ticket-cc"><?php echo $LANG['field_cc'] ?></label>
    <input class="form-control" id="ticket-cc" type="text" name="cc" value="<?php echo field($_POST['cc'] ?? '') ?>">
    <div class="form-text"><?php echo $LANG['separate_by_space'] ?></div>
  </div>
<?php /************************************************************/
  }
/********************************************************** PHP */?>
  <div class="col-12">
    <div class="alert alert-light border d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-0">
      <span><strong><?php echo $LANG['field_department'] ?>:</strong> <?php echo field($dept_name) ?></span>
      <a class="btn btn-sm btn-outline-secondary" href="<?php echo $HD_CURPAGE ?>">Change department</a>
    </div>
  </div>
<?php /************************************************************/
  $res = mysql_query( "SELECT * FROM {$pre}field WHERE ( dept_id = '0' || dept_id = '$dept_id' ) ORDER BY dept_id" );
  if( mysql_num_rows( $res ) )
  {
    while( $row = mysql_fetch_array( $res ) )
    {
      $required = $row['required'] ? ' required' : '';
      $required_label = $row['required'] ? ' <span class="text-danger">*</span>' : '';
      echo '<div class="col-md-6"><label class="form-label" for="custom-' . field($row['id']) . '">' . field($row['name']) . $required_label . '</label>';
      echo '<input class="form-control" id="custom-' . field($row['id']) . '" type="text" name="' . field($row['id']) . '" value="' . field($_POST[$row['id']] ?? '') . '"' . $required . '></div>';
    }
  }
/********************************************************** PHP */?>
  <div class="col-12">
    <label class="form-label" for="ticket-subject"><?php echo $LANG['field_subject'] ?> <span class="text-danger">*</span></label>
    <input class="form-control form-control-lg" id="ticket-subject" type="text" name="subject" value="<?php echo field($_POST['subject'] ?? '') ?>" required>
  </div>
  <div class="col-12">
    <div class="d-flex justify-content-between align-items-center gap-2">
      <label class="form-label" for="ticket-message"><?php echo $LANG['field_message'] ?> <span class="text-danger">*</span></label>
      <?php if ($data['tags']): ?><a class="small" href="<?php echo $HD_URL_TICKET_TAGS ?>" target="_blank" rel="noopener">Message formatting help</a><?php endif; ?>
    </div>
    <textarea class="form-control" id="ticket-message" name="message" rows="8" required><?php echo field($_POST['message'] ?? '') ?></textarea>
    <div class="form-text">Describe the problem, what you expected, and any steps needed to reproduce it.</div>
  </div>
  <div class="col-md-6">
    <label class="form-label" for="ticket-priority"><?php echo $LANG['field_priority'] ?></label>
    <select class="form-select" id="ticket-priority" name="priority">
      <option value="<?php echo $PRIORITY_LOW ?>"><?php echo $LANG['field_priority_low'] ?></option>
      <option value="<?php echo $PRIORITY_MEDIUM ?>"><?php echo $LANG['field_priority_medium'] ?></option>
      <option value="<?php echo $PRIORITY_HIGH ?>"><?php echo $LANG['field_priority_high'] ?></option>
    </select>
  </div>
  <div class="col-md-6 d-flex align-items-end">
    <div class="form-check form-switch mb-2">
      <input class="form-check-input" id="ticket-notify" type="checkbox" name="notify" checked>
      <label class="form-check-label" for="ticket-notify"><?php echo $LANG['ticket_notify'] ?></label>
    </div>
  </div>
  <div class="col-12">
    <div class="captcha-panel border rounded-3 p-3">
      <label class="form-label" for="captcha-key">Security code</label>
      <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3">
        <div class="d-flex align-items-center gap-2">
          <img class="captcha-image rounded border" id="captcha-image" src="./include/view.php" alt="Security verification code">
          <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('captcha-image').src='./include/view.php?refresh='+Date.now()" aria-label="Show a new security code">Refresh</button>
        </div>
        <input class="form-control" id="captcha-key" type="text" name="key" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" autocomplete="off" placeholder="Enter the 5 digits shown" required>
      </div>
      <div class="form-text mt-2">Can’t read it? Select Refresh to generate a clearer code.</div>
    </div>
  </div>
  <div class="col-12 d-flex flex-column-reverse flex-sm-row justify-content-end gap-2 pt-2">
    <button type="reset" class="btn btn-outline-secondary">Reset</button>
    <button type="submit" class="btn btn-primary btn-lg">Create ticket</button>
  </div>
</form>
<?php /************************************************************/
}
/********************************************************** PHP */?>
<?php /************************************************************/
if( trim( $data['header'] ) == "" )
{
/********************************************************** PHP */?>
<?php  
include "./include/footer.php";
?>
<?php /************************************************************/
}
else
  eval( "?> {$data['footer']} <?php" );
/********************************************************** PHP */?>
