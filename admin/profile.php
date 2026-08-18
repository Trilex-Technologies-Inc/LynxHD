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

if( $_SESSION[login_type] == $LOGIN_INVALID )
  Header( "Location: {$HD_URL_LOGIN}?redirect=" . urlencode( $HD_CURPAGE ) );

if( isset( $_POST[name] ) )
{
  if( trim( $_POST[name] ) == "" ||
      trim( $_POST[email] ) == "" ||
      ( (trim( $_POST[password1] ) != "") && ($_POST[password1] != $_POST[password2]) ) )
    $msg = "<div class=\"errorbox\">Please completely fill the name and email fields, and make sure your passwords (if specified) match.</div><br />";
  else
  {
    if( trim( $_POST[password1] ) == "" )
      $password = $_SESSION[user][password];
    else
      $password = crypt( $_POST[password1], $ENCRYPT_KEY );

    $_POST[notify] = 0;

    if( $_POST[notifycreation] == "on" )
      $_POST[notify] |= $HD_NOTIFY_CREATION;
    if( $_POST[notifyreply] == "on" )
      $_POST[notify] |= $HD_NOTIFY_REPLY;
    if( $_POST[savelogin] == "on" )
      $_POST[notify] |= $HD_NOTIFY_SAVELOGIN;

    mysql_query( "UPDATE {$pre}user SET name = '{$_POST[name]}', password = '$password', email = '{$_POST[email]}', sms = '{$_POST[sms]}', signature = '{$_POST[signature]}', notify = '{$_POST[notify]}' WHERE ( id = '{$_SESSION[user][id]}' )" );

    $row = mysql_fetch_array( mysql_query( "SELECT * FROM {$pre}user WHERE ( id = '{$_SESSION[user][id]}' )" ) );
    $_SESSION[user] = $row;
    $_SESSION[login_type] = $LOGIN_USER;
    $_SESSION[login] = $row[email];
    $_SESSION[password] = $row[password];

    $msg = "<div class=\"successbox\">Your user profile and options have been updated.</div><br />";
  }
}
else
  while( list( $key, $val ) = each( $_SESSION[user] ) )
    $_POST[$key] = $val;

include "./include/header.php";
/********************************************************** PHP */?>
<div class="title">Your Profile & Options</div><br /><?php echo $msg ?>
<div id="container">
<form class="wufoo" action="<?php echo $HD_CURPAGE ?>" method="post">
<input type="hidden" name="cmd" value="add" />
<ul>
<h1>- General Settings -</h1>
    <li>
	   <label class="desc">Name:&nbsp;</label>
    <div>
    	<input class="field text medium" type="text" name="name" size="30" value="<?php echo field( $_POST[name] ) ?>" />   
	</div>
</li>
<li>
	   <label class="desc">Email:&nbsp;</label>
    <div>
    	<input class="field text medium" type="text" name="email" size="30" value="<?php echo field( $_POST[email] ) ?>" />	
	</div>
</li>
    <li>
	   <label class="desc">SMS Email:&nbsp;</label>
    <div>
    	<input class="field text medium" type="text" name="sms" size="30" value="<?php echo field( $_POST[sms] ) ?>" />	
	</div>
</li>    
    <li>
	   <label class="desc">(Leave blank to keep same password)</label>
</li>	   
    <li>
	   <label class="desc">Password:&nbsp;</label>
    <div>
    	<input class="field text medium" type="password" name="password1" size="30" />
	</div>
</li>    
   <li>
	   <label class="desc">Password Again:&nbsp;</label>
    <div>
    	<input class="field text medium" type="password" name="password2" size="30" />
	</div>
</li>    
    <h1>- Email Notifications -</h1>
    <li>
	   <label class="desc">Notifications will be sent to your email and SMS email (if specified).</label>
</li>	   
    <li>
	<span>
          <input  type="checkbox" name="notifycreation" <?php echo ($_POST[notify] & $HD_NOTIFY_CREATION) ? "checked" : "" ?> /> Notify me when new tickets are created<br />
          <input  type="checkbox" name="notifyreply" <?php echo ($_POST[notify] & $HD_NOTIFY_REPLY) ? "checked" : "" ?> /> Notify me when customers reply to tickets I've handled<br />
    </span>
</li>
       <h1>- Other Options -</h1>
	<li>
	<span>	
          <input  type="checkbox" name="savelogin" <?php echo ($_POST[notify] & $HD_NOTIFY_SAVELOGIN) ? "checked" : "" ?> /> Save my login information<br />
	</span>
</li>
       <h1>- Signature -</h1>
    <li>
	   <label class="desc">Your signature (if specified) will be displayed at the bottom of each post you make when responding to tickets.</label>
    <div>
		<textarea class="field textarea medium" name="signature" rows="5" cols="40"><?php echo field( $_POST[signature] ) ?></textarea>	
	</div>
</li>  
    <div class="buttons">
    	<button type="submit" class="positive">Update</button>
		<button type="reset" class="negative">Reset</button>
	</div>
</form>

<br />
<?php /************************************************************/
include "./include/footer.php";
/********************************************************** PHP */?>