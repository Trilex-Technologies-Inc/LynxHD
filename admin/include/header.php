<?php
$admin_logged_in = $INSTALLED && (($_SESSION['login_type'] ?? $LOGIN_INVALID) != $LOGIN_INVALID);
$global_priv = 0;
$new_message_count = 0;
if ($admin_logged_in) {
  $global_priv = get_row_count("SELECT COUNT(*) FROM {$pre}privilege WHERE ( user_id = '{$_SESSION['user']['id']}' && dept_id = '0' && admin = '1' )");
  $new_message_count = get_row_count("SELECT COUNT(*) FROM {$pre}message WHERE ( user_id = '{$_SESSION['user']['id']}' && viewed = '0' )");
}
$current_admin_page = basename($_SERVER['PHP_SELF'] ?? 'index.php');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title><?php echo htmlspecialchars($website_name . ' ' . $script_name, ENT_QUOTES, 'UTF-8') ?></title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
  <link href="./vendor/sb-admin-2/css/sb-admin-2.min.css" rel="stylesheet">
  <link href="./css/form.css" rel="stylesheet">
  <link href="./css/buttons.css" rel="stylesheet">
  <link href="./css/sb-admin-overrides.css" rel="stylesheet">
  <?php echo $EXTRA_HEADER ?? '' ?>
</head>
<body id="page-top" class="<?php echo $admin_logged_in ? '' : 'bg-gradient-primary' ?>">
<?php if ($admin_logged_in): ?>
<div id="wrapper">
  <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="browse.php">
      <div class="sidebar-brand-icon"><i class="fas fa-headset"></i></div>
      <div class="sidebar-brand-text mx-3">LynxHD</div>
    </a>
    <hr class="sidebar-divider my-0">
    <li class="nav-item <?php echo $current_admin_page === 'browse.php' ? 'active' : '' ?>">
      <a class="nav-link" href="browse.php"><i class="fas fa-fw fa-tachometer-alt"></i><span>Dashboard</span></a>
    </li>
    <hr class="sidebar-divider">
    <div class="sidebar-heading">Support</div>
    <li class="nav-item <?php echo in_array($current_admin_page, array('adminticket.php', 'adminsurvey.php', 'stats.php', 'adminview.php')) ? 'active' : '' ?>">
      <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#ticketMenu" aria-expanded="false" aria-controls="ticketMenu"><i class="fas fa-fw fa-ticket-alt"></i><span>Tickets</span></a>
      <div id="ticketMenu" class="collapse" data-parent="#accordionSidebar"><div class="bg-white py-2 collapse-inner rounded">
        <h6 class="collapse-header">Ticket tools</h6>
        <a class="collapse-item" href="adminticket.php">Create ticket</a>
        <?php if ($global_priv): ?><a class="collapse-item" href="adminsurvey.php">Surveys</a><?php endif; ?>
        <a class="collapse-item" href="stats.php">Statistics</a>
      </div></div>
    </li>
    <li class="nav-item <?php echo $current_admin_page === 'messages.php' ? 'active' : '' ?>">
      <a class="nav-link" href="messages.php"><i class="fas fa-fw fa-envelope"></i><span>Message center</span></a>
    </li>
    <hr class="sidebar-divider">
    <div class="sidebar-heading">Management</div>
    <li class="nav-item <?php echo in_array($current_admin_page, array('user.php', 'profile.php')) ? 'active' : '' ?>">
      <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#userMenu" aria-expanded="false" aria-controls="userMenu"><i class="fas fa-fw fa-users"></i><span>Users</span></a>
      <div id="userMenu" class="collapse" data-parent="#accordionSidebar"><div class="bg-white py-2 collapse-inner rounded">
        <a class="collapse-item" href="user.php">Manage users</a>
        <a class="collapse-item" href="profile.php">My profile</a>
      </div></div>
    </li>
    <li class="nav-item <?php echo in_array($current_admin_page, array('department.php', 'email.php', 'replies.php', 'repliesview.php')) ? 'active' : '' ?>">
      <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#departmentMenu" aria-expanded="false" aria-controls="departmentMenu"><i class="fas fa-fw fa-building"></i><span>Departments</span></a>
      <div id="departmentMenu" class="collapse" data-parent="#accordionSidebar"><div class="bg-white py-2 collapse-inner rounded">
        <a class="collapse-item" href="department.php">Manage departments</a>
        <?php if ($global_priv): ?><a class="collapse-item" href="email.php">Email processing</a><?php endif; ?>
        <a class="collapse-item" href="replies.php">Auto-replies</a>
      </div></div>
    </li>
    <li class="nav-item <?php echo in_array($current_admin_page, array('general.php', 'emails.php', 'faqadmin.php', 'backup.php', 'manual.php')) ? 'active' : '' ?>">
      <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#siteMenu" aria-expanded="false" aria-controls="siteMenu"><i class="fas fa-fw fa-cogs"></i><span>Site management</span></a>
      <div id="siteMenu" class="collapse" data-parent="#accordionSidebar"><div class="bg-white py-2 collapse-inner rounded">
        <?php if ($global_priv): ?>
          <a class="collapse-item" href="general.php">Help desk settings</a>
          <a class="collapse-item" href="emails.php">Email templates</a>
          <a class="collapse-item" href="./upload/index.php">Download manager</a>
        <?php endif; ?>
        <a class="collapse-item" href="faqadmin.php">Knowledge base</a>
        <?php if ($global_priv): ?><a class="collapse-item" href="backup.php">Backup</a><?php endif; ?>
        <a class="collapse-item" href="manual.php">Manual</a>
      </div></div>
    </li>
    <hr class="sidebar-divider d-none d-md-block">
    <div class="text-center d-none d-md-inline"><button class="rounded-circle border-0" id="sidebarToggle" type="button" aria-label="Toggle sidebar"></button></div>
  </ul>

  <div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
      <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
        <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3" type="button" aria-label="Toggle sidebar"><i class="fa fa-bars"></i></button>
        <div class="d-none d-sm-inline-block text-gray-600 font-weight-bold"><?php echo htmlspecialchars($script_name ?: 'Administration', ENT_QUOTES, 'UTF-8') ?></div>
        <ul class="navbar-nav ml-auto">
          <li class="nav-item"><a class="nav-link position-relative" href="messages.php" title="Messages"><i class="fas fa-envelope fa-fw"></i><?php if ($new_message_count): ?><span class="badge badge-danger badge-counter"><?php echo $new_message_count > 99 ? '99+' : (int) $new_message_count ?></span><?php endif; ?></a></li>
          <div class="topbar-divider d-none d-sm-block"></div>
          <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo htmlspecialchars($_SESSION['user']['name'], ENT_QUOTES, 'UTF-8') ?></span><span class="admin-avatar"><i class="fas fa-user"></i></span></a>
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
              <a class="dropdown-item" href="profile.php"><i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>Profile</a>
              <a class="dropdown-item" href="../index.php" target="_blank"><i class="fas fa-external-link-alt fa-sm fa-fw mr-2 text-gray-400"></i>View site</a>
              <div class="dropdown-divider"></div>
              <a class="dropdown-item" href="index.php?cmd=logout"><i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>Log out</a>
            </div>
          </li>
        </ul>
      </nav>
      <div class="container-fluid admin-content">
        <?php if ($global_priv && $PATH_TO_HELPDESK == ''): ?><div class="alert alert-warning shadow-sm"><strong>Configuration required:</strong> Set the help desk URL under Site Management &rarr; Help Desk Settings.</div><?php endif; ?>
<?php else: ?>
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-xl-7 col-lg-8 col-md-9">
        <div class="card o-hidden border-0 shadow-lg my-5">
          <div class="card-body p-0"><div class="p-4 p-md-5 admin-login-content">
            <div class="text-center mb-4"><i class="fas fa-headset fa-3x text-primary mb-3"></i><h1 class="h4 text-gray-900">LynxHD Administration</h1></div>
<?php endif; ?>
