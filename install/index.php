<?php
/*
Created by: Adam Patterson

This is a free aplication you can change what 
ever you like as long as you keep mention of my name. 

	Copyright (c) 2007, Adam Patterson
	http://www.adam-patterson.com | http://www.studiolounge.net
	Installer is released under the GPL license
	http://www.gnu.org/licenses/gpl.txt

This script is designed to let users create a config.php file used to connect 
to a MySQL DB and install the default MySQL into the DB.

*/

if (!is_writable('../include/')) die("Sorry, I can't write to the include directory. You'll have to either change the permissions on your installation directory or create your settings.php manually.");

if (isset($_GET['step']))
	$step = $_GET['step'];
else
	$step = 0;
header( 'Content-Type: text/html; charset=utf-8' );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Installer &rsaquo; Setup Configuration File</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="../css/install.css" rel="stylesheet" type="text/css" />
</style>
</head>
<body>
<center><img src="../images/logo.jpg" /></center>

<?php
switch($step) {
	case 0:
// Check if settings.php has been created
if (file_exists('../include/settings.php'))
	die("<p>The file 'settings.php' already exists. If you need to reset any of the configuration items in this file, please delete it first.</p></body></html>");
?>
<p>Welcome to your install. Before getting started, we need some information on the database. You will need to know the following items before proceeding:</p>
<ol>
  <strong><li>Database name</li>
  <li>Database username</li>
  <li>Database password</li>
  <li>Database host</li>
  </strong>
</ol>
<p>If for any reason this automatic file creation doesn't work, don't worry. All this does is fill in the database information to a configuration file. You may also simply open <code>settings-sample.php</code> in a text editor, fill in your information, and save it as <code>settings.php</code>.</p>
<p>In all likelihood, these items were supplied to you by your Hosting Company. If you do not have this information, then you will need to contact them before you can continue. If you&#8217;re all ready, <a href="?step=1">let&#8217;s go</a>! </p>
<?php
	break;

	case 1:
	?>
</p>
<form method="post" action="?step=2">
  <p>Below you should enter your database connection details. If you're not sure about these, contact your host. </p>
  <table>
    <tr>
      <th scope="row">Database Name</th>
      <td><input name="dbname" type="text" size="25"/></td>
      <td>The name of the database you want to run your script in. </td>
    </tr>
    <tr>
      <th scope="row">User Name</th>
      <td><input name="uname" type="text" size="25"/></td>
      <td>Your MySQL username</td>
    </tr>
    <tr>
      <th scope="row">Password</th>
      <td><input name="pwd" type="text" size="25"/></td>
      <td>Your MySQL password.</td>
    </tr>
    <tr>
      <th scope="row">Database Host</th>
      <td><input name="dbhost" type="text" size="25" /></td>
      <td>Usually: localhost</td>
    </tr>
    <tr>
      <th scope="row">Database Prefix</th>
      <td><input name="dbprefix" type="text" size="25" value="" /></td>
      <td>If you are sharing a database with another script, you can add a prefix like hd_ to the coldbrew db tables. If not you can leave this blank.</td>
    </tr>
    <tr>
      <th scope="row">Path to MySQL</th>
      <td><input name="dbsqlpath" type="text" size="25" value="" /></td>
      <td>This is needed if you are going to preform helpdesk backups. (not required)</td>
    </tr>
  </table>
  <h2 class="step">
    <input name="submit" type="submit" id="fsubmit" value="Submit" />
  </h2>
</form>
<?php
	break;	
	case 2:
	$db_name  = trim($_POST['dbname']);
    $db_user   = trim($_POST['uname']);
    $db_password = trim($_POST['pwd']);
    $db_host  = trim($_POST['dbhost']);
	$db_prefix  = trim($_POST['dbprefix']);
	$db_path_to_mysql  = trim($_POST['dbsqlpath']);

    // We'll fail here if the values are no good.
    require_once('open-db.php');
	$handle = fopen('../include/settings.php', 'w');
	
$source = array (
"<? \n",
"$","db_name = 'databasename';	// The name of the database \n",
"$","db_user = 'username'; 	// MySQL username \n",		
"$","db_password = 'passwords';	// MySQL Password \n",	
"$","db_host = 'localhost';	// Most likely you wont need to change this \n",
"$","db_prefix = 'hd_';	// Prefix for tables (i.e. if prefix is 'hd_', tables will be 'hd_ticket', etc.) \n",
"$","db_path_to_mysql = 'sqlpath';	// Needed if you are going to use help desk backups \n",
"?>" );

$search = array ( databasename, username, passwords, localhost, hd_, sqlpath );
$replace = array ($db_name, $db_user, $db_password, $db_host, $db_prefix, $db_path_to_mysql);

$source = str_replace ( $search, $replace, $source );
foreach ( $source as $str )
	fwrite($handle, $str);
?>
<p>Excellent!</p>
<a href="?step=3">let&#8217;s check the connection to MySQL</a>!
</p>
<?php
	break;
	case 3:
	
if (file_exists("../include/settings.php")) {

    // Prefix the table names. These already have `backticks` around them!
    $db_schema = array();

/*
Insert your MySQL in the $db_schema[] array, simply cut and paste the 
$db_schema[] chunk leaving the opening " and closing ";

You can also put $variables in the MySQL and have the data carried over.
*/
 
$db_schema[] = "CREATE TABLE `test` (
  `id` int(4) NOT NULL auto_increment,
  `rowName` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`id`)
 );";
  
require_once('../include/settings.php');
require_once('open-db.php');

      echo "<h3>Checking Connection...</h3>";
      foreach($db_schema as $sql) {
       mysql_query($sql);
      }
      echo "<h3>Connected!</h3><br /><br /><a href=\"../setup.php\">Lets install the tables and create the admin account.</a>";
  }
	
	break;
}
?>
<p id="footer"></p>
</body>
</html>
