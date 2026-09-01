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

$HD_CURPAGE = $HD_URL_TICKET_LOST;

$options = array( "header", "footer", "logo", "title", "background", "outsidebackground", "border", "topbar", "menu", "styles", "email", "url", "emailheader", "emailfooter", "email_ticket_lookup", "email_ticket_lookup_subject" );
$data = get_options( $options );

$success = 0;

if( $_GET['cmd'] == "lost" && isset( $_GET['email'] ) )
{
  $res = mysql_query( "SELECT subject, ticket_id FROM {$pre}ticket WHERE ( email = '{$_GET['email']}' ) ORDER BY date DESC" );
  if( mysql_num_rows( $res ) )
  {
    eval( "\$email_subject = \"{$data['email_ticket_lookup_subject']}\";" );
    eval( "\$email_message = \"{$data['email_ticket_lookup']}\";" );

    while( $row = mysql_fetch_array( $res ) )
    {
      $email_message .= "{$LANG['field_ticket_id']} {$row['ticket_id']}\n";
      $email_message .= "{$LANG['field_subject']} {$row['subject']}\n";
      $email_message .= $PATH_TO_HELPDESK . $HD_URL_TICKET_VIEW . "?cmd=view&id={$row['ticket_id']}&email={$_GET['email']}\n\n";
    }

    hd_mail( $_GET['email'], $email_subject, $email_message, "From: {$data['email']}" );

    $success = 1;
  }
  else
    $msg = "<div class=\"normal\"><div class=\"normal\"><font color=\"#FF0000\">{$LANG['no_ticket_address']}</font></div><br />";
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
<br /><?php echo $msg ?>
<?php /************************************************************/
if( $success )
  echo "<div class=\"normal\">{$LANG['ticket_info_sent']}</div>";
else
{
/********************************************************** PHP */?>
<div class="normal">
<?php echo "<div class=\"clean-gray\">{$LANG['email_address_used']}</div>" ?>
<br /><br />
<div id="container">
<h1><?php echo $LANG['retrieve_lost_ticket'] ?></h1>
<form class="wufoo" action="<?php echo $HD_CURPAGE ?>" method="get">
<input type="hidden" name="cmd" value="lost" />
<ul>
<li>
	   <label class="desc">Email:</label>
    <div>   
    <input class="field text medium" type="text" name="email" size="30" value="<?php echo field( $_GET['email'] ?? '' ) ?>" /> </div>
</li>
<div class="buttons">
    <button type="submit" class="positive">Lookup</button>
</div>
</form>
</div>
<?php /************************************************************/
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
