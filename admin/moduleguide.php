<?php
include '../include/settings.php';include '../include/include.php';include '../modules/system.php';
$HD_CURPAGE='moduleguide.php';if(($_SESSION['login_type']??$LOGIN_INVALID)==$LOGIN_INVALID){header('Location: index.php');exit;}
$dir=preg_replace('/[^a-z0-9_-]/','',(string)($_GET['module']??''));$manifest=hd_module_manifest($dir);if(!$manifest){header('Location: modules.php');exit;}
$guide=__DIR__."/../modules/$dir/GUIDE.md";$content=is_file($guide)?file_get_contents($guide):'No guide is available for this module.';$script_name='Module Guide';include './include/header.php';
?>
<div class="d-sm-flex align-items-center justify-content-between mb-4"><div><h1 class="h3 mb-1 text-gray-800"><?php echo htmlspecialchars($manifest['name'],ENT_QUOTES,'UTF-8')?> guide</h1><p class="mb-0 text-muted">Setup and usage instructions.</p></div><a class="btn btn-sm btn-light mt-3 mt-sm-0" href="<?php echo htmlspecialchars($manifest['manage_url']??'modules.php',ENT_QUOTES,'UTF-8')?>"><i class="fas fa-arrow-left mr-1"></i>Back</a></div>
<div class="card shadow-sm"><div class="card-body"><div style="white-space:pre-wrap;line-height:1.8"><?php echo htmlspecialchars($content,ENT_QUOTES,'UTF-8')?></div></div></div>
<?php include './include/footer.php'; ?>
