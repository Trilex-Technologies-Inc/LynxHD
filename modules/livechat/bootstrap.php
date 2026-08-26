<?php

function livechat_escape($value)
{
    $connection = $GLOBALS['_lynxhd_mysql_connection'] ?? null;
    return $connection ? mysqli_real_escape_string($connection, (string)$value) : addslashes((string)$value);
}

function livechat_enabled()
{
    global $pre;
    if (!livechat_installed()) return false;
    $result = mysql_query("SELECT text FROM {$pre}options WHERE name = 'livechat_enabled' LIMIT 1");
    $row = $result ? mysql_fetch_array($result) : false;
    return $row && $row[0] === '1';
}

function livechat_set_enabled($enabled)
{
    global $pre;
    if (!livechat_installed()) return false;
    $value = $enabled ? '1' : '0';
    if (get_row_count("SELECT COUNT(*) FROM {$pre}options WHERE name='livechat_enabled'")) return mysql_query("UPDATE {$pre}options SET text='$value' WHERE name='livechat_enabled'");
    return mysql_query("INSERT INTO {$pre}options(name,text) VALUES('livechat_enabled','$value')");
}

function livechat_color()
{
    global $pre;
    if (!livechat_installed()) return '#4f46e5';
    $result = mysql_query("SELECT text FROM {$pre}options WHERE name='livechat_color' LIMIT 1");
    $row = $result ? mysql_fetch_array($result) : false;
    $color = $row ? trim((string)$row[0]) : '';
    return preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? strtolower($color) : '#4f46e5';
}

function livechat_color_variant($color, $amount)
{
    $color = ltrim($color, '#');
    $channels = array(hexdec(substr($color, 0, 2)), hexdec(substr($color, 2, 2)), hexdec(substr($color, 4, 2)));
    foreach ($channels as &$channel) $channel = max(0, min(255, $channel + $amount));
    unset($channel);
    return sprintf('#%02x%02x%02x', $channels[0], $channels[1], $channels[2]);
}

function livechat_enable(){ return livechat_set_enabled(true); }
function livechat_disable(){ return livechat_set_enabled(false); }

function livechat_installed()
{
    global $pre;
    $conversation = livechat_escape($pre . 'livechat_conversation');
    $message = livechat_escape($pre . 'livechat_message');
    $result = mysql_query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN ('$conversation','$message')");
    $row = $result ? mysql_fetch_array($result) : false;
    return $row && (int)$row[0] === 2;
}

function livechat_ensure_canned_messages()
{
    global $pre;
    return mysql_query("CREATE TABLE IF NOT EXISTS {$pre}livechat_canned_message (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        title VARCHAR(120) NOT NULL,
        body TEXT NOT NULL,
        language VARCHAR(40) NOT NULL DEFAULT 'English',
        operator_id INT NOT NULL DEFAULT 0,
        created_at INT UNSIGNED NOT NULL,
        updated_at INT UNSIGNED NOT NULL,
        PRIMARY KEY (id), KEY language_operator (language, operator_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function livechat_ensure_department()
{
    global $pre;
    $table = livechat_escape($pre . 'livechat_conversation');
    $result = mysql_query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='$table' AND column_name='dept_id'");
    $row = $result ? mysql_fetch_array($result) : false;
    $department_ready = ($row && (int)$row[0] > 0) || mysql_query("ALTER TABLE {$pre}livechat_conversation ADD dept_id INT NOT NULL DEFAULT 0 AFTER visitor_email, ADD KEY department (dept_id)");
    $result = mysql_query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='$table' AND column_name='ip_address'");
    $row = $result ? mysql_fetch_array($result) : false;
    $ip_ready = ($row && (int)$row[0] > 0) || mysql_query("ALTER TABLE {$pre}livechat_conversation ADD ip_address VARCHAR(45) NOT NULL DEFAULT '' AFTER dept_id");
    return $department_ready && $ip_ready;
}

function livechat_ensure_blocks()
{
    global $pre;
    return mysql_query("CREATE TABLE IF NOT EXISTS {$pre}livechat_block (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        conversation_id INT UNSIGNED NOT NULL DEFAULT 0,
        visitor_token CHAR(64) NOT NULL DEFAULT '',
        visitor_email VARCHAR(190) NOT NULL DEFAULT '',
        ip_address VARCHAR(45) NOT NULL DEFAULT '',
        blocked_by INT NOT NULL DEFAULT 0,
        created_at INT UNSIGNED NOT NULL,
        PRIMARY KEY (id), KEY visitor_token (visitor_token), KEY visitor_email (visitor_email), KEY ip_address (ip_address)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function livechat_install()
{
    global $pre;
    $conversation_created = mysql_query("CREATE TABLE IF NOT EXISTS {$pre}livechat_conversation (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        visitor_token CHAR(64) NOT NULL,
        visitor_name VARCHAR(100) NOT NULL,
        visitor_email VARCHAR(190) NOT NULL DEFAULT '',
        dept_id INT NOT NULL DEFAULT 0,
        ip_address VARCHAR(45) NOT NULL DEFAULT '',
        status ENUM('open','closed') NOT NULL DEFAULT 'open',
        created_at INT UNSIGNED NOT NULL,
        updated_at INT UNSIGNED NOT NULL,
        PRIMARY KEY (id), UNIQUE KEY visitor_token (visitor_token), KEY status_updated (status, updated_at), KEY department (dept_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $message_created = mysql_query("CREATE TABLE IF NOT EXISTS {$pre}livechat_message (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        conversation_id INT UNSIGNED NOT NULL,
        sender ENUM('visitor','operator') NOT NULL,
        sender_id INT NOT NULL DEFAULT 0,
        body TEXT NOT NULL,
        created_at INT UNSIGNED NOT NULL,
        PRIMARY KEY (id), KEY conversation_messages (conversation_id, id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $canned_created = livechat_ensure_canned_messages();
    $department_ready = livechat_ensure_department();
    $blocks_created = livechat_ensure_blocks();
    if (!$conversation_created || !$message_created || !$canned_created || !$department_ready || !$blocks_created) return false;
    if (get_row_count("SELECT COUNT(*) FROM {$pre}options WHERE name='livechat_enabled'")) {
        mysql_query("UPDATE {$pre}options SET text='0' WHERE name='livechat_enabled'");
    } else {
        mysql_query("INSERT INTO {$pre}options (name,text) VALUES ('livechat_enabled','0')");
    }
    if (!get_row_count("SELECT COUNT(*) FROM {$pre}options WHERE name='livechat_color'")) {
        mysql_query("INSERT INTO {$pre}options (name,text) VALUES ('livechat_color','#4f46e5')");
    }
    return livechat_installed();
}

function livechat_uninstall()
{
    global $pre;
    $blocks_removed = mysql_query("DROP TABLE IF EXISTS {$pre}livechat_block");
    $canned_removed = mysql_query("DROP TABLE IF EXISTS {$pre}livechat_canned_message");
    $message_removed = mysql_query("DROP TABLE IF EXISTS {$pre}livechat_message");
    $conversation_removed = mysql_query("DROP TABLE IF EXISTS {$pre}livechat_conversation");
    $setting_removed = mysql_query("DELETE FROM {$pre}options WHERE name IN ('livechat_enabled','livechat_color')");
    return $blocks_removed && $canned_removed && $message_removed && $conversation_removed && $setting_removed && !livechat_installed();
}

function livechat_render_widget($asset_prefix = '')
{
    global $pre;
    if (!livechat_enabled()) return;
    $base = htmlspecialchars($asset_prefix . 'modules/livechat/', ENT_QUOTES, 'UTF-8');
    $color = livechat_color();
    $color_dark = livechat_color_variant($color, -24);
    $color_accent = livechat_color_variant($color, 24);
    $color_rgb = implode(',', array(hexdec(substr($color, 1, 2)), hexdec(substr($color, 3, 2)), hexdec(substr($color, 5, 2))));
    $department_options = '';
    $departments = mysql_query("SELECT id,name FROM {$pre}dept WHERE id != 0 ORDER BY sortnum,name");
    while ($departments && ($department = mysql_fetch_array($departments, MYSQLI_ASSOC))) {
        $department_options .= '<option value="' . (int)$department['id'] . '">' . htmlspecialchars($department['name'], ENT_QUOTES, 'UTF-8') . '</option>';
    }
    echo '<link rel="stylesheet" href="' . $base . 'widget.css">';
    echo '<div id="lynx-livechat" style="--lc-primary:' . $color . ';--lc-primary-dark:' . $color_dark . ';--lc-accent:' . $color_accent . ';--lc-primary-rgb:' . $color_rgb . '" data-api="' . $base . 'api.php">
      <button class="lc-launch" type="button" aria-expanded="false" aria-label="Open live support chat">
        <span class="lc-launch-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20 15a4 4 0 0 1-4 4H8l-5 3 1.7-5.1A7 7 0 0 1 3 12V8a4 4 0 0 1 4-4h9a4 4 0 0 1 4 4v7Z"/></svg><span class="lc-online-dot"></span></span>
        <span class="lc-launch-copy"><strong>Chat with us</strong><small>We are online</small></span>
      </button>
      <section class="lc-panel" hidden aria-label="Live support chat">
        <header class="lc-header"><div class="lc-agent-avatar" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20 15a4 4 0 0 1-4 4H8l-5 3 1.7-5.1A7 7 0 0 1 3 12V8a4 4 0 0 1 4-4h9a4 4 0 0 1 4 4v7Z"/></svg></div><div class="lc-header-copy"><strong>Live support</strong><span><i></i>Online and ready to help</span></div><button class="lc-close" type="button" aria-label="Close chat">&times;</button></header>
        <form class="lc-start"><div class="lc-welcome"><span class="lc-wave" aria-hidden="true">&#128075;</span><h2>How can we help?</h2><p>Share your details to start a conversation with our support team.</p></div><label for="lc-visitor-name">Your name</label><input class="lc-name" id="lc-visitor-name" maxlength="100" placeholder="Enter your name" autocomplete="name" required><label for="lc-visitor-email">Email address <span>Optional</span></label><input class="lc-email" id="lc-visitor-email" maxlength="190" type="email" placeholder="you@example.com" autocomplete="email"><label for="lc-visitor-department">Department</label><select class="lc-department" id="lc-visitor-department" required><option value="">Choose a department</option>' . $department_options . '</select><button class="lc-begin" type="submit"><span>Start conversation</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg></button><small class="lc-privacy">Your details are used only to assist with this conversation.</small></form>
        <div class="lc-room" hidden><div class="lc-messages" aria-live="polite" aria-label="Chat messages"></div><form class="lc-compose"><input class="lc-text" maxlength="2000" placeholder="Write a message…" aria-label="Message" autocomplete="off"><button type="submit" aria-label="Send message"><svg viewBox="0 0 24 24"><path d="m22 2-7 20-4-9-9-4 20-7Z"/><path d="M22 2 11 13"/></svg></button></form></div><div class="lc-error" role="alert"></div>
      </section>
    </div>';
    echo '<script src="' . $base . 'widget.js" defer></script>';
}
