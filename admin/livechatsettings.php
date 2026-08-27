<?php
include '../include/settings.php';
include '../include/include.php';
include '../modules/livechat/bootstrap.php';
include '../modules/system.php';
$HD_CURPAGE = 'livechatsettings.php';
if (($_SESSION['login_type'] ?? $LOGIN_INVALID) == $LOGIN_INVALID) { header('Location: index.php?redirect=livechatsettings.php'); exit; }
$global_priv = get_row_count("SELECT COUNT(*) FROM {$pre}privilege WHERE user_id='" . (int)$_SESSION['user']['id'] . "' AND dept_id='0'");
if (!$global_priv) { header("Location: $HD_URL_BROWSE"); exit; }
$livechat_settings_csrf = $_SESSION['livechat_settings_csrf'] ?? '';
if ($livechat_settings_csrf === '') {
    $livechat_settings_csrf = bin2hex(random_bytes(32));
    $_SESSION['livechat_settings_csrf'] = $livechat_settings_csrf;
}
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['csrf_token']) || !hash_equals($livechat_settings_csrf, (string)$_POST['csrf_token']))) {
    $msg = '<div class="alert alert-danger">The request could not be verified. Please try again.</div>';
} elseif (isset($_POST['save_livechat']) && livechat_installed()) {
    $enabled = isset($_POST['livechat_enabled']) ? '1' : '0';
    $color = trim((string)($_POST['livechat_color'] ?? ''));
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $color = '#4f46e5';
    $color = strtolower($color);
    if (get_row_count("SELECT COUNT(*) FROM {$pre}options WHERE name='livechat_enabled'")) mysql_query("UPDATE {$pre}options SET text='$enabled' WHERE name='livechat_enabled'");
    else mysql_query("INSERT INTO {$pre}options (name,text) VALUES ('livechat_enabled','$enabled')");
    if (get_row_count("SELECT COUNT(*) FROM {$pre}options WHERE name='livechat_color'")) mysql_query("UPDATE {$pre}options SET text='$color' WHERE name='livechat_color'");
    else mysql_query("INSERT INTO {$pre}options (name,text) VALUES ('livechat_color','$color')");
    hd_module_sync('livechat', true, $enabled === '1');
    $msg = '<div class="alert alert-success">Live chat setting saved.</div>';
} elseif (isset($_POST['unblock_visitor']) && livechat_installed() && livechat_ensure_blocks()) {
    $block_id = (int)($_POST['block_id'] ?? 0);
    if ($block_id > 0) {
        $block_result = mysql_query("SELECT conversation_id FROM {$pre}livechat_block WHERE id=$block_id LIMIT 1");
        $block_row = $block_result ? mysql_fetch_array($block_result, MYSQLI_ASSOC) : false;
        mysql_query("DELETE FROM {$pre}livechat_block WHERE id=$block_id LIMIT 1");
        if ($block_row && (int)$block_row['conversation_id'] > 0) {
            $conversation_id = (int)$block_row['conversation_id'];
            mysql_query("UPDATE {$pre}livechat_conversation SET status='open',updated_at=" . time() . " WHERE id=$conversation_id");
        }
    }
    header('Location: livechatsettings.php?visitor_unblocked=1#blocked-visitors'); exit;
} elseif (isset($_POST['save_canned']) && livechat_installed() && livechat_ensure_canned_messages()) {
    $canned_id = (int)($_POST['canned_id'] ?? 0);
    $title = trim((string)($_POST['canned_title'] ?? ''));
    $body = livechat_canned_plain_text($_POST['canned_body'] ?? '');
    $language = trim((string)($_POST['canned_language'] ?? 'English'));
    $operator_id = max(0, (int)($_POST['canned_operator'] ?? 0));
    if ($title === '' || $body === '' || strlen($title) > 120 || strlen($body) > 2000 || strlen($language) > 40) {
        $msg = '<div class="alert alert-danger">Enter a title and a message (maximum 120 and 2,000 characters).</div>';
    } else {
        $title_sql = livechat_escape($title); $body_sql = livechat_escape($body); $language_sql = livechat_escape($language === '' ? 'English' : $language); $now = time();
        if ($canned_id) mysql_query("UPDATE {$pre}livechat_canned_message SET title='$title_sql',body='$body_sql',language='$language_sql',operator_id=$operator_id,updated_at=$now WHERE id=$canned_id");
        else mysql_query("INSERT INTO {$pre}livechat_canned_message (title,body,language,operator_id,created_at,updated_at) VALUES ('$title_sql','$body_sql','$language_sql',$operator_id,$now,$now)");
        header('Location: livechatsettings.php?canned_saved=1#canned-messages'); exit;
    }
} elseif (isset($_POST['delete_canned']) && livechat_installed() && livechat_ensure_canned_messages()) {
    $canned_id = (int)($_POST['canned_id'] ?? 0);
    if ($canned_id) mysql_query("DELETE FROM {$pre}livechat_canned_message WHERE id=$canned_id");
    header('Location: livechatsettings.php?canned_deleted=1#canned-messages'); exit;
}
$installed = livechat_installed();
$department_ready = $installed && livechat_ensure_department();
$canned_ready = $installed && livechat_ensure_canned_messages();
$blocks_ready = $installed && livechat_ensure_blocks();
$enabled = $installed && livechat_enabled();
$livechat_color = $installed ? livechat_color() : '#4f46e5';
$livechat_base_url = rtrim((string)($PATH_TO_HELPDESK ?? ''), '/');
if ($livechat_base_url === '' && !empty($_SERVER['HTTP_HOST'])) {
    $livechat_scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $livechat_base_url = $livechat_scheme . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
}
$livechat_embed_code = '<script src="' . $livechat_base_url . '/modules/livechat/embed.js" defer></script>';
$canned_edit = false;
if ($canned_ready && isset($_GET['edit_canned'])) {
    $edit_id = (int)$_GET['edit_canned'];
    $edit_result = mysql_query("SELECT * FROM {$pre}livechat_canned_message WHERE id=$edit_id LIMIT 1");
    $canned_edit = $edit_result ? mysql_fetch_array($edit_result, MYSQLI_ASSOC) : false;
}
$language_filter = trim((string)($_GET['canned_language'] ?? ''));
$operator_filter = isset($_GET['canned_operator']) ? (int)$_GET['canned_operator'] : -1;
$canned_panel_open = $canned_edit || isset($_GET['canned_saved']) || isset($_GET['canned_deleted']) || isset($_POST['save_canned']) || $language_filter !== '' || $operator_filter >= 0;
$canned_where = array();
if ($language_filter !== '') $canned_where[] = "language='" . livechat_escape($language_filter) . "'";
if ($operator_filter >= 0) $canned_where[] = "operator_id=$operator_filter";
$canned_query = "SELECT c.*,u.name operator_name FROM {$pre}livechat_canned_message c LEFT JOIN {$pre}user u ON u.id=c.operator_id" . ($canned_where ? ' WHERE ' . implode(' AND ', $canned_where) : '') . ' ORDER BY c.title';
$canned_messages = $canned_ready ? mysql_query($canned_query) : false;
$canned_languages = $canned_ready ? mysql_query("SELECT DISTINCT language FROM {$pre}livechat_canned_message ORDER BY language") : false;
$operators = mysql_query("SELECT id,name FROM {$pre}user ORDER BY name");
$operator_options = array(); while ($operators && ($operator_row = mysql_fetch_array($operators, MYSQLI_ASSOC))) $operator_options[] = $operator_row;
$canned_total = $canned_ready ? get_row_count("SELECT COUNT(*) FROM {$pre}livechat_canned_message") : 0;
$blocked_total = $blocks_ready ? get_row_count("SELECT COUNT(*) FROM {$pre}livechat_block") : 0;
$blocked_visitors = $blocks_ready ? mysql_query("SELECT b.*,c.visitor_name,u.name blocked_by_name
    FROM {$pre}livechat_block b
    LEFT JOIN {$pre}livechat_conversation c ON c.id=b.conversation_id
    LEFT JOIN {$pre}user u ON u.id=b.blocked_by
    ORDER BY b.created_at DESC,b.id DESC") : false;
$history_search = trim((string)($_GET['history_search'] ?? ''));
$history_page = max(1, (int)($_GET['history_page'] ?? 1));
$history_limit = 25; $history_offset = ($history_page - 1) * $history_limit;
$history_where = "c.status='closed'";
if ($history_search !== '') {
    $history_term = livechat_escape($history_search);
    $history_where .= " AND (c.visitor_name LIKE '%$history_term%' OR c.visitor_email LIKE '%$history_term%' OR c.ip_address LIKE '%$history_term%' OR EXISTS(SELECT 1 FROM {$pre}livechat_message sm WHERE sm.conversation_id=c.id AND sm.body LIKE '%$history_term%'))";
}
$history_total = $installed ? get_row_count("SELECT COUNT(*) FROM {$pre}livechat_conversation c WHERE $history_where") : 0;
$history_pages = max(1, (int)ceil($history_total / $history_limit));
if ($history_page > $history_pages) { $history_page = $history_pages; $history_offset = ($history_page - 1) * $history_limit; }
$history_rows = $installed ? mysql_query("SELECT c.*,d.name department_name,
    (SELECT u.name FROM {$pre}livechat_message m LEFT JOIN {$pre}user u ON u.id=m.sender_id WHERE m.conversation_id=c.id AND m.sender='operator' AND m.sender_id>0 ORDER BY m.id DESC LIMIT 1) operator_name,
    (SELECT COUNT(*) FROM {$pre}livechat_message m WHERE m.conversation_id=c.id AND m.sender='visitor') visitor_messages,
    (SELECT COUNT(*) FROM {$pre}livechat_message m WHERE m.conversation_id=c.id) total_messages
    FROM {$pre}livechat_conversation c LEFT JOIN {$pre}dept d ON d.id=c.dept_id WHERE $history_where ORDER BY c.updated_at DESC LIMIT $history_offset,$history_limit") : false;
$script_name = 'Live Chat Settings';
include './include/header.php';
?>
<div class="d-sm-flex align-items-center justify-content-between mb-4"><div><h1 class="h3 mb-1 text-gray-800">Live Chat Settings</h1><p class="mb-0 text-muted">Configure the widget, website embed, and reusable replies.</p></div><a class="btn btn-primary btn-sm mt-3 mt-sm-0" href="livechat.php"><i class="fas fa-comments mr-1"></i>Open conversations</a></div>
<?php echo $msg ?>
<?php if (!$installed): ?>
<div class="alert alert-info shadow-sm"><i class="fas fa-info-circle mr-1"></i> Live Chat is not installed. <a class="alert-link" href="modules.php">Install it from the Modules page</a>.</div>
<?php else: ?>
<?php if ($enabled): ?>
<section class="card shadow-sm mb-4 livechat-card" id="visitors-on-site">
  <div class="card-header bg-white py-3 d-flex flex-wrap align-items-center justify-content-between">
    <div class="d-flex align-items-center"><span class="livechat-icon mr-3"><i class="fas fa-users"></i></span><div><h2 class="h5 mb-1 text-gray-900">Visitors on site</h2><small class="text-muted">Visitors who have opened a live-chat conversation.</small></div></div>
    <span class="small text-muted" id="visitor-monitor-status"><i class="fas fa-sync-alt mr-1"></i>Updating…</span>
  </div>
  <div class="table-responsive"><table class="table table-hover mb-0 livechat-visitors-table">
    <thead><tr><th>Name</th><th>Actions</th><th>Visitor's address</th><th>First seen</th><th>Last seen</th><th>Invited by</th><th>Invitation time</th><th>Invitations / Chats</th><th>Misc</th></tr></thead>
    <tbody id="visitor-monitor-body"><tr><td colspan="9" class="text-center text-muted py-4">Loading visitors…</td></tr></tbody>
  </table></div>
  <div class="card-footer bg-white d-flex justify-content-between small text-muted"><span>Set status as "Away"</span><span><span class="visitor-online-dot"></span><?php echo htmlspecialchars(trim((string)($_SESSION['user']['name'] ?? '')) ?: 'Administrator', ENT_QUOTES, 'UTF-8') ?></span></div>
</section>
<script>
(function(){var table=document.getElementById('visitor-monitor-body'),state=document.getElementById('visitor-monitor-status'),endpoint='../modules/livechat/api.php';
function elapsed(timestamp){var seconds=Math.max(0,Math.floor(Date.now()/1000)-Number(timestamp||0)),hours=Math.floor(seconds/3600),minutes=Math.floor(seconds%3600/60);return(hours?hours+':':'')+String(minutes).padStart(2,'0')+':'+String(seconds%60).padStart(2,'0')}
function addCell(row,text,className){var cell=document.createElement('td');cell.textContent=text;if(className)cell.className=className;row.appendChild(cell);return cell}
function render(items){table.innerHTML='';if(!items.length){var empty=document.createElement('tr');addCell(empty,'There are no live-chat visitors yet.','text-center text-muted py-4').colSpan=9;table.appendChild(empty);return}items.forEach(function(visitor){var row=document.createElement('tr'),name=addCell(row,'');var link=document.createElement('a');link.href='livechat.php?conversation='+encodeURIComponent(visitor.id);link.textContent=visitor.visitor_name||'Guest';name.appendChild(link);var action=addCell(row,'');var open=document.createElement('a');open.href=link.href;open.className='visitor-chat-action';open.title='Open conversation';open.setAttribute('aria-label','Open conversation with '+(visitor.visitor_name||'Guest'));open.innerHTML='<i class="fas fa-hourglass-half"></i>';action.appendChild(open);addCell(row,visitor.ip_address||'—');addCell(row,elapsed(visitor.created_at));addCell(row,elapsed(visitor.updated_at));addCell(row,'—');addCell(row,'—');addCell(row,visitor.status==='open'?'0 / 1':'0 / 0');addCell(row,visitor.status==='open'?'Online':'Chat closed',visitor.status==='open'?'text-success':'text-muted');table.appendChild(row)})}
function refresh(){fetch(endpoint,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'operator_list'})}).then(function(response){return response.json().then(function(json){if(!response.ok)throw Error(json.error||'Unable to load visitors.');return json})}).then(function(result){render(result.conversations||[]);state.innerHTML='<i class="fas fa-check-circle text-success mr-1"></i>Up to date'}).catch(function(error){state.textContent=error.message})}
refresh();setInterval(refresh,3000)})();
</script>
<?php endif; ?>
<section class="card shadow-sm mb-4 livechat-card"><div class="card-header bg-white py-3 d-flex flex-wrap align-items-center justify-content-between"><div class="d-flex align-items-center"><span class="livechat-icon mr-3"><i class="fas fa-comment-dots"></i></span><div><h2 class="h5 mb-1 text-gray-900">Chat widget <span class="badge <?php echo $enabled?'badge-success':'badge-secondary' ?> ml-1"><?php echo $enabled?'Enabled':'Disabled' ?></span></h2><div class="small text-muted">Control availability and the customer-facing chat color.</div></div></div><button class="btn btn-sm btn-outline-primary livechat-manage mt-2 mt-sm-0" data-toggle="collapse" data-target="#widget-settings" aria-controls="widget-settings" aria-expanded="true"><i class="fas fa-cog mr-1"></i>Manage<i class="fas fa-chevron-down ml-2 livechat-chevron"></i></button></div><div class="collapse show" id="widget-settings"><div class="card-body"><form method="post" class="d-flex flex-column flex-md-row align-items-md-center justify-content-between"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($livechat_settings_csrf, ENT_QUOTES, 'UTF-8') ?>"><div class="custom-control custom-switch mb-3 mb-md-0"><input class="custom-control-input" id="livechat-enabled" type="checkbox" name="livechat_enabled" value="1" <?php echo $enabled?'checked':'' ?>><label class="custom-control-label font-weight-bold" for="livechat-enabled">Enable customer chat box</label><small class="d-block text-muted">When disabled, the widget is removed from every public page.</small></div><div class="form-group d-flex align-items-center mb-3 mb-md-0 mx-md-4"><label class="font-weight-bold mb-0 mr-2" for="livechat-color">Chat box color</label><input class="form-control p-1" id="livechat-color" type="color" name="livechat_color" value="<?php echo htmlspecialchars($livechat_color, ENT_QUOTES, 'UTF-8') ?>" style="width:56px;height:40px" aria-describedby="livechat-color-help"><small class="text-muted ml-2" id="livechat-color-help">Buttons and header</small></div><button class="btn btn-primary" type="submit" name="save_livechat" value="1"><i class="fas fa-save mr-1"></i>Save settings</button></form></div></div></section>
<section class="card shadow-sm mb-4 livechat-card"><div class="card-header bg-white py-3 d-flex flex-wrap align-items-center justify-content-between"><div class="d-flex align-items-center"><span class="livechat-icon mr-3"><i class="fas fa-code"></i></span><div><h2 class="h5 mb-1 text-gray-900">Website embed code</h2><div class="small text-muted">Add live chat to another website.</div></div></div><button class="btn btn-sm btn-outline-primary livechat-manage mt-2 mt-sm-0" data-toggle="collapse" data-target="#embed-settings" aria-controls="embed-settings" aria-expanded="false"><i class="fas fa-cog mr-1"></i>Manage<i class="fas fa-chevron-down ml-2 livechat-chevron"></i></button></div><div class="collapse" id="embed-settings"><div class="card-body"><p class="text-muted">Copy this code and paste it before the closing <code>&lt;/body&gt;</code> tag on your other website.</p><div class="input-group"><textarea class="form-control bg-light no-tinymce" id="livechat-embed-code" rows="2" readonly spellcheck="false" onclick="this.select()"><?php echo htmlspecialchars($livechat_embed_code, ENT_QUOTES, 'UTF-8') ?></textarea><div class="input-group-append"><button class="btn btn-primary" type="button" id="copy-livechat-embed" title="Copy embed code"><i class="fas fa-copy mr-1"></i> Copy</button></div></div><small class="form-text text-muted">Add <code>data-position=&quot;left&quot;</code> to the script tag to place the chat on the left.</small></div></div></section>
<script>document.getElementById('copy-livechat-embed').addEventListener('click',function(){var button=this,code=document.getElementById('livechat-embed-code'),original='<i class="fas fa-copy mr-1"></i> Copy';function copied(){button.innerHTML='<i class="fas fa-check mr-1"></i> Copied';setTimeout(function(){button.innerHTML=original},1500)}if(navigator.clipboard&&window.isSecureContext){navigator.clipboard.writeText(code.value).then(copied)}else{code.select();document.execCommand('copy');copied()}});</script>
<?php endif; ?>
<?php if ($installed): ?>
<section class="card shadow-sm mb-4 livechat-card" id="chat-history">
  <div class="card-header bg-white py-3 d-flex flex-wrap align-items-center justify-content-between"><div class="d-flex align-items-center"><span class="livechat-icon mr-3"><i class="fas fa-history"></i></span><div><h2 class="h5 mb-1 text-gray-900">Chat history <span class="badge badge-light border ml-1"><?php echo (int)$history_total ?></span></h2><small class="text-muted">Search and review closed visitor conversations.</small></div></div></div>
  <div class="card-body border-top"><form method="get" action="livechatsettings.php#chat-history" class="form-row align-items-end"><div class="form-group col-md-8 col-lg-6 mb-md-0"><label for="history-search">Visitor name, email, address, or message</label><input class="form-control" id="history-search" type="search" name="history_search" value="<?php echo htmlspecialchars($history_search,ENT_QUOTES,'UTF-8') ?>" placeholder="Search chat history"></div><div class="form-group col-md-4 col-lg-2 mb-md-0"><button class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i>Search</button></div><?php if ($history_search !== ''): ?><div class="form-group col-md-4 col-lg-2 mb-0"><a class="btn btn-light btn-block" href="livechatsettings.php#chat-history">Clear</a></div><?php endif; ?></form></div>
  <div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Visitor</th><th>Address</th><th>Department</th><th>Operator</th><th>Visitor messages</th><th>Time in chat</th><th>Closed</th><th class="text-right">Log</th></tr></thead><tbody>
  <?php $history_count=0; while ($history_rows && ($history=mysql_fetch_array($history_rows,MYSQLI_ASSOC))): $history_count++; $history_duration=max(0,(int)$history['updated_at']-(int)$history['created_at']); ?>
    <tr><td><strong><?php echo htmlspecialchars($history['visitor_name']?:'Guest',ENT_QUOTES,'UTF-8') ?></strong><?php if ($history['visitor_email']): ?><small class="d-block text-muted"><?php echo htmlspecialchars($history['visitor_email'],ENT_QUOTES,'UTF-8') ?></small><?php endif; ?></td><td><?php echo htmlspecialchars($history['ip_address']?:'—',ENT_QUOTES,'UTF-8') ?></td><td><?php echo htmlspecialchars($history['department_name']?:'Global',ENT_QUOTES,'UTF-8') ?></td><td><?php echo htmlspecialchars($history['operator_name']?:'—',ENT_QUOTES,'UTF-8') ?></td><td><?php echo (int)$history['visitor_messages'] ?></td><td><?php echo sprintf('%02d:%02d:%02d',floor($history_duration/3600),floor(($history_duration%3600)/60),$history_duration%60) ?></td><td class="text-nowrap"><?php echo date('Y-m-d H:i',(int)$history['updated_at']) ?></td><td class="text-right"><button class="btn btn-sm btn-outline-primary history-open" type="button" data-conversation="<?php echo (int)$history['id'] ?>" title="Open chat log"><i class="fas fa-external-link-alt"></i></button></td></tr>
  <?php endwhile; if (!$history_count): ?><tr><td colspan="8" class="text-center text-muted py-4">No closed conversations found.</td></tr><?php endif; ?>
  </tbody></table></div>
  <?php if ($history_pages > 1): ?><div class="card-footer bg-white d-flex justify-content-between align-items-center"><span class="small text-muted">Page <?php echo $history_page ?> of <?php echo $history_pages ?></span><div><?php $history_query=$history_search!==''?'&history_search='.urlencode($history_search):''; if ($history_page>1): ?><a class="btn btn-sm btn-outline-primary mr-1" href="?history_page=<?php echo $history_page-1,$history_query ?>#chat-history">Previous</a><?php endif; if ($history_page<$history_pages): ?><a class="btn btn-sm btn-outline-primary" href="?history_page=<?php echo $history_page+1,$history_query ?>#chat-history">Next</a><?php endif; ?></div></div><?php endif; ?>
</section>
<script>Array.prototype.forEach.call(document.querySelectorAll('.history-open'),function(button){button.onclick=function(){var id=button.dataset.conversation,popup=window.open('livechatwindow.php?conversation='+id+'&history=1','lynx_history_'+id,'popup=yes,width=520,height=700,resizable=yes,scrollbars=no');if(popup)popup.focus()}});</script>
<?php endif; ?>
<?php if ($blocks_ready): ?>
<section class="card shadow-sm mb-4 livechat-card" id="blocked-visitors">
  <div class="card-header bg-white py-3 d-flex flex-wrap align-items-center justify-content-between"><div class="d-flex align-items-center"><span class="livechat-icon mr-3"><i class="fas fa-user-slash"></i></span><div><h2 class="h5 mb-1 text-gray-900">Blocked users <span class="badge badge-light border ml-1"><?php echo (int)$blocked_total ?></span></h2><small class="text-muted">Review visitors who cannot start or continue a conversation.</small></div></div><button class="btn btn-sm btn-outline-primary livechat-manage mt-2 mt-sm-0" data-toggle="collapse" data-target="#blocked-settings" aria-controls="blocked-settings" aria-expanded="<?php echo isset($_GET['visitor_unblocked'])?'true':'false' ?>"><i class="fas fa-cog mr-1"></i>Manage<i class="fas fa-chevron-down ml-2 livechat-chevron"></i></button></div>
  <div class="collapse <?php echo isset($_GET['visitor_unblocked'])?'show':'' ?>" id="blocked-settings">
  <?php if (isset($_GET['visitor_unblocked'])): ?><div class="alert alert-success rounded-0 mb-0">The visitor has been unblocked.</div><?php endif; ?>
  <div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Visitor</th><th>Email</th><th>IP address</th><th>Blocked by</th><th>Blocked on</th><th class="text-right">Action</th></tr></thead><tbody>
  <?php $blocked_count = 0; while ($blocked_visitors && ($blocked = mysql_fetch_array($blocked_visitors, MYSQLI_ASSOC))): $blocked_count++; ?>
    <tr><td class="font-weight-bold"><?php echo htmlspecialchars($blocked['visitor_name'] ?: 'Unknown visitor', ENT_QUOTES, 'UTF-8') ?></td><td><?php echo htmlspecialchars($blocked['visitor_email'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td><td><?php echo htmlspecialchars($blocked['ip_address'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td><td><?php echo htmlspecialchars($blocked['blocked_by_name'] ?: 'Unknown operator', ENT_QUOTES, 'UTF-8') ?></td><td class="text-nowrap"><?php echo date('Y-m-d H:i', (int)$blocked['created_at']) ?></td><td class="text-right"><form method="post" class="d-inline" onsubmit="return confirm('Unblock this visitor?')"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($livechat_settings_csrf, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="block_id" value="<?php echo (int)$blocked['id'] ?>"><button class="btn btn-sm btn-outline-success" name="unblock_visitor" value="1"><i class="fas fa-unlock mr-1"></i>Unblock</button></form></td></tr>
  <?php endwhile; if (!$blocked_count): ?><tr><td colspan="6" class="text-center text-muted py-4">No blocked live-chat users.</td></tr><?php endif; ?>
  </tbody></table></div></div>
</section>
<?php endif; ?>
<?php if (false && $installed && $enabled): // Conversation console lives on livechat.php. ?>
<div class="row" id="chat-console" data-api="../modules/livechat/api.php"><div class="col-lg-4 mb-4"><div class="card shadow-sm"><div class="card-header font-weight-bold">Conversations</div><div class="list-group list-group-flush" id="chat-list"><div class="p-3 text-muted">Loading…</div></div></div></div><div class="col-lg-8"><div class="card shadow-sm"><div class="card-header d-flex justify-content-between"><strong id="chat-title">Select a conversation</strong><div><button id="chat-block" class="btn btn-sm btn-outline-danger mr-2" hidden><i class="fas fa-ban mr-1"></i>Block user</button><button id="chat-close" class="btn btn-sm btn-outline-secondary" hidden>Close chat</button></div></div><div id="chat-messages" class="p-3 bg-light" style="height:420px;overflow:auto"></div><form id="chat-reply" class="card-footer" hidden><div class="input-group"><div class="input-group-prepend"><button class="btn btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-comment-dots mr-1"></i>Canned</button><div class="dropdown-menu" id="chat-canned-menu"><?php $quick_result = mysql_query("SELECT title,body FROM {$pre}livechat_canned_message WHERE operator_id=0 OR operator_id=" . (int)$_SESSION['user']['id'] . " ORDER BY title"); while ($quick_result && ($quick = mysql_fetch_array($quick_result, MYSQLI_ASSOC))): ?><button class="dropdown-item chat-canned-choice" type="button" data-message="<?php echo htmlspecialchars($quick['body'], ENT_QUOTES, 'UTF-8') ?>"><?php echo htmlspecialchars($quick['title'], ENT_QUOTES, 'UTF-8') ?></button><?php endwhile; ?></div></div><input id="chat-text" class="form-control" maxlength="2000" required placeholder="Type a reply"><div class="input-group-append"><button class="btn btn-primary">Send</button></div></div></form></div></div></div>
<script>
(function(){var c=document.getElementById('chat-console'),list=document.getElementById('chat-list'),box=document.getElementById('chat-messages'),form=document.getElementById('chat-reply'),title=document.getElementById('chat-title'),closeBtn=document.getElementById('chat-close'),blockBtn=document.getElementById('chat-block'),active=0,last=0;
function api(d){return fetch(c.dataset.api,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(d)}).then(function(r){return r.json().then(function(j){if(!r.ok)throw Error(j.error||'Request failed');return j})})}
function loadList(){api({action:'operator_list'}).then(function(r){list.innerHTML='';if(!r.conversations.length)list.innerHTML='<div class="p-3 text-muted">No conversations yet.</div>';r.conversations.forEach(function(x){var b=document.createElement('button');b.type='button';b.className='list-group-item list-group-item-action'+(+x.id===active?' active':'');b.innerHTML='<div class="d-flex justify-content-between"><strong></strong><small></small></div><span class="small d-block"></span><em class="small"></em>';b.querySelector('strong').textContent=x.visitor_name;b.querySelector('small').textContent=x.status;b.querySelector('span').textContent=x.last_message||'New conversation';b.querySelector('em').textContent=x.department_name||'No department';b.onclick=function(){active=+x.id;last=0;box.innerHTML='';title.textContent=x.visitor_name+(x.visitor_email?' · '+x.visitor_email:'')+(x.department_name?' · '+x.department_name:'');form.hidden=false;closeBtn.hidden=false;blockBtn.hidden=false;poll();loadList()};list.appendChild(b)})})}
function poll(){if(!active)return;api({action:'operator_poll',conversation_id:active,after:last}).then(function(r){r.messages.forEach(function(m){var d=document.createElement('div'),meta=document.createElement('div'),body=document.createElement('div'),time=new Date(+m.created_at*1000);d.className='mb-2 p-2 rounded '+(m.sender==='operator'?'bg-primary text-white ml-auto':'bg-white');d.style.maxWidth='80%';meta.className='small font-weight-bold mb-1';meta.style.opacity='.75';meta.textContent=m.sender_name+' · '+time.toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});body.style.whiteSpace='pre-wrap';body.textContent=m.body;d.appendChild(meta);d.appendChild(body);box.appendChild(d);last=Math.max(last,+m.id)});if(r.messages.length)box.scrollTop=box.scrollHeight;form.hidden=r.conversation.status==='closed'}).catch(function(e){console.error(e)})}
form.onsubmit=function(e){e.preventDefault();var i=document.getElementById('chat-text');api({action:'operator_send',conversation_id:active,body:i.value}).then(function(){i.value='';poll();loadList()}).catch(function(e){alert(e.message)})};Array.prototype.forEach.call(document.querySelectorAll('.chat-canned-choice'),function(b){b.onclick=function(){var i=document.getElementById('chat-text');i.value=b.dataset.message;i.focus()}});closeBtn.onclick=function(){if(active&&confirm('Close this conversation?'))api({action:'operator_close',conversation_id:active}).then(function(){form.hidden=true;loadList()})};blockBtn.onclick=function(){if(active&&confirm('Block this user from live chat? This also closes the conversation.'))api({action:'operator_block',conversation_id:active}).then(function(){form.hidden=true;blockBtn.hidden=true;loadList()}).catch(function(e){alert(e.message)})};loadList();setInterval(function(){loadList();poll()},3000)})();
</script>
<?php endif; ?>
<?php if ($canned_ready): ?>
<section class="card shadow-sm mb-4 livechat-card" id="canned-messages">
  <div class="card-header bg-white py-3 d-flex flex-wrap align-items-center justify-content-between"><div class="d-flex align-items-center"><span class="livechat-icon mr-3"><i class="fas fa-comment-alt"></i></span><div><h2 class="h5 mb-1 text-gray-900">Canned messages <span class="badge badge-light border ml-1"><?php echo (int)$canned_total ?></span></h2><small class="text-muted">Reusable replies for live-chat operators.</small></div></div><button class="btn btn-sm btn-outline-primary livechat-manage mt-2 mt-sm-0" type="button" data-toggle="collapse" data-target="#canned-settings" aria-controls="canned-settings" aria-expanded="<?php echo $canned_panel_open?'true':'false' ?>"><i class="fas fa-cog mr-1"></i>Manage<i class="fas fa-chevron-down ml-2 livechat-chevron"></i></button></div>
  <div class="collapse <?php echo $canned_panel_open?'show':'' ?>" id="canned-settings"><div class="card-body border-bottom text-right"><button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#canned-editor"><i class="fas fa-plus mr-1"></i>Add message</button></div>
  <?php if (isset($_GET['canned_saved'])): ?><div class="alert alert-success rounded-0 mb-0">Canned message saved.</div><?php elseif (isset($_GET['canned_deleted'])): ?><div class="alert alert-success rounded-0 mb-0">Canned message removed.</div><?php endif; ?>
  <div class="collapse <?php echo $canned_edit || isset($_POST['save_canned']) ? 'show' : '' ?>" id="canned-editor"><div class="card-body border-bottom"><form method="post" id="canned-message-form"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($livechat_settings_csrf, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="canned_id" value="<?php echo (int)($canned_edit['id'] ?? 0) ?>"><div class="form-row"><div class="form-group col-md-4"><label for="canned-title">Title</label><input class="form-control" id="canned-title" name="canned_title" maxlength="120" required value="<?php echo htmlspecialchars($canned_edit['title'] ?? ($_POST['canned_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div><div class="form-group col-md-3"><label for="canned-language">Language</label><input class="form-control" id="canned-language" name="canned_language" maxlength="40" value="<?php echo htmlspecialchars($canned_edit['language'] ?? ($_POST['canned_language'] ?? 'English'), ENT_QUOTES, 'UTF-8') ?>"></div><div class="form-group col-md-5"><label for="canned-operator">Available to</label><select class="form-control" id="canned-operator" name="canned_operator"><option value="0">All operators</option><?php foreach ($operator_options as $operator): ?><option value="<?php echo (int)$operator['id'] ?>" <?php echo (int)($canned_edit['operator_id'] ?? ($_POST['canned_operator'] ?? 0)) === (int)$operator['id'] ? 'selected' : '' ?>><?php echo htmlspecialchars($operator['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div></div><div class="form-group"><label for="canned-body">Message</label><textarea class="form-control" id="canned-body" name="canned_body" maxlength="2000" rows="4"><?php echo htmlspecialchars($canned_edit['body'] ?? ($_POST['canned_body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea></div><div class="text-right"><?php if ($canned_edit): ?><a class="btn btn-light mr-2" href="livechat.php#canned-messages">Cancel</a><?php endif; ?><button class="btn btn-primary" type="submit" name="save_canned" value="1" onclick="if(window.tinymce)tinymce.triggerSave()"><i class="fas fa-save mr-1"></i>Save message</button></div></form></div></div>
  <div class="card-body pb-2"><form method="get" action="livechat.php#canned-messages" class="form-row align-items-end"><div class="form-group col-sm-5 col-lg-3"><label for="filter-language">Language</label><select class="form-control form-control-sm" id="filter-language" name="canned_language"><option value="">All languages</option><?php while ($canned_languages && ($language = mysql_fetch_array($canned_languages, MYSQLI_ASSOC))): ?><option <?php echo $language_filter === $language['language'] ? 'selected' : '' ?>><?php echo htmlspecialchars($language['language'], ENT_QUOTES, 'UTF-8') ?></option><?php endwhile; ?></select></div><div class="form-group col-sm-5 col-lg-3"><label for="filter-operator">Operator</label><select class="form-control form-control-sm" id="filter-operator" name="canned_operator"><option value="-1">All operators</option><option value="0" <?php echo $operator_filter === 0 ? 'selected' : '' ?>>Shared</option><?php foreach ($operator_options as $operator): ?><option value="<?php echo (int)$operator['id'] ?>" <?php echo $operator_filter === (int)$operator['id'] ? 'selected' : '' ?>><?php echo htmlspecialchars($operator['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div><div class="form-group col-sm-2"><button class="btn btn-sm btn-outline-primary btn-block">Filter</button></div></form></div>
  <div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Title</th><th>Message</th><th>Language</th><th>Operator</th><th class="text-right">Modify</th></tr></thead><tbody><?php $canned_count = 0; while ($canned_messages && ($canned = mysql_fetch_array($canned_messages, MYSQLI_ASSOC))): $canned_count++; ?><tr><td class="font-weight-bold"><?php echo htmlspecialchars($canned['title'], ENT_QUOTES, 'UTF-8') ?></td><td class="canned-preview"><?php echo htmlspecialchars(livechat_canned_plain_text($canned['body']), ENT_QUOTES, 'UTF-8') ?></td><td><?php echo htmlspecialchars($canned['language'], ENT_QUOTES, 'UTF-8') ?></td><td><?php echo $canned['operator_id'] ? htmlspecialchars($canned['operator_name'] ?: 'Unknown operator', ENT_QUOTES, 'UTF-8') : 'All operators' ?></td><td class="text-right text-nowrap"><a class="btn btn-sm btn-outline-primary mr-1" href="livechat.php?edit_canned=<?php echo (int)$canned['id'] ?>#canned-messages"><i class="fas fa-pen mr-1"></i>Edit</a><form method="post" class="d-inline" onsubmit="return confirm('Remove this canned message?')"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($livechat_settings_csrf, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="canned_id" value="<?php echo (int)$canned['id'] ?>"><button class="btn btn-sm btn-outline-danger" name="delete_canned" value="1"><i class="fas fa-trash mr-1"></i>Remove</button></form></td></tr><?php endwhile; if (!$canned_count): ?><tr><td colspan="5" class="text-center text-muted py-4">No canned messages match these filters.</td></tr><?php endif; ?></tbody></table></div></div>
</section>
<script>
Array.prototype.forEach.call(document.querySelectorAll('a[href^="livechat.php"][href*="canned"], form[action^="livechat.php"]'), function (element) {
  var attribute = element.tagName === 'FORM' ? 'action' : 'href';
  element.setAttribute(attribute, element.getAttribute(attribute).replace('livechat.php', 'livechatsettings.php'));
});
</script>
<?php endif; ?>
<style>
.livechat-card{border:0;border-radius:.75rem;overflow:hidden}.livechat-card .card-header{border-bottom:0}.livechat-card .collapse.show{border-top:1px solid #e3e6f0}.livechat-icon{display:grid;place-items:center;flex:0 0 42px;width:42px;height:42px;border-radius:10px;background:#eef2ff;color:#4e73df}.livechat-card .table thead th{border-top:1px solid #e3e6f0;background:#f8f9fc;color:#5a5c69;font-size:.75rem;text-transform:uppercase;letter-spacing:.03em}.livechat-card .table td{vertical-align:middle}.livechat-card textarea[readonly]{font-family:SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono",monospace;font-size:.85rem}.livechat-card .badge{vertical-align:middle;font-size:.68rem}.livechat-chevron{font-size:.65rem;transition:transform .2s}.livechat-manage[aria-expanded="true"] .livechat-chevron{transform:rotate(180deg)}.canned-preview{max-width:380px;white-space:pre-wrap;line-height:1.45;color:#5a6172}.livechat-visitors-table{font-size:.82rem}.livechat-visitors-table th{white-space:nowrap}.visitor-chat-action{color:#e49a00}.visitor-online-dot{display:inline-block;width:8px;height:8px;margin-right:5px;border-radius:50%;background:#3fb950}
@media(max-width:575.98px){.livechat-card .card-header>button{width:100%;margin-left:3.6rem}.livechat-card .input-group{display:block}.livechat-card .input-group textarea{width:100%;border-radius:.35rem}.livechat-card .input-group-append{margin-top:.5rem}.livechat-card .input-group-append .btn{border-radius:.35rem}}
</style>
<?php include './include/footer.php'; ?>
