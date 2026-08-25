<?php
include '../include/settings.php';
include '../include/include.php';
include '../modules/livechat/bootstrap.php';
$HD_CURPAGE = 'modules.php';
if (($_SESSION['login_type'] ?? $LOGIN_INVALID) == $LOGIN_INVALID) { header('Location: index.php?redirect=modules.php'); exit; }
$global_priv = get_row_count("SELECT COUNT(*) FROM {$pre}privilege WHERE user_id='" . (int)$_SESSION['user']['id'] . "' AND dept_id='0'");
if (!$global_priv) { header("Location: $HD_URL_BROWSE"); exit; }

$modules_csrf = $_SESSION['modules_csrf'] ?? '';
if ($modules_csrf === '') {
    $modules_csrf = bin2hex(random_bytes(32));
    $_SESSION['modules_csrf'] = $modules_csrf;
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($modules_csrf, (string)$_POST['csrf_token'])) {
        $msg = '<div class="alert alert-danger">The request could not be verified. Please try again.</div>';
    } elseif (($_POST['module'] ?? '') !== 'livechat') {
        $msg = '<div class="alert alert-danger">The selected module is not available.</div>';
    } elseif (isset($_POST['install_module'])) {
        $msg = livechat_install()
            ? '<div class="alert alert-success">Live Chat was installed successfully.</div>'
            : '<div class="alert alert-danger">Live Chat could not be installed. Check the database permissions.</div>';
    } elseif (isset($_POST['uninstall_module'])) {
        $msg = livechat_uninstall()
            ? '<div class="alert alert-success">Live Chat and all of its data were removed.</div>'
            : '<div class="alert alert-danger">Live Chat could not be completely removed. Check the database permissions.</div>';
    }
}

$livechat_installed = livechat_installed();
$livechat_enabled = $livechat_installed && livechat_enabled();
$script_name = 'Modules';
include './include/header.php';
?>
<div class="d-sm-flex align-items-center justify-content-between mb-4">
  <div><h1 class="h3 mb-1 text-gray-800">Modules</h1><p class="mb-0 text-muted">Install and manage features that extend your help desk.</p></div>
</div>
<?php echo $msg ?>
<div class="row">
  <div class="col-xl-6 col-lg-8 mb-4">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between mb-3">
          <div class="d-flex align-items-center">
            <div class="bg-primary text-white rounded p-3 mr-3"><i class="fas fa-comments fa-2x"></i></div>
            <div><h2 class="h5 mb-1 text-gray-900">Live Chat</h2><span class="badge badge-<?php echo $livechat_installed ? ($livechat_enabled ? 'success' : 'secondary') : 'light' ?>"><?php echo $livechat_installed ? ($livechat_enabled ? 'Enabled' : 'Disabled') : 'Not installed' ?></span></div>
          </div>
          <span class="small text-muted">Version 1.0</span>
        </div>
        <p class="text-muted">Talk with visitors from the support site in real time and manage conversations from the admin area.</p>
      </div>
      <div class="card-footer bg-white d-flex justify-content-end">
        <?php if ($livechat_installed): ?>
          <a class="btn btn-primary mr-2" href="livechat.php"><i class="fas fa-cog mr-1"></i>Manage</a>
          <form method="post" onsubmit="return confirm('Uninstall Live Chat? All conversations and messages will be permanently deleted.');">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($modules_csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="module" value="livechat">
            <button class="btn btn-outline-danger" type="submit" name="uninstall_module" value="1"><i class="fas fa-trash-alt mr-1"></i>Uninstall</button>
          </form>
        <?php else: ?>
          <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($modules_csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="module" value="livechat">
            <button class="btn btn-success" type="submit" name="install_module" value="1"><i class="fas fa-download mr-1"></i>Install</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php include './include/footer.php'; ?>
