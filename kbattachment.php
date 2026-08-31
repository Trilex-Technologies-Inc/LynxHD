<?php
include "./include/settings.php";
include "./include/include.php";

$article_id = (int)($_GET['id'] ?? 0);
$requested_file = (string)($_GET['file'] ?? '');
$file = basename($requested_file);

if ($article_id < 1 || $file === '' || $file !== $requested_file) {
  http_response_code(400);
  exit;
}

$schedule_sql = '';
$staff_request = (($_SESSION['login_type'] ?? $LOGIN_INVALID) !== $LOGIN_INVALID);
$columns = mysql_query("SHOW COLUMNS FROM {$pre}faq");
while ($columns && ($column = mysql_fetch_array($columns, MYSQLI_ASSOC)))
  if (!$staff_request && $column['Field'] === 'publish_date')
    $schedule_sql = " AND (publish_date IS NULL OR publish_date <= CURDATE()) AND (expiry_date IS NULL OR expiry_date >= CURDATE())";

$article = mysql_fetch_array(mysql_query("SELECT id FROM {$pre}faq WHERE id = '$article_id' AND parent = '-1' $schedule_sql"));
$path = "{$HD_KB_FILES}/{$article_id}/{$file}";
if (!$article || !is_file($path)) {
  http_response_code(404);
  exit;
}

$content_type = function_exists('mime_content_type') ? mime_content_type($path) : 'application/octet-stream';
header('Content-Type: ' . ($content_type ?: 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($file));
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
