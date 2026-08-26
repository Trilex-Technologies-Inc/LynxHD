<?php
include __DIR__ . '/../../include/settings.php';
include __DIR__ . '/../../include/include.php';
include __DIR__ . '/bootstrap.php';
header_remove('X-Frame-Options');
header('Content-Security-Policy: frame-ancestors *');
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Live support chat</title><style>html,body{width:100%;height:100%;margin:0;overflow:hidden;background:transparent}</style></head><body>
<?php livechat_render_widget('../../'); ?>
</body></html>
