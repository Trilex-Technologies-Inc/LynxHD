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

$HD_CURPAGE = $HD_URL_BACKUP;

if( $_SESSION[login_type] == $LOGIN_INVALID || !$_SESSION[user][admin] )
  Header( "Location: {$HD_URL_LOGIN}?redirect=" . urlencode( $HD_CURPAGE ) );

if( (trim( $db_path_to_mysql ) != "") && ($db_path_to_mysql[strlen( $db_path_to_mysql ) - 1] != "/") )
  $db_path_to_mysql .= "/";

if( $_GET[cmd] == "send" )
{
  $fp = popen( "{$db_path_to_mysql}mysqldump -u {$db_user} -p{$db_password} {$db_name} {$pre}dept {$pre}faq {$pre}options {$pre}pop {$pre}post {$pre}privilege {$pre}reply  {$pre}ticket {$pre}user {$pre}survey {$pre}field", "r" );

  if( !$fp )
    $msg = "<div class=\"errorbox\">Could not run mysqldump.  Check the '\$db_pathtomysql' variable in settings.php</div><br />";
  else
  {
    Header( "Content-type: application/octet-stream" ); 
    Header( "Content-disposition: attachment; filename=helpdesk.sql" ); 
    
    while( !feof( $fp ) )
      echo fread( $fp, 1024 );

    exit;
  }
}
else if( $_POST[cmd] == "import" )
{
  if( $_FILES[backup][size] )
  { 
    exec( "{$db_path_to_mysql}mysql -u {$db_user} -p{$db_password} -f {$db_name} < {$_FILES[backup][tmp_name]}" );
    $msg = "<div class=\"successbox\">Backup restored.</div><br />";
  }
}

include "./include/header.php";
/********************************************************** PHP */?>
<div class="title"><?php echo $script_name ?> Help Desk Backup</div><br /><?php echo $msg ?>
<table width="100%" border="0" cellpadding="5">
<tr><td>
  <div class="clean-gray">
    You can create a backup of the entire help desk, including users, tickets, settings, etc.
    You may also restore a backup.  Note that when you restore a backup, it will append
    to the database.  If you want to start from scratch, first remove all tables associated
    with the help desk.
  </div>
</td></tr>
</table>
<br />
<div id="container">
	<h1>Create Helpdesk Backup</h1>
<div class="buttons">
    <a href="<?php echo $HD_CURPAGE ?>?cmd=send" class="positive">
        <img src="./images/site-config.png" alt=""/> 
        Create Database Backup
    </a>
</div></div>
<br />
<div id="container">
<h1>Restore the database</h1>
<form class="wufoo" action="<?php echo $HD_CURPAGE ?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="cmd" value="import" />
<input type="hidden" name="MAX_FILE_SIZE" value="10000000">
<input type="file" name="backup">
<div class="buttons">
	<button type="submit" class="negative">Restore Backup</button>
</div>
</form>
</div>
<?php /************************************************************/
include "./include/footer.php";
/********************************************************** PHP */?>
