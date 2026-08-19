<?php
require_once __DIR__ . '/../include/mysql-compat.php';
// Make a MySQL Connection
$cid = mysql_connect($db_host,$db_user,$db_password);
if (!$cid) { print "ERROR: " . mysql_error() . "\n ";    
}
#select database to use
mysql_select_db("$db_name") or die(mysql_error());
?>
