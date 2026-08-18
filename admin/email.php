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

$HD_CURPAGE = $HD_URL_EMAIL;

if( $_SESSION[login_type] == $LOGIN_INVALID )
  Header( "Location: {$HD_URL_LOGIN}?redirect=" . urlencode( $HD_CURPAGE ) );

$global_priv = get_row_count( "SELECT COUNT(*) FROM {$pre}privilege WHERE ( user_id = '{$_SESSION[user][id]}' && dept_id = '0' && admin = '1' )" );
if( !$global_priv )
  Header( "Location: $HD_URL_BROWSE" );

if( $_POST[cmd] == "add" )
{
  if( trim( $_POST[email] ) != "" )
  {
    if( !get_row_count( "SELECT COUNT(*) FROM {$pre}pop WHERE ( email = '{$_POST[email]}' )" ) )
      mysql_query( "INSERT INTO {$pre}pop ( email, dept_id, del ) VALUES ( '{$_POST[email]}', '{$_POST[department]}', '1' )" );
    else
      $msg = "<div class=\"errorbox\">An email processor with that address already exists.</div><br />";
  }
}
else if( $_POST[cmd] == "update" )
{
  $delete = ($_POST[del] == "on") ? 1 : 0;

  if( trim( $_POST[password] ) != "" )
    $password = ", password = '{$_POST[password]}'";
  else
    $password = "";

  mysql_query( "UPDATE {$pre}pop SET server = '{$_POST[server]}', port = '{$_POST[port]}', username = '{$_POST[username]}', del = '$delete' $password WHERE ( id = '{$_POST[id]}' )" );
  echo mysql_error( );
}   
else if( $_GET[cmd] == "del" )
  mysql_query( "DELETE FROM {$pre}pop WHERE ( id = '{$_GET[id]}' )" );
else if( $_GET[cmd] == "process" )
{
  include "email-pop.php";

  if( count( $error ) )
  {
    $msg = "<div class=\"successbox\">";
    for( $i = 0; $i < count( $error ); $i++ )
      $msg .= "* {$error[$i]}<br />";

    $msg .= "</div><br />";
  }      
}

include "./include/header.php";
/********************************************************** PHP */?>
<div class="title"><?php echo $script_name ?> Email Processing</div><br /><?php echo $msg ?>
<?php /************************************************************/
$res = mysql_query( "SELECT pop.*, dept.name FROM {$pre}pop AS pop, {$pre}dept AS dept WHERE ( dept.id = pop.dept_id )" );
if( mysql_num_rows( $res ) )
{
/********************************************************** PHP */?>

<div class="buttons">
    <a href="<?php echo $HD_CURPAGE ?>?cmd=process" class="positive">
        <img src="./images/ticket-fetch.png" alt=""/> 
        Process Emails into Tickets
    </a>
</div>
<br />
<?php /************************************************************/
}
/********************************************************** PHP */?>
	<div class="clean-gray">
	You can specify POP3 settings (if using POP3 method) after creating.
</div>
<br />
<div id="container">
	<h1>New Email Processor</h1>
<form class="wufoo" action="<?php echo $HD_CURPAGE ?>" method="post">
<input type="hidden" name="cmd" value="add" />
<ul>
      <li>
	   <label class="desc">Email:&nbsp;</label>
    <div>
    	<input class="field text medium" type="text" name="email" size="30" />	
	</div>
</li>
      <li>
	   <label class="desc">Department:&nbsp;</label>
    <span>
        <select class="field select" name="department">
<?php /************************************************************/
$res_dept = mysql_query( "SELECT id, name FROM {$pre}dept ORDER BY sortnum" );
while( $row_dept = mysql_fetch_array( $res_dept ) )
  echo "<option value=\"{$row_dept[id]}\">" . field( $row_dept[name] ) . "</option>";
/********************************************************** PHP */?>
        </select>
    </span>
</li>
<div class="buttons">
    <button type="submit" class="positive">Create</button>
</div>
</form>
</div>

<?php /************************************************************/
while( $row = mysql_fetch_array( $res ) )
{
/********************************************************** PHP */?>
<table width="100%" bgcolor="#3c91c7" border="0" cellspacing="1" cellpadding="2">
<tr><td bgcolor="#3c91c7">
<h1>
<a href="javascript:if(confirm('Are you sure you want to remove this email processor?')) window.location.href = '<?php echo $HD_CURPAGE ?>?cmd=del&id=<?php echo $row[id] ?>'"><img src="./images/ticket-delete.png" border="0" align="absmiddle" alt="Delete" /></a> <?php echo $row[email] ?> [<?php echo field( $row[name] ) ?>]
</h1>
</td></tr>
<tr><td bgcolor="#FFFFFF">
<table width="100%" border="0" cellspacing="0" cellpadding="5"><tr><td>
  <table bgcolor="#FFFFFF" width="100%" border="0" cellspacing="0" cellpadding="4">
  <div class="clean-gray">POP3 settings (you may leave these blank if you are using the forwarding method described in the manual):</div>
  <tr><td><img src="./images/blank.gif" height="2" /></td></tr>
  </table>
  <table border="0" cellspacing="5" cellpadding="0">
  <form action="<?php echo $HD_CURPAGE ?>" method="post">
  <input type="hidden" name="cmd" value="update" />
  <input type="hidden" name="id" value="<?php echo $row[id] ?>" />
  <tr>
    <td align="right"><div class="smallinfo">Server:</div></td><td><div class="smallinfo"><input type="text" name="server" value="<?php echo field( $row[server] ) ?>" />&nbsp;&nbsp;Port: <input type="text" name="port" size="4" value="<?php echo field( $row[port] ) ?>" /></div></td>
  </tr>
  <tr>
    <td align="right"><div class="smallinfo">Username:</div></td><td><div class="smallinfo"><input type="text" name="username" value="<?php echo field( $row[username] ) ?>" />&nbsp;&nbsp;Password: <input type="password" name="password" size="12"  /> (Leave blank to keep password)</div></td>
  </tr>
  <tr><td></td><td><div class="smallinfo"><input type="checkbox" name="del" <?php if( $row[del] ) echo "checked" ?> /> Delete emails from server after creating tickets</div></td></tr>
  <tr><td></td><td><img src="./images/blank.gif" width="1" height="8"><div class="buttons">
    <button type="submit" class="positive">Update</button>
    </div></td></tr>
  </form>
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