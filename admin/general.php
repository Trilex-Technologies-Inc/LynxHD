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

$HD_CURPAGE = $HD_URL_GENERAL;

if( $_SESSION['login_type'] == $LOGIN_INVALID )
  Header( "Location: {$HD_URL_LOGIN}?redirect=" . urlencode( $HD_CURPAGE ) );

$global_priv = get_row_count( "SELECT COUNT(*) FROM {$pre}privilege WHERE ( user_id = '{$_SESSION['user']['id']}' && dept_id = '0' )" );
if( !$global_priv )
  Header( "Location: $HD_URL_BROWSE" );

$options = array(
  "helpdeskurl", "url", "title", "email", "autoclose", "autodelete", "uploads",
  "banned_emails", "banned_ips", "floodcontrol", "tags", "cc", "smtp_enabled",
  "smtp_host", "smtp_port", "smtp_encryption", "smtp_username", "smtp_password"
);

if( isset( $_POST['helpdeskurl'] ) )
{
  $saved_smtp = get_options(array('smtp_password'));
  foreach( $options as $option )
    if( !isset($_POST[$option]) )
      $_POST[$option] = '';
  if( $_POST['smtp_password'] === '' )
    $_POST['smtp_password'] = $saved_smtp['smtp_password'];

  for( $i = 0; $i < count( $options ); $i++ )
  {
    $exists = get_row_count( "SELECT COUNT(*) FROM {$pre}options WHERE ( name = '{$options[$i]}' )" );
    if( $exists )
      mysql_query( "UPDATE {$pre}options SET text = '" . $_POST[$options[$i]] . "' WHERE ( name = '{$options[$i]}' )" );
    else
      mysql_query( "INSERT INTO {$pre}options ( name, text ) VALUES ( '{$options[$i]}', '" . $_POST[$options[$i]] . "' )" );
  }

  if( ($_POST['cmd'] ?? '') === 'test_smtp' )
  {
    $test_email = trim($_POST['smtp_test_email'] ?? '');
    if( empty($_POST['smtp_enabled']) )
      $msg = '<div class="errorbox">Enable SMTP before running the SMTP test.</div><br />';
    else if( !filter_var($test_email, FILTER_VALIDATE_EMAIL) )
      $msg = '<div class="errorbox">Enter a valid recipient address for the SMTP test.</div><br />';
    else
    {
      $smtp_error = '';
      $sent = hd_mail(
        $test_email,
        'LynxHD SMTP test',
        "This test message confirms that SMTP is configured correctly.\n\nSent: " . date(DATE_RFC2822),
        "From: {$_POST['email']}",
        $smtp_error
      );
      $msg = $sent
        ? '<div class="successbox">SMTP test sent successfully to ' . field($test_email) . '.</div><br />'
        : '<div class="errorbox">SMTP test failed: ' . field($smtp_error ?: 'Unknown mail error') . '</div><br />';
    }
  }
  else
    $msg = '<div class="successbox">Settings updated successfully.</div><br />';
}

$_POST = get_options( $options );
$_POST['smtp_password'] = '';

get_helpdesk_path( );

include "./include/header.php";
/********************************************************** PHP */?>
<div class="d-sm-flex align-items-center justify-content-between mb-4">
  <div><h1 class="h3 mb-1 text-gray-800">General settings</h1><p class="mb-0 text-muted">Configure your help desk, email delivery, and ticket policies.</p></div>
</div>
<?php echo $msg ?? '' ?>
<form action="<?php echo field($HD_CURPAGE) ?>" method="post" class="general-settings-form">
  <div class="row">
    <div class="col-xl-7">
      <section class="card shadow-sm mb-4">
        <div class="card-header py-3"><h2 class="h6 m-0 font-weight-bold text-primary"><i class="fas fa-globe mr-2"></i>Help desk identity</h2></div>
        <div class="card-body">
          <div class="form-group"><label for="helpdeskurl">Help desk URL</label><input class="form-control" id="helpdeskurl" type="url" name="helpdeskurl" required value="<?php echo field($_POST['helpdeskurl']) ?>" placeholder="https://support.example.com/"><small class="form-text text-muted">Full public URL, including the trailing slash.</small></div>
          <div class="form-group"><label for="site-url">Website URL</label><input class="form-control" id="site-url" type="url" name="url" value="<?php echo field($_POST['url']) ?>" placeholder="https://www.example.com/"><small class="form-text text-muted">Used for links in outgoing emails.</small></div>
          <div class="form-group"><label for="helpdesk-title">Help desk name</label><input class="form-control" id="helpdesk-title" type="text" name="title" required value="<?php echo field($_POST['title']) ?>"></div>
          <div class="custom-control custom-switch"><input class="custom-control-input" id="uploads" type="checkbox" name="uploads" value="1" <?php if($_POST['uploads']) echo 'checked' ?>><label class="custom-control-label" for="uploads">Allow file attachments</label><small class="d-block text-muted">Customers and staff can attach files to tickets.</small></div>
        </div>
      </section>

      <section class="card shadow-sm mb-4">
        <div class="card-header py-3"><h2 class="h6 m-0 font-weight-bold text-primary"><i class="fas fa-envelope mr-2"></i>Email delivery</h2></div>
        <div class="card-body">
          <div class="form-group"><label for="helpdesk-email">Sender address</label><input class="form-control" id="helpdesk-email" type="text" name="email" required value="<?php echo field($_POST['email']) ?>" placeholder="Support Team &lt;support@example.com&gt;"><small class="form-text text-muted">The From address used for help desk email.</small></div>
          <hr>
          <div class="custom-control custom-switch mb-3"><input class="custom-control-input" id="smtp-enabled" type="checkbox" name="smtp_enabled" value="1" <?php if($_POST['smtp_enabled']) echo 'checked' ?>><label class="custom-control-label font-weight-bold" for="smtp-enabled">Send through SMTP</label><small class="d-block text-muted">When off, LynxHD uses the server's PHP mail service.</small></div>
          <div id="smtp-settings" class="border rounded bg-light p-3">
            <div class="form-row"><div class="form-group col-md-8"><label for="smtp-host">SMTP host</label><input class="form-control" id="smtp-host" type="text" name="smtp_host" value="<?php echo field($_POST['smtp_host']) ?>" placeholder="smtp.example.com"></div><div class="form-group col-md-4"><label for="smtp-port">Port</label><input class="form-control" id="smtp-port" type="number" min="1" max="65535" name="smtp_port" value="<?php echo field($_POST['smtp_port'] ?: '587') ?>"></div></div>
            <div class="form-group"><label for="smtp-encryption">Encryption</label><select class="form-control" id="smtp-encryption" name="smtp_encryption"><option value="starttls" <?php if($_POST['smtp_encryption'] === 'starttls' || $_POST['smtp_encryption'] === '') echo 'selected' ?>>STARTTLS (recommended)</option><option value="ssl" <?php if($_POST['smtp_encryption'] === 'ssl') echo 'selected' ?>>TLS/SSL</option><option value="none" <?php if($_POST['smtp_encryption'] === 'none') echo 'selected' ?>>None</option></select></div>
            <div class="form-row"><div class="form-group col-md-6"><label for="smtp-username">Username</label><input class="form-control" id="smtp-username" type="text" name="smtp_username" value="<?php echo field($_POST['smtp_username']) ?>" autocomplete="username"></div><div class="form-group col-md-6"><label for="smtp-password">Password</label><input class="form-control" id="smtp-password" type="password" name="smtp_password" autocomplete="new-password" placeholder="Keep saved password"><small class="form-text text-muted">Leave blank to keep it unchanged.</small></div></div>
            <div class="form-group mb-0"><label for="smtp-test-email">Test recipient</label><div class="input-group"><input class="form-control" id="smtp-test-email" type="email" name="smtp_test_email" value="<?php echo field($_SESSION['user']['email'] ?? '') ?>"><div class="input-group-append"><button class="btn btn-outline-primary" type="submit" name="cmd" value="test_smtp"><i class="fas fa-paper-plane mr-1"></i>Save &amp; test</button></div></div></div>
          </div>
        </div>
      </section>
    </div>

    <div class="col-xl-5">
      <section class="card shadow-sm mb-4">
        <div class="card-header py-3"><h2 class="h6 m-0 font-weight-bold text-primary"><i class="fas fa-clock mr-2"></i>Ticket lifecycle</h2></div>
        <div class="card-body"><p class="text-muted small">Use 0 to disable either automatic action.</p><div class="form-group"><label for="autoclose">Close inactive tickets after</label><div class="input-group"><input class="form-control" id="autoclose" type="number" min="0" name="autoclose" value="<?php echo field($_POST['autoclose']) ?>"><div class="input-group-append"><span class="input-group-text">days</span></div></div></div><div class="form-group mb-0"><label for="autodelete">Delete closed tickets after</label><div class="input-group"><input class="form-control" id="autodelete" type="number" min="0" name="autodelete" value="<?php echo field($_POST['autodelete']) ?>"><div class="input-group-append"><span class="input-group-text">days</span></div></div></div></div>
      </section>

      <section class="card shadow-sm mb-4">
        <div class="card-header py-3"><h2 class="h6 m-0 font-weight-bold text-primary"><i class="fas fa-shield-alt mr-2"></i>Access and spam controls</h2></div>
        <div class="card-body"><div class="form-group"><label for="banned-ips">Banned IP addresses</label><textarea class="form-control no-tinymce" id="banned-ips" name="banned_ips" rows="4" placeholder="One entry per line"><?php echo field($_POST['banned_ips']) ?></textarea></div><div class="form-group"><label for="banned-emails">Banned email addresses</label><textarea class="form-control no-tinymce" id="banned-emails" name="banned_emails" rows="4" placeholder="One entry per line"><?php echo field($_POST['banned_emails']) ?></textarea></div><div class="custom-control custom-switch"><input class="custom-control-input" id="floodcontrol" type="checkbox" name="floodcontrol" value="1" <?php if($_POST['floodcontrol']) echo 'checked' ?>><label class="custom-control-label" for="floodcontrol">Prevent duplicate ticket submissions</label></div></div>
      </section>

      <section class="card shadow-sm mb-4">
        <div class="card-header py-3"><h2 class="h6 m-0 font-weight-bold text-primary"><i class="fas fa-sliders-h mr-2"></i>Customer features</h2></div>
        <div class="card-body"><div class="custom-control custom-switch mb-4"><input class="custom-control-input" id="tags" type="checkbox" name="tags" value="1" <?php if($_POST['tags']) echo 'checked' ?>><label class="custom-control-label" for="tags">Enable message tags</label><small class="d-block text-muted">Allow formatting tags in ticket posts. <a href="<?php echo field($HD_URL_TICKET_TAGS) ?>" target="_blank" rel="noopener">View supported tags</a>.</small></div><div class="custom-control custom-switch"><input class="custom-control-input" id="cc" type="checkbox" name="cc" value="1" <?php if($_POST['cc']) echo 'checked' ?>><label class="custom-control-label" for="cc">Allow customer carbon copies</label><small class="d-block text-muted">Customers can add other recipients to ticket updates.</small></div></div>
      </section>
    </div>
  </div>
  <div class="card shadow-sm mb-4 general-settings-actions"><div class="card-body d-flex flex-column flex-sm-row justify-content-end"><button type="reset" class="btn btn-light mr-sm-2 mb-2 mb-sm-0">Reset changes</button><button type="submit" class="btn btn-primary" name="cmd" value="add"><i class="fas fa-save mr-1"></i>Save settings</button></div></div>
</form>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var toggle = document.getElementById('smtp-enabled');
  var panel = document.getElementById('smtp-settings');
  function updateSmtpState() {
    panel.classList.toggle('smtp-settings-disabled', !toggle.checked);
  }
  toggle.addEventListener('change', updateSmtpState);
  updateSmtpState();
});
</script>
<?php /************************************************************/
include "./include/footer.php";
/********************************************************** PHP */?>
