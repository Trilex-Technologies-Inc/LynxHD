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

$HD_CURPAGE = $HD_URL_MESSAGES;

if( $_SESSION['login_type'] == $LOGIN_INVALID )
  Header( "Location: {$HD_URL_LOGIN}?redirect=" . urlencode( $HD_CURPAGE ) );

$selected_users = (isset($_POST['users']) && is_array($_POST['users'])) ? $_POST['users'] : array();
$selected_departments = (isset($_POST['dept']) && is_array($_POST['dept'])) ? $_POST['dept'] : array();
$data = get_options( array( 'tags' ) );

if( $_POST['cmd'] == "action" )
{
  if( $_POST['action'] == "delete" )
  {
    $query = "";
      
    reset( $_POST );

    while( list( $key, $val ) = each( $_POST ) )
    {
      if( is_int( $key ) && $val == "on" )
      {
        mysql_query( "DELETE FROM {$pre}message WHERE ( ticket_id = '$key' && user_id = '{$_SESSION['user']['id']}' )" );
        if( !get_row_count( "SELECT COUNT(*) FROM {$pre}message WHERE ( ticket_id = '$key' )" ) )
        {
          mysql_query( "DELETE FROM {$pre}ticket WHERE ( id = '$key' )" );
          mysql_query( "DELETE FROM {$pre}post WHERE ( ticket_id = '$key' )" );
          mysql_query( "DELETE FROM {$pre}message WHERE ( ticket_id = '$key' )" );

          if( is_dir( "{$HD_TICKET_FILES}/{$key}" ) )
            system( "rm -rf {$HD_TICKET_FILES}/{$key}" );
        }
      }
    }
  }
  else if( ($_POST['action'] == "read") || ($_POST['action'] == "unread") )
  {
    $viewed = ($_POST['action'] == "read");

    while( list( $key, $val ) = each( $_POST ) )
    {
      if( is_int( $key ) && $val == "on" )
        mysql_query( "UPDATE {$pre}message SET viewed = '$viewed' WHERE ( ticket_id = '$key' && user_id = '{$_SESSION['user']['id']}' )" );
    }
  }
}

if( $_POST['cmd'] == "new" )
{
  $_POST['users'] = $selected_users;
  $_POST['dept'] = $selected_departments;

  if( ((count( $_POST['users'] ) + count( $_POST['dept'] )) > 0) && (trim( $_POST['subject'] ?? '' ) != "") && (trim( $_POST['message'] ?? '' ) != "") )
  {
    for( $i = 0; $i < count( $_POST['dept'] ); $i++ )
    {
      $res = mysql_query( "SELECT user_id FROM {$pre}privilege WHERE ( dept_id = '{$_POST['dept'][$i]}' || dept_id = '0' )" );
      while( $row = mysql_fetch_array( $res ) )
      {
        if( $row['user_id'] != $_SESSION['user']['id'] )
        {
          $found = 0;

          for( $j = 0; $j < count( $_POST['users'] ); $j++ )
            if( $_POST['users'][$j] == $row['user_id'] )
              $found = 1;

          if( !$found )
            $_POST['users'][] = $row['user_id'];
        }
      }
    }

    $ticket = "M" . strtoupper( base_convert( time( ), 10, 16 ) );
    
    mysql_query( "INSERT INTO {$pre}ticket ( ticket_id, dept_id, subject, date, status, notify, priority, lastactivity ) VALUES ( '$ticket', '{$_SESSION['user']['id']}', '{$_POST['subject']}', '" . time( ) . "', '$HD_STATUS_OPEN', '" . (($_POST['notify'] ?? '') == "on" ? "1" : "0") . "', '{$_POST['priority']}', '" . time( ) . "' )" );

    $id = mysql_insert_id( );

    mysql_query( "INSERT INTO {$pre}post ( ticket_id, user_id, date, subject, message ) VALUES ( '$id', '{$_SESSION['user']['id']}', '" . time( ) . "', '{$_POST['subject']}', '{$_POST['message']}' )" );

    for( $i = 0; $i < count( $_POST['users'] ); $i++ )
      mysql_query( "INSERT INTO {$pre}message ( ticket_id, user_id, viewed ) VALUES ( '$id', '{$_POST['users'][$i]}', '0' )" );

    mysql_query( "INSERT INTO {$pre}message( ticket_id, user_id, viewed ) VALUES ( '$id', '{$_SESSION['user']['id']}', '1' )" );

    Header( "Location: $HD_CURPAGE" );
    exit;
  }
  else
    $msg = "<div class=\"errorbox\">All fields are required to send a message.</div><br />";
}

$_GET['results'] = 10;

$rows_query = "SELECT COUNT(*) FROM {$pre}message WHERE ( user_id = '{$_SESSION['user']['id']}' )";

$query = "SELECT ticket.*, message.viewed FROM {$pre}message AS message, {$pre}ticket AS ticket WHERE ( message.user_id = '{$_SESSION['user']['id']}' && ticket.id = message.ticket_id ) ORDER BY lastactivity DESC";

$results = get_row_count( $rows_query );
$unread_messages = get_row_count( "SELECT COUNT(*) FROM {$pre}message WHERE ( user_id = '{$_SESSION['user']['id']}' && viewed = '0' )" );

if( !isset( $_GET['offset'] ) || $_GET['offset'] < 0 || $_GET['offset'] >= $results )
  $_GET['offset'] = 0;

$query .= " LIMIT {$_GET['offset']},{$_GET['results']}";

include "./include/header.php";
/********************************************************** PHP */?>
<div class="d-sm-flex align-items-center justify-content-between mb-4">
  <div><h1 class="h3 mb-1 text-gray-800">Message center</h1><p class="mb-0 text-gray-600">Private conversations with your support team.</p></div>
  <a class="btn btn-primary btn-sm shadow-sm mt-3 mt-sm-0" href="#new-message"><i class="fas fa-pen fa-sm mr-1"></i> New message</a>
</div>
<?php echo $msg ?>

<div class="row mb-1">
  <div class="col-md-6 col-xl-3 mb-4"><div class="card border-left-primary shadow-sm h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-primary text-uppercase mb-1">All messages</div><div class="h4 mb-0 font-weight-bold text-gray-800"><?php echo number_format($results) ?></div></div><div class="col-auto"><i class="fas fa-envelope fa-2x text-gray-300"></i></div></div></div></div></div>
  <div class="col-md-6 col-xl-3 mb-4"><div class="card border-left-warning shadow-sm h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Unread</div><div class="h4 mb-0 font-weight-bold text-gray-800"><?php echo number_format($unread_messages) ?></div></div><div class="col-auto"><i class="fas fa-envelope-open-text fa-2x text-gray-300"></i></div></div></div></div></div>
</div>

<form name="tickets" method="post" id="message-list-form"><input type="hidden" name="cmd" value="action">
<div class="card shadow-sm mb-4 message-list">
  <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between"><div><h2 class="h6 m-0 font-weight-bold text-primary">Inbox</h2><small class="text-muted"><?php echo number_format($results) ?> conversation<?php echo $results == 1 ? '' : 's' ?></small></div><div class="message-legend text-muted"><span><img src="./images/mail-newresponse.png" alt=""> Unread</span><span><img src="./images/mail.png" alt=""> Read</span></div></div>
  <div class="table-responsive"><table class="table table-hover mb-0 message-table"><thead><tr><th class="message-select"><input type="checkbox" id="select-all-messages" aria-label="Select all messages"></th><th>Message</th><th>Subject</th><th class="text-center">Posts</th><th>Activity</th><th>Last post</th></tr></thead><tbody>
<?php /************************************************************/
$res = mysql_query( $query );
$visible_rows = 0;
while( $row = mysql_fetch_array( $res ) )
{
  $visible_rows++;
  $res_post_user = mysql_query( "SELECT user_id, private FROM {$pre}post WHERE ( ticket_id = '{$row['id']}' ) ORDER BY date DESC LIMIT 1" );
  $row_post_user = mysql_fetch_array( $res_post_user ) ?: array( 'user_id' => 0, 'private' => 0 );

  $res_staff_user = mysql_query( "SELECT name FROM {$pre}user WHERE ( id = '{$row_post_user['user_id']}' )" );
  $row_staff_user = mysql_fetch_array( $res_staff_user );
  $last_user_name = $row_staff_user ? field($row_staff_user['name']) : "Unknown user";

  if( $row_post_user['user_id'] == $_SESSION['user']['id'] )
    $user_info = "<strong>" . $last_user_name . "</strong>";
  else
    $user_info = $last_user_name;
  
  $res_post = mysql_query( "SELECT COUNT(*) FROM {$pre}post WHERE ( ticket_id = '{$row['id']}' )" );
  $row_post = mysql_fetch_array( $res_post ) ?: array( 0 => 0 );

  echo $row['viewed'] ? "<tr>" : "<tr class=\"message-unread\">";
  
  if( $row['viewed'] )
    $image = "./images/mail.png";
  else
    $image = "./images/mail-newresponse.png";

  echo "<td><input class=\"message-checkbox\" type=\"checkbox\" name=\"{$row['id']}\" aria-label=\"Select message " . field($row['ticket_id']) . "\"></td>";
  echo "<td><a class=\"font-weight-bold text-nowrap\" href=\"{$HD_URL_ADMINVIEW}?cmd=view&id={$row['ticket_id']}\">" . field($row['ticket_id']) . "</a></td>";
  echo "<td class=\"message-subject\"><img src=\"{$image}\" alt=\"\"> <a href=\"{$HD_URL_ADMINVIEW}?cmd=view&id={$row['ticket_id']}\">" . field( $row['subject'] ) . "</a>" . (!$row['viewed'] ? " <span class=\"badge badge-warning ml-1\">Unread</span>" : "") . "</td>";

  if( $row_post[0] <= 0 )
    $replies = "<span class=\"text-danger font-weight-bold\">0</span>";
  else
    $replies = $row_post[0];

  echo "<td class=\"text-center\">$replies</td>";

  $lastactivity = time( ) - $row['lastactivity'];
  if( $lastactivity > 86400 )
  {
    if( (int)($lastactivity / 86400 ) <= 1 )
      $lastactivity = "<span class=\"text-danger font-weight-bold\">" . (int)($lastactivity / 86400) . "d</span>";
    else
      $lastactivity = (int)($lastactivity / 86400) . "d";
  }
  else if( $lastactivity > 3600 )
    $lastactivity = "<span class=\"text-danger font-weight-bold\">" . (int)($lastactivity / 3600) . "h</span>";
  else
    $lastactivity = "<span class=\"text-danger font-weight-bold\">" . max(0, (int)($lastactivity / 60 )) . "m</span>";

  echo "<td class=\"text-nowrap\">$lastactivity</td>";
  echo "<td>$user_info</td>";

  echo "</tr>";
}
if (!$visible_rows) echo '<tr><td colspan="6"><div class="message-empty"><i class="far fa-envelope-open"></i><h3>Your inbox is empty</h3><p>Messages between you and your team will appear here.</p></div></td></tr>';
/********************************************************** PHP */?>
</tbody></table></div>
<?php $query_params = $_GET; $previous_url = $next_url = ''; if ($_GET['offset'] > 0) { $query_params['offset'] = max(0, $_GET['offset'] - $_GET['results']); $previous_url = $HD_CURPAGE . '?' . http_build_query($query_params); } if ($_GET['offset'] < ($results - $_GET['results'])) { $query_params['offset'] = $_GET['offset'] + $_GET['results']; $next_url = $HD_CURPAGE . '?' . http_build_query($query_params); } ?>
<div class="card-footer d-flex flex-wrap align-items-center justify-content-between"><div class="form-inline message-actions"><label class="mr-2" for="message-action">With selected</label><select class="form-control form-control-sm mr-2" id="message-action" name="action"><option value="read">Mark as read</option><option value="unread">Mark as unread</option><option value="delete">Delete</option></select><button class="btn btn-primary btn-sm" type="submit">Apply</button></div><nav class="mt-3 mt-md-0" aria-label="Message pages"><ul class="pagination pagination-sm mb-0"><li class="page-item <?php echo $previous_url ? '' : 'disabled' ?>"><a class="page-link" <?php echo $previous_url ? 'href="' . field($previous_url) . '"' : 'href="#" tabindex="-1" aria-disabled="true"' ?>><i class="fas fa-chevron-left mr-1"></i> Previous</a></li><li class="page-item disabled"><span class="page-link"><?php echo $results ? number_format($_GET['offset'] + 1) . '–' . number_format(min($_GET['offset'] + $_GET['results'], $results)) : '0' ?> of <?php echo number_format($results) ?></span></li><li class="page-item <?php echo $next_url ? '' : 'disabled' ?>"><a class="page-link" <?php echo $next_url ? 'href="' . field($next_url) . '"' : 'href="#" tabindex="-1" aria-disabled="true"' ?>>Next <i class="fas fa-chevron-right ml-1"></i></a></li></ul></nav></div>
</div></form>

<div class="card shadow-sm mb-4" id="new-message"><div class="card-header py-3"><h2 class="h6 m-0 font-weight-bold text-primary"><i class="fas fa-pen mr-2"></i>New message</h2></div><div class="card-body">
<form action="<?php echo field($HD_CURPAGE) ?>#new-message" method="post" class="message-compose"><input type="hidden" name="cmd" value="new">
  <div class="form-group"><label>Recipients <span class="text-danger" aria-hidden="true">*</span></label><p class="small text-muted mb-2">Choose individual users, entire departments, or both. Hold Ctrl/Cmd to select more than one.</p><div class="form-row"><div class="col-md-6 mb-3 mb-md-0"><label class="small" for="message-users">Users</label><select class="form-control" id="message-users" name="users[]" multiple size="6">
<?php /************************************************************/
$res = mysql_query( "SELECT id, name FROM {$pre}user ORDER BY name" );
while( $row = mysql_fetch_array( $res ) )
{
  if( $row['id'] != $_SESSION['user']['id'] )
    echo "<option value=\"" . (int)$row['id'] . "\"" . (in_array($row['id'], $selected_users) ? " selected" : "") . ">" . field( $row['name'] ) . "</option>\n";
}
/********************************************************** PHP */?>
</select></div><div class="col-md-6"><label class="small" for="message-departments">Departments</label><select class="form-control" id="message-departments" name="dept[]" multiple size="6">
<?php /************************************************************/
$res = mysql_query( "SELECT id, name FROM {$pre}dept WHERE ( id != '0' ) ORDER BY name" );
while( $row = mysql_fetch_array( $res ) )
  echo "<option value=\"" . (int)$row['id'] . "\"" . (in_array($row['id'], $selected_departments) ? " selected" : "") . ">" . field( $row['name'] ) . "</option>\n";
/********************************************************** PHP */?>
</select></div></div></div>
  <div class="form-group"><label for="message-subject">Subject <span class="text-danger" aria-hidden="true">*</span></label><input class="form-control" id="message-subject" type="text" name="subject" required maxlength="255" value="<?php echo field($_POST['subject'] ?? '') ?>"></div>
  <div class="form-group"><div class="d-flex justify-content-between"><label for="message-body">Message <span class="text-danger" aria-hidden="true">*</span></label><?php if ($data['tags']): ?><a class="small" href="<?php echo field($HD_URL_TICKET_TAGS) ?>" target="_blank" rel="noopener">Message tags <i class="fas fa-external-link-alt fa-xs"></i></a><?php endif; ?></div><textarea class="form-control" id="message-body" name="message" rows="8"><?php echo field($_POST['message'] ?? '') ?></textarea></div>
  <div class="d-flex flex-wrap align-items-center justify-content-between"><div class="custom-control custom-checkbox mb-3 mb-sm-0"><input class="custom-control-input" type="checkbox" id="message-notify" name="notify" <?php echo (isset($_POST['notify']) && $_POST['notify'] == 'on') ? 'checked' : '' ?>><label class="custom-control-label" for="message-notify">Notify recipients</label></div><div><button class="btn btn-light mr-2" type="reset">Clear</button><button class="btn btn-primary" type="submit"><i class="fas fa-paper-plane mr-1"></i> Send message</button></div></div>
</form></div></div>

<script>
(function () {
  var form = document.getElementById('message-list-form');
  var selectAll = document.getElementById('select-all-messages');
  if (!form || !selectAll) return;
  var boxes = Array.prototype.slice.call(form.querySelectorAll('.message-checkbox'));
  selectAll.addEventListener('change', function () { boxes.forEach(function (box) { box.checked = selectAll.checked; }); });
  boxes.forEach(function (box) { box.addEventListener('change', function () { selectAll.checked = boxes.length > 0 && boxes.every(function (item) { return item.checked; }); selectAll.indeterminate = !selectAll.checked && boxes.some(function (item) { return item.checked; }); }); });
  form.addEventListener('submit', function (event) { var selected = boxes.some(function (box) { return box.checked; }); var action = form.querySelector('[name="action"]'); if (!selected) { event.preventDefault(); window.alert('Select at least one message first.'); return; } if (action && action.value === 'delete' && !window.confirm('Delete the selected messages?')) event.preventDefault(); });
}());
</script>

<?php /************************************************************/
include "./include/footer.php";
/********************************************************** PHP */?>
