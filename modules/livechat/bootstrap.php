<?php

function livechat_escape($value)
{
    $connection = $GLOBALS['_lynxhd_mysql_connection'] ?? null;
    return $connection ? mysqli_real_escape_string($connection, (string)$value) : addslashes((string)$value);
}

function livechat_enabled()
{
    global $pre;
    $result = mysql_query("SELECT text FROM {$pre}options WHERE name = 'livechat_enabled' LIMIT 1");
    $row = $result ? mysql_fetch_array($result) : false;
    return $row && $row[0] === '1';
}

function livechat_install()
{
    global $pre;
    mysql_query("CREATE TABLE IF NOT EXISTS {$pre}livechat_conversation (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        visitor_token CHAR(64) NOT NULL,
        visitor_name VARCHAR(100) NOT NULL,
        visitor_email VARCHAR(190) NOT NULL DEFAULT '',
        status ENUM('open','closed') NOT NULL DEFAULT 'open',
        created_at INT UNSIGNED NOT NULL,
        updated_at INT UNSIGNED NOT NULL,
        PRIMARY KEY (id), UNIQUE KEY visitor_token (visitor_token), KEY status_updated (status, updated_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    mysql_query("CREATE TABLE IF NOT EXISTS {$pre}livechat_message (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        conversation_id INT UNSIGNED NOT NULL,
        sender ENUM('visitor','operator') NOT NULL,
        sender_id INT NOT NULL DEFAULT 0,
        body TEXT NOT NULL,
        created_at INT UNSIGNED NOT NULL,
        PRIMARY KEY (id), KEY conversation_messages (conversation_id, id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function livechat_render_widget($asset_prefix = '')
{
    if (!livechat_enabled()) return;
    $base = htmlspecialchars($asset_prefix . 'modules/livechat/', ENT_QUOTES, 'UTF-8');
    echo '<link rel="stylesheet" href="' . $base . 'widget.css">';
    echo '<div id="lynx-livechat" data-api="' . $base . 'api.php"><button class="lc-launch" type="button" aria-expanded="false">Chat with us</button><section class="lc-panel" hidden><header><strong>Live support</strong><button class="lc-close" type="button" aria-label="Close chat">&times;</button></header><div class="lc-start"><input class="lc-name" maxlength="100" placeholder="Your name" aria-label="Your name"><input class="lc-email" maxlength="190" type="email" placeholder="Email (optional)" aria-label="Email"><button class="lc-begin" type="button">Start chat</button></div><div class="lc-room" hidden><div class="lc-messages" aria-live="polite"></div><form><input class="lc-text" maxlength="2000" placeholder="Type your message" aria-label="Message" autocomplete="off"><button type="submit">Send</button></form></div><div class="lc-error" role="alert"></div></section></div>';
    echo '<script src="' . $base . 'widget.js" defer></script>';
}
