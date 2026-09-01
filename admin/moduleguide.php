<?php
include '../include/settings.php';include '../include/include.php';
$HD_CURPAGE='moduleguide.php';if(($_SESSION['login_type']??$LOGIN_INVALID)==$LOGIN_INVALID){header('Location: index.php');exit;}
$guide=__DIR__.'/../MODULE_DEVELOPMENT.md';$content=is_file($guide)?file_get_contents($guide):'The module development guide is unavailable.';$script_name='Module Development Guide';include './include/header.php';
?>
<div class="d-sm-flex align-items-center justify-content-between mb-4"><div><h1 class="h3 mb-1 text-gray-800">How to create a module</h1><p class="mb-0 text-muted">Developer guide for building installable LynxHD modules.</p></div><a class="btn btn-sm btn-light mt-3 mt-sm-0" href="modules.php"><i class="fas fa-arrow-left mr-1"></i>Back to modules</a></div>
<div class="card shadow-sm"><div class="card-body"><div style="white-space:pre-wrap;line-height:1.8"><?php echo htmlspecialchars($content,ENT_QUOTES,'UTF-8')?></div></div></div>
<?php include './include/footer.php'; ?>
