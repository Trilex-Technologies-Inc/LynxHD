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
<div class="title">General Settings</div><br /><?php echo $msg ?>
<table width="100%" border="0" >
<tr><td>
  <div class="clean-gray">
    Below you can modify the general settings.  The 'URL To Help Desk' must be set
    in order for the help desk to be completely operational.
  </div>
</td></tr>
</table>
<br />
<div id="container">
<h1>- General Settings -</h1>
<form class="wufoo" action="<?php echo $HD_CURPAGE ?>" method="post">
	<ul>
      <div class="clean-gray">The URL to the help desk must be of the full form (ie <i>http://www.yoursite.com/helpdesk/</i>).  The URL
      of your site should be the URL you want to appear at the bottom of emails (most likely your homepage).
      </div>
<li>
	   <label class="desc">URL To Help Desk:</label>
    <div>
    	<input class="field text medium" type="text" name="helpdeskurl" size="30" value="<?php echo field( $_POST['helpdeskurl'] ) ?>" />
   </div>
</li>
     <li>
	   <label class="desc">URL To Your Site:</label>
    <div>
    	<input class="field text medium" type="text" name="url" size="30" value="<?php echo field( $_POST['url'] ) ?>" /><br /><img src="./images/blank.gif" width="1" height="12" />  
	</div>
</li>
      <div class="clean-gray">The name of your help desk will appear in the title of web pages and
      at the bottom of emails sent by the help desk.
      </div>
    <li>
	   <label class="desc">Name Of Help Desk:</label>
    <div>
    	<input class="field text medium" type="text" name="title" size="30" value="<?php echo field( $_POST['title'] ) ?>" /><br /><img src="./images/blank.gif" width="1" height="12" />
	</div>
</li>
      <div class="clean-gray">This will allow customers and staff to attach files to tickets.</div>
  <li>
	   <label class="desc">Allow file attachments</label>
    <div>
    	<input class="field checkbox" type="checkbox" name="uploads" <?php if( $_POST['uploads'] ) echo "checked"  ?> />
	</div>
</li>
    <h1>- Email Settings -</h1>   
      <div class="clean-gray">The email address you specify below is where all emails sent by the help
      desk will appear to have came from.  This can include both a name and email in standard
      format (ie '<i>My Help Desk &lt;helpdesk@yoursite.com&gt</i>').
      </div>
   <li>
	   <label class="desc">Email Of Help Desk:</label>
    <div>
    	<input class="field text medium" type="text" name="email" size="30" value="<?php echo field( $_POST['email'] ) ?>" /><br /><img src="./images/blank.gif" width="1" height="12" />
	</div>
	</li>
	<h1>- SMTP Delivery -</h1>
	<div class="clean-gray">Send all help desk email through an authenticated SMTP server. When disabled, LynxHD uses PHP mail as before.</div>
	<li>
	  <label class="desc" for="smtp-enabled">Use SMTP</label>
	  <div><input class="field checkbox" id="smtp-enabled" type="checkbox" name="smtp_enabled" <?php if( $_POST['smtp_enabled'] ) echo "checked" ?> /></div>
	</li>
	<li>
	  <label class="desc" for="smtp-host">SMTP Host</label>
	  <div><input class="field text medium" id="smtp-host" type="text" name="smtp_host" value="<?php echo field($_POST['smtp_host']) ?>" placeholder="smtp.example.com" /></div>
	</li>
	<li>
	  <label class="desc" for="smtp-port">SMTP Port</label>
	  <div><input class="field text small" id="smtp-port" type="number" min="1" max="65535" name="smtp_port" value="<?php echo field($_POST['smtp_port'] ?: '587') ?>" /></div>
	</li>
	<li>
	  <label class="desc" for="smtp-encryption">Encryption</label>
	  <div><select class="field select medium" id="smtp-encryption" name="smtp_encryption">
	    <option value="starttls" <?php if($_POST['smtp_encryption'] === 'starttls' || $_POST['smtp_encryption'] === '') echo 'selected' ?>>STARTTLS (recommended)</option>
	    <option value="ssl" <?php if($_POST['smtp_encryption'] === 'ssl') echo 'selected' ?>>TLS/SSL</option>
	    <option value="none" <?php if($_POST['smtp_encryption'] === 'none') echo 'selected' ?>>None</option>
	  </select></div>
	</li>
	<li>
	  <label class="desc" for="smtp-username">SMTP Username</label>
	  <div><input class="field text medium" id="smtp-username" type="text" name="smtp_username" value="<?php echo field($_POST['smtp_username']) ?>" autocomplete="username" /></div>
	</li>
	<li>
	  <label class="desc" for="smtp-password">SMTP Password</label>
	  <div><input class="field text medium" id="smtp-password" type="password" name="smtp_password" value="" autocomplete="new-password" placeholder="Leave blank to keep the saved password" /></div>
	</li>
	<li class="smtp-test-panel">
	  <label class="desc" for="smtp-test-email">Send Test To</label>
	  <div class="smtp-test-controls">
	    <input class="field text medium" id="smtp-test-email" type="email" name="smtp_test_email" value="<?php echo field($_SESSION['user']['email'] ?? '') ?>" />
	    <button class="btn btn-outline-primary" type="submit" name="cmd" value="test_smtp"><i class="fas fa-paper-plane mr-1"></i> Save &amp; Test SMTP</button>
	  </div>
	</li>
	   <h1>- Auto-Ticket Management -</h1>
      <div class="clean-gray">You can have tickets automatically deleted and closed using the settings below.  Set each to '0' if you don't want
      them used.</div>
    <li>
	   <label class="desc">Close Tickets Inactive For:</label>
    <div>
    	<input type="text" name="autoclose" size="5" value="<?php echo field( $_POST['autoclose'] ) ?>" /> days
	</div>
</li>
    <li>
	   <label class="desc">Delete Tickets Closed For:</label>
    <div>
    	<input type="text" name="autodelete" size="5" value="<?php echo field( $_POST['autodelete'] ) ?>" /> days
	</div>
</li>
   <h1>- Banning -</h1>
      <div class="clean-gray">Specify IPs and email addresses you wish to ban from using the help desk.</div>
   <li>
	   <label class="desc">Banned IPs:</label>
    <div>
    	<textarea class="field textarea medium" name="banned_ips" rows="5" cols="40"><?php echo field( $_POST['banned_ips'] ) ?></textarea>
	</div>
</li>
   <li>
	   <label class="desc">Banned Emails:</label>
    <div>
    	<textarea class="field textarea medium" name="banned_emails" rows="5" cols="40"><?php echo field( $_POST['banned_emails'] ) ?></textarea>
	</div>
</li>
    <h1>- Flood Control -</h1>   
      <div class="clean-gray">Allows you to prevent duplicate tickets/postings.</div>
    <li>
	   <label class="desc">Enable flood control</label>
    <div>
	   	<input class="field checkbox" type="checkbox" name="floodcontrol" <?php if( $_POST['floodcontrol'] ) echo "checked"  ?> />
	</div>
</li>

      <h1>- Message Tags -</h1>
      <div class="clean-gray">Check the box below to enable message tags.  Message tags allow certain tags, such as [b][/b], etc., to create
      bold text, tables, lists, and more within the message of a post (much like many bulletin boards).  You can 
      <a href="<?php echo $HD_URL_TICKET_TAGS ?>" target="_blank">view</a> the available tags.</div>
    <li>
	   <label class="desc">Enable the use of message tags in posts.</label>
    <div>
		<input class="field checkbox" type="checkbox" name="tags"<?php echo ($_POST['tags'] ? " checked" : "") ?>  />
	</div>
</li>
    <h1>- Carbon Copies -</h1>
      <div class="clean-gray">Carbon copies allow the customer to enter emails on the ticket to receive emails to other addresses
      when his/her ticket is replied to.  If this is unchecked, the carbon copy box will not appear.  Either way, staff
      will always be able to setup carbon copies thru the staff ticket view.</div>
   <li>
	   <label class="desc">Enable the use of carbon copies for customers.</label>
    <div>
	   	<input class="field checkbox" type="checkbox" name="cc"<?php echo ($_POST['cc'] ? " checked" : "") ?>  /> 
	</div>
</li>	
   <div class="buttons">
	    <button type="submit" class="positive" name="cmd" value="add">Update</button>
	<button type="reset" class="negative">Reset</button>
</div>
</form>
</div>
<br />
<?php /************************************************************/
include "./include/footer.php";
/********************************************************** PHP */?>
