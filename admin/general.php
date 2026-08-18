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

if( $_SESSION[login_type] == $LOGIN_INVALID )
  Header( "Location: {$HD_URL_LOGIN}?redirect=" . urlencode( $HD_CURPAGE ) );

$global_priv = get_row_count( "SELECT COUNT(*) FROM {$pre}privilege WHERE ( user_id = '{$_SESSION[user][id]}' && dept_id = '0' )" );
if( !$global_priv )
  Header( "Location: $HD_URL_BROWSE" );

$options = array( "helpdeskurl", "url", "title", "email", "autoclose", "autodelete", "uploads", "banned_emails", "banned_ips", "floodcontrol", "tags", "cc" );

if( isset( $_POST[helpdeskurl] ) )
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
<input type="hidden" name="cmd" value="add" />
<ul>
      <div class="clean-gray">The URL to the help desk must be of the full form (ie <i>http://www.yoursite.com/helpdesk/</i>).  The URL
      of your site should be the URL you want to appear at the bottom of emails (most likely your homepage).
      </div>
<li>
	   <label class="desc">URL To Help Desk:</label>
    <div>
    	<input class="field text medium" type="text" name="helpdeskurl" size="30" value="<?php echo field( $_POST[helpdeskurl] ) ?>" />
   </div>
</li>
     <li>
	   <label class="desc">URL To Your Site:</label>
    <div>
    	<input class="field text medium" type="text" name="url" size="30" value="<?php echo field( $_POST[url] ) ?>" /><br /><img src="./images/blank.gif" width="1" height="12" />  
	</div>
</li>
      <div class="clean-gray">The name of your help desk will appear in the title of web pages and
      at the bottom of emails sent by the help desk.
      </div>
    <li>
	   <label class="desc">Name Of Help Desk:</label>
    <div>
    	<input class="field text medium" type="text" name="title" size="30" value="<?php echo field( $_POST[title] ) ?>" /><br /><img src="./images/blank.gif" width="1" height="12" />
	</div>
</li>
      <div class="clean-gray">This will allow customers and staff to attach files to tickets.</div>
  <li>
	   <label class="desc">Allow file attachments</label>
    <div>
    	<input class="field checkbox" type="checkbox" name="uploads" <?php if( $_POST[uploads] ) echo "checked"  ?> />
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
    	<input class="field text medium" type="text" name="email" size="30" value="<?php echo field( $_POST[email] ) ?>" /><br /><img src="./images/blank.gif" width="1" height="12" />
	</div>
</li>
   <h1>- Auto-Ticket Management -</h1>    
      <div class="clean-gray">You can have tickets automatically deleted and closed using the settings below.  Set each to '0' if you don't want
      them used.</div>
    <li>
	   <label class="desc">Close Tickets Inactive For:</label>
    <div>
    	<input type="text" name="autoclose" size="5" value="<?php echo field( $_POST[autoclose] ) ?>" /> days
	</div>
</li>
    <li>
	   <label class="desc">Delete Tickets Closed For:</label>
    <div>
    	<input type="text" name="autodelete" size="5" value="<?php echo field( $_POST[autodelete] ) ?>" /> days
	</div>
</li>
   <h1>- Banning -</h1>
      <div class="clean-gray">Specify IPs and email addresses you wish to ban from using the help desk.</div>
   <li>
	   <label class="desc">Banned IPs:</label>
    <div>
    	<textarea class="field textarea medium" name="banned_ips" rows="5" cols="40"><?php echo field( $_POST[banned_ips] ) ?></textarea>
	</div>
</li>
   <li>
	   <label class="desc">Banned Emails:</label>
    <div>
    	<textarea class="field textarea medium" name="banned_emails" rows="5" cols="40"><?php echo field( $_POST[banned_emails] ) ?></textarea>
	</div>
</li>
    <h1>- Flood Control -</h1>   
      <div class="clean-gray">Allows you to prevent duplicate tickets/postings.</div>
    <li>
	   <label class="desc">Enable flood control</label>
    <div>
	   	<input class="field checkbox" type="checkbox" name="floodcontrol" <?php if( $_POST[floodcontrol] ) echo "checked"  ?> />
	</div>
</li>

      <h1>- Message Tags -</h1>
      <div class="clean-gray">Check the box below to enable message tags.  Message tags allow certain tags, such as [b][/b], etc., to create
      bold text, tables, lists, and more within the message of a post (much like many bulletin boards).  You can 
      <a href="<?php echo $HD_URL_TICKET_TAGS ?>" target="_blank">view</a> the available tags.</div>
    <li>
	   <label class="desc">Enable the use of message tags in posts.</label>
    <div>
		<input class="field checkbox" type="checkbox" name="tags"<?php echo ($_POST[tags] ? " checked" : "") ?>  />
	</div>
</li>
    <h1>- Carbon Copies -</h1>
      <div class="clean-gray">Carbon copies allow the customer to enter emails on the ticket to receive emails to other addresses
      when his/her ticket is replied to.  If this is unchecked, the carbon copy box will not appear.  Either way, staff
      will always be able to setup carbon copies thru the staff ticket view.</div>
   <li>
	   <label class="desc">Enable the use of carbon copies for customers.</label>
    <div>
	   	<input class="field checkbox" type="checkbox" name="cc"<?php echo ($_POST[cc] ? " checked" : "") ?>  /> 
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