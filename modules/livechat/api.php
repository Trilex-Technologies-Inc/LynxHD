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
if (!livechat_ensure_visitors()) lc_reply(array('error'=>'Live chat could not prepare visitor monitoring.'), 503);
if (!livechat_ensure_invitations()) lc_reply(array('error'=>'Live chat could not prepare chat invitations.'), 503);
if (!livechat_enabled()) lc_reply(array('error'=>'Live chat is disabled.'), 403);
$data = lc_body(); $action = $data['action'] ?? 'poll';
$operator_id = (int)($_SESSION['user']['id'] ?? 0);
$is_operator = (($_SESSION['login_type'] ?? $LOGIN_INVALID) == $LOGIN_USER)
    && $operator_id > 0
    && get_row_count("SELECT COUNT(*) FROM {$pre}privilege WHERE user_id=$operator_id AND dept_id=0") > 0;

if ($action === 'operator_list' || $action === 'operator_visitors' || $action === 'operator_invite' || $action === 'operator_poll' || $action === 'operator_send' || $action === 'operator_close' || $action === 'operator_reopen' || $action === 'operator_block') {
    if (!$is_operator) lc_reply(array('error'=>'Authentication required.'), 401);
    if ($action === 'operator_list') {
        $items=array(); $res=mysql_query("SELECT c.*,d.name department_name,(SELECT body FROM {$pre}livechat_message m WHERE m.conversation_id=c.id ORDER BY m.id DESC LIMIT 1) last_message FROM {$pre}livechat_conversation c LEFT JOIN {$pre}dept d ON d.id=c.dept_id ORDER BY (c.status='open') DESC,c.updated_at DESC LIMIT 100");
        while ($res && ($row=mysql_fetch_array($res, MYSQLI_ASSOC))) $items[]=$row;
        lc_reply(array('conversations'=>$items));
    }
    if ($action === 'operator_visitors') {
        $now=time(); $active_after=$now-30; $visitors=array(); $waiting=array();
        mysql_query("UPDATE {$pre}livechat_invitation SET status='expired',responded_at=$now WHERE status='pending' AND expires_at<$now");
        $res=mysql_query("SELECT v.*,c.status,c.created_at conversation_created,d.name department_name,
            EXISTS(SELECT 1 FROM {$pre}livechat_message m WHERE m.conversation_id=c.id AND m.sender='operator' AND m.sender_id>0) answered,
            (SELECT i.id FROM {$pre}livechat_invitation i WHERE i.visitor_key=v.visitor_key AND i.status='pending' AND i.expires_at>=$now ORDER BY i.id DESC LIMIT 1) invitation_id,
            (SELECT COUNT(*) FROM {$pre}livechat_invitation i WHERE i.visitor_key=v.visitor_key) invitations
            FROM {$pre}livechat_visitor v
            LEFT JOIN {$pre}livechat_conversation c ON c.id=v.conversation_id
            LEFT JOIN {$pre}dept d ON d.id=c.dept_id
            WHERE v.last_seen >= $active_after ORDER BY v.first_seen ASC LIMIT 200");
        while ($res && ($row=mysql_fetch_array($res, MYSQLI_ASSOC))) {
            foreach (array('id','conversation_id','first_seen','last_seen','chats_started','conversation_created','answered','invitation_id','invitations') as $field) $row[$field]=(int)$row[$field];
            $visitors[]=$row;
            if ($row['conversation_id'] && $row['status']==='open' && !$row['answered']) $waiting[]=$row;
        }
        lc_reply(array('server_time'=>$now,'waiting'=>$waiting,'visitors'=>$visitors));
    }
    if ($action === 'operator_invite') {
        $visitor_id=(int)($data['visitor_id']??0); $now=time();
        $res=mysql_query("SELECT * FROM {$pre}livechat_visitor WHERE id=$visitor_id AND last_seen>=".($now-30)." LIMIT 1");
        $visitor=$res?mysql_fetch_array($res,MYSQLI_ASSOC):false;
        if (!$visitor) lc_reply(array('error'=>'This visitor is no longer online.'),404);
        if ((int)$visitor['conversation_id']>0) lc_reply(array('error'=>'This visitor already has a conversation.'),409);
        $visitor_ip=livechat_escape($visitor['ip_address']);
        if ($visitor_ip!=='' && get_row_count("SELECT COUNT(*) FROM {$pre}livechat_block WHERE ip_address='$visitor_ip'")) lc_reply(array('error'=>'This visitor is blocked.'),409);
        $key=livechat_escape($visitor['visitor_key']);
        if (get_row_count("SELECT COUNT(*) FROM {$pre}livechat_invitation WHERE visitor_key='$key' AND status='pending' AND expires_at>=$now")) lc_reply(array('error'=>'An invitation is already pending.'),409);
        $message=trim((string)($data['message']??'Hello! Would you like to chat with us?'));
        if ($message==='' || strlen($message)>500) lc_reply(array('error'=>'Enter an invitation message up to 500 characters.'),422);
        $message=livechat_escape($message); $expires=$now+120; $uid=(int)$_SESSION['user']['id'];
        if (!mysql_query("INSERT INTO {$pre}livechat_invitation (visitor_id,visitor_key,operator_id,message,created_at,expires_at) VALUES ($visitor_id,'$key',$uid,'$message',$now,$expires)")) lc_reply(array('error'=>'The invitation could not be sent.'),500);
        lc_reply(array('ok'=>true,'invitation_id'=>(int)mysql_insert_id(),'expires_at'=>$expires));
    }
    $id=(int)($data['conversation_id'] ?? 0); $res=mysql_query("SELECT * FROM {$pre}livechat_conversation WHERE id=$id LIMIT 1"); $conversation=$res?mysql_fetch_array($res, MYSQLI_ASSOC):false;
    if (!$conversation) lc_reply(array('error'=>'Conversation not found.'), 404);
    if ($action === 'operator_poll') lc_reply(array('conversation'=>$conversation,'messages'=>lc_messages($id,(int)($data['after']??0))));
    if ($action === 'operator_close') { mysql_query("UPDATE {$pre}livechat_conversation SET status='closed',updated_at=".time()." WHERE id=$id"); lc_reply(array('ok'=>true)); }
    if ($action === 'operator_reopen') {
        $visitor_token = livechat_escape($conversation['visitor_token']);
        if (get_row_count("SELECT COUNT(*) FROM {$pre}livechat_block WHERE visitor_token='$visitor_token'") > 0) lc_reply(array('error'=>'Unblock this visitor before reopening the conversation.'), 409);
        mysql_query("UPDATE {$pre}livechat_conversation SET status='open',updated_at=".time()." WHERE id=$id"); lc_reply(array('ok'=>true));
    }
    if ($action === 'operator_block') {
        $token=livechat_escape($conversation['visitor_token']); $email=livechat_escape($conversation['visitor_email']); $ip=livechat_escape((string)($conversation['ip_address'] ?? '')); $uid=(int)$_SESSION['user']['id']; $now=time();
        if (!get_row_count("SELECT COUNT(*) FROM {$pre}livechat_block WHERE visitor_token='$token'")) mysql_query("INSERT INTO {$pre}livechat_block (conversation_id,visitor_token,visitor_email,ip_address,blocked_by,created_at) VALUES ($id,'$token','$email','$ip',$uid,$now)");
        mysql_query("UPDATE {$pre}livechat_conversation SET status='closed',updated_at=$now WHERE id=$id"); lc_reply(array('ok'=>true));
    }
    $body=trim((string)($data['body']??'')); if ($body==='' || strlen($body)>2000) lc_reply(array('error'=>'Enter a message up to 2000 characters.'),422);
    $body=livechat_escape($body); $uid=(int)$_SESSION['user']['id']; $now=time(); mysql_query("INSERT INTO {$pre}livechat_message (conversation_id,sender,sender_id,body,created_at) VALUES ($id,'operator',$uid,'$body',$now)"); mysql_query("UPDATE {$pre}livechat_conversation SET status='open',updated_at=$now WHERE id=$id"); lc_reply(array('ok'=>true));
}

$token=(string)($data['token']??'');
if ($action === 'presence') {
    $visitor_key=trim((string)($data['visitor_key']??''));
    if (!preg_match('/^[a-f0-9]{64}$/',$visitor_key)) lc_reply(array('error'=>'Invalid visitor identifier.'),422);
    $safe_key=livechat_escape($visitor_key); $now=time(); $ip=livechat_escape(lc_ip_address());
    $agent=livechat_escape(substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,255));
    $page=livechat_escape(substr(trim((string)($data['page_url']??'')),0,1000));
    $referrer=livechat_escape(substr(trim((string)($data['referrer']??'')),0,1000));
    mysql_query("INSERT INTO {$pre}livechat_visitor (visitor_key,ip_address,user_agent,page_url,referrer,first_seen,last_seen)
        VALUES ('$safe_key','$ip','$agent','$page','$referrer',$now,$now)
        ON DUPLICATE KEY UPDATE ip_address='$ip',user_agent='$agent',page_url='$page',referrer='$referrer',last_seen=$now");
    mysql_query("UPDATE {$pre}livechat_invitation SET status='expired',responded_at=$now WHERE visitor_key='$safe_key' AND status='pending' AND expires_at<$now");
    $invite_res=mysql_query("SELECT i.id,i.message,i.expires_at,COALESCE(NULLIF(u.name,''),'Support') operator_name FROM {$pre}livechat_invitation i LEFT JOIN {$pre}user u ON u.id=i.operator_id WHERE i.visitor_key='$safe_key' AND i.status='pending' AND i.expires_at>=$now ORDER BY i.id DESC LIMIT 1");
    $invitation=$invite_res?mysql_fetch_array($invite_res,MYSQLI_ASSOC):false;
    if ($invitation) { $invitation['id']=(int)$invitation['id']; $invitation['expires_at']=(int)$invitation['expires_at']; }
    lc_reply(array('ok'=>true,'server_time'=>$now,'invitation'=>$invitation?:null));
}
if ($action === 'invitation_response') {
    $visitor_key=trim((string)($data['visitor_key']??'')); $invitation_id=(int)($data['invitation_id']??0); $response=(string)($data['response']??''); $now=time();
    if (!preg_match('/^[a-f0-9]{64}$/',$visitor_key) || !$invitation_id || !in_array($response,array('accept','decline'),true)) lc_reply(array('error'=>'Invalid invitation response.'),422);
    $safe_key=livechat_escape($visitor_key);
    $res=mysql_query("SELECT i.*,v.visitor_name,v.ip_address FROM {$pre}livechat_invitation i INNER JOIN {$pre}livechat_visitor v ON v.id=i.visitor_id WHERE i.id=$invitation_id AND i.visitor_key='$safe_key' LIMIT 1");
    $invitation=$res?mysql_fetch_array($res,MYSQLI_ASSOC):false;
    if (!$invitation || $invitation['status']!=='pending' || (int)$invitation['expires_at']<$now) lc_reply(array('error'=>'This invitation is no longer available.'),409);
    if ($response==='decline') { mysql_query("UPDATE {$pre}livechat_invitation SET status='declined',responded_at=$now WHERE id=$invitation_id"); lc_reply(array('ok'=>true)); }
    $token=bin2hex(random_bytes(32)); $safe_token=livechat_escape($token); $name=livechat_escape($invitation['visitor_name']?:'Guest'); $ip=livechat_escape($invitation['ip_address']); $uid=(int)$invitation['operator_id'];
    mysql_query("INSERT INTO {$pre}livechat_conversation (visitor_token,visitor_name,dept_id,ip_address,created_at,updated_at) VALUES ('$safe_token','$name',0,'$ip',$now,$now)");
    $conversation_id=(int)mysql_insert_id();
    if (!$conversation_id) lc_reply(array('error'=>'The conversation could not be started.'),500);
    $message=livechat_escape($invitation['message']);
    mysql_query("INSERT INTO {$pre}livechat_message (conversation_id,sender,sender_id,body,created_at) VALUES ($conversation_id,'operator',$uid,'$message',$now)");
    mysql_query("UPDATE {$pre}livechat_invitation SET status='accepted',conversation_id=$conversation_id,responded_at=$now WHERE id=$invitation_id");
    mysql_query("UPDATE {$pre}livechat_visitor SET conversation_id=$conversation_id,chats_started=chats_started+1,last_seen=$now WHERE visitor_key='$safe_key'");
    lc_reply(array('ok'=>true,'token'=>$token,'conversation_id'=>$conversation_id,'messages'=>lc_messages($conversation_id)));
}
if ($action === 'start') {
    $name=trim((string)($data['name']??'')); $email=trim((string)($data['email']??'')); $department_id=(int)($data['department_id']??0);
    $department_exists=$department_id>0 && get_row_count("SELECT COUNT(*) FROM {$pre}dept WHERE id=$department_id")>0;
    if ($name==='' || strlen($name)>100 || ($email!=='' && !filter_var($email,FILTER_VALIDATE_EMAIL)) || !$department_exists) lc_reply(array('error'=>'Enter your name, a valid email address, and choose a department.'),422);
    if (lc_is_blocked('', $email)) lc_reply(array('error'=>'You are not permitted to start a chat.'),403);
    $token=bin2hex(random_bytes(32)); $safeToken=livechat_escape($token); $name=livechat_escape($name); $email=livechat_escape($email); $ip=livechat_escape(lc_ip_address()); $now=time();
    mysql_query("INSERT INTO {$pre}livechat_conversation (visitor_token,visitor_name,visitor_email,dept_id,ip_address,created_at,updated_at) VALUES ('$safeToken','$name','$email',$department_id,'$ip',$now,$now)");
    $conversation_id=(int)mysql_insert_id();
    $visitor_key=trim((string)($data['visitor_key']??''));
    if (preg_match('/^[a-f0-9]{64}$/',$visitor_key)) {
        $safe_key=livechat_escape($visitor_key);
        mysql_query("UPDATE {$pre}livechat_visitor SET conversation_id=$conversation_id,visitor_name='$name',chats_started=chats_started+1,last_seen=$now WHERE visitor_key='$safe_key'");
    }
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
