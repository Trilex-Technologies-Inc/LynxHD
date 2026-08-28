<?php
$is_modern_home = !empty($modern_home);
$install_available = file_exists(__DIR__ . '/../install/open-db.php');
$asset_prefix = isset($asset_prefix) ? $asset_prefix : '';
?>
<!doctype html>
<html lang="<?php echo field($GLOBALS['HD_LOCALE'] ?? 'en') ?>" dir="<?php echo field($GLOBALS['HD_DIRECTION'] ?? 'ltr') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="LynxHD customer support desk">
  <title><?php echo $is_modern_home ? 'LynxHD Support Desk' : 'LynxHD Help Desk'; ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<?php if (!$is_modern_home): ?>
  <link rel="stylesheet" href="<?php echo $asset_prefix ?>css/structure.css">
  <link rel="stylesheet" href="<?php echo $asset_prefix ?>css/form.css">
  <link rel="stylesheet" href="<?php echo $asset_prefix ?>css/theme.css">
  <link rel="stylesheet" href="<?php echo $asset_prefix ?>css/buttons.css">
  <link rel="stylesheet" href="<?php echo $asset_prefix ?>css/client.css">
<?php endif; ?>
  <link href="<?php echo $asset_prefix ?>css/home.css" rel="stylesheet">
  <script src="<?php echo $asset_prefix ?>include/tinymce/js/tinymce/tinymce.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (typeof tinymce === 'undefined') return;
      tinymce.init({
        selector: 'textarea:not(.no-tinymce)',
        license_key: 'gpl',
        plugins: 'advlist autolink autoresize charmap code fullscreen image link lists media preview searchreplace table visualblocks wordcount',
        toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image media table | removeformat code fullscreen',
        menubar: 'file edit view insert format tools table help',
        min_height: 280,
        autoresize_bottom_margin: 24,
        browser_spellcheck: true,
        convert_urls: false,
        promotion: false,
        branding: false
      });
    });
  </script>
</head>
<body class="<?php echo $is_modern_home ? '' : 'lynx-legacy-page'; ?>">
  <header class="site-header bg-white border-bottom">
    <nav class="navbar navbar-expand-sm" aria-label="Primary navigation">
      <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo $asset_prefix ?>index.php">
          <img src="<?php echo $asset_prefix ?>images/logo.jpg" alt="LynxHD" class="brand-logo" style="max-height:48px;width:auto">
        </a>
        <div class="d-flex align-items-center gap-2">
          <?php if( count($GLOBALS['HD_LANGUAGES'] ?? array()) > 1 ): ?><form method="get" class="mb-0"><?php foreach( $_GET as $query_name => $query_value ): if( $query_name !== 'lang' && is_scalar($query_value) ): ?><input type="hidden" name="<?php echo field($query_name) ?>" value="<?php echo field($query_value) ?>"><?php endif; endforeach; ?><label class="visually-hidden" for="public-language">Language</label><select class="form-select form-select-sm" id="public-language" name="lang" onchange="this.form.submit()" aria-label="Language"><?php foreach( $GLOBALS['HD_LANGUAGES'] as $code => $language ): ?><option value="<?php echo field($code) ?>" <?php echo ($GLOBALS['HD_LOCALE'] ?? 'en') === $code ? 'selected' : '' ?>><?php echo field($language['name']) ?></option><?php endforeach; ?></select></form><?php endif; ?>
          <a class="btn btn-sm btn-outline-primary" href="<?php echo $asset_prefix ?>index.php">Support home</a>
        </div>
      </div>
    </nav>
  </header>

<?php if (!$is_modern_home): ?>
  <main class="container py-4 py-md-5">
<?php if ($install_available): ?>
    <div class="alert alert-warning d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3" role="alert">
      <div>
        <strong>Installation files detected.</strong>
        <span>If this is a new installation, run the installer. Otherwise, remove the <code>install</code> directory for security.</span>
      </div>
      <a class="btn btn-warning text-nowrap" href="<?php echo $asset_prefix ?>install/index.php">Open installer</a>
    </div>
<?php endif; ?>
    <section class="card border-0 shadow-sm">
      <div class="card-body p-4 p-md-5">
        <h1 class="h3 mb-4">Lynx Helpdesk System</h1>
<?php endif; ?>
