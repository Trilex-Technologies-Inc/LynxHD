<?php 
////////////////////////////////////////////////////////////////////
// LynxHD Formely ColdBrew Help Desk  
// -----------------------------------------------------------------
//
// License info can be found in license.txt.
// You must leave this notice as is.
//
// LynxHD Formely ColdBrew Helpdesk has been modified and mantained by:
//
//      Old Author: James Paige
//      New Author: Trilex Labs
//         Web: http://www.lynxhd.com
// -----------------------------------------------------------------
////////////////////////////////////////////////////////////////////
include "../include/settings.php";
include "../include/include.php";

$HD_CURPAGE = $HD_URL_FAQADMIN;

if( $_SESSION['login_type'] == $LOGIN_INVALID )
  Header( "Location: {$HD_URL_LOGIN}?redirect=" . urlencode( $HD_CURPAGE ) );

$global_priv = get_row_count( "SELECT COUNT(*) FROM {$pre}privilege WHERE ( user_id = '{$_SESSION['user']['id']}' && dept_id = '0' && admin = '1' )" );

// Keep existing installations compatible with the article metadata feature.
$faq_columns = array();
$faq_column_result = mysql_query("SHOW COLUMNS FROM {$pre}faq");
while ($faq_column_result && ($faq_column = mysql_fetch_array($faq_column_result, MYSQLI_ASSOC)))
  $faq_columns[$faq_column['Field']] = true;
if (!isset($faq_columns['kb_number']))
  mysql_query("ALTER TABLE {$pre}faq ADD kb_number varchar(16) NOT NULL default '' AFTER id");
if (!isset($faq_columns['publish_date']))
  mysql_query("ALTER TABLE {$pre}faq ADD publish_date date default NULL AFTER date");
if (!isset($faq_columns['expiry_date']))
  mysql_query("ALTER TABLE {$pre}faq ADD expiry_date date default NULL AFTER publish_date");
mysql_query("UPDATE {$pre}faq SET kb_number = CONCAT('KB', LPAD(id, 7, '0')) WHERE parent = '-1' AND kb_number = ''");
mysql_query("UPDATE {$pre}faq SET publish_date = FROM_UNIXTIME(date, '%Y-%m-%d') WHERE parent = '-1' AND publish_date IS NULL AND date > 0");

$faq_csrf = $_SESSION['faq_csrf'] ?? '';
if ($faq_csrf === '') {
  $faq_csrf = bin2hex(random_bytes(24));
  $_SESSION['faq_csrf'] = $faq_csrf;
}

function faq_date_value($value)
{
  $value = trim((string)$value);
  $date = DateTime::createFromFormat('Y-m-d', $value);
  return $date && $date->format('Y-m-d') === $value ? $value : '';
}

function faq_store_attachment($article_id, $upload, &$message)
{
  global $HD_KB_FILES;
  if (!is_array($upload) || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE)
    return true;
  if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'])) {
    $message = '<div class="alert alert-warning">The attachment could not be uploaded.</div>';
    return false;
  }
  if (($upload['size'] ?? 0) > 10 * 1024 * 1024) {
    $message = '<div class="alert alert-warning">Attachments must be 10 MB or smaller.</div>';
    return false;
  }
  $name = preg_replace('/[^A-Za-z0-9._ -]/', '_', basename($upload['name']));
  if ($name === '' || $name === '.' || $name === '..') $name = 'attachment';
  $directory = "../{$HD_KB_FILES}/" . (int)$article_id;
  if (!is_dir($directory) && !mkdir($directory, 0775, true)) {
    $message = '<div class="alert alert-warning">The attachment directory could not be created.</div>';
    return false;
  }
  $path_info = pathinfo($name);
  $base = $path_info['filename'];
  $extension = isset($path_info['extension']) ? '.' . $path_info['extension'] : '';
  $candidate = $name;
  for ($suffix = 2; file_exists($directory . '/' . $candidate); $suffix++)
    $candidate = $base . '-' . $suffix . $extension;
  if (!move_uploaded_file($upload['tmp_name'], $directory . '/' . $candidate)) {
    $message = '<div class="alert alert-warning">The attachment could not be saved.</div>';
    return false;
  }
  return true;
}

if( $_POST['cmd'] == "newcategory" )
{
  if( $global_priv )
  {
    if( trim( $_POST['name'] ?? '' ) != "" )
    {
      if( !get_row_count( "SELECT COUNT(*) FROM {$pre}faq WHERE ( parent = '{$_POST['parent']}' && description = '{$_POST['name']}' )" ) )
      {
        $category_created = mysql_query( "INSERT INTO {$pre}faq ( description, symptoms, solution, category, parent, date ) VALUES ( '{$_POST['name']}', '" . ($_POST['description'] ?? '') . "', '', '-1', '{$_POST['parent']}', '" . time( ) . "' )" );
        if( $category_created )
        {
          Header( "Location: {$HD_CURPAGE}?parent=" . urlencode( $_POST['parent'] ) . "&created=1" );
          exit;
        }
        else
          $msg = '<div class="alert alert-danger">The category could not be created: ' . field( mysql_error() ) . '</div>';
      }
      else
        $msg = '<div class="alert alert-warning">A category with that name already exists.</div>';
    }
  }
}
else if( $_GET['cmd'] == "deletecat" )
{
  if( $global_priv )
  {
    $exists = get_row_count( "SELECT COUNT(*) FROM {$pre}faq WHERE ( parent = '{$_GET['id']}' )" );
    if( !$exists )
      mysql_query( "DELETE FROM {$pre}faq WHERE ( category = '{$_GET['id']}' || id = '{$_GET['id']}' )" );
    else
      $msg = "<div class=\"errorbox\">You must delete this category's subcategories first.</div><br />";
  }
}
else if( $_GET['cmd'] == "deleteentry" )
{
  if( $global_priv ) {
    $delete_id = (int)($_GET['id'] ?? 0);
    mysql_query( "DELETE FROM {$pre}faq WHERE ( id = '$delete_id' && parent = '-1' )" );
    $delete_directory = "../{$HD_KB_FILES}/{$delete_id}";
    if (is_dir($delete_directory)) {
      foreach (glob($delete_directory . '/*') ?: array() as $delete_file)
        if (is_file($delete_file)) unlink($delete_file);
      rmdir($delete_directory);
    }
  }

  unset( $_GET['cmd'] );
}
else if( $_POST['cmd'] == "edit" )
{
  if( $global_priv )
  {
    if (!hash_equals($faq_csrf, (string)($_POST['csrf_token'] ?? ''))) {
      $msg = '<div class="alert alert-danger">The request could not be verified.</div>';
    }
    else if( trim( $_POST['description'] ?? '' ) != "" )
    {
      $editing_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
      $existing = $editing_id ? mysql_fetch_array(mysql_query("SELECT category FROM {$pre}faq WHERE id = '$editing_id'")) : false;
      $is_article_edit = !$existing || (int)$existing['category'] !== -1;
      $publish_date = faq_date_value($_POST['publish_date'] ?? '');
      $expiry_date = faq_date_value($_POST['expiry_date'] ?? '');
      if ($is_article_edit && $publish_date === '') $publish_date = date('Y-m-d');
      if ($is_article_edit && $expiry_date !== '' && $expiry_date < $publish_date) {
        $msg = '<div class="alert alert-warning">Expiry date cannot be earlier than publish date.</div>';
      } else {
        $publish_sql = $is_article_edit ? "'{$publish_date}'" : 'NULL';
        $expiry_sql = $is_article_edit && $expiry_date !== '' ? "'{$expiry_date}'" : 'NULL';
        if( $editing_id ) {
          mysql_query( "UPDATE {$pre}faq SET description = '{$_POST['description']}', symptoms = '" . ($_POST['symptoms'] ?? '') . "', solution = '" . ($_POST['solution'] ?? '') . "', publish_date = $publish_sql, expiry_date = $expiry_sql WHERE ( id = '$editing_id' )" );
          $article_id = $editing_id;
        } else {
          mysql_query( "INSERT INTO {$pre}faq ( description, symptoms, solution, date, category, parent, publish_date, expiry_date ) VALUES ( '{$_POST['description']}', '" . ($_POST['symptoms'] ?? '') . "', '" . ($_POST['solution'] ?? '') . "', '" . time( ) . "', '{$_POST['parent']}', '-1', $publish_sql, $expiry_sql )" );
          $article_id = mysql_insert_id();
          mysql_query("UPDATE {$pre}faq SET kb_number = CONCAT('KB', LPAD(id, 7, '0')) WHERE id = '" . (int)$article_id . "'");
        }
        if ($is_article_edit) faq_store_attachment($article_id, $_FILES['attachment'] ?? null, $msg);
        if (!isset($msg) || $msg === '') {
          Header("Location: {$HD_CURPAGE}?parent=" . (int)($_POST['parent'] ?? 0) . "&cmd=view&id=" . (int)$article_id . "&saved=1");
          exit;
        }
      }
    }
  }

  unset( $_POST['cmd'] );
}


if( !isset( $_POST['parent'] ) )
{
  if( isset( $_GET['parent'] ) )
    $_POST['parent'] = $_GET['parent'];
  else
    $_POST['parent'] = 0;
}

$res = mysql_query( "SELECT description, symptoms, parent FROM {$pre}faq WHERE ( id = '{$_POST['parent']}' )" );
if( mysql_num_rows( $res ) )
  $row_cat = mysql_fetch_array( $res );
else
{
  $row_cat['description'] = "Main";
  $row_cat['symptoms'] = "";
  $row_cat['parent'] = -1;
  $_POST['parent'] = 0;
}

if( isset( $_GET['cmd'] ) )
  $_POST['cmd'] = $_GET['cmd'];

$category_path = array();
$path_id = (int)$_POST['parent'];
$path_guard = 0;
while( $path_id > 0 && $path_guard < 50 )
{
  $path_row = mysql_fetch_array( mysql_query( "SELECT id, description, parent FROM {$pre}faq WHERE ( id = '$path_id' && category = '-1' )" ) );
  if( !$path_row )
    break;
  array_unshift( $category_path, $path_row );
  $path_id = (int)$path_row['parent'];
  $path_guard++;
}

include "./include/header.php";
/********************************************************** PHP */?>
<div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between mb-4"><div><h1 class="h3 mb-1 text-gray-800">Knowledge Base</h1><p class="text-muted mb-0">Organize support articles into searchable categories.</p></div><form class="form-inline mt-3 mt-lg-0" action="<?php echo field($HD_CURPAGE) ?>" method="get"><input type="hidden" name="cmd" value="search"><label class="sr-only" for="faq-search">Search</label><input class="form-control mr-2" id="faq-search" type="search" name="search" value="<?php echo field($_GET['search'] ?? '') ?>" placeholder="Search articles" required><button class="btn btn-primary" type="submit"><i class="fas fa-search mr-1"></i>Search</button></form></div>
<?php echo $msg ?? '' ?>
<?php if(($_GET['created'] ?? '') == '1'): ?><div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i>Category created successfully. It is listed below.</div><?php endif; ?>
<?php
$faq_cmd = $_POST['cmd'] ?? '';
if( $faq_cmd == '' || $faq_cmd == 'deletecat' || $faq_cmd == 'newcategory' ):
?>
  <nav aria-label="Knowledge base location"><ol class="breadcrumb faq-breadcrumb shadow-sm mb-4"><li class="breadcrumb-item<?php echo empty($category_path) ? ' active' : '' ?>"><?php if(empty($category_path)): ?><span aria-current="page"><i class="fas fa-home mr-1"></i>Knowledge base home</span><?php else: ?><a href="<?php echo field($HD_CURPAGE) ?>"><i class="fas fa-home mr-1"></i>Knowledge base home</a><?php endif; ?></li><?php foreach($category_path as $path_index => $path_item): $path_current = $path_index === count($category_path) - 1; ?><li class="breadcrumb-item<?php echo $path_current ? ' active' : '' ?>"><?php if($path_current): ?><span aria-current="page"><?php echo field($path_item['description']) ?></span><?php else: ?><a href="<?php echo field($HD_CURPAGE) ?>?parent=<?php echo (int)$path_item['id'] ?>"><?php echo field($path_item['description']) ?></a><?php endif; ?></li><?php endforeach; ?></ol></nav>

  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4"><div><h2 class="h4 text-gray-800 mb-1"><?php echo $_POST['parent'] == 0 ? 'Knowledge base home' : field($row_cat['description']) ?></h2><p class="text-muted mb-0"><?php echo $_POST['parent'] == 0 ? 'Choose a category to browse its articles, or create content from here.' : (trim($row_cat['symptoms']) !== '' ? field($row_cat['symptoms']) : 'Browse and manage the content in this category.') ?></p></div><?php if($global_priv): ?><div class="d-flex flex-wrap mt-3 mt-md-0 faq-primary-actions"><a class="btn btn-primary mr-2" href="<?php echo field($HD_CURPAGE) ?>?cmd=edit&amp;parent=<?php echo (int)$_POST['parent'] ?>"><i class="fas fa-file-alt mr-1"></i>New article</a><button class="btn btn-outline-primary" type="button" data-toggle="collapse" data-target="#create-category" aria-expanded="false" aria-controls="create-category"><i class="fas fa-folder-plus mr-1"></i>New subcategory</button></div><?php endif; ?></div>

  <?php if($global_priv): ?>
  <div class="collapse<?php echo isset($msg) && ($_POST['cmd'] ?? '') == 'newcategory' ? ' show' : '' ?>" id="create-category"><div class="card border-left-primary shadow-sm mb-4"><div class="card-header py-3"><h3 class="h6 m-0 font-weight-bold text-primary">Create a subcategory in <?php echo $_POST['parent'] == 0 ? 'Knowledge base home' : field($row_cat['description']) ?></h3></div><div class="card-body"><form action="<?php echo field($HD_CURPAGE) ?>" method="post"><input type="hidden" name="cmd" value="newcategory"><input type="hidden" name="parent" value="<?php echo (int)$_POST['parent'] ?>"><div class="form-row"><div class="form-group col-md-5"><label for="category-name">Category name <span class="text-danger">*</span></label><input class="form-control" id="category-name" type="text" name="name" required placeholder="e.g. Account and billing"></div><div class="form-group col-md-5"><label for="category-description">Short description</label><input class="form-control" id="category-description" type="text" name="description" placeholder="What will users find here?"></div><div class="form-group col-md-2 d-flex align-items-end"><button class="btn btn-primary btn-block" type="submit">Create</button></div></div></form></div></div></div>
  <?php if($_POST['parent'] != 0): ?><div class="d-flex flex-wrap justify-content-end mb-3"><a class="btn btn-link btn-sm text-secondary" href="<?php echo field($HD_CURPAGE) ?>?cmd=edit&amp;id=<?php echo (int)$_POST['parent'] ?>&amp;parent=<?php echo (int)$_POST['parent'] ?>"><i class="fas fa-edit mr-1"></i>Edit this category</a><a class="btn btn-link btn-sm text-danger" href="<?php echo field($HD_CURPAGE) ?>?cmd=deletecat&amp;id=<?php echo (int)$_POST['parent'] ?>&amp;parent=<?php echo (int)$_POST['parent'] ?>" onclick="return confirm('Delete this category and all articles inside it? This cannot be undone.')"><i class="fas fa-trash-alt mr-1"></i>Delete this category</a></div><?php endif; ?>
  <?php endif; ?>

  <?php $res = mysql_query("SELECT id, description, symptoms FROM {$pre}faq WHERE ( parent = '{$_POST['parent']}' && category = '-1' ) ORDER BY description"); ?>
  <div class="d-flex align-items-center mb-3"><span class="faq-section-icon"><i class="fas fa-folder"></i></span><div><h2 class="h5 mb-0 text-gray-800">Subcategories</h2><small class="text-muted">Open a folder to see its articles and nested categories.</small></div></div><?php if(mysql_num_rows($res)): ?><div class="row mb-4"><?php while($row = mysql_fetch_array($res)): $items = get_row_count("SELECT COUNT(*) FROM {$pre}faq WHERE ( category = '{$row['id']}' )"); $subcats = get_row_count("SELECT COUNT(*) FROM {$pre}faq WHERE ( parent = '{$row['id']}' && category = '-1' )"); ?><div class="col-md-6 mb-3"><a class="card shadow-sm h-100 text-decoration-none faq-category-card" href="<?php echo field($HD_CURPAGE) ?>?parent=<?php echo (int)$row['id'] ?>"><div class="card-body d-flex"><span class="faq-folder-icon"><i class="fas fa-folder"></i></span><div class="flex-grow-1"><h3 class="h6 text-primary mb-1"><?php echo field($row['description']) ?></h3><p class="text-muted small mb-2"><?php echo trim($row['symptoms']) !== '' ? field($row['symptoms']) : 'No description provided.' ?></p><span class="badge badge-light border mr-1"><?php echo $subcats ?> subcategor<?php echo $subcats == 1 ? 'y' : 'ies' ?></span><span class="badge badge-light border"><?php echo $items ?> article<?php echo $items == 1 ? '' : 's' ?></span></div><i class="fas fa-chevron-right text-gray-300 align-self-center ml-3"></i></div></a></div><?php endwhile; ?></div><?php else: ?><div class="faq-empty-state mb-4"><i class="far fa-folder-open"></i><div><strong>No subcategories here</strong><p class="mb-0">Articles can still be added directly to this location.</p></div></div><?php endif; ?>
  <?php $res = mysql_query("SELECT id, description FROM {$pre}faq WHERE ( category = '{$_POST['parent']}' ) ORDER BY description"); ?>
  <div class="card shadow-sm mb-4 faq-articles"><div class="card-header py-3 d-flex align-items-center"><span class="faq-section-icon mr-2"><i class="fas fa-file-alt"></i></span><div><h2 class="h6 font-weight-bold text-primary mb-0">Articles</h2><small class="text-muted">Support answers stored directly in this location.</small></div></div><div class="list-group list-group-flush"><?php if(!mysql_num_rows($res)): ?><div class="list-group-item faq-empty-state border-0"><i class="far fa-file-alt"></i><div><strong>No articles yet</strong><p class="mb-0"><?php echo $global_priv ? 'Create the first article using the button above.' : 'There are no published answers in this location.' ?></p></div></div><?php else: while($row = mysql_fetch_array($res)): ?><a class="list-group-item list-group-item-action d-flex align-items-center justify-content-between" href="<?php echo field($HD_CURPAGE) ?>?parent=<?php echo (int)$_POST['parent'] ?>&amp;cmd=view&amp;id=<?php echo (int)$row['id'] ?>"><span><i class="far fa-file-alt text-gray-400 mr-2"></i><?php echo field($row['description']) ?></span><i class="fas fa-chevron-right text-gray-300" aria-hidden="true"></i></a><?php endwhile; endif; ?></div></div>
<?php elseif($faq_cmd == 'view'):
  $faq_id = $_GET['id'] ?? '';
  $res = mysql_query("SELECT * FROM {$pre}faq WHERE ( id = '$faq_id' )");
  $row = mysql_fetch_array($res) ?: array('id'=>0,'category'=>0,'description'=>'Article not found','symptoms'=>'','solution'=>'');
?>
  <a class="btn btn-sm btn-light mb-3" href="<?php echo field($HD_CURPAGE) ?>?parent=<?php echo (int)$row['category'] ?>">&larr; Back to category</a>
  <?php if(($_GET['saved'] ?? '') === '1'): ?><div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i>Article saved successfully.</div><?php endif; ?>
  <?php if($global_priv && $row['id']): ?><div class="mb-3"><a class="btn btn-sm btn-outline-primary mr-2" href="<?php echo field($HD_CURPAGE) ?>?cmd=edit&amp;parent=<?php echo (int)$row['category'] ?>&amp;id=<?php echo (int)$row['id'] ?>">Edit article</a><a class="btn btn-sm btn-outline-danger" href="<?php echo field($HD_CURPAGE) ?>?cmd=deleteentry&amp;parent=<?php echo (int)$row['category'] ?>&amp;id=<?php echo (int)$row['id'] ?>" onclick="return confirm('Delete this article?')">Delete article</a></div><?php endif; ?>
  <?php $article_files = array(); $article_dir = "../{$HD_KB_FILES}/" . (int)$row['id']; if($dir = @opendir($article_dir)) { while(($file = readdir($dir)) !== false) if($file !== '.' && $file !== '..' && is_file($article_dir . '/' . $file)) $article_files[] = $file; closedir($dir); sort($article_files, SORT_NATURAL | SORT_FLAG_CASE); } ?>
  <article class="card shadow-sm"><div class="card-header d-flex flex-wrap justify-content-between align-items-center"><h2 class="h5 mb-0"><?php echo field($row['description']) ?></h2><?php if(!empty($row['kb_number'])): ?><span class="badge badge-primary"><?php echo field($row['kb_number']) ?></span><?php endif; ?></div><div class="card-body"><dl class="row small mb-4"><dt class="col-sm-2">Publish date</dt><dd class="col-sm-4"><?php echo field($row['publish_date'] ?: 'Not set') ?></dd><dt class="col-sm-2">Expiry date</dt><dd class="col-sm-4"><?php echo field($row['expiry_date'] ?: 'No expiry') ?></dd></dl><section class="mb-4"><h3 class="h6 text-uppercase text-muted">Symptoms</h3><div><?php echo trim($row['symptoms']) === '' ? '<p class="text-muted">No symptoms.</p>' : render_editor_content($row['symptoms']) ?></div></section><section class="mb-4"><h3 class="h6 text-uppercase text-muted">Solution</h3><div><?php echo trim($row['solution']) === '' ? '<p class="text-muted">No solution available.</p>' : render_editor_content($row['solution']) ?></div></section><?php if($article_files): ?><section><h3 class="h6 text-uppercase text-muted">Attachments</h3><div class="list-group list-group-flush"><?php foreach($article_files as $article_file): ?><a class="list-group-item list-group-item-action px-0" href="../kbattachment.php?id=<?php echo (int)$row['id'] ?>&amp;file=<?php echo urlencode($article_file) ?>" target="_blank"><i class="fas fa-paperclip mr-2"></i><?php echo field($article_file) ?></a><?php endforeach; ?></div></section><?php endif; ?></div></article>
<?php elseif($faq_cmd == 'edit'):
  $row = array('id'=>0,'category'=>$_POST['parent'] ?? 0,'description'=>'','symptoms'=>'','solution'=>'');
  if(isset($_GET['id'])) { $fetched = mysql_fetch_array(mysql_query("SELECT * FROM {$pre}faq WHERE ( id = '{$_GET['id']}' )")); if(is_array($fetched)) $row = array_merge($row, $fetched); }
  $is_entry = $row['category'] != -1;
  $back_id = $is_entry ? $row['category'] : $row['parent'];
?>
  <a class="btn btn-sm btn-light mb-3" href="<?php echo field($HD_CURPAGE) ?>?parent=<?php echo (int)$back_id ?>">&larr; Back to category</a>
  <?php if($global_priv): ?><section class="card shadow-sm faq-editor"><div class="card-header py-3"><h2 class="h6 font-weight-bold text-primary mb-0"><?php echo $row['id'] ? 'Edit' : 'Create' ?> <?php echo $is_entry ? 'article' : 'category' ?></h2></div><div class="card-body"><form action="<?php echo field($HD_CURPAGE) ?>" method="post" enctype="multipart/form-data"><?php if(isset($_GET['id'])): ?><input type="hidden" name="id" value="<?php echo (int)$_GET['id'] ?>"><?php endif; ?><input type="hidden" name="csrf_token" value="<?php echo field($faq_csrf) ?>"><input type="hidden" name="cmd" value="edit"><input type="hidden" name="parent" value="<?php echo (int)($_POST['parent'] ?? 0) ?>"><?php if($is_entry): ?><div class="form-row"><div class="form-group col-md-4"><label for="faq-number">Knowledge base number</label><input class="form-control" id="faq-number" value="<?php echo field($row['kb_number'] ?? 'Assigned when saved') ?>" readonly><small class="form-text text-muted">Generated automatically and cannot be changed.</small></div><div class="form-group col-md-4"><label for="faq-publish-date">Publish date <span class="text-danger">*</span></label><input class="form-control" id="faq-publish-date" type="date" name="publish_date" value="<?php echo field($row['publish_date'] ?? date('Y-m-d')) ?>" required></div><div class="form-group col-md-4"><label for="faq-expiry-date">Expiry date</label><input class="form-control" id="faq-expiry-date" type="date" name="expiry_date" value="<?php echo field($row['expiry_date'] ?? '') ?>" min="<?php echo field($row['publish_date'] ?? date('Y-m-d')) ?>"><small class="form-text text-muted">Leave blank to keep published indefinitely.</small></div></div><?php endif; ?><div class="form-group"><label for="faq-description"><?php echo $is_entry ? 'Article title' : 'Category name' ?> <span class="text-danger">*</span></label><input class="form-control" id="faq-description" type="text" name="description" value="<?php echo field($row['description']) ?>" required><?php if($is_entry): ?><small class="form-text text-muted">Use the question or issue a customer is likely to recognize.</small><?php endif; ?></div><?php if($is_entry): ?><div class="form-group"><label for="faq-symptoms">Problem or symptoms</label><small class="form-text text-muted mb-2">Describe when this article applies and what the customer may observe.</small><textarea class="form-control" id="faq-symptoms" name="symptoms" rows="6"><?php echo field($row['symptoms']) ?></textarea></div><div class="form-group"><div class="d-flex justify-content-between"><label for="faq-solution">Resolution</label><a class="small" href="tickettags.php" target="_blank" rel="noopener">Message tags <i class="fas fa-external-link-alt fa-xs"></i></a></div><small class="form-text text-muted mb-2">Provide clear steps that solve the issue.</small><textarea class="form-control" id="faq-solution" name="solution" rows="8"><?php echo field($row['solution']) ?></textarea></div><div class="form-group"><label for="faq-attachment">Attachment</label><div class="custom-file"><input class="custom-file-input" id="faq-attachment" name="attachment" type="file"><label class="custom-file-label" for="faq-attachment">Choose file</label></div><small class="form-text text-muted">Maximum file size: 10 MB. Existing attachments are retained.</small></div><?php else: ?><div class="form-group"><label for="faq-symptoms">Short description</label><input class="form-control" id="faq-symptoms" type="text" name="symptoms" value="<?php echo field($row['symptoms']) ?>" placeholder="What content belongs in this category?"></div><?php endif; ?><div class="text-right"><a class="btn btn-light mr-2" href="<?php echo field($HD_CURPAGE) ?>?parent=<?php echo (int)$back_id ?>">Cancel</a><button class="btn btn-primary" type="submit"><i class="fas fa-save mr-1"></i>Save <?php echo $is_entry ? 'article' : 'category' ?></button></div></form></div></section><?php endif; ?>
<?php elseif($faq_cmd == 'search'):
  $search = $_GET['search'] ?? '';
  $res = mysql_query("SELECT * FROM {$pre}faq WHERE ( parent = '-1' && (description LIKE '%$search%' || symptoms LIKE '%$search%' || solution LIKE '%$search%') ) ORDER BY date DESC");
?>
  <a class="btn btn-sm btn-light mb-3" href="<?php echo field($HD_CURPAGE) ?>">&larr; Back to categories</a><div class="card shadow-sm"><div class="card-header"><h2 class="h6 mb-0">Search results for “<?php echo field($search) ?>”</h2></div><div class="list-group list-group-flush"><?php if(!mysql_num_rows($res)): ?><div class="list-group-item text-muted">No results found.</div><?php else: while($row = mysql_fetch_array($res)): $row_cat = mysql_fetch_array(mysql_query("SELECT description FROM {$pre}faq WHERE ( id = '{$row['category']}' )")) ?: array('description'=>''); ?><a class="list-group-item list-group-item-action" href="<?php echo field($HD_CURPAGE) ?>?parent=<?php echo (int)$_POST['parent'] ?>&amp;cmd=view&amp;id=<?php echo (int)$row['id'] ?>"><strong><?php echo field($row['description']) ?></strong><?php if(trim($row_cat['description']) !== ''): ?><small class="text-muted ml-2"><?php echo field($row_cat['description']) ?></small><?php endif; ?></a><?php endwhile; endif; ?></div></div>
<?php endif; ?>
<?php /************************************************************/
include "./include/footer.php";
/********************************************************** PHP */?>
