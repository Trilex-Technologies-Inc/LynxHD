<?php
include '../include/settings.php';
include '../include/include.php';
include '../modules/livechat/bootstrap.php';
$HD_CURPAGE = 'livechat.php';
if (($_SESSION['login_type'] ?? $LOGIN_INVALID) == $LOGIN_INVALID) { header('Location: index.php?redirect=livechat.php'); exit; }
$global_priv = get_row_count("SELECT COUNT(*) FROM {$pre}privilege WHERE user_id='" . (int)$_SESSION['user']['id'] . "' AND dept_id='0'");
if (!$global_priv) { header("Location: $HD_URL_BROWSE"); exit; }
$livechat_csrf = $_SESSION['livechat_csrf'] ?? '';
if ($livechat_csrf === '') {
    $livechat_csrf = bin2hex(random_bytes(32));
    $_SESSION['livechat_csrf'] = $livechat_csrf;
}
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['csrf_token']) || !hash_equals($livechat_csrf, (string)$_POST['csrf_token']))) {
    $msg = '<div class="alert alert-danger">The request could not be verified. Please try again.</div>';
} elseif (isset($_POST['install_livechat'])) {
    $msg = livechat_install()
        ? '<div class="alert alert-success">Live chat installed. You can now enable the customer chat box.</div>'
        : '<div class="alert alert-danger">Live chat could not be installed. Check the database permissions.</div>';
} elseif (isset($_POST['uninstall_livechat'])) {
    $msg = livechat_uninstall()
        ? '<div class="alert alert-success">Live chat and all of its data were removed.</div>'
        : '<div class="alert alert-danger">Live chat could not be completely removed. Check the database permissions.</div>';
} elseif (isset($_POST['save_livechat']) && livechat_installed()) {
    $enabled = isset($_POST['livechat_enabled']) ? '1' : '0';
    if (get_row_count("SELECT COUNT(*) FROM {$pre}options WHERE name='livechat_enabled'")) mysql_query("UPDATE {$pre}options SET text='$enabled' WHERE name='livechat_enabled'");
    else mysql_query("INSERT INTO {$pre}options (name,text) VALUES ('livechat_enabled','$enabled')");
    $msg = '<div class="alert alert-success">Live chat setting saved.</div>';
}
$installed = livechat_installed();
$enabled = $installed && livechat_enabled();
$script_name = 'Live Chat';
include './include/header.php';
?>
<div class="d-sm-flex align-items-center justify-content-between mb-4"><div><h1 class="h3 mb-1 text-gray-800">Live chat</h1><p class="mb-0 text-muted">Talk to visitors from the support site in real time.</p></div></div>
<?php echo $msg ?>
<?php if (!$installed): ?>
<div class="card shadow-sm mb-4"><div class="card-body d-flex flex-column flex-sm-row align-items-sm-center justify-content-between"><div class="mb-3 mb-sm-0"><h2 class="h5 mb-1">Install Live Chat</h2><p class="mb-0 text-muted">Creates the conversation and message tables and adds the module setting.</p></div><form method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($livechat_csrf, ENT_QUOTES, 'UTF-8') ?>"><button class="btn btn-success" type="submit" name="install_livechat" value="1"><i class="fas fa-download mr-1"></i>Install</button></form></div></div>
<?php else: ?>
<div class="card shadow-sm mb-4"><div class="card-body"><form method="post" class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($livechat_csrf, ENT_QUOTES, 'UTF-8') ?>"><div class="custom-control custom-switch mb-3 mb-sm-0"><input class="custom-control-input" id="livechat-enabled" type="checkbox" name="livechat_enabled" value="1" <?php echo $enabled?'checked':'' ?>><label class="custom-control-label font-weight-bold" for="livechat-enabled">Enable customer chat box</label><small class="d-block text-muted">When disabled, the widget is removed from every public page.</small></div><button class="btn btn-primary" type="submit" name="save_livechat" value="1"><i class="fas fa-save mr-1"></i>Save</button></form></div></div>
<div class="card border-danger shadow-sm mb-4"><div class="card-body d-flex flex-column flex-sm-row align-items-sm-center justify-content-between"><div class="mb-3 mb-sm-0"><h2 class="h5 text-danger mb-1">Uninstall Live Chat</h2><p class="mb-0 text-muted">Permanently deletes every conversation, message, and module setting.</p></div><form method="post" onsubmit="return confirm('Uninstall Live Chat? All conversations and messages will be permanently deleted.');"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($livechat_csrf, ENT_QUOTES, 'UTF-8') ?>"><button class="btn btn-outline-danger" type="submit" name="uninstall_livechat" value="1"><i class="fas fa-trash-alt mr-1"></i>Uninstall</button></form></div></div>
<?php endif; ?>
<?php if ($installed && $enabled): ?>
<div class="row" id="chat-console" data-api="../modules/livechat/api.php"><div class="col-lg-4 mb-4"><div class="card shadow-sm"><div class="card-header font-weight-bold">Conversations</div><div class="list-group list-group-flush" id="chat-list"><div class="p-3 text-muted">Loading…</div></div></div></div><div class="col-lg-8"><div class="card shadow-sm"><div class="card-header d-flex justify-content-between"><strong id="chat-title">Select a conversation</strong><button id="chat-close" class="btn btn-sm btn-outline-danger" hidden>Close chat</button></div><div id="chat-messages" class="p-3 bg-light" style="height:420px;overflow:auto"></div><form id="chat-reply" class="card-footer d-flex" hidden><input id="chat-text" class="form-control mr-2" maxlength="2000" required placeholder="Type a reply"><button class="btn btn-primary">Send</button></form></div></div></div>
<script>
(function(){var c=document.getElementById('chat-console'),list=document.getElementById('chat-list'),box=document.getElementById('chat-messages'),form=document.getElementById('chat-reply'),title=document.getElementById('chat-title'),closeBtn=document.getElementById('chat-close'),active=0,last=0;
function api(d){return fetch(c.dataset.api,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(d)}).then(function(r){return r.json().then(function(j){if(!r.ok)throw Error(j.error||'Request failed');return j})})}
function loadList(){api({action:'operator_list'}).then(function(r){list.innerHTML='';if(!r.conversations.length)list.innerHTML='<div class="p-3 text-muted">No conversations yet.</div>';r.conversations.forEach(function(x){var b=document.createElement('button');b.type='button';b.className='list-group-item list-group-item-action'+(+x.id===active?' active':'');b.innerHTML='<div class="d-flex justify-content-between"><strong></strong><small></small></div><span class="small"></span>';b.querySelector('strong').textContent=x.visitor_name;b.querySelector('small').textContent=x.status;b.querySelector('span').textContent=x.last_message||'New conversation';b.onclick=function(){active=+x.id;last=0;box.innerHTML='';title.textContent=x.visitor_name+(x.visitor_email?' · '+x.visitor_email:'');form.hidden=false;closeBtn.hidden=false;poll();loadList()};list.appendChild(b)})})}
function poll(){if(!active)return;api({action:'operator_poll',conversation_id:active,after:last}).then(function(r){r.messages.forEach(function(m){var d=document.createElement('div'),meta=document.createElement('div'),body=document.createElement('div'),time=new Date(+m.created_at*1000);d.className='mb-2 p-2 rounded '+(m.sender==='operator'?'bg-primary text-white ml-auto':'bg-white');d.style.maxWidth='80%';meta.className='small font-weight-bold mb-1';meta.style.opacity='.75';meta.textContent=m.sender_name+' · '+time.toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});body.style.whiteSpace='pre-wrap';body.textContent=m.body;d.appendChild(meta);d.appendChild(body);box.appendChild(d);last=Math.max(last,+m.id)});if(r.messages.length)box.scrollTop=box.scrollHeight;form.hidden=r.conversation.status==='closed'}).catch(function(e){console.error(e)})}
form.onsubmit=function(e){e.preventDefault();var i=document.getElementById('chat-text');api({action:'operator_send',conversation_id:active,body:i.value}).then(function(){i.value='';poll();loadList()}).catch(function(e){alert(e.message)})};closeBtn.onclick=function(){if(active&&confirm('Close this conversation?'))api({action:'operator_close',conversation_id:active}).then(function(){form.hidden=true;loadList()})};loadList();setInterval(function(){loadList();poll()},3000)})();
</script>
<?php endif; include './include/footer.php'; ?>
