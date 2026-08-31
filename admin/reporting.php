<?php
include '../include/settings.php';
include '../include/include.php';
include '../modules/system.php';

$HD_CURPAGE = 'reporting.php';
if (($_SESSION['login_type'] ?? $LOGIN_INVALID) == $LOGIN_INVALID) {
    header('Location: index.php?redirect=reporting.php');
    exit;
}
$user_id = (int)$_SESSION['user']['id'];
$global_priv = get_row_count("SELECT COUNT(*) FROM {$pre}privilege WHERE user_id=$user_id AND dept_id=0 AND admin=1") > 0;
if (!$global_priv) { header("Location: $HD_URL_BROWSE"); exit; }

function report_valid_date($value)
{
    if ($value === '') return true;
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value;
}
function report_table_exists($name)
{
    $safe = live_report_escape($name);
    return get_row_count("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='$safe'") > 0;
}
function live_report_escape($value)
{
    $connection = $GLOBALS['_lynxhd_mysql_connection'] ?? null;
    return $connection ? mysqli_real_escape_string($connection, (string)$value) : addslashes((string)$value);
}
function report_duration($seconds)
{
    $seconds = max(0, (int)$seconds);
    $hours = (int)floor($seconds / 3600);
    $minutes = (int)floor(($seconds % 3600) / 60);
    return ($hours ? $hours . 'h ' : '') . $minutes . 'm';
}

$from = trim((string)($_GET['from'] ?? ''));
$to = trim((string)($_GET['to'] ?? ''));
$date_error = '';
if (!report_valid_date($from) || !report_valid_date($to) || ($from !== '' && $to !== '' && $from > $to)) {
    $date_error = 'Choose a valid date range.';
    $from = $to = '';
}
$start = $from !== '' ? strtotime($from . ' 00:00:00') : 0;
$end = $to !== '' ? strtotime($to . ' +1 day 00:00:00') : 0;
function report_period_sql($column, $start, $end)
{
    $parts = array();
    if ($start) $parts[] = "$column >= " . (int)$start;
    if ($end) $parts[] = "$column < " . (int)$end;
    return $parts ? ' AND ' . implode(' AND ', $parts) : '';
}

$ticket_period = report_period_sql('date', $start, $end);
$tickets = array(
    'total' => get_row_count("SELECT COUNT(*) FROM {$pre}ticket WHERE 1=1$ticket_period"),
    'open' => get_row_count("SELECT COUNT(*) FROM {$pre}ticket WHERE status=$HD_STATUS_OPEN$ticket_period"),
    'waiting' => get_row_count("SELECT COUNT(*) FROM {$pre}ticket WHERE status=$HD_STATUS_OPEN AND lastpost=-1$ticket_period"),
    'closed' => get_row_count("SELECT COUNT(*) FROM {$pre}ticket WHERE status=$HD_STATUS_CLOSED$ticket_period"),
    'held' => get_row_count("SELECT COUNT(*) FROM {$pre}ticket WHERE status=$HD_STATUS_HELD$ticket_period")
);
$department_rows = array();
$department_result = mysql_query("SELECT COALESCE(NULLIF(d.name,''),'Unassigned') department_name,COUNT(*) total,
    SUM(t.status=$HD_STATUS_OPEN) open_count,SUM(t.status=$HD_STATUS_OPEN AND t.lastpost=-1) waiting_count,
    SUM(t.status=$HD_STATUS_CLOSED) closed_count,SUM(t.status=$HD_STATUS_HELD) held_count
    FROM {$pre}ticket t LEFT JOIN {$pre}dept d ON d.id=t.dept_id WHERE 1=1" . report_period_sql('t.date', $start, $end) . " GROUP BY t.dept_id,d.name ORDER BY total DESC,department_name");
while ($department_result && ($row = mysql_fetch_array($department_result, MYSQLI_ASSOC))) $department_rows[] = $row;

$livechat_available = hd_module_enabled('livechat') && report_table_exists($pre . 'livechat_conversation');
$livechat = array('total'=>0,'open'=>0,'closed'=>0,'waiting'=>0,'messages'=>0);
$operator_rows = array();
if ($livechat_available) {
    $chat_period = report_period_sql('c.created_at', $start, $end);
    $livechat['total'] = get_row_count("SELECT COUNT(*) FROM {$pre}livechat_conversation c WHERE 1=1$chat_period");
    $livechat['open'] = get_row_count("SELECT COUNT(*) FROM {$pre}livechat_conversation c WHERE c.status='open'$chat_period");
    $livechat['closed'] = get_row_count("SELECT COUNT(*) FROM {$pre}livechat_conversation c WHERE c.status='closed'$chat_period");
    $livechat['waiting'] = get_row_count("SELECT COUNT(*) FROM {$pre}livechat_conversation c WHERE c.status='open' AND NOT EXISTS(SELECT 1 FROM {$pre}livechat_message m WHERE m.conversation_id=c.id AND m.sender='operator' AND m.sender_id>0)$chat_period");
    $livechat['messages'] = get_row_count("SELECT COUNT(*) FROM {$pre}livechat_message m INNER JOIN {$pre}livechat_conversation c ON c.id=m.conversation_id WHERE 1=1$chat_period");
    if (report_table_exists($pre . 'livechat_operator_status_history')) {
        $history_conditions = array();
        if ($start) $history_conditions[] = '(h.ended_at=0 OR h.ended_at>' . (int)$start . ')';
        if ($end) $history_conditions[] = 'h.started_at<' . (int)$end;
        $history_where = $history_conditions ? 'WHERE ' . implode(' AND ', $history_conditions) : '';
        $range_start = $start ?: 0; $range_end = $end ?: time(); $now = time();
        $operator_result = mysql_query("SELECT u.name,
            SUM(CASE WHEN h.status='checked-in' THEN GREATEST(0,LEAST(IF(h.ended_at=0,$now,h.ended_at),$range_end)-GREATEST(h.started_at,$range_start)) ELSE 0 END) checked_in_seconds,
            SUM(CASE WHEN h.status='away' THEN GREATEST(0,LEAST(IF(h.ended_at=0,$now,h.ended_at),$range_end)-GREATEST(h.started_at,$range_start)) ELSE 0 END) away_seconds,
            SUM(CASE WHEN h.status='checked-out' THEN GREATEST(0,LEAST(IF(h.ended_at=0,$now,h.ended_at),$range_end)-GREATEST(h.started_at,$range_start)) ELSE 0 END) checked_out_seconds
            FROM {$pre}livechat_operator_status_history h LEFT JOIN {$pre}user u ON u.id=h.operator_id $history_where GROUP BY h.operator_id,u.name ORDER BY u.name");
        while ($operator_result && ($row = mysql_fetch_array($operator_result, MYSQLI_ASSOC))) $operator_rows[] = $row;
    }
}

if (($_GET['export'] ?? '') === 'csv') {
    $filename = 'lynxhd-report-' . ($from ?: 'all') . '-to-' . ($to ?: date('Y-m-d')) . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    fputcsv($output, array('Report period', $from ?: 'All time', $to ?: 'Present'));
    fputcsv($output, array()); fputcsv($output, array('Tickets', 'Count'));
    foreach (array('Total'=>'total','Open'=>'open','Waiting reply'=>'waiting','Closed'=>'closed','Held'=>'held') as $label=>$key) fputcsv($output, array($label, $tickets[$key]));
    fputcsv($output, array()); fputcsv($output, array('Department','Total','Open','Waiting reply','Closed','Held'));
    foreach ($department_rows as $row) fputcsv($output, array($row['department_name'],$row['total'],$row['open_count'],$row['waiting_count'],$row['closed_count'],$row['held_count']));
    if ($livechat_available) {
        fputcsv($output, array()); fputcsv($output, array('Live Chat', 'Count'));
        foreach (array('Conversations'=>'total','Open'=>'open','Waiting'=>'waiting','Closed'=>'closed','Messages'=>'messages') as $label=>$key) fputcsv($output, array($label, $livechat[$key]));
        if ($operator_rows) { fputcsv($output,array()); fputcsv($output,array('Operator','Checked in','Away','Checked out')); foreach($operator_rows as $row) fputcsv($output,array($row['name']?:'Unknown',report_duration($row['checked_in_seconds']),report_duration($row['away_seconds']),report_duration($row['checked_out_seconds']))); }
    }
    fclose($output); exit;
}

$script_name = 'Reporting';
include './include/header.php';
?>
<div class="d-sm-flex align-items-center justify-content-between mb-4"><div><h1 class="h3 mb-1 text-gray-800"><i class="fas fa-chart-bar text-primary mr-2"></i>Reporting</h1><p class="mb-0 text-muted">Ticket and Live Chat activity in one report.</p></div></div>
<?php if ($date_error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($date_error,ENT_QUOTES,'UTF-8') ?></div><?php endif; ?>
<div class="card shadow-sm border-0 mb-4"><div class="card-body"><form method="get" class="form-row align-items-end"><div class="form-group col-md-4 mb-md-0"><label for="report-from">From</label><input class="form-control" id="report-from" type="date" name="from" value="<?php echo htmlspecialchars($from,ENT_QUOTES,'UTF-8') ?>"></div><div class="form-group col-md-4 mb-md-0"><label for="report-to">To</label><input class="form-control" id="report-to" type="date" name="to" value="<?php echo htmlspecialchars($to,ENT_QUOTES,'UTF-8') ?>"></div><div class="col-md-4 d-flex mb-0"><button class="btn btn-primary mr-2" type="submit"><i class="fas fa-filter mr-1"></i>Run report</button><button class="btn btn-outline-success" type="submit" name="export" value="csv"><i class="fas fa-file-csv mr-1"></i>Export CSV</button></div></form></div></div>
<div class="row"><?php foreach(array(array('Total tickets','total','primary','fa-ticket-alt'),array('Open','open','info','fa-folder-open'),array('Waiting reply','waiting','warning','fa-reply'),array('Closed','closed','success','fa-check-circle'),array('Held','held','secondary','fa-pause-circle')) as $card): ?><div class="col-xl col-md-4 col-sm-6 mb-4"><div class="card border-left-<?php echo $card[2] ?> shadow-sm h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-<?php echo $card[2] ?> text-uppercase mb-1"><?php echo $card[0] ?></div><div class="h4 mb-0 font-weight-bold text-gray-800"><?php echo number_format($tickets[$card[1]]) ?></div></div><div class="col-auto"><i class="fas <?php echo $card[3] ?> fa-2x text-gray-300"></i></div></div></div></div></div><?php endforeach; ?></div>
<div class="card shadow-sm border-0 mb-4"><div class="card-header bg-white py-3"><h2 class="h6 mb-0 font-weight-bold text-primary">Tickets by department</h2></div><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Department</th><th>Total</th><th>Open</th><th>Waiting reply</th><th>Closed</th><th>Held</th></tr></thead><tbody><?php foreach($department_rows as $row): ?><tr><td class="font-weight-bold"><?php echo htmlspecialchars($row['department_name'],ENT_QUOTES,'UTF-8') ?></td><td><?php echo number_format($row['total']) ?></td><td><?php echo number_format($row['open_count']) ?></td><td><?php echo number_format($row['waiting_count']) ?></td><td><?php echo number_format($row['closed_count']) ?></td><td><?php echo number_format($row['held_count']) ?></td></tr><?php endforeach;if(!$department_rows): ?><tr><td colspan="6" class="text-center text-muted py-4">No tickets in this period.</td></tr><?php endif; ?></tbody></table></div></div>
<?php if ($livechat_available): ?>
<div class="card shadow-sm border-0 mb-4"><div class="card-header bg-white py-3"><h2 class="h6 mb-0 font-weight-bold text-success"><i class="fas fa-comments mr-2"></i>Live Chat report</h2></div><div class="card-body"><div class="row text-center"><?php foreach(array('Conversations'=>'total','Open'=>'open','Waiting'=>'waiting','Closed'=>'closed','Messages'=>'messages') as $label=>$key): ?><div class="col mb-3"><div class="h4 font-weight-bold text-gray-800"><?php echo number_format($livechat[$key]) ?></div><div class="small text-uppercase text-muted"><?php echo $label ?></div></div><?php endforeach; ?></div></div><?php if($operator_rows): ?><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Operator</th><th>Checked in</th><th>Away</th><th>Checked out</th></tr></thead><tbody><?php foreach($operator_rows as $row): ?><tr><td class="font-weight-bold"><?php echo htmlspecialchars($row['name']?:'Unknown',ENT_QUOTES,'UTF-8') ?></td><td><?php echo report_duration($row['checked_in_seconds']) ?></td><td><?php echo report_duration($row['away_seconds']) ?></td><td><?php echo report_duration($row['checked_out_seconds']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div>
<?php endif; ?>
<style>.table thead th{white-space:nowrap;border-top:0;background:#f8f9fc;color:#5a5c69;font-size:.72rem;text-transform:uppercase}</style>
<?php include './include/footer.php'; ?>
