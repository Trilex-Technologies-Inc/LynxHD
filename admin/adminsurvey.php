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

$HD_CURPAGE = $HD_URL_SURVEY;

if( $_SESSION['login_type'] == $LOGIN_INVALID )
  Header( "Location: {$HD_URL_LOGIN}?redirect=" . urlencode( $HD_CURPAGE ) );

$global_priv = get_row_count( "SELECT COUNT(*) FROM {$pre}privilege WHERE ( user_id = '{$_SESSION['user']['id']}' && dept_id = '0' && admin = '1' )" );
if( !$global_priv )
  Header( "Location: {$HD_URL_LOGIN}?redirect=" . urlencode( $HD_CURPAGE ) );

if( $_GET['cmd'] == "delete" )
  mysql_query( "DELETE FROM {$pre}survey" );

if( isset( $_POST['survey1'] ) )
{
  for( $i = 1; $i <= 10; $i++ )
  {
    if( trim( $_POST["survey{$i}"] ?? '' ) != "" )
    {
      $exists = get_row_count( "SELECT COUNT(*) FROM {$pre}options WHERE ( name = 'survey{$i}' )" );
      if( !$exists )
        mysql_query( "INSERT INTO {$pre}options ( name, num, text ) VALUES ( 'survey{$i}', '$i', '" . $_POST["survey{$i}"] . "' )" );
      else
        mysql_query( "UPDATE {$pre}options SET text = '" . $_POST["survey{$i}"] . "' WHERE ( name = 'survey{$i}' )" );
    }
    else
      mysql_query( "DELETE FROM {$pre}options WHERE ( name = 'survey{$i}' )" );
  }

  $autosurvey = ($_POST['autosend'] == "on") ? "1" : "0";
  $repeatsurvey = ($_POST['repeat'] == "on") ? "1" : "0";

  mysql_query( "UPDATE {$pre}options SET text = '$autosurvey' WHERE ( name = 'autosurvey' ) ");
  mysql_query( "UPDATE {$pre}options SET text = '$repeatsurvey' WHERE ( name = 'repeatsurvey' ) ");
}

$res = mysql_query( "SELECT text FROM {$pre}options WHERE ( name = 'autosurvey' )" );
$row = mysql_fetch_array( $res );
$_POST['autosend'] = $row[0];

$res = mysql_query( "SELECT text FROM {$pre}options WHERE ( name = 'repeatsurvey' )" );
$row = mysql_fetch_array( $res );
$_POST['repeat'] = $row[0];

for( $i = 1; $i <= 10; $i++ )
{
  $res = mysql_query( "SELECT text FROM {$pre}options WHERE ( name = 'survey{$i}' )" );
  $row = mysql_fetch_array( $res );
  $_POST["survey{$i}"] = $row[0];
}

include "./include/header.php";
/********************************************************** PHP */?>
<div class="title"><?php echo $script_name ?> Surveys</div><br /><?php echo $msg ?>
<div class="normal">
<?php /************************************************************/
$num_fields = get_row_count( "SELECT COUNT(*) FROM {$pre}options WHERE ( name LIKE 'survey%' )" );
$num_surveys = get_row_count( "SELECT COUNT(*) FROM {$pre}survey" );

if( $num_surveys )
{
  $res = mysql_query( "SELECT date FROM {$pre}survey ORDER BY date DESC LIMIT 1" );
  $row = mysql_fetch_array( $res );
  $date = $row[0];

  echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#3c91c7\"><tr><td><div class=\"normal\" style=\"color: white\">There have been a total of <b>$num_surveys</b> survey(s).  The last survey was conducted on <b>" . date( "F j, Y", $row[0] ) . ".</b></div></td></tr></table><br />";

  $res = mysql_query( "SELECT * FROM {$pre}options WHERE ( name LIKE 'survey%' ) ORDER BY num" );
  while( $row = mysql_fetch_array( $res ) )
  {
    $res_temp = mysql_query( "SELECT AVG( rating{$row['num']} ) FROM {$pre}survey" );
    $row_temp = mysql_fetch_array( $res_temp );
    $avg = $row_temp[0];
   
    echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#99CCFF\"><tr><td><div class=\"normal\">";
    echo "<b>{$row['num']}. " . field( $row['text'] ) . "</b> <i>(";
    printf( "%.2f", $avg );
    echo "/5 average rating)</i>"; 
    echo "</div></td></tr></table><br />Customers who rated this a score of:<br /><br />";

    for( $i = 1; $i <= 5; $i++ )
    {
      $num_votes = get_row_count( "SELECT COUNT(*) FROM {$pre}survey WHERE ( rating{$row['num']} = '$i' )" );
      echo "<b>$i</b>:&nbsp;&nbsp;&nbsp;&nbsp;";
      echo "<img src=\"./images/histoclose.gif\" />";
      echo "<img src=\"./images/histobar.gif\" width=\"" . round( $num_votes / $num_surveys * 200 ) . "\" height=\"12\" />";
      echo "<img src=\"./images/histoclose.gif\" /> ";
      echo "<b>" . round( $num_votes / $num_surveys * 100 ) . "%</b> <font size=\"1\">[$num_votes customer(s)]</font><br />";
    }
    echo "<br />";
  }
/********************************************************** PHP */?>
<a name="#browse"></a>
<br />
<a href="javascript:if(confirm('Are you sure you want to delete all surveys?')) window.location.href='<?php echo $CURPAGE ?>?cmd=delete'"><img src="images/ticket-delete.png" border="0" hspace="5" />Delete All Surveys</a><br /><br />
<?php /************************************************************/
  $results = 10;
  if( !isset( $_GET['offset'] ) || $_GET['offset'] < 0 || $_GET['offset'] >= $num_surveys )
    $_GET['offset'] = 0;
/********************************************************** PHP */?>
<table width="100%" border="0" cellspacing="1" cellpadding="5" bgcolor="#3c91c7"><tr><td><div class="tableheader">
<?php /************************************************************/
  if( $_GET['offset'] > $results )
    $prevoffset = $_GET['offset'] - $results;
  else if ( $_GET['offset'] != 0 )
    $prevoffset = 0;

  if( $_GET['offset'] < ($num_surveys - $results) )
    $nextoffset = $_GET['offset'] + $results;

  if( isset( $prevoffset ) )
    echo "<a href=\"{$CURPAGE}?offset={$prevoffset}#browse\"><b>&lt;&lt;</b></a> - ";

  echo "Browsing Surveys";

  if( isset( $nextoffset ) )
    echo " - <a href=\"{$CURPAGE}?offset={$nextoffset}#browse\"><b>&gt;&gt;</b></a> "; 
/********************************************************** PHP */?>
</div></td></tr></table>
<table width="100%" border="0" cellspacing="1" cellpadding="3">
<tr bgcolor="#99CCFF"><td><div class="tableheader">Ticket#</div></td><td><div class="tableheader">E-mail</div></td><td><div class="tableheader">Date</div></td>
<?php /************************************************************/
  for( $i = 1; $i <= $num_fields; $i++ )
    echo "<td><div class=\"tableheader\">{$i}.</div></td>";
/********************************************************** PHP */?>
<td><div class="tableheader">Avg</div></td><td><div class="tableheader">Comments</div></td>
</tr>
<?php /************************************************************/
  $res = mysql_query( "SELECT * FROM {$pre}survey ORDER BY date DESC LIMIT {$_GET['offset']},$results" );
  while( $row = mysql_fetch_array( $res ) )
  {
    $bgcolor = ($bgcolor == "#E8EDFF") ? "#E8EDFF" : "#E8EDFF";

    $res_temp = mysql_query( "SELECT ticket_id FROM {$pre}ticket WHERE ( id = '{$row['ticket_id']}' )" );
    $row_temp = mysql_fetch_array( $res_temp );
    if( $row_temp )
      $ticket = "<a href=\"{$HD_URL_ADMINVIEW}?cmd=view&id={$row_temp[0]}\" target=\"_blank\">{$row_temp[0]}</a>";  
    else
      $ticket = "N/A";

    echo "<tr bgcolor=\"$bgcolor\"><td><div class=\"normal\">$ticket</div></td><td><div class=\"normal\"><a href=\"mailto:{$row['email']}\">{$row['email']}</a></div></td><td><div class=\"normal\">" . date( "m-j-Y", $row['date'] ) . "</div></td>\n";

    $total = 0;
    for( $i = 1; $i <= $num_fields; $i++ )
    {
      echo "<td><div class=\"normal\">" . $row["rating{$i}"] . "/5</div></td>";
      $total += $row["rating{$i}"];
    }   

    echo "<td><div class=\"normal\"><b>";
    printf( "%.2f", $total / $num_fields );
    echo "</b>/5</div></td>";

    if( trim( $row['comments'] ) != "" )
      $comments = "<a href=\"javascript:alert('" . addslashes( htmlspecialchars( $row['comments'] ) ) . "')\">" . substr( field( $row['comments'] ), 0, 10 ) . "...</a>";
    else
      $comments = "No comments";

    echo "<td><div class=\"normal\">$comments</div></td>";

    echo "</tr>";
  }
/********************************************************** PHP */?>
</table>
<?php /************************************************************/
}
else echo "<b>There are currently no completed surveys.</b><br />";
/********************************************************** PHP */?>
<br />
<div id="container">
	<h1>Survey Configuration</h1>
<table width="100%" >
<tr><td><br />
  <div class="clean-gray">
    The following options allow you to configure the use of customer surveys for the help desk.
    The 10 text boxes below allow you to specify the questions that will be asked of the customer
    (rating 1 to 5) in addition to his personal comments.  Leave fields blank if you do not want them used.  Also,
    if you do change the questions, be sure to delete the surveys (found above) so that new
    data won't conflict with the old.
  </div>
</td></tr>
</table><br />
<form class="wufoo" action="<?php echo $CURPAGE ?>" method="post">
<input type="hidden" name="cmd" value="setup" />
<table>
<tr><td align="right"><div class="normal">1.</div></td><td><input type="text" name="survey1" size="50" value="<?php echo field( $_POST['survey1'] ) ?>" /></td><td align="right"><div class="normal">6.</div></td><td><input type="text" name="survey6" size="50" value="<?php echo field( $_POST['survey6'] ) ?>" /></td></tr>
<tr><td align="right"><div class="normal">2.</div></td><td><input type="text" name="survey2" size="50" value="<?php echo field( $_POST['survey2'] ) ?>" /></td><td align="right"><div class="normal">7.</div></td><td><input type="text" name="survey7" size="50" value="<?php echo field( $_POST['survey7'] ) ?>" /></td></tr>
<tr><td align="right"><div class="normal">3.</div></td><td><input type="text" name="survey3" size="50" value="<?php echo field( $_POST['survey3'] ) ?>" /></td><td align="right"><div class="normal">8.</div></td><td><input type="text" name="survey8" size="50" value="<?php echo field( $_POST['survey8'] ) ?>" /></td></tr>
<tr><td align="right"><div class="normal">4.</div></td><td><input type="text" name="survey4" size="50" value="<?php echo field( $_POST['survey4'] ) ?>" /></td><td align="right"><div class="normal">9.</div></td><td><input type="text" name="survey9" size="50" value="<?php echo field( $_POST['survey9'] ) ?>" /></td></tr>
<tr><td align="right"><div class="normal">5.</div></td><td><input type="text" name="survey5" size="50" value="<?php echo field( $_POST['survey5'] ) ?>" /></td><td align="right"><div class="normal">10.</div></td><td><input type="text" name="survey10" size="50" value="<?php echo field( $_POST['survey10'] ) ?>" /></td></tr>
</table>
<br />
<input type="checkbox" name="autosend" <?php if( $_POST['autosend'] ) echo "checked" ?> /> Auto-send surveys when tickets are closed.<br />
<input type="checkbox" name="repeat" <?php if( $_POST['repeat'] ) echo "checked" ?> /> Send surveys to users who have already been surveyed for other tickets.<br /><br />
<div class="buttons">
    <button type="submit" class="positive">Update Configuration</button>
</div>
</form>
</div>
</div>
<?php /************************************************************/
include "./include/footer.php";
/********************************************************** PHP */?>
