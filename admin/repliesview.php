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

$HD_CURPAGE = $HD_URL_REPLIESVIEW;

if( $_SESSION['login_type'] == $LOGIN_INVALID )
  Header( "Location: {$HD_URL_LOGIN}?redirect=" . urlencode( $HD_CURPAGE ) );

$global_priv = get_row_count( "SELECT COUNT(*) FROM {$pre}privilege WHERE ( user_id = '{$_SESSION['user']['id']}' && dept_id = '0' && admin = '1' )" );

if( isset( $_GET['cmd'] ) )
  $_POST['cmd'] = $_GET['cmd'];

if( $_POST['cmd'] == "add" )
{
  $priv = get_row_count( "SELECT COUNT(*) FROM {$pre}privilege WHERE ( user_id = '{$_SESSION['user']['id']}' && dept_id = '{$_GET['dept_id']}' && admin = '1' )" );
  if( $priv || $global_priv )
  {
    $_POST['phrase'] = trim( $_POST['phrase'] ?? '' );
  
    if( $_POST['phrase'] == "" )
      $exists = get_row_count( "SELECT COUNT(id) FROM {$pre}reply WHERE ( dept_id = '{$_POST['dept_id']}' )" );
    else
      $exists = get_row_count( "SELECT COUNT(id) FROM {$pre}reply WHERE ( dept_id = '{$_POST['dept_id']}' && phrase = '{$_POST['phrase']}' )" );

    if( $exists )
      $msg = "<div class=\"errorbox\">An auto-reply assigned to that department with that specific phrase already exists.  If you left the phrase blank (which creates a reply that will be used with all tickets), you must make sure there are no other phrases for this department.</div><br />";
    else
    {
      mysql_query( "INSERT INTO {$pre}reply ( dept_id, reply, phrase ) VALUES ( '{$_POST['dept_id']}', '{$_POST['reply']}', '{$_POST['phrase']}' )" );
      Header( "Location: $HD_URL_REPLIES" );
    }
  }
}
else if( $_POST['cmd'] == "edit" )
{
  $priv = get_row_count( "SELECT COUNT(*) FROM {$pre}privilege WHERE ( user_id = '{$_SESSION['user']['id']}' && dept_id = '{$_GET['dept_id']}' && admin = '1' )" );
  if( !isset( $_POST['dept_id'] ) )
  {
    $res = mysql_query( "SELECT * FROM {$pre}reply WHERE ( id = '{$_GET['id']}' )" );
    $row = mysql_fetch_array( $res );

    if( $row && isset( $_GET['id'] ) )
    {
      while( list( $key, $val ) = each( $row ) )
        $_POST[$key] = $val;

      $_POST['id'] = $_GET['id'];
    }
    else
      $_POST['cmd'] == "add";
  }
  else if( $global_priv || $priv )
  {
    $_POST['phrase'] = trim( $_POST['phrase'] ?? '' );

    mysql_query( "UPDATE {$pre}reply SET dept_id = '{$_POST['dept_id']}', reply = '{$_POST['reply']}', phrase = '{$_POST['phrase']}' WHERE ( id = '{$_POST['id']}' )" );
    Header( "Location: $HD_URL_REPLIES" );
  }
}

include "./include/header.php";
/********************************************************** PHP */?>
<div class="title"><?php echo $script_name ?> Auto-Replies</div><br /><?php echo $msg ?>
<a class="btn btn-sm btn-light mb-3" href="<?php echo $HD_URL_REPLIES ?>">&larr; Back to auto-replies</a>
<section class="card shadow-sm mb-4"><div class="card-header"><h2 class="h6 mb-0">Auto-Reply Options</h2></div><div class="card-body">
<form action="<?php echo $HD_CURPAGE ?>" method="post">
<?php /************************************************************/
if( $_POST['cmd'] == "edit" )
{
  echo "<input type=\"hidden\" name=\"cmd\" value=\"edit\" />";
  echo "<input type=\"hidden\" name=\"id\" value=\"{$_POST['id']}\">";
}
else
  echo "<input type=\"hidden\" name=\"cmd\" value=\"add\" />";
/********************************************************** PHP */?>
<?php /************************************************************/
if( $global_priv || $priv )
{
/********************************************************** PHP */?>
    <div class="form-group"><label for="reply-department">Department</label><small class="form-text text-muted mb-2">Select the department that should use this auto-reply.</small>
<?php /************************************************************/
echo "<select class=\"form-control\" id=\"reply-department\" name=\"dept_id\">";

if( !$global_priv )
  $res = mysql_query( "SELECT dept.name, dept.id FROM {$pre}dept AS dept, {$pre}privilege AS priv WHERE ( priv.user_id = '{$_SESSION['user']['id']}' && priv.admin = '1' && priv.dept_id = dept.id )" );
else
  $res = mysql_query( "SELECT name, id FROM {$pre}dept" );
  
while( $row = mysql_fetch_array( $res ) )
  echo "<option value=\"{$row['id']}\"" . (($row['id'] == $_POST['dept_id']) ? " selected" : "") . ">" . field( $row['name'] ) . "</option>\n";

echo "</select>";
/********************************************************** PHP */?>
    </div>
<?php /************************************************************/
}
/********************************************************** PHP */?>
    <div class="form-group"><label for="reply-message">Message</label><small class="form-text text-muted mb-2">Appended to the ticket information email sent to the client.</small><textarea class="form-control" id="reply-message" name="reply" rows="6"><?php echo field( $_POST['reply'] ?? '' ) ?></textarea></div>
    <div class="form-group"><label for="reply-phrase">Key phrase</label><small class="form-text text-muted mb-2">Matched against the ticket subject. Leave blank to reply to every new ticket in this department.</small><input class="form-control" id="reply-phrase" type="text" name="phrase" value="<?php echo field( $_POST['phrase'] ?? '' ) ?>"></div>
<?php /************************************************************/
if( $global_priv || $priv )
{
/********************************************************** PHP */?>
    <div class="text-right"><input class="btn btn-light mr-2" type="reset"><input class="btn btn-primary" type="submit" value="Update"></div>
<?php /************************************************************/
}
/********************************************************** PHP */?>
</form>
</div></section>
<?php /************************************************************/
include "./include/footer.php";
/********************************************************** PHP */?>
