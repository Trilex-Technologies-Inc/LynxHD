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

$res = mysql_query( "SELECT description, parent FROM {$pre}faq WHERE ( id = '{$_POST['parent']}' )" );
if( mysql_num_rows( $res ) )
  $row_cat = mysql_fetch_array( $res );
else
{
  $row_cat['description'] = "Main";
  $row_cat['parent'] = -1;
  $_POST['parent'] = 0;
}

if( isset( $_GET['cmd'] ) )
  $_POST['cmd'] = $_GET['cmd'];

include "./include/header.php";
/********************************************************** PHP */?>
<div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between mb-4"><div><h1 class="h3 mb-1 text-gray-800">Knowledge Base</h1><p class="text-muted mb-0">Organize support articles into searchable categories.</p></div><form class="form-inline mt-3 mt-lg-0" action="<?php echo field($HD_CURPAGE) ?>" method="get"><input type="hidden" name="cmd" value="search"><label class="sr-only" for="faq-search">Search</label><input class="form-control mr-2" id="faq-search" type="search" name="search" value="<?php echo field($_GET['search'] ?? '') ?>" placeholder="Search articles" required><button class="btn btn-primary" type="submit"><i class="fas fa-search mr-1"></i>Search</button></form></div>
<?php echo $msg ?? '' ?>
<?php if(($_GET['created'] ?? '') == '1'): ?><div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i>Category created successfully. It is listed below.</div><?php endif; ?>
<?php
$faq_cmd = $_POST['cmd'] ?? '';
if( $faq_cmd == '' || $faq_cmd == 'deletecat' || $faq_cmd == 'newcategory' ):
?>
  <nav class="mb-3" aria-label="Breadcrumb"><a href="<?php echo field($HD_CURPAGE) ?>">Main category</a><?php if($row_cat['parent'] != -1): ?> <span class="text-muted mx-2">/</span><a href="<?php echo field($HD_CURPAGE) ?>?parent=<?php echo (int)$row_cat['parent'] ?>">Parent category</a><?php endif; ?><span class="text-muted mx-2">/</span><strong><?php echo field($row_cat['description']) ?></strong></nav>

  <?php if($global_priv): ?>
  <div class="card shadow-sm mb-4"><div class="card-header"><h2 class="h6 mb-0">Create category</h2></div><div class="card-body"><form action="<?php echo field($HD_CURPAGE) ?>" method="post"><input type="hidden" name="cmd" value="newcategory"><input type="hidden" name="parent" value="<?php echo (int)$_POST['parent'] ?>"><div class="form-row"><div class="form-group col-md-5"><label for="category-name">Category name</label><input class="form-control" id="category-name" type="text" name="name" required></div><div class="form-group col-md-5"><label for="category-description">Description</label><input class="form-control" id="category-description" type="text" name="description"></div><div class="form-group col-md-2 d-flex align-items-end"><button class="btn btn-primary btn-block" type="submit">Create</button></div></div><?php if($_POST['parent'] != 0): ?><small class="text-muted">This will be created inside the current category.</small><?php endif; ?></form></div></div>
  <div class="d-flex flex-wrap mb-4"><a class="btn btn-primary mr-2 mb-2" href="<?php echo field($HD_CURPAGE) ?>?cmd=edit&amp;parent=<?php echo (int)$_POST['parent'] ?>"><i class="fas fa-plus mr-1"></i>New article</a><?php if($_POST['parent'] != 0): ?><a class="btn btn-outline-secondary mr-2 mb-2" href="<?php echo field($HD_CURPAGE) ?>?cmd=edit&amp;id=<?php echo (int)$_POST['parent'] ?>&amp;parent=<?php echo (int)$_POST['parent'] ?>">Edit category</a><a class="btn btn-outline-danger mb-2" href="<?php echo field($HD_CURPAGE) ?>?cmd=deletecat&amp;id=<?php echo (int)$_POST['parent'] ?>&amp;parent=<?php echo (int)$_POST['parent'] ?>" onclick="return confirm('Delete this category and all its entries?')">Delete category</a><?php endif; ?></div>
  <?php endif; ?>

  <?php $res = mysql_query("SELECT id, description, symptoms FROM {$pre}faq WHERE ( parent = '{$_POST['parent']}' && category = '-1' ) ORDER BY description"); ?>
  <h2 class="h5 mb-3">Categories</h2><?php if(mysql_num_rows($res)): ?><div class="row mb-4"><?php while($row = mysql_fetch_array($res)): $items = get_row_count("SELECT COUNT(*) FROM {$pre}faq WHERE ( category = '{$row['id']}' )"); $subcats = get_row_count("SELECT COUNT(*) FROM {$pre}faq WHERE ( parent = '{$row['id']}' && category = '-1' )"); ?><div class="col-md-6 mb-3"><a class="card shadow-sm h-100 text-decoration-none" href="<?php echo field($HD_CURPAGE) ?>?parent=<?php echo (int)$row['id'] ?>"><div class="card-body"><h3 class="h6 text-primary"><?php echo field($row['description']) ?></h3><p class="text-muted small mb-2"><?php echo trim($row['symptoms']) !== '' ? field($row['symptoms']) : 'No description' ?></p><span class="badge badge-light mr-1"><?php echo $subcats ?> subcategories</span><span class="badge badge-light"><?php echo $items ?> articles</span></div></a></div><?php endwhile; ?></div><?php else: ?><div class="alert alert-light border mb-4">No categories have been created at this level yet.</div><?php endif; ?>
  <?php $res = mysql_query("SELECT id, description FROM {$pre}faq WHERE ( category = '{$_POST['parent']}' ) ORDER BY description"); ?>
  <div class="card shadow-sm mb-4"><div class="card-header"><h2 class="h6 mb-0">Articles</h2></div><div class="list-group list-group-flush"><?php if(!mysql_num_rows($res)): ?><div class="list-group-item text-muted">No articles in this category.</div><?php else: while($row = mysql_fetch_array($res)): ?><a class="list-group-item list-group-item-action d-flex justify-content-between" href="<?php echo field($HD_CURPAGE) ?>?parent=<?php echo (int)$_POST['parent'] ?>&amp;cmd=view&amp;id=<?php echo (int)$row['id'] ?>"><span><?php echo field($row['description']) ?></span><span aria-hidden="true">&rarr;</span></a><?php endwhile; endif; ?></div></div>
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
  $back_id = $is_entry ? $row['category'] : $row['id'];
?>
  <a class="btn btn-sm btn-light mb-3" href="<?php echo field($HD_CURPAGE) ?>?parent=<?php echo (int)$back_id ?>">&larr; Back to category</a>
  <?php if($global_priv): ?><section class="card shadow-sm"><div class="card-header"><h2 class="h6 mb-0"><?php echo $row['id'] ? 'Edit' : 'Create' ?> <?php echo $is_entry ? 'article' : 'category' ?></h2></div><div class="card-body"><form action="<?php echo field($HD_CURPAGE) ?>" method="post"><?php if(isset($_GET['id'])): ?><input type="hidden" name="id" value="<?php echo (int)$_GET['id'] ?>"><?php endif; ?><input type="hidden" name="cmd" value="edit"><input type="hidden" name="parent" value="<?php echo (int)($_POST['parent'] ?? 0) ?>"><div class="form-group"><label for="faq-description"><?php echo $is_entry ? 'Description' : 'Category name' ?></label><input class="form-control" id="faq-description" type="text" name="description" value="<?php echo field($row['description']) ?>" required></div><?php if($is_entry): ?><div class="form-group"><label for="faq-symptoms">Symptoms</label><textarea class="form-control" id="faq-symptoms" name="symptoms" rows="6"><?php echo field($row['symptoms']) ?></textarea></div><div class="form-group"><div class="d-flex justify-content-between"><label for="faq-solution">Solution</label><a class="small" href="tickettags.php" target="_blank">Message tags</a></div><textarea class="form-control" id="faq-solution" name="solution" rows="8"><?php echo field($row['solution']) ?></textarea></div><?php else: ?><div class="form-group"><label for="faq-symptoms">Description</label><input class="form-control" id="faq-symptoms" type="text" name="symptoms" value="<?php echo field($row['symptoms']) ?>"></div><?php endif; ?><div class="text-right"><button class="btn btn-light mr-2" type="reset">Reset</button><button class="btn btn-primary" type="submit">Save</button></div></form></div></section><?php endif; ?>
<?php elseif($faq_cmd == 'search'):
  $search = $_GET['search'] ?? '';
  $res = mysql_query("SELECT * FROM {$pre}faq WHERE ( parent = '-1' && (description LIKE '%$search%' || symptoms LIKE '%$search%' || solution LIKE '%$search%') ) ORDER BY date DESC");
?>
  <a class="btn btn-sm btn-light mb-3" href="<?php echo field($HD_CURPAGE) ?>">&larr; Back to categories</a><div class="card shadow-sm"><div class="card-header"><h2 class="h6 mb-0">Search results for “<?php echo field($search) ?>”</h2></div><div class="list-group list-group-flush"><?php if(!mysql_num_rows($res)): ?><div class="list-group-item text-muted">No results found.</div><?php else: while($row = mysql_fetch_array($res)): $row_cat = mysql_fetch_array(mysql_query("SELECT description FROM {$pre}faq WHERE ( id = '{$row['category']}' )")) ?: array('description'=>''); ?><a class="list-group-item list-group-item-action" href="<?php echo field($HD_CURPAGE) ?>?parent=<?php echo (int)$_POST['parent'] ?>&amp;cmd=view&amp;id=<?php echo (int)$row['id'] ?>"><strong><?php echo field($row['description']) ?></strong><?php if(trim($row_cat['description']) !== ''): ?><small class="text-muted ml-2"><?php echo field($row_cat['description']) ?></small><?php endif; ?></a><?php endwhile; endif; ?></div></div>
<?php endif; ?>
<?php /************************************************************/
include "./include/footer.php";
/********************************************************** PHP */?>
