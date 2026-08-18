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

if( !isset( $_POST[parent] ) )
{
  if( isset( $_GET[parent] ) )
    $_POST[parent] = $_GET[parent];
  else
    $_POST[parent] = 0;
}

$res = mysql_query( "SELECT description, parent FROM {$pre}faq WHERE ( id = '{$_POST[parent]}' )" );
if( mysql_num_rows( $res ) )
  $row_cat = mysql_fetch_array( $res );
else
{
  $row_cat[description] = "Main";
  $row_cat[parent] = -1;
  $_POST[parent] = 0;
}

if( isset( $_GET[cmd] ) )
  $_POST[cmd] = $_GET[cmd];

if( trim( $data[header] ) == "" )
{
/********************************************************** PHP */?>
<?php 
include "./include/header.php";
?>
<?php /************************************************************/
}
else
  eval( "?> {$data[header]} <?" );
/********************************************************** PHP */?>
<style type="text/css">
<?php echo $data[styles] ?>
</style>
<div class="title"><?php echo $LANG[knowledge_base] ?></div><br /><?php echo $msg ?>
<div class="normal">
<form action="<?php echo $HD_CURPAGE ?>" method="get">
<input type="hidden" name="cmd" value="search">
<b><?php echo $LANG[search_for] ?></b> <input type="text" name="search" /> <input type="submit" value="<?php echo $LANG[faq_search_button] ?>" />
</form>
<?php /************************************************************/
if( !isset( $_POST[cmd] ) )
{
/********************************************************** PHP */?>
<?php if( $row_cat[parent] != -1 ) echo "&lt;&lt; <a href=\"{$HD_CURPAGE}\">{$LANG[faq_main_category]}</a> &lt; <a href=\"{$HD_CURPAGE}?parent={$row_cat[parent]}\">{$LANG[faq_parent_category]}</a> | "; ?> <b> <br /><br /><?php echo $LANG[faq_browsing] ?> '<?php echo field( $row_cat[description] ) ?>'</b><br /><br /><br />
<?php /************************************************************/
  $res = mysql_query( "SELECT id, description, symptoms FROM {$pre}faq WHERE ( parent = '{$_POST[parent]}' ) ORDER BY description" );
  if( mysql_num_rows( $res ) )
  {
    echo "<table width=\"100%\" border=\"0\" cellspacing=\"5\" cellpadding=\"0\">";
    
    $i = 0;
    while( $row = mysql_fetch_array( $res ) )
    {
      if( $i % 2 == 0 )
        echo "<tr>";
      
      $items = get_row_count( "SELECT COUNT(*) FROM {$pre}faq WHERE ( category = '{$row[id]}' )" );
      $subcats = get_row_count( "SELECT COUNT(*) FROM {$pre}faq WHERE ( parent = '{$row[id]}' )" );
      echo "<td valign=\"top\"><div class=\"normal\"><b><a href=\"$HD_CURPAGE?parent={$row[id]}\">" . field( $row[description] ) . "</b></a> <br /><img src=\"./images/blank.gif\" width=\"1\" height=\"5\"><br />" . ((trim( $row[symptoms] ) != "") ? field( $row[symptoms] ) : "{$LANG[faq_no_description]}") . "</div><br /></td>";

      if( $i % 2 == 1 )
        echo "</tr>";

      $i++;
    }

    echo "</table><br />";
  }

  $res = mysql_query( "SELECT id, description FROM {$pre}faq WHERE ( category = '{$_POST[parent]}' ) ORDER BY description" );
  if( mysql_num_rows( $res ) )
  {
    echo "<table width=\"100%\" border=\"0\" cellspacing=\"5\" cellpadding=\"4\">";
    $res = mysql_query( "SELECT id, description FROM {$pre}faq WHERE ( category = '{$_POST[parent]}' ) ORDER BY description" );

    while( $row = mysql_fetch_array( $res ) )
    {
      $bgcolor = ($bgcolor == "#DDDDDD") ? "#EEEEEE" : "#DDDDDD";
      echo "<tr bgcolor=\"$bgcolor\"><td valign=\"top\"><div class=\"normal\"><a href=\"{$HD_CURPAGE}?parent={$_POST[parent]}&cmd=view&id={$row[id]}\">" . field( $row[description] ) . "</a></div><br /></td></tr>";
    }
    echo "</table>";
  }
}
else if( $_POST[cmd] == "view" )
{
  $res = mysql_query( "SELECT * FROM {$pre}faq WHERE ( id = '{$_GET[id]}' ) ORDER BY description" );
  $row = mysql_fetch_array( $res );

  echo "&lt;&lt; <a href=\"{$HD_CURPAGE}\">{$LANG[faq_main_category]}</a> &lt; <a href=\"{$HD_CURPAGE}?parent={$row[category]}\">{$LANG[faq_parent_category]}</a><br /><br />";

  echo "<div class=\"normal\"><b>" . field( $row[description] ) . "</b></div><br />";
  echo "<b>{$LANG[faq_symptoms]}</b><br /><hr height=\"1\" size=\"1\" />";

  if( trim( $row[symptoms] ) == "" )
    echo "{$LANG[faq_no_symptoms]}";
  else
    echo parse_tags( $row[symptoms] );

  echo "<br /><br /><b>{$LANG[faq_solution]}</b><br /><hr height=\"1\" size=\"1\" />";

  if( trim( $row[solution] ) == "" )
    echo "{$LANG[faq_no_solution]}";
  else
    echo parse_tags( $row[solution] );
}
else if( $_POST[cmd] == "search" )
{
  echo "<a href=\"{$HD_CURPAGE}\"><b>{$LANG[faq_categories]}</b></a><br /><br />";
  $res = mysql_query( "SELECT * FROM {$pre}faq WHERE ( description LIKE '%{$_GET[search]}%' || symptoms LIKE '%{$_GET[search]}%' || solution LIKE '%{$_GET[search]}%' ) ORDER BY description" );
  if( !mysql_num_rows( $res ) )
    echo "{$LANG[faq_no_results]}";
  else
  {
    echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"4\">";
    
    while( $row = mysql_fetch_array( $res ) )
    {
      $res_cat = mysql_query( "SELECT description FROM {$pre}faq WHERE ( id = '{$row[category]}' )" );
      $row_cat = mysql_fetch_array( $res_cat );

      $bgcolor = ($bgcolor == "#DDDDDD") ? "#EEEEEE" : "#DDDDDD";
      echo "<tr bgcolor=\"$bgcolor\"><td><div class=\"normal\"><a href=\"{$HD_CURPAGE}?parent={$_POST[parent]}&cmd=view&id={$row[id]}\">" . field( $row[description] ) . "</a>" . ((trim( $row_cat[description] ) != "") ? "&nbsp;&nbsp;[{$row_cat[description]}]" : "") . "</div></td></tr>";
    }

    echo "</table>";
  }
}
/********************************************************** PHP */?>
<br />
<?php /************************************************************/
if( trim( $data[header] ) == "" )
{
/********************************************************** PHP */?>
<?php 
include "./include/footer.php";
?>
<?php /************************************************************/
}
else
  eval( "?> {$data[footer]} <?" );
/********************************************************** PHP */?>