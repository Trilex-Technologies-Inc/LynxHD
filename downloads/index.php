<?php
// Public download library. Uploading and deletion belong in an authenticated
// administration area and are intentionally not exposed from this page.

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$download_directory = __DIR__;
$hidden_files = array('index.php', '.htaccess', '.htpasswd', '.DS_Store');

if (!empty($_GET['download'])) {
    $requested_name = basename((string) $_GET['download']);
    $requested_path = $download_directory . DIRECTORY_SEPARATOR . $requested_name;

    if ($requested_name === '' || in_array($requested_name, $hidden_files, true) || !is_file($requested_path) || !is_readable($requested_path)) {
        http_response_code(404);
        exit('The requested download was not found.');
    }

    header('Content-Type: application/octet-stream');
    header('Content-Length: ' . filesize($requested_path));
    header('Content-Disposition: attachment; filename="' . str_replace(array('"', "\r", "\n"), '', $requested_name) . '"');
    header('X-Content-Type-Options: nosniff');
    readfile($requested_path);
    exit;
}

$files = array();
$directory = opendir($download_directory);
while (($name = readdir($directory)) !== false) {
    $path = $download_directory . DIRECTORY_SEPARATOR . $name;
    if ($name[0] === '.' || in_array($name, $hidden_files, true) || !is_file($path)) {
        continue;
    }
    $files[] = array(
        'name' => $name,
        'size' => filesize($path),
        'modified' => filemtime($path),
        'extension' => strtolower(pathinfo($name, PATHINFO_EXTENSION)),
    );
}
closedir($directory);

usort($files, function ($left, $right) {
    return $right['modified'] <=> $left['modified'];
});

function format_file_size($bytes)
{
    $units = array('B', 'KB', 'MB', 'GB');
    $size = max(0, (float) $bytes);
    $unit = 0;
    while ($size >= 1024 && $unit < count($units) - 1) {
        $size /= 1024;
        $unit++;
    }
    return ($unit === 0 ? (int) $size : number_format($size, 1)) . ' ' . $units[$unit];
}

chdir(dirname(__DIR__));
include './include/settings.php';
include './include/include.php';
$asset_prefix = '../';
include './include/header.php';
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
  <div>
    <span class="badge text-bg-primary mb-2">Resource library</span>
    <h2 class="h3 mb-1">Downloads</h2>
    <p class="text-secondary mb-0">Download files and resources provided by our support team.</p>
  </div>
  <?php if ($files): ?><span class="text-secondary small"><?php echo count($files) ?> file<?php echo count($files) === 1 ? '' : 's' ?></span><?php endif; ?>
</div>

<?php if (!$files): ?>
  <div class="empty-state text-center border rounded-3 p-5">
    <div class="empty-state-icon mb-3" aria-hidden="true">&darr;</div>
    <h3 class="h5">No downloads available</h3>
    <p class="text-secondary mb-0">Files published by the support team will appear here.</p>
  </div>
<?php else: ?>
  <div class="download-list d-grid gap-3">
    <?php foreach ($files as $file): ?>
      <article class="download-card card border shadow-sm">
        <div class="card-body p-3 p-md-4 d-flex flex-column flex-sm-row align-items-sm-center gap-3">
          <div class="download-icon" aria-hidden="true">
            <img src="resources/zip.gif" alt="">
          </div>
          <div class="flex-grow-1 min-width-0">
            <h3 class="h6 mb-1 text-break"><?php echo htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8') ?></h3>
            <div class="d-flex flex-wrap gap-2 text-secondary small">
              <span><?php echo format_file_size($file['size']) ?></span>
              <span aria-hidden="true">&bull;</span>
              <time datetime="<?php echo date('c', $file['modified']) ?>">Updated <?php echo date('M j, Y', $file['modified']) ?></time>
              <?php if ($file['extension'] !== ''): ?><span class="badge text-bg-light text-uppercase"><?php echo htmlspecialchars($file['extension'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
            </div>
          </div>
          <a class="btn btn-primary text-nowrap" href="?download=<?php echo rawurlencode($file['name']) ?>">Download</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php include './include/footer.php'; ?>
