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
  if( $global_priv )
    mysql_query( "DELETE FROM {$pre}faq WHERE ( id = '{$_GET['id']}' && parent = '-1' )" );

  unset( $_GET['cmd'] );
}
else if( $_POST['cmd'] == "edit" )
{
  if( $global_priv )
  {
    if( trim( $_POST['description'] ?? '' ) != "" )
    {
      if( isset( $_POST['id'] ) )
        mysql_query( "UPDATE {$pre}faq SET description = '{$_POST['description']}', symptoms = '" . ($_POST['symptoms'] ?? '') . "', solution = '" . ($_POST['solution'] ?? '') . "' WHERE ( id = '{$_POST['id']}' )" );
      else
        mysql_query( "INSERT INTO {$pre}faq ( description, symptoms, solution, date, category, parent ) VALUES ( '{$_POST['description']}', '" . ($_POST['symptoms'] ?? '') . "', '" . ($_POST['solution'] ?? '') . "', '" . time( ) . "', '{$_POST['parent']}', '-1' )" );
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
  <?php if($global_priv && $row['id']): ?><div class="mb-3"><a class="btn btn-sm btn-outline-primary mr-2" href="<?php echo field($HD_CURPAGE) ?>?cmd=edit&amp;parent=<?php echo (int)$row['category'] ?>&amp;id=<?php echo (int)$row['id'] ?>">Edit article</a><a class="btn btn-sm btn-outline-danger" href="<?php echo field($HD_CURPAGE) ?>?cmd=deleteentry&amp;parent=<?php echo (int)$row['category'] ?>&amp;id=<?php echo (int)$row['id'] ?>" onclick="return confirm('Delete this article?')">Delete article</a></div><?php endif; ?>
  <article class="card shadow-sm"><div class="card-header"><h2 class="h5 mb-0"><?php echo field($row['description']) ?></h2></div><div class="card-body"><section class="mb-4"><h3 class="h6 text-uppercase text-muted">Symptoms</h3><div><?php echo trim($row['symptoms']) === '' ? '<p class="text-muted">No symptoms.</p>' : render_editor_content($row['symptoms']) ?></div></section><section><h3 class="h6 text-uppercase text-muted">Solution</h3><div><?php echo trim($row['solution']) === '' ? '<p class="text-muted">No solution available.</p>' : render_editor_content($row['solution']) ?></div></section></div></article>
<?php elseif($faq_cmd == 'edit'):
  $row = array('id'=>0,'category'=>$_POST['parent'] ?? 0,'description'=>'','symptoms'=>'','solution'=>'');
  if(isset($_GET['id'])) { $fetched = mysql_fetch_array(mysql_query("SELECT * FROM {$pre}faq WHERE ( id = '{$_GET['id']}' )")); if(is_array($fetched)) $row = array_merge($row, $fetched); }
  $is_entry = $row['category'] != -1;
  $back_id = $is_entry ? $row['category'] : $row['parent'];
?>
  <a class="btn btn-sm btn-light mb-3" href="<?php echo field($HD_CURPAGE) ?>?parent=<?php echo (int)$back_id ?>">&larr; Back to category</a>
  <?php if($global_priv): ?><section class="card shadow-sm faq-editor"><div class="card-header py-3"><h2 class="h6 font-weight-bold text-primary mb-0"><?php echo $row['id'] ? 'Edit' : 'Create' ?> <?php echo $is_entry ? 'article' : 'category' ?></h2></div><div class="card-body"><form action="<?php echo field($HD_CURPAGE) ?>" method="post"><?php if(isset($_GET['id'])): ?><input type="hidden" name="id" value="<?php echo (int)$_GET['id'] ?>"><?php endif; ?><input type="hidden" name="cmd" value="edit"><input type="hidden" name="parent" value="<?php echo (int)($_POST['parent'] ?? 0) ?>"><div class="form-group"><label for="faq-description"><?php echo $is_entry ? 'Article title' : 'Category name' ?> <span class="text-danger">*</span></label><input class="form-control" id="faq-description" type="text" name="description" value="<?php echo field($row['description']) ?>" required><?php if($is_entry): ?><small class="form-text text-muted">Use the question or issue a customer is likely to recognize.</small><?php endif; ?></div><?php if($is_entry): ?><div class="form-group"><label for="faq-symptoms">Problem or symptoms</label><small class="form-text text-muted mb-2">Describe when this article applies and what the customer may observe.</small><textarea class="form-control" id="faq-symptoms" name="symptoms" rows="6"><?php echo field($row['symptoms']) ?></textarea></div><div class="form-group"><div class="d-flex justify-content-between"><label for="faq-solution">Resolution</label><a class="small" href="tickettags.php" target="_blank" rel="noopener">Message tags <i class="fas fa-external-link-alt fa-xs"></i></a></div><small class="form-text text-muted mb-2">Provide clear steps that solve the issue.</small><textarea class="form-control" id="faq-solution" name="solution" rows="8"><?php echo field($row['solution']) ?></textarea></div><?php else: ?><div class="form-group"><label for="faq-symptoms">Short description</label><input class="form-control" id="faq-symptoms" type="text" name="symptoms" value="<?php echo field($row['symptoms']) ?>" placeholder="What content belongs in this category?"></div><?php endif; ?><div class="text-right"><a class="btn btn-light mr-2" href="<?php echo field($HD_CURPAGE) ?>?parent=<?php echo (int)$back_id ?>">Cancel</a><button class="btn btn-primary" type="submit"><i class="fas fa-save mr-1"></i>Save <?php echo $is_entry ? 'article' : 'category' ?></button></div></form></div></section><?php endif; ?>
<?php elseif($faq_cmd == 'search'):
  $search = $_GET['search'] ?? '';
  $res = mysql_query("SELECT * FROM {$pre}faq WHERE ( parent = '-1' && (description LIKE '%$search%' || symptoms LIKE '%$search%' || solution LIKE '%$search%') ) ORDER BY date DESC");
?>
  <a class="btn btn-sm btn-light mb-3" href="<?php echo field($HD_CURPAGE) ?>">&larr; Back to categories</a><div class="card shadow-sm"><div class="card-header"><h2 class="h6 mb-0">Search results for “<?php echo field($search) ?>”</h2></div><div class="list-group list-group-flush"><?php if(!mysql_num_rows($res)): ?><div class="list-group-item text-muted">No results found.</div><?php else: while($row = mysql_fetch_array($res)): $row_cat = mysql_fetch_array(mysql_query("SELECT description FROM {$pre}faq WHERE ( id = '{$row['category']}' )")) ?: array('description'=>''); ?><a class="list-group-item list-group-item-action" href="<?php echo field($HD_CURPAGE) ?>?parent=<?php echo (int)$_POST['parent'] ?>&amp;cmd=view&amp;id=<?php echo (int)$row['id'] ?>"><strong><?php echo field($row['description']) ?></strong><?php if(trim($row_cat['description']) !== ''): ?><small class="text-muted ml-2"><?php echo field($row_cat['description']) ?></small><?php endif; ?></a><?php endwhile; endif; ?></div></div>
<?php endif; ?>
<?php /************************************************************/
include "./include/footer.php";
/********************************************************** PHP */?>
