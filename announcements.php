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

$HD_CURPAGE = $HD_URL_MESSAGES;

$_GET['results'] = 10;

$rows_query = "SELECT COUNT(*) FROM {$pre}message WHERE ( user_id = '2' )";

$query = "SELECT ticket.*, message.viewed FROM {$pre}message AS message, {$pre}ticket AS ticket WHERE ( message.user_id = '2' && ticket.id = message.ticket_id ) ORDER BY lastactivity DESC";

$results = get_row_count( $rows_query );

if( !isset( $_GET['offset'] ) || $_GET['offset'] < 0 || $_GET['offset'] >= $results )
  $_GET['offset'] = 0;

$query .= " LIMIT {$_GET['offset']},{$_GET['results']}";

include "./include/header.php";
/********************************************************** PHP */?>
<div class="title"><center>Site Announcements</center></div><br /><?php echo $msg ?>
<table width="100%" border="0" cellspacing="1" cellpadding="3">
<tr bgcolor="#99CCFF"><td width="20"><input type="checkbox" name="all" onclick="checkall( );" /></td></td><td width="100"><div class="tableheader">Message#</div></td><td width="50%"><div class="tableheader">Subject</div></td><td><div class="tableheader">Posted</div></td><td><div class="tableheader">Last Post</div></td></tr>
<?php /************************************************************/

$res = mysql_query( $query );
while( $row = mysql_fetch_array( $res ) )
{
  $res_post_user = mysql_query( "SELECT user_id, private, message FROM {$pre}post WHERE ( ticket_id = '{$row['id']}' ) ORDER BY date DESC LIMIT 1" );
  $row_post_user = mysql_fetch_array( $res_post_user );

  $res_staff_user = mysql_query( "SELECT name FROM {$pre}user WHERE ( id = '{$row_post_user['user_id']}' )" );
  $row_staff_user = mysql_fetch_array( $res_staff_user );
  
  if( $row_post_user['user_id'] == $_SESSION['user']['id'] )
    $user_info = "<b>" . $row_staff_user['name'] . "</b>";
  else
    $user_info = $row_staff_user['name']; 
  
  $res_post = mysql_query( "SELECT COUNT(*) FROM {$pre}post WHERE ( ticket_id = '{$row['id']}' )" );
  $row_post = mysql_fetch_array( $res_post );

  $bgcolor = ($bgcolor == "#DDDDDD") ? "#EEEEEE" : "#DDDDDD";
  echo "<tr bgcolor=\"$bgcolor\">";
  
  if( $row['viewed'] )
    $image = "./images/mail.png";
  else
    $image = "./images/mail-response.png";


  echo "<td><input type=\"checkbox\" name=\"{$row['id']}\" /></td>";
  echo "<td><div class=\"normal\"><a href=\"{$HD_URL_ANNOUNVIEW}?cmd=view&id={$row['ticket_id']}\">{$row['ticket_id']}</a></div></td>";
  echo "<td><div class=\"normal\"><img src=\"{$image}\" /> <a href=\"{$HD_URL_ANNOUNVIEW}?cmd=view&id={$row['ticket_id']}\">" . field( $row['subject'] ) . "</a></div></td>";

  

  $lastactivity = time( ) - $row['lastactivity'];
  if( $lastactivity > 86400 )
  {
    if( (int)($lastactivity / 86400 ) <= 1 )
      $lastactivity = "<font color=\"#FF0000\"><b>" . (int)($lastactivity / 86400) . "d</b></font>";
    else
      $lastactivity = (int)($lastactivity / 86400) . "d";
  }
  else if( $lastactivity > 3600 )
    $lastactivity = "<font color=\"#FF0000\"><b>" . (int)($lastactivity / 3600) . "h</b></font>";
  else
    $lastactivity = "<font color=\"#FF0000\"><b>" . (int)($lastactivity / 60 ) . "m</b></font>";

  echo "<td><div class=\"normal\">$lastactivity ago</div></td>";
  echo "<td><div class=\"normal\"><span style=\"font-size: 8pt\">$user_info</span></div></td>";
  echo "</tr>";
  echo "<td><div class=\"normal\"><span style=\"font-size: 8pt\">Message: </span></div></td>";
}  

/********************************************************** PHP */?>
</table>
<br /><br />
<?php /************************************************************/
include "./include/footer.php";
/********************************************************** PHP */?>