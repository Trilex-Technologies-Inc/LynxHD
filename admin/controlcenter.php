<?php
include '../include/settings.php';
include '../include/include.php';
include '../modules/system.php';

$HD_CURPAGE = 'controlcenter.php';
if (($_SESSION['login_type'] ?? $LOGIN_INVALID) == $LOGIN_INVALID) {
    header('Location: index.php?redirect=controlcenter.php');
    exit;
}
$user_id = (int)$_SESSION['user']['id'];
$global_priv = get_row_count("SELECT COUNT(*) FROM {$pre}privilege WHERE user_id=$user_id AND dept_id=0 AND admin=1") > 0;
if (!$global_priv) {
    header("Location: $HD_URL_BROWSE");
    exit;
}

$control_items = array(
    array('general.php', 'fa-cogs', 'primary', 'Help desk settings', 'Site identity, URLs, uploads, security, email delivery, and automation.'),
    array('languages.php', 'fa-language', 'primary', 'Languages', 'Add languages, configure text direction, and translate interface labels.'),
    array('department.php', 'fa-building', 'info', 'Departments', 'Configure support departments and assign staff members.'),
    array('user.php', 'fa-users-cog', 'success', 'Users and permissions', 'Manage administrator accounts, operators, and access.'),
    array('emails.php', 'fa-envelope-open-text', 'warning', 'Email templates', 'Customize the messages sent by the help desk.'),
    array('email.php', 'fa-mail-bulk', 'info', 'Email processing', 'Configure incoming support email and ticket creation.'),
    array('modules.php', 'fa-puzzle-piece', 'primary', 'Modules', 'Install, enable, disable, and configure optional features.'),
    array('backup.php', 'fa-database', 'danger', 'Backup', 'Create and manage help-desk database backups.'),
    array('manual.php', 'fa-book', 'secondary', 'Administrator manual', 'Open the built-in administration documentation.')
);
if (hd_module_enabled('livechat')) {
    array_splice($control_items, 6, 0, array(array('livechatsettings.php', 'fa-comments', 'success', 'Live Chat', 'Configure widget appearance, canned replies, and website embed code.')));
}

$script_name = 'Control Center';
include './include/header.php';
?>
<div class="d-sm-flex align-items-center justify-content-between mb-4"><div><h1 class="h3 mb-1 text-gray-800"><i class="fas fa-sliders-h text-primary mr-2"></i>Control Center</h1><p class="mb-0 text-muted">Administration settings and configuration tools in one place.</p></div></div>
<div class="row">
<?php foreach ($control_items as $item): ?>
  <div class="col-xl-4 col-md-6 mb-4">
    <a class="card shadow-sm h-100 text-decoration-none border-left-<?php echo $item[2] ?>" href="<?php echo htmlspecialchars($item[0], ENT_QUOTES, 'UTF-8') ?>">
      <div class="card-body d-flex align-items-start">
        <div class="rounded-circle bg-<?php echo $item[2] ?> text-white d-flex align-items-center justify-content-center mr-3" style="width:48px;height:48px;flex:0 0 48px"><i class="fas <?php echo $item[1] ?> fa-lg"></i></div>
        <div><h2 class="h6 font-weight-bold text-gray-800 mb-1"><?php echo htmlspecialchars($item[3], ENT_QUOTES, 'UTF-8') ?></h2><p class="small text-muted mb-0"><?php echo htmlspecialchars($item[4], ENT_QUOTES, 'UTF-8') ?></p></div>
      </div>
    </a>
  </div>
<?php endforeach; ?>
</div>
<?php include './include/footer.php'; ?>
