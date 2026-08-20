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

$HD_CURPAGE = $HD_URL_BROWSE;

if( $_SESSION['login_type'] == $LOGIN_INVALID )
  Header( "Location: {$HD_URL_LOGIN}?redirect=" . urlencode( $HD_CURPAGE ) );

$global_priv = get_row_count( "SELECT COUNT(*) FROM {$pre}privilege WHERE ( user_id = '{$_SESSION['user']['id']}' && dept_id = '0' )" );

if( $_POST['cmd'] == "action" )
{
  if( $_POST['action'] == "reply" )
  {
    $query = "";
      
    reset( $_POST );

    while( list( $key, $val ) = each( $_POST ) )
    {
      if( is_int( $key ) && $val == "on" )
        $query .= $key . ";";
    }

    Header( "Location: {$HD_URL_MASSREPLY}?tickets=$query" );
  }
  else if( $_POST['action'] == "survey" )
  {
    while( list( $key, $val ) = each( $_POST ) )
    {
      if( is_int( $key ) && $val == "on" )
        send_survey( $key );
    }
  }
  else if( $_POST['action'] == "flag" )
  {
    while( list( $key, $val ) = each( $_POST ) )
    {
      if( is_int( $key ) && $val == "on" )
      {
        $res_flag = mysql_query( "SELECT flag FROM {$pre}ticket WHERE ( id = '$key' )" );
        $row_flag = mysql_fetch_array( $res_flag );
        if( $row_flag['flag'] != -1 )
          mysql_query( "UPDATE {$pre}ticket SET flag = '-1' WHERE ( id = '$key' )" );
        else
          mysql_query( "UPDATE {$pre}ticket SET flag = '0' WHERE ( id = '$key' )" );
      }
    }
  }
  else
  {
    reset( $_POST );
        
    while( list( $key, $val ) = each( $_POST ) )
    {
      if( is_int( $key ) && $val == "on" )
      {
        if( $_POST['action'] != "delete" )
        {
          if( $_POST['action'] == "open" )
            $status = $HD_STATUS_OPEN;
          else if( $_POST['action'] == "close" )
            $status = $HD_STATUS_CLOSED;
          else if( $_POST['action'] == "hold" )
            $status = $HD_STATUS_HELD;
          
          mysql_query( "UPDATE {$pre}ticket SET status = '$status' WHERE ( id = '$key' )" );
        }
        else
        {
          if( @is_dir( "{$HD_TICKET_FILES}/{$key}" ) )
            system( "rm -rf {$HD_TICKET_FILES}/{$key}" );

          mysql_query( "DELETE FROM {$pre}ticket WHERE ( id = '$key' )" );
          mysql_query( "DELETE FROM {$pre}post WHERE ( ticket_id = '$key' )" );
        }
      }
    }
  }
}

$query = "";

// The browse page can be opened without query parameters.  Populate the
// filter values before they are used in SQL filters and the form below.
$browse_defaults = array(
  "search" => "",
  "lookin" => "subject",
  "priority" => "any",
  "department" => 0,
  "closed" => "",
  "mine" => "",
  "replies" => "",
  "results" => 20,
  "order" => "activity"
);

foreach( $browse_defaults as $key => $value )
  if( !isset( $_GET[$key] ) )
    $_GET[$key] = $value;

if( trim( $_GET['search'] ?? '' ) != "" )
{
  if( $_GET['lookin'] == "subject" )
    $query .= " && ticket.subject LIKE '%{$_GET['search']}%'";
  else if( $_GET['lookin'] == "message" )
    $query .= " && post.message LIKE '%{$_GET['search']}%'";
  else if( $_GET['lookin'] == "name" )
    $query .= " && ticket.name LIKE '%{$_GET['search']}%'";
  else if( $_GET['lookin'] == "email" )
    $query .= " && ticket.email LIKE '%{$_GET['search']}%'";
  else
  {
    $res = mysql_query( "SELECT text FROM {$pre}options WHERE ( name = '{$_GET['lookin']}' )" );
    $row = mysql_fetch_array( $res );
    $query .= " && ticket.custom LIKE '%{$row['text']}\n{$_GET['search']}%'";
  }
}

if( $_GET['priority'] == "low" )
  $query .= " && ticket.priority = '{$PRIORITY_LOW}'";
else if( $_GET['priority'] == "medium" )
  $query .= " && ticket.priority = '{$PRIORITY_MEDIUM}'";
else if( $_GET['priority'] == "high" )
  $query .= " && ticket.priority = '{$PRIORITY_HIGH}'";

if( $_GET['department'] != 0 )
  $query .= " && ticket.dept_id = '{$_GET['department']}'";
  
if( $_GET['closed'] != "on" )
  $query .= " && ticket.status != '$HD_STATUS_CLOSED'";

if( $_GET['mine'] == "on" )
  $query .= " && post.user_id = '{$_SESSION['user']['id']}'";

if( $_GET['replies'] == "on" )
  $query .= " && ticket.lastpost = '-1'";

if( $_GET['results'] < 1 || $_GET['results'] > 1000 )
  $_GET['results'] = 20;

$order = "";

if( $_GET['order'] == "activity" )
  $order .= "ticket.lastactivity DESC";
else if( $_GET['order'] == "date" )
  $order .= "ticket.date DESC";
else if( $_GET['order'] == "priority" )
  $order .= "ticket.priority DESC";
else if( $_GET['order'] == "activityrev" )
  $order .= "ticket.lastactivity ASC";
else if( $_GET['order'] == "daterev" )
  $order .= "ticket.date ASC";
else if( $_GET['order'] == "priorityrev" )
  $order .= "ticket.priority ASC";
else
  $order .= "ticket.lastactivity DESC";

$rows_query = "SELECT COUNT( DISTINCT( ticket.id ) ) FROM {$pre}ticket AS ticket, {$pre}post AS post, {$pre}privilege AS priv WHERE ( ticket.ticket_id NOT LIKE 'M%' && post.ticket_id = ticket.id && ((ticket.dept_id = priv.dept_id && priv.user_id = '{$_SESSION['user']['id']}') || (priv.dept_id = '0' && priv.user_id = '{$_SESSION['user']['id']}')) " . $query . " ) ORDER BY " . $order;

$query = "SELECT DISTINCT( ticket.id ), ticket.* FROM {$pre}ticket AS ticket, {$pre}post AS post, {$pre}privilege AS priv WHERE ( ticket.ticket_id NOT LIKE 'M%' && post.ticket_id = ticket.id && ((ticket.dept_id = priv.dept_id && priv.user_id = '{$_SESSION['user']['id']}') || (priv.dept_id = '0' && priv.user_id = '{$_SESSION['user']['id']}')) " . $query . " ) ORDER BY " . $order;

$results = get_row_count( $rows_query );

if( !isset( $_GET['offset'] ) || $_GET['offset'] < 0 || $_GET['offset'] >= $results )
  $_GET['offset'] = 0;

$query .= " LIMIT {$_GET['offset']},{$_GET['results']}";

mysql_query( "UPDATE {$pre}dept SET id = '0' WHERE ( name = 'Global (All Departments)' )" );

include "./include/header.php";
/********************************************************** PHP */?>
<div class="d-sm-flex align-items-center justify-content-between mb-4">
  <div>
    <h1 class="h3 mb-1 text-gray-800">Browse tickets</h1>
    <p class="mb-0 text-gray-600">Find, review, and manage support requests.</p>
  </div>
  <a class="btn btn-primary btn-sm shadow-sm mt-3 mt-sm-0" href="adminticket.php"><i class="fas fa-plus fa-sm mr-1"></i> New ticket</a>
</div>
<?php echo $msg ?>

<div class="card shadow-sm mb-4 browse-filters">
  <div class="card-header py-3 d-flex align-items-center"><i class="fas fa-filter text-primary mr-2"></i><h2 class="h6 m-0 font-weight-bold text-primary">Filter tickets</h2></div>
  <div class="card-body">
    <form action="<?php echo field( $HD_CURPAGE ) ?>" method="get">
      <div class="form-row">
        <div class="form-group col-lg-4 col-md-6"><label for="ticket-search">Search</label><input id="ticket-search" class="form-control" type="search" name="search" value="<?php echo field( $_GET['search'] ) ?>" placeholder="Subject, requester, or message"></div>
        <div class="form-group col-lg-2 col-md-6"><label for="ticket-lookin">Search in</label><select id="ticket-lookin" class="form-control" name="lookin">
          <option value="subject" <?php echo ($_GET['lookin'] == "subject") ? "selected" : "" ?>>Subject</option><option value="message" <?php echo ($_GET['lookin'] == "message") ? "selected" : "" ?>>Posts</option><option value="name" <?php echo ($_GET['lookin'] == "name") ? "selected" : "" ?>>Name</option><option value="email" <?php echo ($_GET['lookin'] == "email") ? "selected" : "" ?>>Email</option>
          <?php $res = mysql_query( "SELECT name, text FROM {$pre}options WHERE ( name LIKE 'custom%' )" ); while( $row = mysql_fetch_array( $res ) ) echo "<option value=\"" . field($row['name']) . "\" " . (($_GET['lookin'] == $row['name']) ? "selected" : "") . ">" . field( $row['text'] ) . "</option>\n"; ?>
        </select></div>
        <div class="form-group col-lg-2 col-md-4"><label for="ticket-priority">Priority</label><select id="ticket-priority" class="form-control" name="priority"><option value="any" <?php echo ($_GET['priority'] == "any") ? "selected" : "" ?>>Any priority</option><option value="low" <?php echo ($_GET['priority'] == "low") ? "selected" : "" ?>>Low</option><option value="medium" <?php echo ($_GET['priority'] == "medium") ? "selected" : "" ?>>Medium</option><option value="high" <?php echo ($_GET['priority'] == "high") ? "selected" : "" ?>>High</option></select></div>
        <div class="form-group col-lg-2 col-md-4"><label for="ticket-department">Department</label><select id="ticket-department" class="form-control" name="department">
          <?php if( $global_priv ) $res_dept = mysql_query( "SELECT name, id FROM {$pre}dept ORDER BY sortnum" ); else $res_dept = mysql_query( "SELECT dept.name, dept.id FROM {$pre}privilege AS priv, {$pre}dept AS dept WHERE ( priv.user_id = '{$_SESSION['user']['id']}' && priv.dept_id = dept.id )" ); while( $row_dept = mysql_fetch_array( $res_dept ) ) echo "<option value=\"" . (int)$row_dept['id'] . "\" " . (($row_dept['id'] == $_GET['department']) ? "selected" : "") . ">" . field($row_dept['name']) . "</option>\n"; ?>
        </select></div>
        <div class="form-group col-lg-2 col-md-4"><label for="ticket-results">Per page</label><input id="ticket-results" class="form-control" type="number" name="results" min="1" max="1000" value="<?php echo (int)$_GET['results'] ?>"></div>
      </div>
      <div class="form-row align-items-end">
        <div class="form-group col-xl-5 col-lg-6"><label for="ticket-order">Sort by</label><select id="ticket-order" class="form-control" name="order"><option value="activity" <?php echo ($_GET['order'] == "activity") ? "selected" : "" ?>>Recent activity — newest first</option><option value="date" <?php echo ($_GET['order'] == "date") ? "selected" : "" ?>>Ticket age — newest first</option><option value="priority" <?php echo ($_GET['order'] == "priority") ? "selected" : "" ?>>Priority — high to low</option><option value="activityrev" <?php echo ($_GET['order'] == "activityrev") ? "selected" : "" ?>>Recent activity — oldest first</option><option value="daterev" <?php echo ($_GET['order'] == "daterev") ? "selected" : "" ?>>Ticket age — oldest first</option><option value="priorityrev" <?php echo ($_GET['order'] == "priorityrev") ? "selected" : "" ?>>Priority — low to high</option></select></div>
        <div class="form-group col-xl-5 col-lg-6 browse-checks"><div class="custom-control custom-checkbox"><input class="custom-control-input" type="checkbox" id="replies" name="replies" <?php echo ($_GET['replies'] == "on") ? "checked" : "" ?>><label class="custom-control-label" for="replies">New replies only</label></div><div class="custom-control custom-checkbox"><input class="custom-control-input" type="checkbox" id="closed" name="closed" <?php echo ($_GET['closed'] == "on") ? "checked" : "" ?>><label class="custom-control-label" for="closed">Include closed</label></div><div class="custom-control custom-checkbox"><input class="custom-control-input" type="checkbox" id="mine" name="mine" <?php echo ($_GET['mine'] == "on") ? "checked" : "" ?>><label class="custom-control-label" for="mine">Tickets I've joined</label></div></div>
        <div class="form-group col-xl-2 text-xl-right"><a class="btn btn-light mr-2" href="<?php echo field($HD_CURPAGE) ?>">Reset</a><button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i> Search</button></div>
      </div>
    </form>
  </div>
</div>

<form name="tickets" method="post" id="ticket-list-form"><input type="hidden" name="cmd" value="action">
<div class="card shadow-sm mb-4 browse-results">
  <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between">
    <div><h2 class="h6 m-0 font-weight-bold text-primary">Tickets</h2><small class="text-muted"><?php echo number_format($results) ?> result<?php echo $results == 1 ? '' : 's' ?></small></div>
    <div class="browse-legend text-muted mt-2 mt-sm-0"><span><img src="./images/mail-new.png" alt=""> New</span><span><img src="./images/mail-newresponse.png" alt=""> New reply</span><span><img src="./images/mail-closed.png" alt=""> Closed/held</span><span><img src="./images/mail-flag.png" alt=""> Flagged</span></div>
  </div>
  <div class="table-responsive"><table class="table table-hover mb-0 ticket-table"><thead><tr><th class="ticket-select"><input type="checkbox" id="select-all-tickets" aria-label="Select all tickets"></th><th>Ticket</th><th>Submitter</th><th>Subject</th><th>Department</th><th>Priority</th><th>Status</th><th class="text-center">Posts</th><th>Activity</th><th>Last post</th></tr></thead><tbody>
<?php /************************************************************/
$res = mysql_query( $query );
$visible_rows = 0;
while( $row = mysql_fetch_array( $res ) )
{
  $visible_rows++;
  $res_post_user = mysql_query( "SELECT user_id, private FROM {$pre}post WHERE ( ticket_id = '{$row['id']}' ) ORDER BY date DESC LIMIT 1" );
  $row_post_user = mysql_fetch_array( $res_post_user );

  if( $row_post_user['user_id'] == -1 )
    $user_info = "<a href=\"mailto:{$row['email']}\">{$row['name']}</a>";  
  else
  {
    $res_staff_user = mysql_query( "SELECT name FROM {$pre}user WHERE ( id = '{$row_post_user['user_id']}' )" );
    $row_staff_user = mysql_fetch_array( $res_staff_user );

    if( $row_post_user['user_id'] == $_SESSION['user']['id'] )
      $user_info = "<b>" . $row_staff_user['name'] . "</b>";
    else
      $user_info = $row_staff_user['name'];
  }

  $res_post = mysql_query( "SELECT COUNT(*) FROM {$pre}post WHERE ( ticket_id = '{$row['id']}' )" );
  $row_post = mysql_fetch_array( $res_post );

  echo "<tr>";
  
  if( $row['status'] != $HD_STATUS_OPEN )
    $image = "./images/mail-closed.png";
  else if( $row_post[0] == 1 )
    $image = "./images/mail-new.png";
  else if( $row['lastpost'] == -1 )
    $image = "./images/mail-newresponse.png";
  else if( $row_post_user["private"] )
    $image = "./images/mail-privatenote.png";
  else
    $image = "./images/mail.png";

  echo "<td><input class=\"ticket-checkbox\" type=\"checkbox\" name=\"{$row['id']}\" aria-label=\"Select ticket " . field($row['ticket_id']) . "\"></td>";
  echo "<td><a class=\"font-weight-bold text-nowrap\" href=\"{$HD_URL_ADMINVIEW}?cmd=view&id={$row['ticket_id']}\">" . field($row['ticket_id']) . "</a></td>";
  echo "<td><a href=\"mailto:" . field($row['email']) . "\">" . field($row['name']) . "</a></td>";
  echo "<td class=\"ticket-subject\">" . (($row['flag'] == 0 || $row['flag'] == $_SESSION['user']['id']) ? "<img src=\"./images/mail-flag.png\" alt=\"Flagged\" title=\"Flagged\"> " : "") . "<img src=\"{$image}\" alt=\"\"> <a href=\"{$HD_URL_ADMINVIEW}?cmd=view&id={$row['ticket_id']}\">" . field( $row['subject'] ) . "</a></td>";

  $res_dept = mysql_query( "SELECT name FROM {$pre}dept WHERE ( id = '{$row['dept_id']}' )" );
  $row_dept = mysql_fetch_array( $res_dept );

  echo "<td>" . field( $row_dept[0] ) . "</td>";

  if( $row['priority'] == $PRIORITY_LOW )
    $priority = "<span class=\"badge badge-success\">Low</span>";
  else if( $row['priority'] == $PRIORITY_MEDIUM )
    $priority = "<span class=\"badge badge-warning\">Medium</span>";
  else if( $row['priority'] == $PRIORITY_HIGH )
    $priority = "<span class=\"badge badge-danger\">High</span>";

  echo "<td>$priority</td>";

  if( $row['status'] == $HD_STATUS_OPEN )
    $status = "<span class=\"badge badge-primary\">Open</span>";
  else if( $row['status'] == $HD_STATUS_CLOSED )
    $status = "<span class=\"badge badge-secondary\">Closed</span>";
  else if( $row['status'] == $HD_STATUS_HELD )
    $status = "<span class=\"badge badge-warning\">Held</span>";

  echo "<td>$status</td>";
  
  if( $row_post[0] <= 0 )
    $replies = "<font color=\"#FF0000\"><b>0</b></font>";
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
if (!$visible_rows) echo '<tr><td colspan="10"><div class="browse-empty"><i class="far fa-folder-open"></i><h3>No tickets found</h3><p>Try broadening your filters or reset the search.</p></div></td></tr>';
/********************************************************** PHP */?>
</tbody></table></div>
<?php
$query_params = $_GET;
$previous_url = $next_url = '';
if ($_GET['offset'] > 0) { $query_params['offset'] = max(0, $_GET['offset'] - $_GET['results']); $previous_url = $HD_CURPAGE . '?' . http_build_query($query_params); }
if ($_GET['offset'] < ($results - $_GET['results'])) { $query_params['offset'] = $_GET['offset'] + $_GET['results']; $next_url = $HD_CURPAGE . '?' . http_build_query($query_params); }
?>
<div class="card-footer d-flex flex-wrap align-items-center justify-content-between">
  <div class="form-inline browse-actions"><label class="mr-2" for="bulk-action">With selected</label><select class="form-control form-control-sm mr-2" id="bulk-action" name="action"><option value="reply">Mass reply</option><option value="flag">Toggle flag</option><option value="survey">Send survey</option><option value="open">Mark open</option><option value="close">Mark closed</option><option value="hold">Put on hold</option><option value="delete">Delete</option></select><button type="submit" class="btn btn-primary btn-sm">Apply</button></div>
  <nav class="mt-3 mt-md-0" aria-label="Ticket pages"><ul class="pagination pagination-sm mb-0"><li class="page-item <?php echo $previous_url ? '' : 'disabled' ?>"><a class="page-link" <?php echo $previous_url ? 'href="' . field($previous_url) . '"' : 'href="#" tabindex="-1" aria-disabled="true"' ?>><i class="fas fa-chevron-left mr-1"></i> Previous</a></li><li class="page-item disabled"><span class="page-link"><?php echo $results ? number_format($_GET['offset'] + 1) . '–' . number_format(min($_GET['offset'] + $_GET['results'], $results)) : '0' ?> of <?php echo number_format($results) ?></span></li><li class="page-item <?php echo $next_url ? '' : 'disabled' ?>"><a class="page-link" <?php echo $next_url ? 'href="' . field($next_url) . '"' : 'href="#" tabindex="-1" aria-disabled="true"' ?>>Next <i class="fas fa-chevron-right ml-1"></i></a></li></ul></nav>
</div></div></form>
<script>
(function () {
  var form = document.getElementById('ticket-list-form');
  var selectAll = document.getElementById('select-all-tickets');
  if (!form || !selectAll) return;
  var boxes = Array.prototype.slice.call(form.querySelectorAll('.ticket-checkbox'));
  selectAll.addEventListener('change', function () { boxes.forEach(function (box) { box.checked = selectAll.checked; }); });
  boxes.forEach(function (box) { box.addEventListener('change', function () { selectAll.checked = boxes.length > 0 && boxes.every(function (item) { return item.checked; }); selectAll.indeterminate = !selectAll.checked && boxes.some(function (item) { return item.checked; }); }); });
  form.addEventListener('submit', function (event) { var selected = boxes.some(function (box) { return box.checked; }); var action = form.querySelector('[name="action"]'); if (!selected) { event.preventDefault(); window.alert('Select at least one ticket first.'); return; } if (action && action.value === 'delete' && !window.confirm('Delete the selected tickets? This cannot be undone.')) event.preventDefault(); });
}());
</script>
<?php /************************************************************/
include "./include/footer.php";
/********************************************************** PHP */?>
