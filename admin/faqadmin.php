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
        mysql_query( "INSERT INTO {$pre}faq ( description, symptoms, category, parent, date ) VALUES ( '{$_POST['name']}', '{$_POST['description']}', '-1', '{$_POST['parent']}', '" . time( ) . "' )" );
      else
        $msg = "<div class=\"errorbox\">A category with that name already exists.</div><br />";
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
        mysql_query( "UPDATE {$pre}faq SET description = '{$_POST['description']}', symptoms = '{$_POST['symptoms']}', solution = '{$_POST['solution']}' WHERE ( id = '{$_POST['id']}' )" );
      else
        mysql_query( "INSERT INTO {$pre}faq ( description, symptoms, solution, date, category, parent ) VALUES ( '{$_POST['description']}', '{$_POST['symptoms']}', '{$_POST['solution']}', '" . time( ) . "', '{$_POST['parent']}', '-1' )" );
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
<div class="title"><?php echo $script_name ?> Knowledge Base</div><br /><?php echo $msg ?>
<div id="container">
	<h1>Search the Knowledgebase</h1>
<form class="wufoo" action="<?php echo $HD_CURPAGE ?>" method="get">
<input type="hidden" name="cmd" value="search">
<ul>
	<li>
	   <label class="desc">Search for:</label>
	   <div>
	    <input class="field text medium" type="text" name="search" /> 
	   </div>
	</li>
<div class="buttons">
    <button type="submit" class="positive">Search</button>
</div>
</form>
</div>
<?php /************************************************************/
if( !isset( $_POST['cmd'] ) || $_POST['cmd'] == "deletecat" || $_POST['cmd'] == "newcategory" )
{
  if( $global_priv )
  {
/********************************************************** PHP */?>
<div id="container">
	<h1>Create New Category</h1>
<form class="wufoo" action="<?php echo $HD_CURPAGE ?>" method="post">
<input type="hidden" name="cmd" value="newcategory" />
<input type="hidden" name="parent" value="<?php echo $_POST['parent'] ?>" />
<ul>
    <li>
	   <label class="desc">Category Name:</label>
      <div>
    	<input class="field text medium" type="text" name="name" size="30" />	
	  </div>
	  <?php /************************************************************/
    if( isset( $_POST['parent'] ) && $_POST['parent'] != "-1" )
      echo "<p class=\"instruct\">* This will be created as a subcategory for the current category</p>";
/********************************************************** PHP */?>
   </li>
<li>
	   <label class="desc">Description:</label>
      <div>
      	<input class="field text medium" type="text" name="description" size="30" />	  
	</div>
</li>
   <div class="buttons">
    <button type="submit" class="positive">Create</button>
</div>

</form>
</div>
<br />
<?php /************************************************************/
  }
/********************************************************** PHP */?>
<?php if( $row_cat['parent'] != -1 ) echo "&lt;&lt; <a href=\"{$HD_CURPAGE}\">Main Category</a> &lt; <a href=\"{$HD_CURPAGE}?parent={$row_cat['parent']}\">Parent Category</a> | "; ?> <b> Browsing '<?php echo field( $row_cat['description'] ) ?>'</b><br /><br />
<?php /************************************************************/
  if( $global_priv )
  {
/********************************************************** PHP */?>
<table border="0" cellspacing="2" cellpadding="0">
<tr><td><a href="<?php echo $HD_CURPAGE ?>?cmd=new"><img src="./images/ticket-reply.png" border="0" /></a>&nbsp;</td><td><div class="normal"><a href="<?php echo $HD_CURPAGE ?>?cmd=edit&parent=<?php echo $_POST['parent'] ?>">Create New Knowledge Base Entry In This Category</a></div></td></tr>
<?php /************************************************************/
    if( $_POST['parent'] != 0 )
    {
/********************************************************** PHP */?>
<tr><td><a href="<?php echo "{$HD_CURPAGE}?cmd=edit&id={$_POST['parent']}&parent={$_POST['parent']}" ?>"><img src="./images/edit.png" border="0" /></a>&nbsp;</td><td><div class="normal"><a href="<?php echo "{$HD_CURPAGE}?cmd=edit&id={$_POST['parent']}&parent={$_POST['parent']}" ?>">Edit Category</a></div></td></tr>
<tr><td><a href="javascript:if(confirm('This will delete this category and all its entries.  Are you sure you want to do this?')) window.location = '<?php echo $HD_CURPAGE ?>?cmd=deletecat&id=<?php echo $_POST['parent'] ?>&parent=<?php echo $_POST['parent'] ?>'"><img src="./images/ticket-delete.png" border="0" /></a>&nbsp;</td><td><div class="normal"><a href="javascript:if(confirm('This will delete this category and all its entries.  Are you sure you want to do this?')) window.location = '<?php echo $HD_CURPAGE ?>?cmd=deletecat&id=<?php echo $_POST['parent'] ?>&parent=<?php echo $_POST['parent'] ?>'">Delete This Category</a></div></td></tr>
<?php /************************************************************/
    }
/********************************************************** PHP */?>
</table><br />
</div>
<?php /************************************************************/
  }
  $res = mysql_query( "SELECT id, description, symptoms FROM {$pre}faq WHERE ( parent = '{$_POST['parent']}' ) ORDER BY description" );
  if( mysql_num_rows( $res ) )
  {
    echo "<table width=\"100%\" border=\"0\" cellspacing=\"5\" cellpadding=\"0\">";
    
    $i = 0;
    while( $row = mysql_fetch_array( $res ) )
    {
      if( $i++ % 2 == 0 )
        echo "<tr>";
      
      $items = get_row_count( "SELECT COUNT(*) FROM {$pre}faq WHERE ( category = '{$row['id']}' )" );
      $subcats = get_row_count( "SELECT COUNT(*) FROM {$pre}faq WHERE ( parent = '{$row['id']}' )" );
      echo "<td><div class=\"normal\"><b><a href=\"$HD_CURPAGE?parent={$row['id']}\">" . field( $row['description'] ) . "</b></a> (<b>$subcats</b> subcategories, <b>$items</b> entries)<br /><img src=\"./images/blank.gif\" width=\"1\" height=\"5\"><br />" . ((trim( $row['symptoms'] ) != "") ? field( $row['symptoms'] ) : "No description") . "</div><br />";

      if( $i % 2 == 1 )
        echo "</tr>";
    }

    echo "</table><br />";
  }

  $res = mysql_query( "SELECT id, description FROM {$pre}faq WHERE ( category = '{$_POST['parent']}' ) ORDER BY description" );
  if( mysql_num_rows( $res ) )
  {
    echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"4\">";
    $res = mysql_query( "SELECT id, description FROM {$pre}faq WHERE ( category = '{$_POST['parent']}' ) ORDER BY description" );
    $bgcolor = "#EEEEEE";

    while( $row = mysql_fetch_array( $res ) )
    {
      $bgcolor = ($bgcolor == "#DDDDDD") ? "#EEEEEE" : "#DDDDDD";
      echo "<tr bgcolor=\"$bgcolor\"><td><div class=\"normal\"><a href=\"{$HD_CURPAGE}?parent={$_POST['parent']}&cmd=view&id={$row['id']}\">" . field( $row['description'] ) . "</a></div><br /></td></tr>";
    }
    echo "</table>";
  }
}
else if( $_POST['cmd'] == "view" )
{
  $faq_id = $_GET['id'] ?? '';
  $res = mysql_query( "SELECT * FROM {$pre}faq WHERE ( id = '$faq_id' ) ORDER BY description" );
  $row = mysql_fetch_array( $res ) ?: array(
    'id' => 0, 'category' => 0, 'description' => 'Article not found',
    'symptoms' => '', 'solution' => ''
  );

  echo "&lt;&lt; <a href=\"{$HD_CURPAGE}\">Main Category</a> &lt; <a href=\"{$HD_CURPAGE}?parent={$row['category']}\">Parent Category</a><br /><br />";

  if( $global_priv )
    echo "<table border=\"0\" cellspacing=\"2\" cellpadding=\"0\"><tr><td><a href=\"{$HD_CURPAGE}?cmd=edit&parent={$row['category']}&id={$row['id']}\"><img src=\"./images/edit.png\" border=\"0\" /></a>&nbsp;</td><td><div class=\"normal\"><a href=\"{$HD_CURPAGE}?cmd=edit&parent={$row['category']}&id={$row['id']}\">Edit This Entry</a></div></td></tr><tr><td><a href=\"{$HD_CURPAGE}?cmd=deleteentry&parent={$row['category']}&id={$row['id']}\"><img src=\"./images/ticket-delete.png\" border=\"0\" /></a>&nbsp;</td><td><div class=\"normal\"><a href=\"{$HD_CURPAGE}?cmd=deleteentry&parent={$row['category']}&id={$row['id']}\">Delete This Entry</a></div></td></tr></table><br />";

  echo "<div class=\"subtitle\">" . field( $row['description'] ) . "</div><br />";
  echo "<b>SYMPTOMS</b><br /><hr height=\"1\" size=\"1\" />";

  if( trim( $row['symptoms'] ) == "" )
    echo "No symptoms.";
  else
    echo parse_tags( $row['symptoms'] );

  echo "<br /><br /><b>SOLUTION</b><br /><hr height=\"1\" size=\"1\" />";

  if( trim( $row['solution'] ) == "" )
    echo "No solution available.";
  else
    echo parse_tags( $row['solution'] );
}
else if( $_POST['cmd'] == "edit" )
{
  $row = array(
    'id' => 0,
    'category' => $_POST['parent'] ?? 0,
    'description' => '',
    'symptoms' => '',
    'solution' => ''
  );

  if( isset( $_GET['id'] ) )
  {
    $res = mysql_query( "SELECT * FROM {$pre}faq WHERE ( id = '{$_GET['id']}' )" );
    $fetched_row = mysql_fetch_array( $res );
    if( is_array($fetched_row) )
      $row = array_merge( $row, $fetched_row );

    while( list( $key, $val ) = each( $row ) )
      // This key is sent in the URL (and posted) to tell which category to go back to after editing
      if( strtolower( $key ) != "parent" ) 
        $_POST[$key] = $val;
  }

  if( $row['category'] != -1 ) // If it's an entry
    echo "<a href=\"{$HD_CURPAGE}?parent={$row['category']}\"><b>Back to category</b></a><br /><br />";
  else
    echo "<a href=\"{$HD_CURPAGE}?parent={$row['id']}\"><b>Back to category</b></a><br /><br />";

  if( $global_priv )
  {
  /********************************************************** PHP */?>
<table bgcolor="#99CCFF" border="0" cellspacing="0" cellpadding="0"><tr><td align="left"><img src="./images/leftuptransparent.gif" align="top" />&nbsp;</td><td><div class="containertitle">Edit Knowledge Base</div></td><td align="right">&nbsp;<img src="./images/rightuptransparent.gif" align="top" /></td></tr></table>
<table width="100%" bgcolor="#99CCFF" border="0" cellspacing="1" cellpadding="4">
<form action="<?php echo $HD_CURPAGE ?>" method="post">
<?php /************************************************************/
    if( isset( $_GET['id'] ) )
      echo "<input type=\"hidden\" name=\"id\" value=\"{$_GET['id']}\">\n";

    echo "<input type=\"hidden\" name=\"cmd\" value=\"edit\" />\n";
    echo "<input type=\"hidden\" name=\"parent\" value=\"{$_POST['parent']}\" />\n";
/********************************************************** PHP */?>
<tr><td bgcolor="#EEEEEE">
  <table align="center" border="0" cellspacing="2" cellpadding="0">
    <tr><td colspan="2" align="center"><div class="subtitle">- General -</div><img src="./images/blank.gif" width="1" height="12" />    
    <tr><td colspan="2" align="center"><img src="./images/blank.gif" width="1" height="12" /><br />
    <table width="500" border="0" cellspacing="0" cellpadding="10" bgcolor="#FFFFFF">
<?php /************************************************************/
    if( $row['category'] != -1 ) // It's an entry, not a category
    {
/********************************************************** PHP */?>      
    <tr><td>
      <div class="normal">
      Please fill in the information below.  The description is required.  Please use a short description,
      such as 'How do I locate my billing information?'.  You can use <a href="tickettags.php" target="_blank">message tags</a> in the
      symptoms and solution fields.
      </div>
    </td></tr></table>
    <img src="./images/blank.gif" width="1" height="12" />
    </td></tr>
    <tr valign="top">
      <td align="right"><div class="topinfo">Description:&nbsp;</div></td>
      <td><input type="text" name="description" size="30" value="<?php echo field( $_POST['description'] ?? '' ) ?>" /></td>
    </tr>
    <tr valign="top">
      <td align="right"><div class="topinfo">Symptoms:&nbsp;</div></td>
      <td><textarea name="symptoms" rows="5" cols="40"><?php echo field( $_POST['symptoms'] ?? '' ) ?></textarea></td>
    </tr>
    <tr valign="top">
      <td align="right"><div class="topinfo">Solution:&nbsp;</div></td>
      <td><textarea name="solution" rows="5" cols="40"><?php echo field( $_POST['solution'] ?? '' ) ?></textarea><br /><img src="./images/blank.gif" width="1" height="12" /></td>
    </tr>
    <tr><td colspan="2" align="center"><input type="submit" value="Update">&nbsp;&nbsp;<input type="reset"><br /><img src="./images/blank.gif" width="1" height="12" /></td></tr>
<?php /************************************************************/
    }
    else // It's a category
    {
/********************************************************** PHP */?>   
    <tr><td>
      <div class="normal">
      Please fill in the information below to change the category name and title.
      </div>
    </td></tr></table>
    <img src="./images/blank.gif" width="1" height="12" />
    </td></tr>
    <tr valign="top">
      <td align="right"><div class="topinfo">Category Name:&nbsp;</div></td>
      <td><input type="text" name="description" size="30" value="<?php echo field( $_POST['description'] ) ?>" /></td>
    </tr>
    <tr valign="top">
      <td align="right"><div class="topinfo">Description:&nbsp;</div></td>
      <td><input type="text" name="symptoms" size="30" value="<?php echo field( $_POST['symptoms'] ) ?>" /><br /><img src="./images/blank.gif" width="1" height="12" /></td>
    </tr>
    <tr><td colspan="2" align="center"><input type="submit" value="Update">&nbsp;&nbsp;<input type="reset"><br /><img src="./images/blank.gif" width="1" height="12" /></td></tr>
<?php /************************************************************/
    }
/********************************************************** PHP */?>   
  </table>
</td></tr>
</form>
</table>
<?php /************************************************************/
  }
}
else if( $_POST['cmd'] == "search" )
{
  $_GET['search'] = $_GET['search'] ?? '';
  echo "<a href=\"{$HD_CURPAGE}\"><b>Back to categories</b></a><br /><br />";
  $res = mysql_query( "SELECT * FROM {$pre}faq WHERE ( description LIKE '%{$_GET['search']}%' || symptoms LIKE '%{$_GET['search']}%' || solution LIKE '%{$_GET['search']}%' ) ORDER BY date DESC" );
  if( !mysql_num_rows( $res ) )
    echo "No results were found for your search.";
  else
  {
    echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"4\">";
    $bgcolor = "#EEEEEE";
    
    while( $row = mysql_fetch_array( $res ) )
    {
      $res_cat = mysql_query( "SELECT description FROM {$pre}faq WHERE ( id = '{$row['category']}' )" );
      $row_cat = mysql_fetch_array( $res_cat ) ?: array( 'description' => '' );

      $bgcolor = ($bgcolor == "#DDDDDD") ? "#EEEEEE" : "#DDDDDD";
      echo "<tr bgcolor=\"$bgcolor\"><td><div class=\"normal\"><a href=\"{$HD_CURPAGE}?parent={$_POST['parent']}&cmd=view&id={$row['id']}\">" . field( $row['description'] ) . "</a>" . ((trim( $row_cat['description'] ) != "") ? "&nbsp;&nbsp;<span class=\"smallinfo\">[{$row_cat['description']}]</span>" : "") . "</div></td></tr>";
    }

    echo "</table>";
  }
}
/********************************************************** PHP */?>
<?php /************************************************************/
include "./include/footer.php";
/********************************************************** PHP */?>
