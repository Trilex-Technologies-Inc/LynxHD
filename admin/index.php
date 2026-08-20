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

$HD_CURPAGE = $HD_URL_LOGIN;

$msg = "";

$do_redirect = 0;

$options = array( "email", "url", "title" );
$data = get_options( $options );

if( ($_POST['cmd'] ?? '') == "login" )
{
  if( trim( $_POST['password'] ?? '' ) == "" )
  {
    $res = mysql_query( "SELECT id FROM {$pre}user WHERE ( email = '{$_POST['email']}' )" );
    if( mysql_num_rows( $res ) )
    {
      $row = mysql_fetch_array( $res );
      $key = time( );

      mail( $_POST['email'], "Password Change Request", 
            "{$data['title']}\n" .
            "------------------------------\n\n" .
            "To change your password, go to {$PATH_TO_HELPDESK}{$HD_URL_PASSWORD}?key={$key}&id={$row['id']}",
            "From: {$data['email']}" );

      mysql_query( "UPDATE {$pre}user SET pwkey = '$key' WHERE ( id = '{$row['id']}' )" );

      $msg = "<div class=\"successbox\">Your password request has been sent.</div><br />";
    }
    else
      $msg = "<div class=\"errorbox\">Could not find an account with that email address.  Please contact your help desk administrator.</div><br />";
  }
  else
  {
    $_POST['password'] = crypt( $_POST['password'], $ENCRYPT_KEY );

    $res = mysql_query( "SELECT * FROM {$pre}user WHERE ( email = '{$_POST['email']}' && password = '{$_POST['password']}' )" );
    if( !mysql_num_rows( $res ) )
    {
      $msg = "<div class=\"errorbox\">Invalid login information.  Please contact your help desk administrator</div><br />";
      $_SESSION['login_type'] = $LOGIN_INVALID;
    }
    else
    {
      $row = mysql_fetch_array( $res );

      setcookie( "iv_helpdesk_login", $_POST['email'], time( ) + 2592000 );
      if( $row['notify'] & $HD_NOTIFY_SAVELOGIN )
        setcookie( "iv_helpdesk_password", $row['password'], time( ) + 2592000 );
      
      $_SESSION['login'] = $_POST['email'];
      $_SESSION['password'] = $row['password'];
      $_SESSION['login_type'] = $LOGIN_USER;
      $_SESSION['user'] = $row;
      $_SESSION['time'] = time( );

      mysql_query( "UPDATE {$pre}user SET lastlogin = '" . time( ) . "' WHERE ( id = '{$row['id']}' )" );

      // Auto ticket management processes when a login occurrs
      $options = get_options( array( "autoclose", "autodelete", "autosurvey" ) );
      if( $options['autodelete'] > 0 )
      {
        $res_ticket = mysql_query( "SELECT id FROM {$pre}ticket WHERE ( status = '$HD_STATUS_CLOSED' && lastactivity < '" . (time( ) - 86400 * $options['autodelete']) . "' )" );
        if( mysql_num_rows( $res_ticket ) )
        {
          while( $row_ticket = mysql_fetch_array( $res_ticket ) )
          {
            if( is_dir( "{$HD_TICKET_FILES}/{$row_ticket[0]}" ) )
              system( "rm -rf {$HD_TICKET_FILES}/{$row_ticket[0]}" );

            mysql_query( "DELETE FROM {$pre}post WHERE ( ticket_id = '{$row_ticket[0]}' )" );
            mysql_query( "DELETE FROM {$pre}ticket WHERE ( id = '{$row_ticket[0]}' )" );
          }
        }
      }
      if( $options['autoclose'] > 0 ) // Close tickets with low activity and send surveys if enabled
      {
        $res_survey = mysql_query( "SELECT id FROM {$pre}ticket WHERE ( status = '$HD_STATUS_OPEN' && lastactivity < '" . (time( ) - 86400 * $options['autoclose']) . "' )" );
        while( $row_survey = mysql_fetch_array( $res_survey ) )
        {
          mysql_query( "UPDATE {$pre}ticket SET status = '$HD_STATUS_CLOSED' WHERE ( id = '{$row_survey[0]}' )" );

          if( $options['autosurvey'] )
            send_survey( $row_survey[0] );
        }
      }

      if( trim( $_POST['redirect'] ?? '' ) != "" )
        $redirect = $_POST['redirect'];
      else
        $redirect = $HD_URL_BROWSE;
  
      $EXTRA_HEADER = "<meta http-equiv=\"refresh\" content=\"1; URL={$redirect}\" />";
      $msg = "<div class=\"successbox\">Login successful.  Redirecting you now.  Click <a href=\"{$redirect}\">here</a> if you aren't automatically forwarded...</div>";

      $do_redirect = 1;
    }
  }
}
else if( ($_GET['cmd'] ?? '') == "logout" )
{
  session_destroy( );
  unset( $_SESSION );
  setcookie( "iv_helpdesk_password", "", 0, "/" );
}
else
{
  if( $_SESSION['login_type'] != $LOGIN_INVALID )
    Header( "Location: $HD_URL_BROWSE" );
}

if( !isset( $_POST['email'] ) )
  $_POST['email'] = $_COOKIE['iv_helpdesk_login'] ?? '';

include "./include/header.php";
/********************************************************** PHP */?>
<?php echo $msg ?>

<?php /************************************************************/
if( !$do_redirect )
{
/********************************************************** PHP */?>
<p class="text-center text-muted mb-4">Sign in with your administrator account.</p>
<form class="user" action="<?php echo $HD_CURPAGE ?>" method="post">
  <input type="hidden" name="cmd" value="login">
  <input type="hidden" name="redirect" value="<?php echo field(($_GET['redirect'] ?? '') !== '' ? $_GET['redirect'] : ($_POST['redirect'] ?? '')) ?>">
  <div class="form-group">
    <label class="sr-only" for="email">Email address</label>
    <input class="form-control form-control-user" id="email" type="email" name="email" value="<?php echo field($_POST['email']) ?>" placeholder="Email address" required autocomplete="email">
  </div>
  <div class="form-group">
    <label class="sr-only" for="password">Password</label>
    <input class="form-control form-control-user" id="password" type="password" name="password" placeholder="Password" autocomplete="current-password">
  </div>
  <button class="btn btn-primary btn-user btn-block" type="submit">Sign in</button>
  <hr>
  <p class="text-center small text-muted mb-0">Leave the password blank to request password recovery by email.</p>
</form>
<?php /************************************************************/
}
/********************************************************** PHP */?>

<?php /************************************************************/
include "./include/footer.php";
/********************************************************** PHP */?>
