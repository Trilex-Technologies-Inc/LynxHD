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

function livechat_installed()
{
    global $pre;
    $conversation = livechat_escape($pre . 'livechat_conversation');
    $message = livechat_escape($pre . 'livechat_message');
    $result = mysql_query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN ('$conversation','$message')");
    $row = $result ? mysql_fetch_array($result) : false;
    return $row && (int)$row[0] === 2;
}

function livechat_install()
{
    global $pre;
    $conversation_created = mysql_query("CREATE TABLE IF NOT EXISTS {$pre}livechat_conversation (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        visitor_token CHAR(64) NOT NULL,
        visitor_name VARCHAR(100) NOT NULL,
        visitor_email VARCHAR(190) NOT NULL DEFAULT '',
        status ENUM('open','closed') NOT NULL DEFAULT 'open',
        created_at INT UNSIGNED NOT NULL,
        updated_at INT UNSIGNED NOT NULL,
        PRIMARY KEY (id), UNIQUE KEY visitor_token (visitor_token), KEY status_updated (status, updated_at)
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
    if (!$conversation_created || !$message_created) return false;
    if (get_row_count("SELECT COUNT(*) FROM {$pre}options WHERE name='livechat_enabled'")) {
        mysql_query("UPDATE {$pre}options SET text='0' WHERE name='livechat_enabled'");
    } else {
        mysql_query("INSERT INTO {$pre}options (name,text) VALUES ('livechat_enabled','0')");
    }
    return livechat_installed();
}

function livechat_uninstall()
{
    global $pre;
    $message_removed = mysql_query("DROP TABLE IF EXISTS {$pre}livechat_message");
    $conversation_removed = mysql_query("DROP TABLE IF EXISTS {$pre}livechat_conversation");
    $setting_removed = mysql_query("DELETE FROM {$pre}options WHERE name='livechat_enabled'");
    return $message_removed && $conversation_removed && $setting_removed && !livechat_installed();
}

function livechat_render_widget($asset_prefix = '')
{
    if (!livechat_enabled()) return;
    $base = htmlspecialchars($asset_prefix . 'modules/livechat/', ENT_QUOTES, 'UTF-8');
    echo '<link rel="stylesheet" href="' . $base . 'widget.css">';
    echo '<div id="lynx-livechat" data-api="' . $base . 'api.php">
      <button class="lc-launch" type="button" aria-expanded="false" aria-label="Open live support chat">
        <span class="lc-launch-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20 15a4 4 0 0 1-4 4H8l-5 3 1.7-5.1A7 7 0 0 1 3 12V8a4 4 0 0 1 4-4h9a4 4 0 0 1 4 4v7Z"/></svg><span class="lc-online-dot"></span></span>
        <span class="lc-launch-copy"><strong>Chat with us</strong><small>We are online</small></span>
      </button>
      <section class="lc-panel" hidden aria-label="Live support chat">
        <header class="lc-header"><div class="lc-agent-avatar" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20 15a4 4 0 0 1-4 4H8l-5 3 1.7-5.1A7 7 0 0 1 3 12V8a4 4 0 0 1 4-4h9a4 4 0 0 1 4 4v7Z"/></svg></div><div class="lc-header-copy"><strong>Live support</strong><span><i></i>Online and ready to help</span></div><button class="lc-close" type="button" aria-label="Close chat">&times;</button></header>
        <form class="lc-start"><div class="lc-welcome"><span class="lc-wave" aria-hidden="true">&#128075;</span><h2>How can we help?</h2><p>Share your details to start a conversation with our support team.</p></div><label for="lc-visitor-name">Your name</label><input class="lc-name" id="lc-visitor-name" maxlength="100" placeholder="Enter your name" autocomplete="name" required><label for="lc-visitor-email">Email address <span>Optional</span></label><input class="lc-email" id="lc-visitor-email" maxlength="190" type="email" placeholder="you@example.com" autocomplete="email"><button class="lc-begin" type="submit"><span>Start conversation</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg></button><small class="lc-privacy">Your details are used only to assist with this conversation.</small></form>
        <div class="lc-room" hidden><div class="lc-messages" aria-live="polite" aria-label="Chat messages"></div><form class="lc-compose"><input class="lc-text" maxlength="2000" placeholder="Write a message…" aria-label="Message" autocomplete="off"><button type="submit" aria-label="Send message"><svg viewBox="0 0 24 24"><path d="m22 2-7 20-4-9-9-4 20-7Z"/><path d="M22 2 11 13"/></svg></button></form></div><div class="lc-error" role="alert"></div>
      </section>
    </div>';
    echo '<script src="' . $base . 'widget.js" defer></script>';
}
