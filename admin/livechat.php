<?php
include '../include/settings.php';
include '../include/include.php';
include '../modules/livechat/bootstrap.php';
include '../modules/system.php';
$HD_CURPAGE = 'livechat.php';
if (($_SESSION['login_type'] ?? $LOGIN_INVALID) == $LOGIN_INVALID) { header('Location: index.php?redirect=livechat.php'); exit; }
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
    if (get_row_count("SELECT COUNT(*) FROM {$pre}options WHERE name='livechat_enabled'")) mysql_query("UPDATE {$pre}options SET text='$enabled' WHERE name='livechat_enabled'");
    else mysql_query("INSERT INTO {$pre}options (name,text) VALUES ('livechat_enabled','$enabled')");
    hd_module_sync('livechat', true, $enabled === '1');
    $msg = '<div class="alert alert-success">Live chat setting saved.</div>';
} elseif (isset($_POST['save_canned']) && livechat_installed() && livechat_ensure_canned_messages()) {
    $canned_id = (int)($_POST['canned_id'] ?? 0);
    $title = trim((string)($_POST['canned_title'] ?? ''));
    $body = trim((string)($_POST['canned_body'] ?? ''));
    $language = trim((string)($_POST['canned_language'] ?? 'English'));
    $operator_id = max(0, (int)($_POST['canned_operator'] ?? 0));
    if ($title === '' || $body === '' || strlen($title) > 120 || strlen($body) > 2000 || strlen($language) > 40) {
        $msg = '<div class="alert alert-danger">Enter a title and a message (maximum 120 and 2,000 characters).</div>';
    } else {
        $title_sql = livechat_escape($title); $body_sql = livechat_escape($body); $language_sql = livechat_escape($language === '' ? 'English' : $language); $now = time();
        if ($canned_id) mysql_query("UPDATE {$pre}livechat_canned_message SET title='$title_sql',body='$body_sql',language='$language_sql',operator_id=$operator_id,updated_at=$now WHERE id=$canned_id");
        else mysql_query("INSERT INTO {$pre}livechat_canned_message (title,body,language,operator_id,created_at,updated_at) VALUES ('$title_sql','$body_sql','$language_sql',$operator_id,$now,$now)");
        header('Location: livechat.php?canned_saved=1#canned-messages'); exit;
    }
} elseif (isset($_POST['delete_canned']) && livechat_installed() && livechat_ensure_canned_messages()) {
    $canned_id = (int)($_POST['canned_id'] ?? 0);
    if ($canned_id) mysql_query("DELETE FROM {$pre}livechat_canned_message WHERE id=$canned_id");
    header('Location: livechat.php?canned_deleted=1#canned-messages'); exit;
}
$installed = livechat_installed();
$department_ready = $installed && livechat_ensure_department();
$canned_ready = $installed && livechat_ensure_canned_messages();
$enabled = $installed && livechat_enabled();
$canned_edit = false;
if ($canned_ready && isset($_GET['edit_canned'])) {
    $edit_id = (int)$_GET['edit_canned'];
    $edit_result = mysql_query("SELECT * FROM {$pre}livechat_canned_message WHERE id=$edit_id LIMIT 1");
    $canned_edit = $edit_result ? mysql_fetch_array($edit_result, MYSQLI_ASSOC) : false;
}
$language_filter = trim((string)($_GET['canned_language'] ?? ''));
$operator_filter = isset($_GET['canned_operator']) ? (int)$_GET['canned_operator'] : -1;
$canned_where = array();
if ($language_filter !== '') $canned_where[] = "language='" . livechat_escape($language_filter) . "'";
if ($operator_filter >= 0) $canned_where[] = "operator_id=$operator_filter";
$canned_query = "SELECT c.*,u.name operator_name FROM {$pre}livechat_canned_message c LEFT JOIN {$pre}user u ON u.id=c.operator_id" . ($canned_where ? ' WHERE ' . implode(' AND ', $canned_where) : '') . ' ORDER BY c.title';
$canned_messages = $canned_ready ? mysql_query($canned_query) : false;
$canned_languages = $canned_ready ? mysql_query("SELECT DISTINCT language FROM {$pre}livechat_canned_message ORDER BY language") : false;
$operators = mysql_query("SELECT id,name FROM {$pre}user ORDER BY name");
$operator_options = array(); while ($operators && ($operator_row = mysql_fetch_array($operators, MYSQLI_ASSOC))) $operator_options[] = $operator_row;
$script_name = 'Live Chat';
include './include/header.php';
?>
<div class="d-sm-flex align-items-center justify-content-between mb-4"><div><h1 class="h3 mb-1 text-gray-800">Live chat</h1><p class="mb-0 text-muted">Talk to visitors from the support site in real time.</p></div></div>
<?php echo $msg ?>
<?php if (!$installed): ?>
<div class="alert alert-info shadow-sm"><i class="fas fa-info-circle mr-1"></i> Live Chat is not installed. <a class="alert-link" href="modules.php">Install it from the Modules page</a>.</div>
<?php else: ?>
<div class="card shadow-sm mb-4"><div class="card-body"><form method="post" class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($livechat_settings_csrf, ENT_QUOTES, 'UTF-8') ?>"><div class="custom-control custom-switch mb-3 mb-sm-0"><input class="custom-control-input" id="livechat-enabled" type="checkbox" name="livechat_enabled" value="1" <?php echo $enabled?'checked':'' ?>><label class="custom-control-label font-weight-bold" for="livechat-enabled">Enable customer chat box</label><small class="d-block text-muted">When disabled, the widget is removed from every public page.</small></div><button class="btn btn-primary" type="submit" name="save_livechat" value="1"><i class="fas fa-save mr-1"></i>Save</button></form></div></div>
<?php endif; ?>
<?php if ($installed && $enabled): ?>
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
<section class="card shadow-sm mb-4" id="canned-messages">
  <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between"><div><h2 class="h6 m-0 font-weight-bold text-primary">Canned messages</h2><small class="text-muted">Reusable replies for live-chat operators.</small></div><button class="btn btn-sm btn-primary mt-2 mt-sm-0" type="button" data-toggle="collapse" data-target="#canned-editor"><i class="fas fa-plus mr-1"></i>Add message</button></div>
  <?php if (isset($_GET['canned_saved'])): ?><div class="alert alert-success rounded-0 mb-0">Canned message saved.</div><?php elseif (isset($_GET['canned_deleted'])): ?><div class="alert alert-success rounded-0 mb-0">Canned message removed.</div><?php endif; ?>
  <div class="collapse <?php echo $canned_edit || isset($_POST['save_canned']) ? 'show' : '' ?>" id="canned-editor"><div class="card-body border-bottom"><form method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($livechat_settings_csrf, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="canned_id" value="<?php echo (int)($canned_edit['id'] ?? 0) ?>"><div class="form-row"><div class="form-group col-md-4"><label for="canned-title">Title</label><input class="form-control" id="canned-title" name="canned_title" maxlength="120" required value="<?php echo htmlspecialchars($canned_edit['title'] ?? ($_POST['canned_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div><div class="form-group col-md-3"><label for="canned-language">Language</label><input class="form-control" id="canned-language" name="canned_language" maxlength="40" value="<?php echo htmlspecialchars($canned_edit['language'] ?? ($_POST['canned_language'] ?? 'English'), ENT_QUOTES, 'UTF-8') ?>"></div><div class="form-group col-md-5"><label for="canned-operator">Available to</label><select class="form-control" id="canned-operator" name="canned_operator"><option value="0">All operators</option><?php foreach ($operator_options as $operator): ?><option value="<?php echo (int)$operator['id'] ?>" <?php echo (int)($canned_edit['operator_id'] ?? ($_POST['canned_operator'] ?? 0)) === (int)$operator['id'] ? 'selected' : '' ?>><?php echo htmlspecialchars($operator['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div></div><div class="form-group"><label for="canned-body">Message</label><textarea class="form-control" id="canned-body" name="canned_body" maxlength="2000" rows="4" required><?php echo htmlspecialchars($canned_edit['body'] ?? ($_POST['canned_body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea></div><div class="text-right"><?php if ($canned_edit): ?><a class="btn btn-light mr-2" href="livechat.php#canned-messages">Cancel</a><?php endif; ?><button class="btn btn-primary" name="save_canned" value="1"><i class="fas fa-save mr-1"></i>Save message</button></div></form></div></div>
  <div class="card-body pb-2"><form method="get" action="livechat.php#canned-messages" class="form-row align-items-end"><div class="form-group col-sm-5 col-lg-3"><label for="filter-language">Language</label><select class="form-control form-control-sm" id="filter-language" name="canned_language"><option value="">All languages</option><?php while ($canned_languages && ($language = mysql_fetch_array($canned_languages, MYSQLI_ASSOC))): ?><option <?php echo $language_filter === $language['language'] ? 'selected' : '' ?>><?php echo htmlspecialchars($language['language'], ENT_QUOTES, 'UTF-8') ?></option><?php endwhile; ?></select></div><div class="form-group col-sm-5 col-lg-3"><label for="filter-operator">Operator</label><select class="form-control form-control-sm" id="filter-operator" name="canned_operator"><option value="-1">All operators</option><option value="0" <?php echo $operator_filter === 0 ? 'selected' : '' ?>>Shared</option><?php foreach ($operator_options as $operator): ?><option value="<?php echo (int)$operator['id'] ?>" <?php echo $operator_filter === (int)$operator['id'] ? 'selected' : '' ?>><?php echo htmlspecialchars($operator['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div><div class="form-group col-sm-2"><button class="btn btn-sm btn-outline-primary btn-block">Filter</button></div></form></div>
  <div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Title</th><th>Message</th><th>Language</th><th>Operator</th><th class="text-right">Modify</th></tr></thead><tbody><?php $canned_count = 0; while ($canned_messages && ($canned = mysql_fetch_array($canned_messages, MYSQLI_ASSOC))): $canned_count++; ?><tr><td class="font-weight-bold"><?php echo htmlspecialchars($canned['title'], ENT_QUOTES, 'UTF-8') ?></td><td style="white-space:pre-wrap"><?php echo htmlspecialchars($canned['body'], ENT_QUOTES, 'UTF-8') ?></td><td><?php echo htmlspecialchars($canned['language'], ENT_QUOTES, 'UTF-8') ?></td><td><?php echo $canned['operator_id'] ? htmlspecialchars($canned['operator_name'] ?: 'Unknown operator', ENT_QUOTES, 'UTF-8') : 'All operators' ?></td><td class="text-right text-nowrap"><a class="btn btn-sm btn-outline-primary" href="livechat.php?edit_canned=<?php echo (int)$canned['id'] ?>#canned-messages">Edit</a><form method="post" class="d-inline" onsubmit="return confirm('Remove this canned message?')"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($livechat_settings_csrf, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="canned_id" value="<?php echo (int)$canned['id'] ?>"><button class="btn btn-sm btn-link text-danger" name="delete_canned" value="1">Remove</button></form></td></tr><?php endwhile; if (!$canned_count): ?><tr><td colspan="5" class="text-center text-muted py-4">No canned messages match these filters.</td></tr><?php endif; ?></tbody></table></div>
</section>
<?php endif; include './include/footer.php'; ?>
