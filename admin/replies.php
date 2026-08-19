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

$HD_CURPAGE = $HD_URL_REPLIES;

if( $_SESSION['login_type'] == $LOGIN_INVALID )
  Header( "Location: {$HD_URL_LOGIN}?redirect=" . urlencode( $HD_CURPAGE ) );

$global_priv = get_row_count( "SELECT COUNT(*) FROM {$pre}privilege WHERE ( user_id = '{$_SESSION['user']['id']}' && dept_id = '0' && admin = '1' )" );

if( $_GET['cmd'] == "del" )
{
  $priv = get_row_count( "SELECT COUNT(*) FROM {$pre}privilege WHERE ( user_id = '{$_SESSION['user']['id']}' && dept_id = '{$_GET['dept_id']}' && admin = '1' )" );
  if( $priv || $global_priv )
  {
    mysql_query( "DELETE FROM {$pre}reply WHERE ( id = '{$_GET['id']}' && dept_id = '{$_GET['dept_id']}' )" );
    $msg = "<div class=\"successbox\">Successfully deleted auto-reply.</div><br />";
  }
}

include "./include/header.php";
/********************************************************** PHP */?>
<div class="title"><?php echo $script_name ?> Auto-Replies</div><br /><?php echo $msg ?>

  <div class="clean-gray">
    You can view auto-replies in this area.  Below are a list of the departments and their corresponding auto-replies.  You
    can create or delete auto-replies for departments in which you have administrative privileges.
  </div>

<br />
<div class="buttons">
    <a class="positive" href="<?php echo $HD_URL_REPLIESVIEW ?>"> 
        Create New Auto-Reply
    </a>
</div>

<?php /************************************************************/
$res = mysql_query( "SELECT * FROM {$pre}dept ORDER BY sortnum" );
while( $row = mysql_fetch_array( $res ) )
{
/********************************************************** PHP */?>
<table width="100%" bgcolor="#3c91c7" border="0" cellspacing="1" cellpadding="2">
<tr><td bgcolor="#3c91c7">
<h1><?php echo field( $row['name'] ) ?></h1>
</td></tr>
<tr><td bgcolor="#FFFFFF">
<table width="100%" border="0" cellspacing="0" cellpadding="5"><tr><td>
  <table bgcolor="#FFFFFF" width="100%" border="0" cellspacing="0" cellpadding="4">
  <tr><td colspan="2"><div class="clean-gray">Auto-replies for the following phrases will be used in this department:</div></td></tr>
  <tr><td><img src="./images/blank.gif" height="2" /></td></tr>
<?php /************************************************************/
  $res_reply = mysql_query( "SELECT * FROM {$pre}reply WHERE ( dept_id = '{$row['id']}' )" );
  $priv = get_row_count( "SELECT COUNT(id) FROM {$pre}privilege WHERE ( dept_id = '{$row['id']}' && admin = '1' && user_id = '{$_SESSION['user']['id']}' )" );

  if( mysql_num_rows( $res_reply ) )
  {
    while( $row_reply = mysql_fetch_array( $res_reply ) )
    {
      if( $global_priv || $priv )
        echo "<tr><td><a href=\"javascript:if(confirm('Are you sure you want to delete this auto-reply?')) window.location.href = '$HD_CURPAGE?cmd=del&id={$row_reply[0]}&dept_id={$row_reply['dept_id']}'\"><img src=\"./images/ticket-delete.png\" border=\"0\" hspace=\"2\" alt=\"Delete\" /></a><a href=\"$HD_URL_REPLIESVIEW?cmd=edit&id={$row_reply[0]}\"><img src=\"ticket-reply.png\" border=\"0\" hspace=\"2\" alt=\"View/Edit\" /></a></td>";
      else
        echo "<tr><td><img src=\"./images/nodelete.png\" border=\"0\" hspace=\"2\" /></a><a href=\"$HD_URL_REPLIESVIEW?cmd=edit&id={$row_reply[0]}\"></a></td>";

      echo "<td width=\"100%\"><div class=\"normal\"><a href=\"{$HD_URL_REPLIESVIEW}?cmd=edit&id={$row_reply[0]}\">" . (($row_reply['phrase'] == "") ? "[Global Auto-Reply - All Phrases]" : field( $row_reply['phrase'] )) . "</a>";
    }
  }

  if( $priv || $global_priv )
    echo "";
/********************************************************** PHP */?>
  </table>
</td></tr></table>
</td></tr>
<br />
</table>
<?php /************************************************************/
}

/********************************************************** PHP */?>
<?php /************************************************************/
include "./include/footer.php";
/********************************************************** PHP */?>