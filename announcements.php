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

$_GET['offset'] = (int) $_GET['offset'];

$query .= " LIMIT {$_GET['offset']},{$_GET['results']}";

include "./include/header.php";
/********************************************************** PHP */?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
  <div>
    <span class="badge text-bg-primary mb-2">News &amp; updates</span>
    <h2 class="h3 mb-1">Site announcements</h2>
    <p class="text-secondary mb-0">Important notices and the latest information from our support team.</p>
  </div>
  <?php if ($results): ?><span class="text-secondary small"><?php echo (int) $results ?> announcement<?php echo $results == 1 ? '' : 's' ?></span><?php endif; ?>
</div>
<?php echo $msg ?? '' ?>
<div class="announcement-list d-grid gap-3">
<?php /************************************************************/

$res = mysql_query( $query );
if (!mysql_num_rows($res))
  echo '<div class="empty-state text-center border rounded-3 p-5"><div class="empty-state-icon mb-3" aria-hidden="true">!</div><h3 class="h5">No announcements yet</h3><p class="text-secondary mb-0">New updates will appear here when they are published.</p></div>';

while( $row = mysql_fetch_array( $res ) )
{
  $res_post_user = mysql_query( "SELECT user_id, private, message FROM {$pre}post WHERE ( ticket_id = '{$row['id']}' ) ORDER BY date DESC LIMIT 1" );
  $row_post_user = mysql_fetch_array( $res_post_user );

  $row_staff_user = false;
  if (is_array($row_post_user) && isset($row_post_user['user_id'])) {
    $res_staff_user = mysql_query( "SELECT name FROM {$pre}user WHERE ( id = '{$row_post_user['user_id']}' )" );
    $row_staff_user = mysql_fetch_array( $res_staff_user );
  }
  
  $user_info = (is_array($row_staff_user) && trim($row_staff_user['name']) !== '') ? field($row_staff_user['name']) : 'Support team';
  
  $res_post = mysql_query( "SELECT COUNT(*) FROM {$pre}post WHERE ( ticket_id = '{$row['id']}' )" );
  $row_post = mysql_fetch_array( $res_post );

  $lastactivity = time( ) - $row['lastactivity'];
  if( $lastactivity > 86400 )
  {
    if( (int)($lastactivity / 86400 ) <= 1 )
      $lastactivity = (int)($lastactivity / 86400) . " day";
    else
      $lastactivity = (int)($lastactivity / 86400) . " days";
  }
  else if( $lastactivity > 3600 )
    $lastactivity = (int)($lastactivity / 3600) . " hr";
  else
    $lastactivity = max(1, (int)($lastactivity / 60)) . " min";

  $excerpt = trim(strip_tags($row_post_user['message'] ?? ''));
  if (strlen($excerpt) > 180)
    $excerpt = substr($excerpt, 0, 177) . '...';

  echo '<article class="announcement-card card border shadow-sm"><div class="card-body p-4">';
  echo '<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start gap-2 mb-3"><div class="d-flex align-items-center gap-2">';
  if (!$row['viewed']) echo '<span class="badge text-bg-primary">New</span>';
  echo '<span class="text-secondary small">Announcement #' . field($row['ticket_id']) . '</span></div><time class="text-secondary small">' . $lastactivity . ' ago</time></div>';
  echo '<h3 class="h5 mb-2"><a class="stretched-link text-body text-decoration-none" href="' . $HD_URL_ANNOUNVIEW . '?cmd=view&amp;id=' . field($row['ticket_id']) . '">' . field($row['subject']) . '</a></h3>';
  if ($excerpt !== '') echo '<p class="text-secondary mb-3">' . field($excerpt) . '</p>';
  echo '<div class="small text-secondary">Posted by ' . $user_info . '</div></div></article>';
}  

/********************************************************** PHP */?>
</div>
<?php if ($results > $_GET['results']):
  $previous_offset = max(0, $_GET['offset'] - $_GET['results']);
  $next_offset = $_GET['offset'] + $_GET['results']; ?>
<nav class="d-flex justify-content-between mt-4" aria-label="Announcements pagination">
  <?php if ($_GET['offset'] > 0): ?><a class="btn btn-outline-secondary" href="?offset=<?php echo $previous_offset ?>">&larr; Newer</a><?php else: ?><span></span><?php endif; ?>
  <?php if ($next_offset < $results): ?><a class="btn btn-outline-primary" href="?offset=<?php echo $next_offset ?>">Older &rarr;</a><?php endif; ?>
</nav>
<?php endif; ?>
<?php /************************************************************/
include "./include/footer.php";
/********************************************************** PHP */?>
