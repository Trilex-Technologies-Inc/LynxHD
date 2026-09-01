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

if( $_SESSION['login_type'] == $LOGIN_INVALID )
  Header( "Location: {$HD_URL_LOGIN}?redirect=" . urlencode( $HD_CURPAGE ) );

$global_priv = get_row_count( "SELECT COUNT(*) FROM {$pre}privilege WHERE ( user_id = '{$_SESSION['user']['id']}' && dept_id = '0' && admin = '1' )" );
if( !$global_priv )
  Header( "Location: $HD_URL_BROWSE" );

if( $_POST['cmd'] == "add" )
{
  if( trim( $_POST['email'] ?? '' ) != "" )
  {
    if( !get_row_count( "SELECT COUNT(*) FROM {$pre}pop WHERE ( email = '{$_POST['email']}' )" ) )
      mysql_query( "INSERT INTO {$pre}pop ( email, dept_id, del ) VALUES ( '{$_POST['email']}', '{$_POST['department']}', '1' )" );
    else
      $msg = "<div class=\"errorbox\">An email processor with that address already exists.</div><br />";
  }
}
else if( $_POST['cmd'] == "update" )
{
  $delete = (($_POST['del'] ?? '') == "on") ? 1 : 0;

  if( trim( $_POST['password'] ?? '' ) != "" )
    $password = ", password = '{$_POST['password']}'";
  else
    $password = "";

  mysql_query( "UPDATE {$pre}pop SET server = '{$_POST['server']}', port = '{$_POST['port']}', username = '{$_POST['username']}', del = '$delete' $password WHERE ( id = '{$_POST['id']}' )" );
  echo mysql_error( );
}   
else if( $_GET['cmd'] == "del" )
  mysql_query( "DELETE FROM {$pre}pop WHERE ( id = '{$_GET['id']}' )" );
else if( $_GET['cmd'] == "process" )
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
  echo "<option value=\"{$row_dept['id']}\">" . field( $row_dept['name'] ) . "</option>";
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
<section class="card shadow-sm mb-4"><div class="card-header d-flex align-items-center justify-content-between"><div><h2 class="h6 mb-1"><?php echo field($row['email']) ?></h2><small class="text-muted"><?php echo field($row['name']) ?></small></div><a class="btn btn-sm btn-outline-danger" href="javascript:if(confirm('Are you sure you want to remove this email processor?')) window.location.href = '<?php echo $HD_CURPAGE ?>?cmd=del&id=<?php echo $row['id'] ?>'">Delete</a></div><div class="card-body">
  <p class="text-muted">POP3 settings may be left blank when using the forwarding method described in the manual.</p>
  <form action="<?php echo $HD_CURPAGE ?>" method="post">
  <input type="hidden" name="cmd" value="update" />
  <input type="hidden" name="id" value="<?php echo $row['id'] ?>" />
  <div class="form-row"><div class="form-group col-md-8"><label>Server</label><input class="form-control" type="text" name="server" value="<?php echo field($row['server']) ?>"></div><div class="form-group col-md-4"><label>Port</label><input class="form-control" type="number" name="port" value="<?php echo field($row['port']) ?>"></div></div>
  <div class="form-row"><div class="form-group col-md-6"><label>Username</label><input class="form-control" type="text" name="username" value="<?php echo field($row['username']) ?>"></div><div class="form-group col-md-6"><label>Password</label><input class="form-control" type="password" name="password" placeholder="Keep saved password"></div></div>
  <div class="custom-control custom-checkbox mb-3"><input class="custom-control-input" id="delete-mail-<?php echo (int)$row['id'] ?>" type="checkbox" name="del" <?php if($row['del']) echo 'checked' ?>><label class="custom-control-label" for="delete-mail-<?php echo (int)$row['id'] ?>">Delete emails from server after creating tickets</label></div>
  <button type="submit" class="btn btn-primary">Update</button>
  </form>
</div></section>
<?php /************************************************************/
}

/********************************************************** PHP */?>
<?php /************************************************************/
include "./include/footer.php";
/********************************************************** PHP */?>
