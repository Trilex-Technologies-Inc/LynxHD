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

$HD_CURPAGE = $HD_URL_STATS;

if( $_SESSION['login_type'] == $LOGIN_INVALID )
  Header( "Location: {$HD_URL_LOGIN}?redirect=" . urlencode( $HD_CURPAGE ) );

$total_tickets = get_row_count( "SELECT COUNT(*) FROM {$pre}ticket" );
$open_tickets = get_row_count( "SELECT COUNT(*) FROM {$pre}ticket WHERE ( status = '$HD_STATUS_OPEN' )" );
$inactive_tickets = get_row_count( "SELECT COUNT(*) FROM {$pre}ticket WHERE ( status != '$HD_STATUS_OPEN' )" );
$unanswered = get_row_count( "SELECT COUNT(*) FROM {$pre}ticket WHERE ( lastpost = '-1' && status = '$HD_STATUS_OPEN' )" );
$answered_open = max( 0, $open_tickets - $unanswered );
$response_progress = $open_tickets ? round( ($answered_open / $open_tickets) * 100 ) : 0;

$last_ticket_date = 0;
$res_temp = mysql_query( "SELECT date FROM {$pre}ticket ORDER BY date DESC LIMIT 1" );
if( $row_temp = mysql_fetch_array( $res_temp ) )
  $last_ticket_date = $row_temp[0];

$last_reply = false;
$res_temp = mysql_query( "SELECT post.date, user.name, user.id FROM {$pre}post AS post, {$pre}user AS user WHERE ( post.user_id = user.id && post.user_id != '-1' ) ORDER BY post.date DESC LIMIT 1" );
if( $row_temp = mysql_fetch_array( $res_temp ) )
  $last_reply = $row_temp;

include "./include/header.php";
/********************************************************** PHP */?>
<div class="d-sm-flex align-items-center justify-content-between mb-4">
  <div><h1 class="h3 mb-1 text-gray-800"><?php echo field($script_name) ?> statistics</h1><p class="mb-0 text-gray-600">A current overview of help desk activity.</p></div>
  <a class="btn btn-primary btn-sm shadow-sm mt-3 mt-sm-0" href="browse.php"><i class="fas fa-ticket-alt fa-sm mr-1"></i> Browse tickets</a>
</div>
<?php echo $msg ?>

<div class="row stats-summary">
  <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-primary shadow-sm h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-primary text-uppercase mb-1">All tickets</div><div class="h4 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_tickets) ?></div></div><div class="col-auto"><i class="fas fa-ticket-alt fa-2x text-gray-300"></i></div></div></div></div></div>
  <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-success shadow-sm h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-success text-uppercase mb-1">Open</div><div class="h4 mb-0 font-weight-bold text-gray-800"><?php echo number_format($open_tickets) ?></div></div><div class="col-auto"><i class="fas fa-envelope-open fa-2x text-gray-300"></i></div></div></div></div></div>
  <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-warning shadow-sm h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Awaiting reply</div><div class="h4 mb-0 font-weight-bold text-gray-800"><?php echo number_format($unanswered) ?></div></div><div class="col-auto"><i class="fas fa-reply fa-2x text-gray-300"></i></div></div></div></div></div>
  <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-secondary shadow-sm h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Closed or held</div><div class="h4 mb-0 font-weight-bold text-gray-800"><?php echo number_format($inactive_tickets) ?></div></div><div class="col-auto"><i class="fas fa-archive fa-2x text-gray-300"></i></div></div></div></div></div>
</div>

<div class="row">
  <div class="col-lg-7 mb-4"><div class="card shadow-sm h-100"><div class="card-header py-3"><h2 class="h6 m-0 font-weight-bold text-primary">Current workload</h2></div><div class="card-body">
    <div class="d-flex justify-content-between align-items-end mb-2"><div><div class="text-xs font-weight-bold text-uppercase text-gray-600">Open tickets with a staff response</div><div class="h4 mb-0 font-weight-bold text-gray-800"><?php echo number_format($answered_open) ?> <span class="h6 text-gray-500">of <?php echo number_format($open_tickets) ?></span></div></div><span class="badge badge-primary stats-percent"><?php echo $response_progress ?>%</span></div>
    <div class="progress mb-4" style="height: .65rem" role="progressbar" aria-label="Open tickets with a response" aria-valuenow="<?php echo $response_progress ?>" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width: <?php echo $response_progress ?>%"></div></div>
    <div class="stats-breakdown">
      <div><span class="stats-dot bg-success"></span><span>Open and responded</span><strong><?php echo number_format($answered_open) ?></strong></div>
      <div><span class="stats-dot bg-warning"></span><span>Awaiting a response</span><strong><?php echo number_format($unanswered) ?></strong></div>
      <div><span class="stats-dot bg-secondary"></span><span>Closed or held</span><strong><?php echo number_format($inactive_tickets) ?></strong></div>
    </div>
  </div></div></div>
  <div class="col-lg-5 mb-4"><div class="card shadow-sm h-100"><div class="card-header py-3"><h2 class="h6 m-0 font-weight-bold text-primary">Recent activity</h2></div><div class="card-body stats-activity">
    <div class="stats-activity-item"><span class="stats-activity-icon bg-primary text-white"><i class="fas fa-plus"></i></span><div><div class="font-weight-bold text-gray-800">Latest ticket</div><?php if ($last_ticket_date): ?><time datetime="<?php echo date('c', $last_ticket_date) ?>"><?php echo date("F j, Y", $last_ticket_date) . " at " . date("g:i a T", $last_ticket_date) ?></time><?php else: ?><span class="text-muted">No tickets have been created yet.</span><?php endif; ?></div></div>
    <div class="stats-activity-item"><span class="stats-activity-icon bg-info text-white"><i class="fas fa-reply"></i></span><div><div class="font-weight-bold text-gray-800">Latest staff reply</div><?php if ($last_reply): ?><a href="<?php echo field($HD_URL_USER) ?>?cmd=view&amp;id=<?php echo (int)$last_reply[2] ?>"><?php echo field($last_reply[1]) ?></a><div><time datetime="<?php echo date('c', $last_reply[0]) ?>"><?php echo date("F j, Y", $last_reply[0]) . " at " . date("g:i a T", $last_reply[0]) ?></time></div><?php else: ?><span class="text-muted">No staff replies have been posted yet.</span><?php endif; ?></div></div>
  </div></div></div>
</div>
<?php /************************************************************/
include "./include/footer.php";
/********************************************************** PHP */?>
