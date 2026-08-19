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
include "./include/settings.php";
include "./include/include.php";

$HD_CURPAGE = $HD_URL_FAQ;

$options = array( "header", "footer", "logo", "title", "background", "outsidebackground", "border", "topbar", "menu", "styles", "email", "url", "emailheader", "emailfooter" );
$data = get_options( $options );

$success = 0;

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

if( trim( $data['header'] ) == "" )
{
/********************************************************** PHP */?>
<?php 
include "./include/header.php";
?>
<?php /************************************************************/
}
else
  eval( "?> {$data['header']} <?php" );
/********************************************************** PHP */?>
<?php if (trim($data['styles']) !== ''): ?>
<style><?php echo $data['styles'] ?></style>
<?php endif; ?>
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
  <div>
    <span class="badge text-bg-primary mb-2">Help center</span>
    <h2 class="h3 mb-1"><?php echo $LANG['knowledge_base'] ?></h2>
    <p class="text-secondary mb-0">Find answers, troubleshooting steps, and helpful information.</p>
  </div>
  <form action="<?php echo $HD_CURPAGE ?>" method="get" class="faq-search" role="search">
    <input type="hidden" name="cmd" value="search">
    <label class="visually-hidden" for="faq-search"><?php echo $LANG['search_for'] ?></label>
    <div class="input-group input-group-lg">
      <input class="form-control" id="faq-search" type="search" name="search" value="<?php echo field($_GET['search'] ?? '') ?>" placeholder="Search the knowledge base" required>
      <button class="btn btn-primary" type="submit"><?php echo $LANG['faq_search_button'] ?></button>
    </div>
  </form>
</div>
<?php echo $msg ?? '' ?>
<?php /************************************************************/
if( !isset( $_POST['cmd'] ) )
{
/********************************************************** PHP */?>
<nav aria-label="Knowledge base breadcrumb" class="mb-4">
  <ol class="breadcrumb mb-2">
    <li class="breadcrumb-item"><a href="<?php echo $HD_CURPAGE ?>"><?php echo $LANG['faq_main_category'] ?></a></li>
    <?php if ($row_cat['parent'] != -1): ?>
      <li class="breadcrumb-item"><a href="<?php echo $HD_CURPAGE ?>?parent=<?php echo (int) $row_cat['parent'] ?>"><?php echo $LANG['faq_parent_category'] ?></a></li>
    <?php endif; ?>
    <li class="breadcrumb-item active" aria-current="page"><?php echo field($row_cat['description']) ?></li>
  </ol>
</nav>
<h3 class="h5 mb-3"><?php echo $LANG['faq_browsing'] ?> “<?php echo field($row_cat['description']) ?>”</h3>
<?php /************************************************************/
  $res = mysql_query( "SELECT id, description, symptoms FROM {$pre}faq WHERE ( parent = '{$_POST['parent']}' ) ORDER BY description" );
  if( mysql_num_rows( $res ) )
  {
    echo '<div class="row g-3 mb-4">';
    while( $row = mysql_fetch_array( $res ) )
    {
      $items = get_row_count( "SELECT COUNT(*) FROM {$pre}faq WHERE ( category = '{$row['id']}' )" );
      $subcats = get_row_count( "SELECT COUNT(*) FROM {$pre}faq WHERE ( parent = '{$row['id']}' )" );
      $description = trim($row['symptoms']) !== '' ? field($row['symptoms']) : $LANG['faq_no_description'];
      echo '<div class="col-md-6"><a class="faq-category card h-100 border shadow-sm text-decoration-none" href="' . $HD_CURPAGE . '?parent=' . (int) $row['id'] . '"><div class="card-body p-4">';
      echo '<div class="d-flex justify-content-between align-items-start gap-3"><h4 class="h5 text-body mb-2">' . field($row['description']) . '</h4><span class="faq-arrow" aria-hidden="true">&rarr;</span></div>';
      echo '<p class="text-secondary mb-3">' . $description . '</p>';
      echo '<div class="d-flex gap-2"><span class="badge text-bg-light">' . (int) $items . ' articles</span>';
      if ($subcats) echo '<span class="badge text-bg-light">' . (int) $subcats . ' categories</span>';
      echo '</div></div></a></div>';
    }
    echo '</div>';
  }

  $res = mysql_query( "SELECT id, description FROM {$pre}faq WHERE ( category = '{$_POST['parent']}' ) ORDER BY description" );
  if( mysql_num_rows( $res ) )
  {
    echo '<div class="list-group shadow-sm mb-3">';
    $res = mysql_query( "SELECT id, description FROM {$pre}faq WHERE ( category = '{$_POST['parent']}' ) ORDER BY description" );

    while( $row = mysql_fetch_array( $res ) )
    {
      echo '<a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3 p-3" href="' . $HD_CURPAGE . '?parent=' . (int) $_POST['parent'] . '&amp;cmd=view&amp;id=' . (int) $row['id'] . '"><span>' . field($row['description']) . '</span><span class="text-primary" aria-hidden="true">&rarr;</span></a>';
    }
    echo '</div>';
  }
}
else if( $_POST['cmd'] == "view" )
{
  $res = mysql_query( "SELECT * FROM {$pre}faq WHERE ( id = '{$_GET['id']}' ) ORDER BY description" );
  $row = mysql_fetch_array( $res );

  echo '<nav aria-label="Article breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="' . $HD_CURPAGE . '">' . $LANG['faq_main_category'] . '</a></li><li class="breadcrumb-item"><a href="' . $HD_CURPAGE . '?parent=' . (int) $row['category'] . '">' . $LANG['faq_parent_category'] . '</a></li><li class="breadcrumb-item active" aria-current="page">' . field($row['description']) . '</li></ol></nav>';
  echo '<article class="faq-article"><h3 class="h2 mb-4">' . field($row['description']) . '</h3>';
  echo '<section class="mb-4"><h4 class="h5 border-bottom pb-2 mb-3">' . $LANG['faq_symptoms'] . '</h4><div class="faq-copy">';

  if( trim( $row['symptoms'] ) == "" )
    echo "{$LANG['faq_no_symptoms']}";
  else
    echo parse_tags( $row['symptoms'] );

  echo '</div></section><section><h4 class="h5 border-bottom pb-2 mb-3">' . $LANG['faq_solution'] . '</h4><div class="faq-copy">';

  if( trim( $row['solution'] ) == "" )
    echo "{$LANG['faq_no_solution']}";
  else
    echo parse_tags( $row['solution'] );
  echo '</div></section></article>';
}
else if( $_POST['cmd'] == "search" )
{
  echo '<div class="d-flex justify-content-between align-items-center gap-3 mb-3"><h3 class="h5 mb-0">Search results</h3><a class="btn btn-sm btn-outline-secondary" href="' . $HD_CURPAGE . '">' . $LANG['faq_categories'] . '</a></div>';
  $res = mysql_query( "SELECT * FROM {$pre}faq WHERE ( description LIKE '%{$_GET['search']}%' || symptoms LIKE '%{$_GET['search']}%' || solution LIKE '%{$_GET['search']}%' ) ORDER BY description" );
  if( !mysql_num_rows( $res ) )
    echo '<div class="alert alert-light border" role="status">' . $LANG['faq_no_results'] . '</div>';
  else
  {
    echo '<div class="list-group shadow-sm">';
    while( $row = mysql_fetch_array( $res ) )
    {
      $res_cat = mysql_query( "SELECT description FROM {$pre}faq WHERE ( id = '{$row['category']}' )" );
      $row_cat = mysql_fetch_array( $res_cat );

      $category = (is_array($row_cat) && trim($row_cat['description']) !== '') ? '<span class="badge text-bg-light">' . field($row_cat['description']) . '</span>' : '';
      echo '<a class="list-group-item list-group-item-action p-3" href="' . $HD_CURPAGE . '?parent=' . (int) $_POST['parent'] . '&amp;cmd=view&amp;id=' . (int) $row['id'] . '"><span class="d-flex justify-content-between align-items-center gap-3"><strong>' . field($row['description']) . '</strong>' . $category . '</span></a>';
    }
    echo '</div>';
  }
}
/********************************************************** PHP */?>
<?php /************************************************************/
if( trim( $data['header'] ) == "" )
{
/********************************************************** PHP */?>
<?php 
include "./include/footer.php";
?>
<?php /************************************************************/
}
else
  eval( "?> {$data['footer']} <?php" );
/********************************************************** PHP */?>
