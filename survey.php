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

$HD_CURPAGE = $HD_URL_SURVEY;
$CURPAGE = $HD_CURPAGE;

$options = array( "header", "footer", "logo", "title", "background", "outsidebackground", "border", "topbar", "menu", "styles", "email", "url", "emailheader", "emailfooter" );
$data = get_options( $options );

if( isset( $_POST['id'] ) )
{
  $_GET['id'] = $_POST['id'];
  $_GET['email'] = $_POST['email'];
}

$ticketexists = 0;

if( isset( $_GET['id'], $_GET['email'] ) && $_GET['id'] !== '' && $_GET['email'] !== '' )
{
  $exists = get_row_count( "SELECT COUNT(*) FROM {$pre}ticket WHERE ( ticket_id = '{$_GET['id']}' && email = '{$_GET['email']}' )" );
  if( !$exists )
  {
    $msg = "<div class=\"normal\"><font color=\"#FF0000\">";
    eval( "\$msg .= \"{$LANG['no_find_ticket']}\";" );
    $msg .= "</font></div><br />";

    $ticketexists = 0;
  }
  else
  {
    $res = mysql_query( "SELECT id FROM {$pre}ticket WHERE ( ticket_id = '{$_GET['id']}' )" );
    $row = mysql_fetch_array( $res );
    $id = $row[0];
    $ticketexists = 1;
  }
}

if( $ticketexists )
{
  if( isset( $_POST['comments'] ) )
  {
    for( $survey_index = 1; $survey_index <= 10; $survey_index++ )
      $_POST["survey{$survey_index}"] = $_POST["survey{$survey_index}"] ?? 0;
    $exists = get_row_count( "SELECT COUNT(*) FROM {$pre}survey WHERE ( ticket_id = '$id' )" );
    if( !$exists )
      mysql_query( "INSERT INTO {$pre}survey ( ticket_id, rating1, rating2, rating3, rating4, rating5, rating6, rating7, rating8, rating9, rating10, comments, date, email ) VALUES ( '$id', '{$_POST['survey1']}', '{$_POST['survey2']}', '{$_POST['survey3']}', '{$_POST['survey4']}', '{$_POST['survey5']}', '{$_POST['survey6']}', '{$_POST['survey7']}', '{$_POST['survey8']}', '{$_POST['survey9']}', '{$_POST['survey10']}', '{$_POST['comments']}', '" . time( ) . "', '{$_GET['email']}' )" );
  }
}

if( trim( $data['header'] ) == "" )
{
/********************************************************** PHP */?>
<?php 
include "./include/header.php";
?>
<?php /************************************************************/
}
else
  eval( "?> {$data['header']} <?php" );
/********************************************************** PHP */?>
<style type="text/css">
<?php echo $data['styles'] ?>
</style>
<div class="title"><?php echo $LANG['survey'] ?></div><br /><?php echo $msg ?>
<?php /************************************************************/
if( $ticketexists )
{
  if( isset( $_POST['comments'] ) )
  {
/********************************************************** PHP */?>
<div class="normal">
<?php echo $LANG['survey_thanks'] ?>
</div>
<?php /************************************************************/
  }
  else
  {
/********************************************************** PHP */?>
<div class="normal">
<?php eval( "echo \"{$LANG['survey_header']}\";" ); ?>
<form action="<?php echo $CURPAGE ?>" method="post">
<input type="hidden" name="id" value="<?php echo $_GET['id'] ?>" />
<input type="hidden" name="email" value="<?php echo $_GET['email'] ?>" />
<table border="0" cellspacing="10" cellpadding="0">
<?php /************************************************************/
    $res = mysql_query( "SELECT name, text FROM {$pre}options WHERE ( name LIKE 'survey%' ) ORDER BY num" );
    while( $row = mysql_fetch_array( $res ) )
    {
/********************************************************** PHP */?>
<tr>
  <td><div class="normal"><b><?php echo field( $row['text'] ) ?></b></div></td>
  <td>
    <div class="normal">
    <i><?php echo $LANG['survey_poor'] ?></i>
    <input type="radio" value="1" name="<?php echo field( $row['name'] ) ?>" /> 1
    <input type="radio" value="2" name="<?php echo field( $row['name'] ) ?>" /> 2
    <input type="radio" value="3" name="<?php echo field( $row['name'] ) ?>" checked /> 3
    <input type="radio" value="4" name="<?php echo field( $row['name'] ) ?>" /> 4
    <input type="radio" value="5" name="<?php echo field( $row['name'] ) ?>" /> 5
    <i><?php echo $LANG['survey_excellent'] ?></i>
    </div>
  </td>
</tr>
<?php /************************************************************/
    }
/********************************************************** PHP */?>
</table>
<br /><br />
<b><?php echo $LANG['survey_comments'] ?></b><br />
<textarea name="comments" rows="8" cols="45"></textarea>
<br /><br />
<input type="submit" value="<?php echo $LANG['survey_submit'] ?>" />
</div>
</form>
<?php /************************************************************/
  }
}
if( trim( $data['header'] ) == "" )
{
/********************************************************** PHP */?>
<?php 
include "./include/footer.php";
?>
<?php /************************************************************/
}
else
  eval( "?> {$data['footer']} <?php" );
/********************************************************** PHP */?>
