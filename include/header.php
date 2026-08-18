<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<!-- JavaScript -->
<script type="text/javascript" src="js/wufoo.js"></script>
<!-- CSS -->
<link rel="stylesheet" href="css/structure.css" type="text/css" />
<link rel="stylesheet" href="css/form.css" type="text/css" />
<link rel="stylesheet" href="css/theme.css" type="text/css" />
<link rel="stylesheet" href="css/buttons.css" type="text/css" />
<link rel="stylesheet" href="css/client.css" type="text/css" />
<title>Helpdesk - Main</title>
</head>
<body>
<div id="inhalt">
<p align="right">

<h1>Welcome to our support desk</h1>

<p><table width="100%" cellspacing="0" cellpadding="0" align="center" class="main_area">
	<tr>
		<td>
			<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center">
				<tr>
					<td class="header">
						<table cellpadding="0" cellspacing="0">
							<tr>

							</tr>
						</table>
					 </td>
			  	</tr>
			</table>

      <table width="95%" border="0" cellspacing="0" cellpadding="4" align="center">
        <tr>
          <td align="right" colspan="2"><a href="index.php"><img src="./images/home.png" height="32" width="32" align="middle" border="0"></a></td>
                  <?php 
		$filename = './install/open-db.php';

		if (file_exists($filename)) {
   	 		echo "<div class=\"clean-red\"><center>If this is a new install <a href=\"install/index.php\"><b>Let's go to the installer!</b></a> <br />Otherwise please <b>delete the 'install' folder</b> for security purposes and this message will dissapear.</center></div>";
		} else {
   			echo "";
		}
		  ?>
		</tr>
      </table>

	<table width="95%" border="0" cellspacing="3" cellpadding="3" align="center">
	<tr><td colspan="2" style="border-bottom:1px dashed #85BDD8"><div style="font-weight:bold;font-size:16px;height:25px">Lynx Helpdesk System</div></td></tr><!-- end of header -->
<table width="100%" border="0" cellspacing="0" cellpadding="15">
<tr>
<td>&nbsp;
