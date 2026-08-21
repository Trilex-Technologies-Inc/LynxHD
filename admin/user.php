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

$HD_CURPAGE = $HD_URL_USER;

if( $_SESSION['login_type'] == $LOGIN_INVALID )
  Header( "Location: {$HD_URL_LOGIN}?redirect=" . urlencode( $HD_CURPAGE ) );

$options = array( "email", "url", "title" );
$data = get_options( $options );

$global_priv = get_row_count( "SELECT COUNT(*) FROM {$pre}privilege WHERE ( user_id = '{$_SESSION['user']['id']}' && dept_id = '0' && admin = '1' )" );

if( $_POST['cmd'] == "add" )
{
  if( $global_priv )
  {
    if( trim( $_POST['email'] ?? '' ) != "" && trim( $_POST['name'] ?? '' ) != "" )
    {
      if( !get_row_count( "SELECT COUNT(*) FROM {$pre}user WHERE ( email = '{$_POST['email']}' )" ) )
      {
        $pass = "";
        srand( time( ) );
        for( $i = 0; $i < 8; $i++ )
          $pass .= chr( ord( 'a' ) + rand( 0, 25 ) );

        mysql_query( "INSERT INTO {$pre}user ( name, email, sms, signature, password, date, pwkey ) VALUES ( '{$_POST['name']}', '{$_POST['email']}', '', '', '" . crypt( $pass, $ENCRYPT_KEY ) . "', '" . time( ) . "', '' )" );

        $data = get_options( array( "email", "title", "url", "emailheader", "emailfooter" ) );

        mail( $_POST['email'], "New Help Desk Account Created", 
              "{$data['title']}\n" .
              "------------------------------\n\n" .
              "{$_POST['name']},\n\n" .
              "Your help desk account has been created.  Your login information is as follows:\n\n" .
              "Login Email: {$_POST['email']}\n" .
              "Login Password: $pass\n\n" .
              "Please change your password by logging into the help desk and selecting 'Edit Your Profile\n" .
              "and options.\n\n" .
              "You can login by going to: {$PATH_TO_HELPDESK}{$HD_URL_LOGIN}",
              "From: {$data['email']}" );

        $msg = "<div class=\"clean-gray\">User has been created successfully.  An email has been sent to '<b>{$_POST['email']}</b>' with information reguarding the new account.  The initial password for this user is '<b>$pass</b>'.  The user can change this by editing his/her profile after logging in.</div><br />";
      }
      else
        $msg = "<div class=\"errorbox\">A user with that email address already exists.</div><br />";
    }
  }
}
else if( $_GET['cmd'] == "del" && $global_priv )
{
  mysql_query( "DELETE FROM {$pre}user WHERE ( id = '{$_GET['id']}' )" );
  mysql_query( "DELETE FROM {$pre}privilege WHERE ( user_id = '{$_GET['id']}' )" );
}

if( !isset($_GET['tickets']) || $_GET['tickets'] <= 0 || $_GET['tickets'] > 100 )
  $_GET['tickets'] = 20;

include "./include/header.php";
/********************************************************** PHP */?>
<div class="d-sm-flex align-items-center justify-content-between mb-4">
  <div><h1 class="h3 mb-1 text-gray-800">User management</h1><p class="mb-0 text-gray-600">Create support accounts and review team access.</p></div>
  <?php if (($_GET['cmd'] ?? '') == 'view'): ?><a class="btn btn-light btn-sm shadow-sm mt-3 mt-sm-0" href="<?php echo field($HD_CURPAGE) ?>"><i class="fas fa-arrow-left fa-sm mr-1"></i> All users</a><?php elseif ($global_priv): ?><a class="btn btn-primary btn-sm shadow-sm mt-3 mt-sm-0" href="#create-user"><i class="fas fa-user-plus fa-sm mr-1"></i> Create user</a><?php endif; ?>
</div>
<?php echo $msg ?>
<?php /************************************************************/
if( $global_priv )
{
/********************************************************** PHP */?>
<?php /************************************************************/
}

if( $_GET['cmd'] != "view" )
{
  $res = mysql_query( "SELECT * FROM {$pre}user" );
  $user_count = mysql_num_rows($res);
  ?>
  <div class="row mb-1"><div class="col-md-6 col-xl-3 mb-4"><div class="card border-left-primary shadow-sm h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Team members</div><div class="h4 mb-0 font-weight-bold text-gray-800"><?php echo number_format($user_count) ?></div></div><div class="col-auto"><i class="fas fa-users fa-2x text-gray-300"></i></div></div></div></div></div></div>
  <div class="card shadow-sm mb-4 user-list"><div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between"><div><h2 class="h6 m-0 font-weight-bold text-primary">Support team</h2><small class="text-muted">Select a user to view their activity and department access.</small></div></div>
  <div class="table-responsive"><table class="table table-hover mb-0 user-table"><thead><tr><th>User</th><th>Email</th><th>Member since</th><th class="text-right">Actions</th></tr></thead><tbody>
  <?php
  if( $user_count ) while( $row = mysql_fetch_array( $res ) )
  {
    $view_url = $HD_CURPAGE . '?cmd=view&id=' . (int)$row['id'];
    $delete_url = $HD_CURPAGE . '?cmd=del&id=' . (int)$row['id'];
    echo '<tr><td><div class="user-name"><span class="user-initial">' . field(strtoupper(substr($row['name'], 0, 1))) . '</span><a class="font-weight-bold" href="' . field($view_url) . '">' . field($row['name']) . '</a></div></td>';
    echo '<td><a href="mailto:' . field($row['email']) . '">' . field($row['email']) . '</a></td>';
    echo '<td class="text-nowrap">' . date('M j, Y', $row['date']) . '</td><td class="text-right text-nowrap"><a class="btn btn-sm btn-light" href="' . field($view_url) . '"><i class="fas fa-eye mr-1"></i> View</a>';
    if( $global_priv && !$row['admin'] && $row['id'] != $_SESSION['user']['id'] ) echo ' <a class="btn btn-sm btn-outline-danger" href="' . field($delete_url) . '" onclick="return window.confirm(\'Delete this user and their access privileges?\');"><i class="fas fa-trash"></i><span class="sr-only"> Delete ' . field($row['name']) . '</span></a>';
    echo '</td></tr>';
  }
  else echo '<tr><td colspan="4"><div class="user-empty"><i class="fas fa-users"></i><h3>No users yet</h3><p>Create the first support account to get started.</p></div></td></tr>';
  ?>
  </tbody></table></div></div>
  <?php if ($global_priv): ?>
  <div class="card shadow-sm mb-4" id="create-user"><div class="card-header py-3"><h2 class="h6 m-0 font-weight-bold text-primary"><i class="fas fa-user-plus mr-2"></i>Create user</h2></div><div class="card-body"><div class="alert alert-light border small">A secure password is generated automatically and included in the account email.</div><form action="<?php echo field($HD_CURPAGE) ?>#create-user" method="post" class="user-create-form"><input type="hidden" name="cmd" value="add"><div class="form-row"><div class="form-group col-md-5"><label for="user-name">Name</label><input class="form-control" id="user-name" type="text" name="name" required value="<?php echo field($_POST['name'] ?? '') ?>"></div><div class="form-group col-md-5"><label for="user-email">Email address</label><input class="form-control" id="user-email" type="email" name="email" required value="<?php echo field($_POST['email'] ?? '') ?>"></div><div class="form-group col-md-2 d-flex align-items-end"><button class="btn btn-primary btn-block" type="submit">Create</button></div></div></form></div></div>
  <?php endif; ?>
  <?php
}
else if( $_GET['cmd'] == "view" )
{
  $res = mysql_query( "SELECT * FROM {$pre}user WHERE ( id = '{$_GET['id']}' )" );
  $row = mysql_fetch_array( $res );
  if (!$row) { echo '<div class="alert alert-warning">That user could not be found.</div>'; }
  else {
    $res_posts = mysql_query( "SELECT COUNT(id) FROM {$pre}post WHERE ( user_id = '{$row['id']}' )" );
    $row_posts = mysql_fetch_array( $res_posts );
    ?>
    <div class="row"><div class="col-lg-5 mb-4"><div class="card shadow-sm h-100 user-profile-card"><div class="card-body text-center"><span class="user-avatar-lg"><?php echo field(strtoupper(substr($row['name'], 0, 1))) ?></span><h2 class="h4 text-gray-800 mt-3 mb-1"><?php echo field($row['name']) ?></h2><a href="mailto:<?php echo field($row['email']) ?>"><?php echo field($row['email']) ?></a><hr><div class="row text-left"><div class="col-6"><div class="text-xs text-uppercase font-weight-bold text-gray-500">Joined</div><div><?php echo date('M j, Y', $row['date']) ?></div></div><div class="col-6"><div class="text-xs text-uppercase font-weight-bold text-gray-500">Last login</div><div><?php echo $row['lastlogin'] ? date('M j, Y g:i a', $row['lastlogin']) : 'Never' ?></div></div></div></div></div></div><div class="col-lg-7 mb-4"><div class="card shadow-sm h-100"><div class="card-header py-3"><h2 class="h6 m-0 font-weight-bold text-primary">Account activity</h2></div><div class="card-body"><div class="user-stat"><span class="user-stat-icon bg-primary text-white"><i class="fas fa-comment"></i></span><div><div class="text-xs text-uppercase font-weight-bold text-gray-500">Total posts</div><div class="h4 mb-0 text-gray-800 font-weight-bold"><?php echo number_format($row_posts[0]) ?></div></div></div><hr><h3 class="h6 text-gray-800 font-weight-bold">Department access</h3><div class="user-departments"><?php $res_dept = mysql_query( "SELECT dept.name, priv.admin FROM {$pre}privilege AS priv, {$pre}dept AS dept WHERE ( priv.dept_id = dept.id && priv.user_id = '{$row['id']}' )" ); $has_departments = false; while( $row_dept = mysql_fetch_array( $res_dept ) ) { $has_departments = true; echo '<span class="badge badge-light border">' . field($row_dept[0]) . ($row_dept['admin'] ? ' <strong class="text-primary">Admin</strong>' : '') . '</span>'; } if (!$has_departments) echo '<span class="text-muted small">No department access assigned.</span>'; ?></div></div></div></div></div>
    <?php if( $global_priv ) { ?>
    <div class="card shadow-sm mb-4"><div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between"><h2 class="h6 m-0 font-weight-bold text-primary">Recent posts</h2><form action="<?php echo field($HD_CURPAGE) ?>" method="get" class="form-inline mt-2 mt-sm-0"><input type="hidden" name="cmd" value="view"><input type="hidden" name="id" value="<?php echo (int)$row['id'] ?>"><label class="small font-weight-bold mr-2 mb-0" for="post-limit">Show</label><input class="form-control form-control-sm mr-2" id="post-limit" type="number" name="tickets" min="1" max="100" value="<?php echo (int)$_GET['tickets'] ?>"><button class="btn btn-sm btn-light" type="submit">Update</button></form></div><div class="table-responsive"><table class="table table-hover mb-0 user-posts-table"><thead><tr><th>Ticket</th><th>Subject</th><th>Last activity</th></tr></thead><tbody><?php $res_posts = mysql_query( "SELECT DISTINCT( ticket.id ), ticket.* FROM {$pre}ticket AS ticket, {$pre}post AS post WHERE ( post.user_id = '{$row['id']}' && post.ticket_id = ticket.id ) ORDER BY ticket.lastactivity DESC LIMIT {$_GET['tickets']}" ); $has_posts = false; while( $row_posts = mysql_fetch_array( $res_posts ) ) { $has_posts = true; $ticket_url = $HD_URL_ADMINVIEW . '?cmd=view&id=' . $row_posts['ticket_id']; echo '<tr><td><a class="font-weight-bold" href="' . field($ticket_url) . '">' . field($row_posts['ticket_id']) . '</a></td><td><a href="' . field($ticket_url) . '">' . field($row_posts['subject']) . '</a></td><td class="text-nowrap">' . date('M j, Y g:i a', $row_posts['lastactivity']) . '</td></tr>'; } if (!$has_posts) echo '<tr><td colspan="3" class="text-center text-muted py-4">No posts to show.</td></tr>'; ?></tbody></table></div></div>
    <?php } ?>
    <?php
  }
}
/********************************************************** PHP */?>
<?php /************************************************************/
include "./include/footer.php";
/********************************************************** PHP */?>
