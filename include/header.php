<?php
$is_modern_home = !empty($modern_home);
$install_available = file_exists(__DIR__ . '/../install/open-db.php');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="LynxHD customer support desk">
  <title><?php echo $is_modern_home ? 'LynxHD Support Desk' : 'LynxHD Help Desk'; ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<?php if (!$is_modern_home): ?>
  <link rel="stylesheet" href="css/structure.css">
  <link rel="stylesheet" href="css/form.css">
  <link rel="stylesheet" href="css/theme.css">
  <link rel="stylesheet" href="css/buttons.css">
  <link rel="stylesheet" href="css/client.css">
<?php endif; ?>
  <link href="css/home.css" rel="stylesheet">
</head>
<body class="<?php echo $is_modern_home ? '' : 'lynx-legacy-page'; ?>">
  <header class="site-header bg-white border-bottom">
    <nav class="navbar navbar-expand-sm" aria-label="Primary navigation">
      <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
          <img src="images/logo.jpg" alt="LynxHD" class="brand-logo" style="max-height:48px;width:auto">
        </a>
        <a class="btn btn-sm btn-outline-primary" href="index.php">Support home</a>
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
      <a class="btn btn-warning text-nowrap" href="install/index.php">Open installer</a>
    </div>
<?php endif; ?>
    <section class="card border-0 shadow-sm">
      <div class="card-body p-4 p-md-5">
        <h1 class="h3 mb-4">Lynx Helpdesk System</h1>
<?php endif; ?>
