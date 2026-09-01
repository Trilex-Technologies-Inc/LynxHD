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
include_once "../modules/system.php";
include_once "../modules/livechat/bootstrap.php";

$HD_CURPAGE = $HD_URL_BROWSE;
$msg = "";

if( ($_SESSION['login_type'] ?? $LOGIN_INVALID) == $LOGIN_INVALID )
{
  Header( "Location: {$HD_URL_LOGIN}?redirect=" . urlencode( $HD_CURPAGE ) );
  exit;
}

$current_user_id = (int)($_SESSION['user']['id'] ?? 0);
if( $current_user_id <= 0 )
{
  $_SESSION['login_type'] = $LOGIN_INVALID;
  Header( "Location: {$HD_URL_LOGIN}?redirect=" . urlencode( $HD_CURPAGE ) );
  exit;
}

$global_priv = get_row_count( "SELECT COUNT(*) FROM {$pre}privilege WHERE ( user_id = '$current_user_id' && dept_id = '0' )" );
$dashboard_livechat_enabled = $global_priv && hd_module_enabled('livechat') && livechat_installed() && livechat_enabled();

// Dashboard totals follow the same department permissions as the ticket list.
$dashboard_ticket_counts = array('total' => 0, 'open' => 0, 'waiting' => 0, 'closed' => 0, 'held' => 0);
$dashboard_count_result = mysql_query(
  "SELECT COUNT(DISTINCT ticket.id) AS total,
          COUNT(DISTINCT CASE WHEN ticket.status = '$HD_STATUS_OPEN' THEN ticket.id END) AS open,
          COUNT(DISTINCT CASE WHEN ticket.status = '$HD_STATUS_OPEN' AND ticket.lastpost = '-1' THEN ticket.id END) AS waiting,
          COUNT(DISTINCT CASE WHEN ticket.status = '$HD_STATUS_CLOSED' THEN ticket.id END) AS closed,
          COUNT(DISTINCT CASE WHEN ticket.status = '$HD_STATUS_HELD' THEN ticket.id END) AS held
   FROM {$pre}ticket AS ticket, {$pre}privilege AS priv
   WHERE ticket.ticket_id NOT LIKE 'M%'
     AND priv.user_id = '$current_user_id'
     AND (ticket.dept_id = '0' OR ticket.dept_id = priv.dept_id OR priv.dept_id = '0')"
);
if( $dashboard_count_result && ($dashboard_count_row = mysql_fetch_array($dashboard_count_result)) )
  foreach( $dashboard_ticket_counts as $dashboard_count_key => $dashboard_count_value )
    $dashboard_ticket_counts[$dashboard_count_key] = (int)$dashboard_count_row[$dashboard_count_key];

if( ( $_POST['cmd'] ?? "" ) == "action" )
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
  "order" => "activity",
  "state" => ""
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
  
if( $_GET['state'] == "all" )
  $_GET['closed'] = "on";
else if( $_GET['state'] == "open" )
  $query .= " && ticket.status = '$HD_STATUS_OPEN'";
else if( $_GET['state'] == "answered" )
  $query .= " && ticket.status = '$HD_STATUS_OPEN' && ticket.lastpost != '-1'";
else if( $_GET['state'] == "unanswered" )
  $query .= " && ticket.status = '$HD_STATUS_OPEN' && ticket.lastpost = '-1'";
else if( $_GET['state'] == "closed" )
  $query .= " && ticket.status = '$HD_STATUS_CLOSED'";
else if( $_GET['state'] == "held" )
  $query .= " && ticket.status = '$HD_STATUS_HELD'";
else if( $_GET['state'] == "inactive" )
  $query .= " && ticket.status != '$HD_STATUS_OPEN'";
else if( $_GET['closed'] != "on" )
  $query .= " && ticket.status != '$HD_STATUS_CLOSED'";

if( $_GET['mine'] == "on" )
  $query .= " && post.user_id = '$current_user_id'";

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

$rows_query = "SELECT COUNT( DISTINCT( ticket.id ) ) FROM {$pre}ticket AS ticket, {$pre}post AS post, {$pre}privilege AS priv WHERE ( ticket.ticket_id NOT LIKE 'M%' && post.ticket_id = ticket.id && priv.user_id = '$current_user_id' && (ticket.dept_id = '0' || ticket.dept_id = priv.dept_id || priv.dept_id = '0') " . $query . " ) ORDER BY " . $order;

$query = "SELECT DISTINCT( ticket.id ), ticket.* FROM {$pre}ticket AS ticket, {$pre}post AS post, {$pre}privilege AS priv WHERE ( ticket.ticket_id NOT LIKE 'M%' && post.ticket_id = ticket.id && priv.user_id = '$current_user_id' && (ticket.dept_id = '0' || ticket.dept_id = priv.dept_id || priv.dept_id = '0') " . $query . " ) ORDER BY " . $order;

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

<div class="row dashboard-ticket-summary">
<?php
$dashboard_cards = array(
  array('Total tickets', 'total', 'primary', 'fa-ticket-alt', 'all'),
  array('Open', 'open', 'info', 'fa-folder-open', 'open'),
  array('Waiting reply', 'waiting', 'warning', 'fa-reply', 'unanswered'),
  array('Closed', 'closed', 'success', 'fa-check-circle', 'closed'),
  array('Held', 'held', 'secondary', 'fa-pause-circle', 'held')
);
foreach( $dashboard_cards as $dashboard_card ):
?>
  <div class="col-xl col-md-4 col-sm-6 mb-4">
    <a class="card border-left-<?php echo $dashboard_card[2] ?> shadow-sm h-100 py-2 stats-card-link" href="browse.php?state=<?php echo $dashboard_card[4] ?>">
      <div class="card-body"><div class="row no-gutters align-items-center">
        <div class="col mr-2"><div class="text-xs font-weight-bold text-<?php echo $dashboard_card[2] ?> text-uppercase mb-1"><?php echo $dashboard_card[0] ?></div><div class="h4 mb-0 font-weight-bold text-gray-800"><?php echo number_format($dashboard_ticket_counts[$dashboard_card[1]]) ?></div></div>
        <div class="col-auto"><i class="fas <?php echo $dashboard_card[3] ?> fa-2x text-gray-300"></i></div>
      </div></div>
    </a>
  </div>
<?php endforeach; ?>
</div>

<?php if ($dashboard_livechat_enabled): ?>
<section class="card shadow-sm mb-4 border-0" id="dashboard-chat-waiting" data-api="../modules/livechat/api.php">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center"><div><h2 class="h5 mb-1 text-gray-900"><i class="fas fa-hourglass-half text-warning mr-2"></i>Visitors waiting <span class="badge badge-warning ml-1" id="dashboard-waiting-count">0</span></h2><small class="text-muted">Visitors waiting for an operator to answer.</small></div><a class="btn btn-sm btn-outline-primary" href="livechat.php"><i class="fas fa-users mr-1"></i>Live chat</a></div>
  <div class="table-responsive"><table class="table table-hover mb-0 dashboard-waiting-table"><thead><tr><th>Name</th><th>Action</th><th>Address</th><th>State</th><th>Department</th><th>Total time</th><th>Waiting time</th><th>Browser</th></tr></thead><tbody id="dashboard-waiting-body"><tr><td colspan="8" class="text-center text-muted py-3">Loading…</td></tr></tbody></table></div>
</section>
<style>.dashboard-waiting-table{font-size:.82rem}.dashboard-waiting-table thead th{white-space:nowrap;border-top:0;background:#f8f9fc;color:#5a5c69;font-size:.72rem;text-transform:uppercase}.dashboard-waiting-table td{vertical-align:middle}</style>
<script>(function(){var root=document.getElementById('dashboard-chat-waiting'),body=document.getElementById('dashboard-waiting-body'),serverTime=Math.floor(Date.now()/1000);function cell(row,text,className){var td=document.createElement('td');td.textContent=text;if(className)td.className=className;row.appendChild(td);return td}function elapsed(value){var seconds=Math.max(0,serverTime-Number(value||serverTime)),hours=Math.floor(seconds/3600),minutes=Math.floor(seconds%3600/60);return(hours?hours+'h ':'')+minutes+'m '+seconds%60+'s'}function browser(agent){var match=(agent||'').match(/(Edg|Chrome|Firefox|Safari)\/([\d.]+)/);return match?(match[1]==='Edg'?'Edge':match[1])+' '+match[2]:'Unknown'}function openChat(id){var popup=window.open('livechatwindow.php?conversation='+id,'lynx_chat_'+id,'popup=yes,width=520,height=700,resizable=yes,scrollbars=no');if(popup)popup.focus()}function render(result){serverTime=+result.server_time||serverTime;var items=result.waiting||[];document.getElementById('dashboard-waiting-count').textContent=items.length;body.innerHTML='';if(!items.length){var empty=document.createElement('tr'),td=cell(empty,'The list of visitors waiting is empty.','text-center text-muted py-3');td.colSpan=8;body.appendChild(empty);return}items.forEach(function(v){var row=document.createElement('tr'),name=cell(row,'');var link=document.createElement('a');link.href='livechatwindow.php?conversation='+v.conversation_id;link.textContent=v.visitor_name||'Guest';link.onclick=function(e){e.preventDefault();openChat(v.conversation_id)};name.appendChild(link);var action=cell(row,''),button=document.createElement('button');button.type='button';button.className='btn btn-sm btn-outline-primary';button.title='Open chat';button.innerHTML='<i class="fas fa-comment-dots"></i>';button.onclick=function(){openChat(v.conversation_id)};action.appendChild(button);cell(row,v.ip_address||'—');cell(row,'Waiting','text-warning font-weight-bold');cell(row,v.department_name||'—');cell(row,elapsed(v.first_seen));cell(row,elapsed(v.conversation_created));cell(row,browser(v.user_agent));body.appendChild(row)})}function refresh(){fetch(root.dataset.api,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'operator_visitors'})}).then(function(r){return r.json().then(function(j){if(!r.ok)throw Error(j.error||'Unable to load waiting visitors.');return j})}).then(render).catch(function(e){body.innerHTML='<tr><td colspan="8" class="text-center text-danger py-3"></td></tr>';body.querySelector('td').textContent=e.message})}refresh();setInterval(refresh,3000);setInterval(function(){serverTime++},1000)})();</script>
<?php endif; ?>

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
          <?php if( $global_priv ) $res_dept = mysql_query( "SELECT name, id FROM {$pre}dept ORDER BY sortnum" ); else $res_dept = mysql_query( "SELECT dept.name, dept.id FROM {$pre}privilege AS priv, {$pre}dept AS dept WHERE ( priv.user_id = '$current_user_id' && priv.dept_id = dept.id )" ); while( $row_dept = mysql_fetch_array( $res_dept ) ) echo "<option value=\"" . (int)$row_dept['id'] . "\" " . (($row_dept['id'] == $_GET['department']) ? "selected" : "") . ">" . field($row_dept['name']) . "</option>\n"; ?>
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
  $row_post_user = mysql_fetch_array( $res_post_user ) ?: array( 'user_id' => 0, 'private' => 0 );

  if( $row_post_user['user_id'] == -1 )
    $user_info = "<a href=\"mailto:{$row['email']}\">{$row['name']}</a>";  
  else
  {
    $res_staff_user = mysql_query( "SELECT name FROM {$pre}user WHERE ( id = '{$row_post_user['user_id']}' )" );
    $row_staff_user = mysql_fetch_array( $res_staff_user ) ?: array( 'name' => 'Unknown user' );

    if( $row_post_user['user_id'] == $current_user_id )
      $user_info = "<b>" . $row_staff_user['name'] . "</b>";
    else
      $user_info = $row_staff_user['name'];
  }

  $res_post = mysql_query( "SELECT COUNT(*) FROM {$pre}post WHERE ( ticket_id = '{$row['id']}' )" );
  $row_post = mysql_fetch_array( $res_post ) ?: array( 0 => 0 );

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
  echo "<td class=\"ticket-subject\">" . (($row['flag'] == 0 || $row['flag'] == $current_user_id) ? "<img src=\"./images/mail-flag.png\" alt=\"Flagged\" title=\"Flagged\"> " : "") . "<img src=\"{$image}\" alt=\"\"> <a href=\"{$HD_URL_ADMINVIEW}?cmd=view&id={$row['ticket_id']}\">" . field( $row['subject'] ) . "</a></td>";

  $res_dept = mysql_query( "SELECT name FROM {$pre}dept WHERE ( id = '{$row['dept_id']}' )" );
  $row_dept = mysql_fetch_array( $res_dept ) ?: array( 0 => 'Unknown department' );

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
