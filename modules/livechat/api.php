<?php
include __DIR__ . '/../../include/settings.php';
include __DIR__ . '/../../include/include.php';
include __DIR__ . '/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

function lc_reply($data, $status = 200) { http_response_code($status); echo json_encode($data); exit; }
function lc_body() { $raw = json_decode(file_get_contents('php://input'), true); return is_array($raw) ? $raw : $_POST; }
function lc_conversation($token) {
    global $pre; $token = livechat_escape($token);
    $res = mysql_query("SELECT * FROM {$pre}livechat_conversation WHERE visitor_token='$token' LIMIT 1");
    return $res ? mysql_fetch_array($res, MYSQLI_ASSOC) : false;
}
function lc_messages($id, $after = 0) {
    global $pre; $items = array(); $id = (int)$id; $after = (int)$after;
    $res = mysql_query("SELECT m.id,m.sender,m.body,m.created_at,
        CASE WHEN m.sender='visitor' THEN c.visitor_name ELSE COALESCE(NULLIF(u.name,''),'Support') END sender_name
        FROM {$pre}livechat_message m
        INNER JOIN {$pre}livechat_conversation c ON c.id=m.conversation_id
        LEFT JOIN {$pre}user u ON m.sender='operator' AND u.id=m.sender_id
        WHERE m.conversation_id=$id AND m.id>$after ORDER BY m.id ASC LIMIT 200");
    while ($res && ($row = mysql_fetch_array($res, MYSQLI_ASSOC))) { $row['id']=(int)$row['id']; $row['created_at']=(int)$row['created_at']; $items[]=$row; }
    return $items;
}
function lc_ip_address() {
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}
function lc_is_blocked($token = '', $email = '') {
    global $pre;
    $conditions = array();
    $token = trim((string)$token); $email = trim((string)$email); $ip = lc_ip_address();
    if ($token !== '') $conditions[] = "visitor_token='" . livechat_escape($token) . "'";
    if ($email !== '') $conditions[] = "visitor_email='" . livechat_escape($email) . "'";
    if ($ip !== '') $conditions[] = "ip_address='" . livechat_escape($ip) . "'";
    return $conditions && get_row_count("SELECT COUNT(*) FROM {$pre}livechat_block WHERE " . implode(' OR ', $conditions)) > 0;
}

if (!livechat_installed()) lc_reply(array('error'=>'Live chat is not installed.'), 503);
if (!livechat_ensure_department()) lc_reply(array('error'=>'Live chat could not prepare department selection.'), 503);
if (!livechat_ensure_blocks()) lc_reply(array('error'=>'Live chat could not prepare user blocking.'), 503);
if (!livechat_enabled()) lc_reply(array('error'=>'Live chat is disabled.'), 403);
$data = lc_body(); $action = $data['action'] ?? 'poll';
$operator_id = (int)($_SESSION['user']['id'] ?? 0);
$is_operator = (($_SESSION['login_type'] ?? $LOGIN_INVALID) == $LOGIN_USER)
    && $operator_id > 0
    && get_row_count("SELECT COUNT(*) FROM {$pre}privilege WHERE user_id=$operator_id AND dept_id=0") > 0;

if ($action === 'operator_list' || $action === 'operator_poll' || $action === 'operator_send' || $action === 'operator_close' || $action === 'operator_block') {
    if (!$is_operator) lc_reply(array('error'=>'Authentication required.'), 401);
    if ($action === 'operator_list') {
        $items=array(); $res=mysql_query("SELECT c.*,d.name department_name,(SELECT body FROM {$pre}livechat_message m WHERE m.conversation_id=c.id ORDER BY m.id DESC LIMIT 1) last_message FROM {$pre}livechat_conversation c LEFT JOIN {$pre}dept d ON d.id=c.dept_id ORDER BY (c.status='open') DESC,c.updated_at DESC LIMIT 100");
        while ($res && ($row=mysql_fetch_array($res, MYSQLI_ASSOC))) $items[]=$row;
        lc_reply(array('conversations'=>$items));
    }
    $id=(int)($data['conversation_id'] ?? 0); $res=mysql_query("SELECT * FROM {$pre}livechat_conversation WHERE id=$id LIMIT 1"); $conversation=$res?mysql_fetch_array($res, MYSQLI_ASSOC):false;
    if (!$conversation) lc_reply(array('error'=>'Conversation not found.'), 404);
    if ($action === 'operator_poll') lc_reply(array('conversation'=>$conversation,'messages'=>lc_messages($id,(int)($data['after']??0))));
    if ($action === 'operator_close') { mysql_query("UPDATE {$pre}livechat_conversation SET status='closed',updated_at=".time()." WHERE id=$id"); lc_reply(array('ok'=>true)); }
    if ($action === 'operator_block') {
        $token=livechat_escape($conversation['visitor_token']); $email=livechat_escape($conversation['visitor_email']); $ip=livechat_escape((string)($conversation['ip_address'] ?? '')); $uid=(int)$_SESSION['user']['id']; $now=time();
        if (!get_row_count("SELECT COUNT(*) FROM {$pre}livechat_block WHERE visitor_token='$token'")) mysql_query("INSERT INTO {$pre}livechat_block (conversation_id,visitor_token,visitor_email,ip_address,blocked_by,created_at) VALUES ($id,'$token','$email','$ip',$uid,$now)");
        mysql_query("UPDATE {$pre}livechat_conversation SET status='closed',updated_at=$now WHERE id=$id"); lc_reply(array('ok'=>true));
    }
    $body=trim((string)($data['body']??'')); if ($body==='' || strlen($body)>2000) lc_reply(array('error'=>'Enter a message up to 2000 characters.'),422);
    $body=livechat_escape($body); $uid=(int)$_SESSION['user']['id']; $now=time(); mysql_query("INSERT INTO {$pre}livechat_message (conversation_id,sender,sender_id,body,created_at) VALUES ($id,'operator',$uid,'$body',$now)"); mysql_query("UPDATE {$pre}livechat_conversation SET status='open',updated_at=$now WHERE id=$id"); lc_reply(array('ok'=>true));
}

$token=(string)($data['token']??'');
if ($action === 'start') {
    $name=trim((string)($data['name']??'')); $email=trim((string)($data['email']??'')); $department_id=(int)($data['department_id']??0);
    $department_exists=$department_id>0 && get_row_count("SELECT COUNT(*) FROM {$pre}dept WHERE id=$department_id")>0;
    if ($name==='' || strlen($name)>100 || ($email!=='' && !filter_var($email,FILTER_VALIDATE_EMAIL)) || !$department_exists) lc_reply(array('error'=>'Enter your name, a valid email address, and choose a department.'),422);
    if (lc_is_blocked('', $email)) lc_reply(array('error'=>'You are not permitted to start a chat.'),403);
    $token=bin2hex(random_bytes(32)); $safeToken=livechat_escape($token); $name=livechat_escape($name); $email=livechat_escape($email); $ip=livechat_escape(lc_ip_address()); $now=time();
    mysql_query("INSERT INTO {$pre}livechat_conversation (visitor_token,visitor_name,visitor_email,dept_id,ip_address,created_at,updated_at) VALUES ('$safeToken','$name','$email',$department_id,'$ip',$now,$now)");
    $conversation_id=(int)mysql_insert_id();
    $welcome=livechat_escape('Thank you for contacting us. We will be in touch shortly.');
    mysql_query("INSERT INTO {$pre}livechat_message (conversation_id,sender,body,created_at) VALUES ($conversation_id,'operator','$welcome',$now)");
    lc_reply(array('token'=>$token,'conversation_id'=>$conversation_id,'messages'=>lc_messages($conversation_id)));
}
if (!preg_match('/^[a-f0-9]{64}$/',$token) || !($conversation=lc_conversation($token))) lc_reply(array('error'=>'Chat session not found.'),404);
$id=(int)$conversation['id'];
if (lc_is_blocked($token, $conversation['visitor_email'])) lc_reply(array('error'=>'You are not permitted to use chat.'),403);
if ($action === 'poll') lc_reply(array('status'=>$conversation['status'],'messages'=>lc_messages($id,(int)($data['after']??0))));
if ($action === 'send') {
    if ($conversation['status'] !== 'open') lc_reply(array('error'=>'This chat is closed.'),409);
    $body=trim((string)($data['body']??'')); if ($body==='' || strlen($body)>2000) lc_reply(array('error'=>'Enter a message up to 2000 characters.'),422);
    $now=time(); $body=livechat_escape($body); mysql_query("INSERT INTO {$pre}livechat_message (conversation_id,sender,body,created_at) VALUES ($id,'visitor','$body',$now)"); mysql_query("UPDATE {$pre}livechat_conversation SET updated_at=$now WHERE id=$id"); lc_reply(array('ok'=>true));
}
lc_reply(array('error'=>'Unknown action.'),400);
