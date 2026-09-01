<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<?php echo $EXTRA_HEADER ?>
<style type="text/css">
  a
  { 
    color: #182C5A;  
    text-decoration: underline;
  }
	a:visited
  { 
    color: #182C5A;  
    text-decoration: underline;
  }
	a:active
  {
    color: #182C5A;  
    text-decoration: underline;
  }
  a:hover
  {
    color: #333333;  
    text-decoration: underline;
  }
  
  .infobar_01
  {
    font: bold 9pt Verdana, Arial, Helvetica;
    color: #FFFFFF;
  }
  
  .graycontainer
  {
    font: 9pt Arial, Helvetica;
    color: #000000;
  }

  .whitecontainer
  {
    font: 9pt Arial, Helvetica;
    color: #000000;
  }

  #mainmenu .title
  {
    font: bold 9pt Verdana, Arial, Helvetica;
    color: #FFFFFF;
  }
  #mainmenu .options
  {
    font: 9pt Arial, Helvetica;
    color: #000000;
  }

  .title
  {
    font: bold 14pt Arial, Helvetica, Verdana;
    color: #182C5A;
  }

  label
  {
    font: bold 10pt Arial, Helvetica, Verdana;
  }

  .errorbox
  {
    font: bold 10pt Arial, Helvetica, Verdana;
    padding: 5px;
    color: red;
    border: 1px solid red;
  }

  .successbox
  {
    font: bold 10pt Arial, Helvetica, Verdana;
    padding: 5px;
    color: green;
    border: 1px solid green;
  }

  .topinfo
  {
    font: bold 8pt Verdana, Arial, Helvetica;
    color: #000000;
  }

  .normal
  {
    font: 10pt Arial, Helvetica, Verdana;
    color: #000000;
  }

  .tableheader
  {
    font: bold 10pt Arial, Helvetica, Verdana;
    color: #000000;
  }

  .submenu
  {
    font: bold 8pt Verdana, Arial, Helvetica;
  }

  .containertitle
  {
    font: bold 9pt Verdana, Arial, Helvetica;
    color: #FFFFFF;
  }

  .smallinfo
  {
    font: bold 8pt Verdana, Arial, Helvetica;
    color: #000000;
  }

  .subtitle
  {
    font: bold 12pt Arial, Helvetica, Verdana;
    color: #182C5A;
  }

#outside{
	border:1px solid #000099;
	background:#000099;
	}
#navigation-1 {
	padding:1px 0;
	margin:0px;
	list-style:none;
	width:100%;
	height:21px;
	border-top:1px solid #FFFFFF;
	border-bottom:1px solid #FFFFFF;
	font:normal 8pt verdana, arial, helvetica;
}
#navigation-1 li {
	margin:0;
	padding:0;
	display:block;
	float:left;
	position:relative;
	width:148px;
}
#navigation-1 li a:link, #navigation-1 li a:visited {
	padding:4px 0;
	display:block;
	text-align:center;
	text-decoration:none;
	background:#000099;
	color:#ffffff;
	width:148px;
	height:13px;
}
#navigation-1 li:hover a, #navigation-1 li a:hover, #navigation-1 li a:active {
	padding:4px 0;
	display:block;
	text-align:center;
	text-decoration:none;
	background:#0066FF;
	color:#ffffff;
	width:146px;
	height:13px;
	border-left:1px solid #ffffff;
	border-right:1px solid #ffffff;
}
#navigation-1 li ul.navigation-2 {
	margin:0;
	padding:1px 1px 0;
	list-style:none;
	display:none;
	background:#ffffff;
	width:146px;
	position:absolute;
	top:21px;
	left:-1px;
	border:1px solid #000099;
	border-top:none;
}
#navigation-1 li:hover ul.navigation-2 {
	display:block;
}
#navigation-1 li ul.navigation-2 li {
	width:146px;
	clear:left;
	width:146px;
}
#navigation-1 li ul.navigation-2 li a:link, #navigation-1 li ul.navigation-2 li a:visited {
	clear:left;
	background:#000099;
	padding:4px 0;
	width:146px;
	border:none;
	border-bottom:1px solid #ffffff;
	position:relative;
	z-index:1000;
}
#navigation-1 li ul.navigation-2 li:hover a, #navigation-1 li ul.navigation-2 li a:active, #navigation-1 li ul.navigation-2 li a:hover {
	clear:left;
	background:#0066FF;
	padding:4px 0;
	width:146px;
	border:none;
	border-bottom:1px solid #ffffff;
	position:relative;
	z-index:1000;
}
#navigation-1 li ul.navigation-2 li ul.navigation-3 {
	display:none;
	margin:0;
	padding:0;
	list-style:none;
	position:absolute;
	left:145px;
	top:-2px;
	padding:1px 1px 0 1px;
	border:1px solid #000099;
	border-left:1px solid #000099;
	background:#ffffff;
	z-index:900;
}
#navigation-1 li ul.navigation-2 li:hover ul.navigation-3 {
	display:block;
}
#navigation-1 li ul.navigation-2 li ul.navigation-3 li a:link, #navigation-1 li ul.navigation-2 li ul.navigation-3 li a:visited {
	background:#000099;
}
#navigation-1 li ul.navigation-2 li ul.navigation-3 li:hover a, #navigation-1 li ul.navigation-2 li ul.navigation-3 li a:hover, #navigation-1 li ul.navigation-2 li ul.navigation-3 li a:active {
	background:#0066FF;
}
#navigation-1 li ul.navigation-2 li a span {
	position:absolute;
	top:0;
	left:132px;
	font-size:12pt;
	color:#fe676f;
}
#navigation-1 li ul.navigation-2 li:hover a span, #navigation-1 li ul.navigation-2 li a:hover span {
	position:absolute;
	top:0;
	left:132px;
	font-size:12pt;
	color:#ffffff;
}
</style>
<?php /************************************************************/
if( $INSTALLED )
  $global_priv = get_row_count( "SELECT COUNT(*) FROM {$pre}privilege WHERE ( user_id = '{$_SESSION['user']['id']}' && dept_id = '0' && admin = '1' )" );
/********************************************************** PHP */?>
<title>Help Desk</title>
</head>
<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" bgcolor="#EEEEEE">
<br />
<table align="center" width="790" border="0" cellspacing="0" cellpadding="0" bgcolor="#FFFFFF">
<td width="1" bgcolor="#99CCFF"><img src="./images/blank.gif"></td>
<td>
<img src="./images/logo.gif" alt="Heathco" /><br />
<table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#33bbff"><tr><td><img src="./images/blank.gif" width="1" height="2"></td></tr></table>


<?php /************************************************************/
if( $global_priv )
{
/********************************************************** PHP */?>    
<div id="outside">
<ul id="navigation-1">
      <li><a href="/helpdesk/browse.php" title="Main" target="_self" >Main</a>
      <ul class="navigation-2">
      </ul>
   </li>
   <li><a href="" title="Tickets" target="_self" >Tickets</a>
      <ul class="navigation-2">
         <li><a href="/helpdesk/adminticket.php" title="Create Ticket" target="_self" >Create Ticket</a></li>
         <li><a href="/helpdesk/stats.php" title="Statistics" target="_self" >Ticket Statistics</a></li>
         <li><a href="/helpdesk/adminsurvey.php" title="View/Manage Surveys" target="_self" >View/Manage Surveys</a></li>
      </ul>
   </li>
   <li><a href="" title="Site Management" target="_self" >Site Management</a>
      <ul class="navigation-2">
         <li><a href="/helpdesk/general.php" title="General Help Desk Settings" target="_self" >Help Desk Settings</a></li>
         <li><a href="/helpdesk/emails.php" title="Customize Emails" target="_self" >Customize Emails</a></li>
         <li><a href="/helpdesk/form.php" title="Manage Ticket Form Template" target="_self" >Ticket Form Template</a></li>
         <li><a href="/helpdesk/faqadmin.php" title="Knowledge Base" target="_self" >Knowledge Base</a></li>
         <li><a href="/helpdesk/backup.php" title="Help Desk Backup" target="_self" >Help Desk Backup</a></li>
      </ul>
   </li>
   <li><a href="" title="Departments" target="_self" >Departments</a>
      <ul class="navigation-2">
         <li><a href="/helpdesk/email.php" title="Email Processing" target="_self" >Email Processing</a></li>
         <li><a href="/helpdesk/department.php" title="View/Manage Departments" target="_self" >Manage Departments</a></li>
         <li><a href="/helpdesk/replies.php" title="Department Auto-Replies" target="_self" >Department Auto-Replies</a></li>
      </ul>
   </li>
   <li><a href="" title="User Management" target="_self" >User Management</a>
      <ul class="navigation-2">
         <li><a href="/helpdesk/user.php" title="View/Manage Users" target="_self" >View/Manage Users</a></li>
         <li><a href="/helpdesk/profile.php" title="Edit Your Profile & Options" target="_self" >Edit Your Profile</a></li>
         <li><a href="/helpdesk/messages.php" title="Message Center" target="_self" >Message Center</a></li>
      </ul>
   </li>
</ul>
</div>

    <?php /************************************************************/
}
/********************************************************** PHP */?>

<table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#33bbff"><tr><td><img src="./images/blank.gif" width="1" height="2"></td></tr></table>

<table align="center" width="770" border="0" cellspacing="0" cellpadding="0">
<tr><td>
<br />
<?php /************************************************************/
if( $INSTALLED )
  if( $global_priv && $PATH_TO_HELPDESK == "" )
  {
    echo "<table align=\"center\" bgcolor=\"#DDDDDD\" width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"6\">\n";
    echo "<tr><td><div class=\"normal\">You must specify the URL to the help desk in the general settings in order for the help desk to be completely functional.  You can do this in the <a href=\"$HD_URL_GENERAL\">general settings</a> area.  This message will disappear once you have successfully set this value.</div></td></tr>\n";
    echo "</table><br />\n";
  }
/********************************************************** PHP */?>

<table bgcolor="#33bbff" width="100%" border="0" cellspacing="0" cellpadding="5">
  <tr>
    <tr valign="center">
<?php /************************************************************/
if( $_SESSION['login_type'] == $LOGIN_INVALID )
  echo "<td><div class=\"smallinfo\">Not logged in.</div></td>";
else if( get_row_count( "SELECT COUNT(*) FROM {$pre}message WHERE ( user_id = '{$_SESSION['user']['id']}' && viewed = '0' )" ) )
{
  echo "<td width=\"15\"><a href=\"{$HD_URL_MESSAGES}\"><img src=\"browse_newreply.gif\" border=\"0\"></a></td>\n";
  echo "<td><div class=\"smallinfo\"><a href=\"{$HD_URL_MESSAGES}\">You have new messages</a>.</div></td>";
}
else  
  echo "<td><div class=\"smallinfo\">You have no new <a href=\"{$HD_URL_MESSAGES}\">messages</a>.</div></td>"; 
/********************************************************** PHP */?>
    <td align="right">
      <div class="topinfo">
<?php /************************************************************/
if( $INSTALLED )
  if( $_SESSION['login_type'] != $LOGIN_INVALID )
    echo "{$_SESSION['user']['name']} logged in.  You can <a href=\"login.php?cmd=logout\">log out</a>.&nbsp;";
/********************************************************** PHP */?>
      </div>
    </td>
  </tr>
</table>
</div>
<br />