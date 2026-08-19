<?php
require_once __DIR__ . '/../include/mysql-compat.php';
// Make a MySQL Connection
$cid = mysql_connect($db_host,$db_user,$db_password);
if (!$cid) {
    die("Could not connect to MySQL. Check the database host, username, and password.");
}
#select database to use
if (!mysql_select_db($db_name, $cid)) {
    die("Connected to MySQL, but could not select the requested database.");
}
?>
