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

$HD_CURPAGE = $HD_URL_PROFILE;

if( $_SESSION['login_type'] == $LOGIN_INVALID )
  Header( "Location: {$HD_URL_LOGIN}?redirect=" . urlencode( $HD_CURPAGE ) );

$profile_submitted = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';

if( !$profile_submitted )
{
  foreach( $_SESSION['user'] as $key => $val )
    $_POST[$key] = $val;
}

$notify_value = $profile_submitted ? 0 : (int)($_POST['notify'] ?? 0);
if( $profile_submitted )
{
  if( ($_POST['notifycreation'] ?? '') == "on" )
    $notify_value |= $HD_NOTIFY_CREATION;
  if( ($_POST['notifyreply'] ?? '') == "on" )
    $notify_value |= $HD_NOTIFY_REPLY;
  if( ($_POST['savelogin'] ?? '') == "on" )
    $notify_value |= $HD_NOTIFY_SAVELOGIN;
}

if( $profile_submitted )
{
  if( trim( $_POST['name'] ?? '' ) == "" ||
      !filter_var( trim( $_POST['email'] ?? '' ), FILTER_VALIDATE_EMAIL ) ||
      ( (trim( $_POST['password1'] ?? '' ) != "") && ($_POST['password1'] != $_POST['password2']) ) )
    $msg = '<div class="alert alert-danger shadow-sm" role="alert"><i class="fas fa-exclamation-circle mr-2"></i>Enter a name and valid email address, and make sure the new passwords match.</div>';
  else
  {
    if( trim( $_POST['password1'] ?? '' ) == "" )
      $password = $_SESSION['user']['password'];
    else
      $password = crypt( $_POST['password1'], $ENCRYPT_KEY );

    mysql_query( "UPDATE {$pre}user SET name = '{$_POST['name']}', password = '$password', email = '{$_POST['email']}', sms = '" . ($_POST['sms'] ?? '') . "', signature = '" . ($_POST['signature'] ?? '') . "', notify = '$notify_value' WHERE ( id = '{$_SESSION['user']['id']}' )" );

    $row = mysql_fetch_array( mysql_query( "SELECT * FROM {$pre}user WHERE ( id = '{$_SESSION['user']['id']}' )" ) );
    $_SESSION['user'] = $row;
    $_SESSION['login_type'] = $LOGIN_USER;
    $_SESSION['login'] = $row['email'];
    $_SESSION['password'] = $row['password'];

    $msg = '<div class="alert alert-success shadow-sm" role="status"><i class="fas fa-check-circle mr-2"></i>Your profile and preferences have been updated.</div>';
  }
}

include "./include/header.php";
/********************************************************** PHP */?>
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between mb-4">
  <div><h1 class="h3 mb-1 text-gray-800">My profile</h1><p class="text-muted mb-0">Manage your account details and support preferences.</p></div>
  <span class="profile-avatar mt-3 mt-sm-0" aria-hidden="true"><?php echo field( strtoupper( substr( $_POST['name'] ?? '', 0, 1 ) ) ) ?></span>
</div>
<?php echo $msg ?>
<form class="profile-settings-form" action="<?php echo field( $HD_CURPAGE ) ?>" method="post">
  <input type="hidden" name="cmd" value="add">
  <div class="row">
    <div class="col-lg-7">
      <section class="card shadow-sm mb-4">
        <div class="card-header py-3"><h2 class="h6 m-0 font-weight-bold text-primary"><i class="fas fa-id-card mr-2"></i>Account details</h2></div>
        <div class="card-body">
          <div class="form-group"><label for="profile-name">Name <span class="text-danger">*</span></label><input class="form-control" id="profile-name" type="text" name="name" required autocomplete="name" value="<?php echo field( $_POST['name'] ?? '' ) ?>"></div>
          <div class="form-row"><div class="form-group col-md-6"><label for="profile-email">Email address <span class="text-danger">*</span></label><input class="form-control" id="profile-email" type="email" name="email" required autocomplete="email" value="<?php echo field( $_POST['email'] ?? '' ) ?>"><small class="form-text text-muted">Used to sign in and receive notifications.</small></div><div class="form-group col-md-6"><label for="profile-sms">SMS email</label><input class="form-control" id="profile-sms" type="email" name="sms" autocomplete="email" value="<?php echo field( $_POST['sms'] ?? '' ) ?>" placeholder="number@carrier.example"><small class="form-text text-muted">Optional carrier email gateway.</small></div></div>
        </div>
      </section>
      <section class="card shadow-sm mb-4">
        <div class="card-header py-3"><h2 class="h6 m-0 font-weight-bold text-primary"><i class="fas fa-pen-nib mr-2"></i>Reply signature</h2></div>
        <div class="card-body"><p class="small text-muted">This signature is added to the bottom of replies you post to tickets.</p><textarea class="form-control" id="profile-signature" name="signature" rows="7"><?php echo field( $_POST['signature'] ?? '' ) ?></textarea></div>
      </section>
    </div>
    <div class="col-lg-5">
      <section class="card shadow-sm mb-4">
        <div class="card-header py-3"><h2 class="h6 m-0 font-weight-bold text-primary"><i class="fas fa-lock mr-2"></i>Change password</h2></div>
        <div class="card-body"><p class="small text-muted">Leave both fields blank to keep your current password.</p><div class="form-group"><label for="profile-password">New password</label><input class="form-control" id="profile-password" type="password" name="password1" autocomplete="new-password"></div><div class="form-group mb-0"><label for="profile-password-confirm">Confirm new password</label><input class="form-control" id="profile-password-confirm" type="password" name="password2" autocomplete="new-password"></div></div>
      </section>
      <section class="card shadow-sm mb-4">
        <div class="card-header py-3"><h2 class="h6 m-0 font-weight-bold text-primary"><i class="fas fa-bell mr-2"></i>Notifications</h2></div>
        <div class="card-body"><p class="small text-muted">Notifications are sent to your email and SMS email, when provided.</p><div class="custom-control custom-switch mb-3"><input class="custom-control-input" id="notify-creation" type="checkbox" name="notifycreation" <?php echo ($notify_value & $HD_NOTIFY_CREATION) ? 'checked' : '' ?>><label class="custom-control-label" for="notify-creation">New tickets</label><small class="d-block text-muted">Notify me when a ticket is created.</small></div><div class="custom-control custom-switch"><input class="custom-control-input" id="notify-reply" type="checkbox" name="notifyreply" <?php echo ($notify_value & $HD_NOTIFY_REPLY) ? 'checked' : '' ?>><label class="custom-control-label" for="notify-reply">Customer replies</label><small class="d-block text-muted">Notify me about replies to tickets I have handled.</small></div></div>
      </section>
      <section class="card shadow-sm mb-4">
        <div class="card-header py-3"><h2 class="h6 m-0 font-weight-bold text-primary"><i class="fas fa-desktop mr-2"></i>Session</h2></div>
        <div class="card-body"><div class="custom-control custom-switch"><input class="custom-control-input" id="save-login" type="checkbox" name="savelogin" <?php echo ($notify_value & $HD_NOTIFY_SAVELOGIN) ? 'checked' : '' ?>><label class="custom-control-label" for="save-login">Remember my login</label><small class="d-block text-muted">Keep me signed in on this browser.</small></div></div>
      </section>
    </div>
  </div>
  <div class="card shadow-sm mb-4 profile-settings-actions"><div class="card-body d-flex flex-column flex-sm-row align-items-sm-center justify-content-between"><span class="small text-muted mb-3 mb-sm-0"><i class="fas fa-shield-alt mr-1"></i>Your password is only changed when both password fields are completed.</span><div><button class="btn btn-light mr-2" type="reset">Reset changes</button><button class="btn btn-primary" type="submit"><i class="fas fa-save mr-1"></i>Save profile</button></div></div></div>
</form>
<?php /************************************************************/
include "./include/footer.php";
/********************************************************** PHP */?>
