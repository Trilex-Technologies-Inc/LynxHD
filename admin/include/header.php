<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<script src="./js/jquery.min.js" type="text/javascript"></script>
	<script src="./js/menu.js" type="text/javascript"></script>
	<script src="./js/wufoo.js" type="text/javascript"></script>
	<link rel="stylesheet" type="text/css" href="./css/menu.css" />
    <link rel="stylesheet" type="text/css" href="./css/admin.css" />
    <link rel="stylesheet" type="text/css" href="./css/buttons.css" />
	<link rel="stylesheet" href="./css/form.css" type="text/css" />
    <link rel="stylesheet" href="./css/theme.css" type="text/css" />
	

<?php echo $EXTRA_HEADER ?>

<?php /************************************************************/
if( $INSTALLED )
  $global_priv = get_row_count( "SELECT COUNT(*) FROM {$pre}privilege WHERE ( user_id = '{$_SESSION[user][id]}' && dept_id = '0' && admin = '1' )" );
/********************************************************** PHP */?>
<title>Help Desk</title>
</head>
<body>
<p align="right"><?php /************************************************************/
if( $INSTALLED )
  if( $_SESSION[login_type] != $LOGIN_INVALID )
    echo "<font color=\"white\">You are currently logged in as {$_SESSION[user][name]} | Log Out | View Site</font>";
  else 
    echo " ";
 
/********************************************************** PHP */?><br />
<a href=""><img src="./images/blank.gif" alt="" width="1" height="1" border="0" /></a></p>
<h1><?php echo $website_name ?> <?php echo $script_name ?></h1>
<div id="menu">

<ul id="menu1" class="menu">
		<li><a href="browse.php"><img src="./images/main.png" border="0" align="absmiddle">  Main</a></li>
        <li>
			<a href="#"><img src="./images/mail-new.png" border="0" align="absmiddle">  Tickets</a>
			<ul>
			    <li><a href="adminticket.php">Create A Ticket</a></li>
				<?php /************************************************************/
if( $global_priv )
{
/********************************************************** PHP */?>
				<li><a href="adminsurvey.php">Ticket Surveys</a></li>
				<?php /************************************************************/
}
/********************************************************** PHP */?>
				<li><a href="stats.php">Ticket Statistics</a></li>
			</ul>
		</li>
        <li>
			<a href="#"><img src="./images/user-management.png" border="0" align="absmiddle">  User Management</a>
			<ul>
				<li><a href="user.php">View/Manage Users</a></li>
				<li><a href="profile.php">Edit Your Profile</a></li>
			</ul>
		</li>
        <li>
			<a href="#"><img src="./images/departments.png" border="0" align="absmiddle">  Departments</a>
			<ul>
				<li><a href="department.php">View/Manage Departments</a></li>
<?php /************************************************************/
if( $global_priv )
{
/********************************************************** PHP */?>
                <li><a href="email.php">Email Processing</a></li>
<?php /************************************************************/
}
/********************************************************** PHP */?>
				<li><a href="replies.php">Department Auto-Replies</a></li>
			</ul>
		</li>
		<li>
		<a href="#"><img src="./images/site-config.png" border="0" align="absmiddle">  Site Management</a>
			<ul>
<?php /************************************************************/
if( $global_priv )
{
/********************************************************** PHP */?>
                <li><a href="general.php">Help Desk Settings</a></li>
				<li><a href="emails.php">Customize Emails</a></li>
				<li><a href="./upload/index.php">Download Manager</a></li>
<?php /************************************************************/
}
/********************************************************** PHP */?>

				<li><a href="faqadmin.php">Knowledge Base</a></li>
<?php /************************************************************/
if( $global_priv )
{
/********************************************************** PHP */?>
                <li><a href="backup.php">Help Desk Backup</a></li>
<?php /************************************************************/
}
/********************************************************** PHP */?>
                <li><a href="manual.php">LynxHD Manual</a></li>
			</ul>
		</li>
        <li><a href="messages.php"><img src="./images/message-center.png" border="0" align="absmiddle">  Message Center</a></li>
        <li><a href="../index.php" target="_blank"><img src="./images/view-frontend.png" border="0" align="absmiddle">  View Frontend</a></li>
        <li><a href="index.php?cmd=logout"><img src="./images/log-out.png" border="0" align="absmiddle">  Log Out</a></li>
	<br />
	<?php /************************************************************/
if( $INSTALLED )
  if( $global_priv && $PATH_TO_HELPDESK == "" )
  {
    echo "<div id=\"container\">\n";
	echo "<h1>Helpdesk Alert!</h1>\n";
    echo "<p>You must specify the <u>URL to help desk</u> in the <b>Site Management >> Help desk Settings</b> in order for the help desk to be completely functional.  <br /><br />This message will disappear once you have successfully set this value.</p>\n";
    echo "</div>\n";
  }
/********************************************************** PHP */?>
	</ul>
</div>


<div id="content">

<table bgcolor="#99CCFF" width="100%" border="0" cellspacing="0" cellpadding="5">
  <tr>
    <tr valign="center">
<?php /************************************************************/
if( $_SESSION[login_type] == $LOGIN_INVALID )
  echo "<td><div class=\"smallinfowhite\">Not logged in.</div></td>";
else if( get_row_count( "SELECT COUNT(*) FROM {$pre}message WHERE ( user_id = '{$_SESSION[user][id]}' && viewed = '0' )" ) )
{
  echo "<td width=\"15\"><a href=\"{$HD_URL_MESSAGES}\"><img src=\"./images/mail-new.png\" border=\"0\"></a></td>\n";
  echo "<td><div class=\"smallinfo\"><a href=\"{$HD_URL_MESSAGES}\">You have new messages</a>.</div></td>";
}
else  
  echo "<td><div class=\"smallinfo\">You have no new <a href=\"{$HD_URL_MESSAGES}\">messages</a>.</div></td>"; 
/********************************************************** PHP */?>
    <td align="right">
      <div class="smallinfo">
<?php /************************************************************/
if( $INSTALLED )
  if( $_SESSION[login_type] != $LOGIN_INVALID )
    echo "Welcome {$_SESSION[user][name]} &nbsp;";
/********************************************************** PHP */?>
      </div>
    </td>
  </tr>
</table>
<table width="100%" border="0" cellspacing="0" cellpadding="15">
<tr>
<td>


