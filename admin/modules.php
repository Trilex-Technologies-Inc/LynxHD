<?php
include '../include/settings.php'; include '../include/include.php'; include '../modules/system.php';
$HD_CURPAGE='modules.php';
if(($_SESSION['login_type']??$LOGIN_INVALID)==$LOGIN_INVALID){header('Location: index.php?redirect=modules.php');exit;}
$uid=(int)$_SESSION['user']['id'];
$global_priv=get_row_count("SELECT COUNT(*) FROM {$pre}privilege WHERE user_id=$uid AND dept_id=0 AND admin=1")>0;
if(!$global_priv){header("Location: $HD_URL_BROWSE");exit;}
$csrf=$_SESSION['modules_csrf']??'';if($csrf===''){$csrf=bin2hex(random_bytes(32));$_SESSION['modules_csrf']=$csrf;}
$msg='';hd_modules_registry_ready();$available=hd_modules_available();
foreach($available as $dir=>$manifest){$state=hd_module_state($dir);if(!$state['installed']){hd_module_load($dir);$fn=str_replace('-','_',$dir).'_installed';if(function_exists($fn)&&$fn()){$enabled=$dir==='livechat'?livechat_enabled():true;hd_module_sync($dir,true,$enabled);}}}
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!isset($_POST['csrf_token'])||!hash_equals($csrf,(string)$_POST['csrf_token']))$msg='<div class="alert alert-danger">The request could not be verified.</div>';
 else{$dir=preg_replace('/[^a-z0-9_-]/','',(string)($_POST['module']??''));$action=(string)($_POST['action']??'');if(!isset($available[$dir])||!in_array($action,array('install','enable','disable','uninstall'),true))$msg='<div class="alert alert-danger">Invalid module action.</div>';else{$ok=hd_module_action($dir,$action);$name=htmlspecialchars($available[$dir]['name']??$dir,ENT_QUOTES,'UTF-8');$past=array('install'=>'installed','enable'=>'enabled','disable'=>'disabled','uninstall'=>'uninstalled');$msg=$ok?'<div class="alert alert-success">'.$name.' was '.$past[$action].' successfully.</div>':'<div class="alert alert-danger">'.$name.' could not be '.$past[$action].'. Check database permissions.</div>';}}
}
$script_name='Modules';include './include/header.php';
?>
<div class="d-sm-flex align-items-center justify-content-between mb-4">
 <div><h1 class="h3 mb-1 text-gray-800">Modules</h1><p class="mb-0 text-muted">Discover, install, and control optional help-desk features.</p></div>
 <a class="btn btn-sm btn-outline-secondary mt-3 mt-sm-0" href="moduleguide.php"><i class="fas fa-book mr-1"></i>How to create a module</a>
</div>
<?php echo $msg ?>
<div class="row">
<?php foreach($available as $dir=>$module):$state=hd_module_state($dir);$icon=preg_replace('/[^a-z0-9-]/','',(string)($module['icon']??'fa-puzzle-piece')); ?>
 <div class="col-xl-6 mb-4"><div class="card shadow-sm h-100">
  <div class="card-body"><div class="d-flex justify-content-between align-items-start mb-3"><div class="d-flex align-items-center"><div class="bg-primary text-white rounded p-3 mr-3"><i class="fas <?php echo $icon ?> fa-2x"></i></div><div><h2 class="h5 mb-1 text-gray-900"><?php echo htmlspecialchars($module['name']??$dir,ENT_QUOTES,'UTF-8') ?></h2><span class="badge badge-<?php echo !$state['installed']?'light':($state['enabled']?'success':'secondary') ?>"><?php echo !$state['installed']?'Not installed':($state['enabled']?'Enabled':'Disabled') ?></span></div></div><span class="small text-muted">v<?php echo htmlspecialchars($module['version']??'',ENT_QUOTES,'UTF-8') ?></span></div><p class="text-muted mb-2"><?php echo htmlspecialchars($module['description']??'',ENT_QUOTES,'UTF-8') ?></p><small class="text-muted">By <?php echo htmlspecialchars($module['author']??'LynxHD',ENT_QUOTES,'UTF-8') ?></small></div>
  <div class="card-footer bg-white d-flex flex-wrap justify-content-end">
  <?php if($state['installed']): ?>
   <?php if($state['enabled']&&!empty($module['manage_url'])): ?><a class="btn btn-primary mr-2" href="<?php echo htmlspecialchars($module['manage_url'],ENT_QUOTES,'UTF-8') ?>"><i class="fas fa-cog mr-1"></i>Manage</a><?php endif; ?>
   <form method="post" class="d-inline mr-2"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf,ENT_QUOTES,'UTF-8') ?>"><input type="hidden" name="module" value="<?php echo htmlspecialchars($dir,ENT_QUOTES,'UTF-8') ?>"><button class="btn btn-outline-<?php echo $state['enabled']?'warning':'success' ?>" name="action" value="<?php echo $state['enabled']?'disable':'enable' ?>"><i class="fas fa-<?php echo $state['enabled']?'pause':'play' ?> mr-1"></i><?php echo $state['enabled']?'Disable':'Enable' ?></button></form>
   <form method="post" onsubmit="return confirm('Uninstall <?php echo htmlspecialchars($module['name']??$dir,ENT_QUOTES,'UTF-8') ?>? All module data will be permanently deleted.')"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf,ENT_QUOTES,'UTF-8') ?>"><input type="hidden" name="module" value="<?php echo htmlspecialchars($dir,ENT_QUOTES,'UTF-8') ?>"><button class="btn btn-outline-danger" name="action" value="uninstall"><i class="fas fa-trash-alt mr-1"></i>Uninstall</button></form>
  <?php else: ?>
   <form method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf,ENT_QUOTES,'UTF-8') ?>"><input type="hidden" name="module" value="<?php echo htmlspecialchars($dir,ENT_QUOTES,'UTF-8') ?>"><button class="btn btn-success" name="action" value="install"><i class="fas fa-download mr-1"></i>Install</button></form>
  <?php endif; ?>
  </div>
 </div></div>
<?php endforeach; ?>
</div>
<?php include './include/footer.php'; ?>
