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
include "./include/header.php";
?>
	<tr>
		<td>
			<table width="100%">
				<tr>
					<td width="50px" valign="top">
						<a href="newticket.php" title="New Ticket"><img src="./images/createticket.png" border="0" alt="New Ticket"></a>
					</td>
					<td valign="top">
						<div style="height:17px"><a href="newticket.php">
							<b>Create Ticket</b></a>
						</div>
						<div style="padding-top:8px"><a href="newticket.php">Ask your question by creating a new ticket.</a> </div>

					</td>
				</tr>
			</table>
		</td>
				<td>
			<table width="100%">
				<tr>
					<td width="50px" valign="top">
						<a href="ticketview.php" title="View Ticket"><img src="./images/viewticket.png" border="0" alt="View Ticket"></a>

					</td>
					<td valign="top">
						<div style="height:17px"><a href="ticketview.php">
							<b>View Ticket</b></a>
						</div>
						<div style="padding-top:8px"><a href="ticketview.php">Browse and view your tickets here.</a></div>
					</td>
				</tr>

			</table>
		</td>
	</tr>

	<tr>
		<td>
			<table width="100%">

				<tr>
					<td width="50px" valign="top">
						<a href="ticket.php?cmd=lost" title="Lost Ticket"><img src="./images/lostticket.png" border="0" alt="Lost Ticket"></a>
					</td>
					<td valign="top">
						<div style="height:17px"><a href="ticket.php?cmd=lost">
							<b>Lost Ticket</b></a>
						</div>

						<div style="padding-top:8px"><a href="ticket.php?cmd=lost">You can get your ticket list here.</a></div>
					</td>
				</tr>
			</table>
		</td>
		<td>
			<table width="100%">
				<tr>

					<td width="50px" valign="top">
						<a href="faq.php" title="Knowledgebase"><img src="./images/knowledgebase.png" border="0" alt="Knowledgebase"></a>
					</td>
					<td valign="top">
						<div style="height:17px"><a href="faq.php">
							<b>Knowledgebase</b></a>
						</div>
						<div style="padding-top:8px"><a href="faq.php">You can find your questions and answers here.</a></div>

					</td>
				</tr>
			</table>
		</td>
	</tr>

	<tr>

		<td>
			<table width="100%">
				<tr>
					<td width="50px" valign="top">
						<a href="download.php" title="Download"><img src="./images/downloads.png" border="0" alt="Download"></a>
					</td>
					<td valign="top">
						<div style="height:17px"><a href="./downloads/index.php">
							<b>Download</b></a>

						</div>
						<div style="padding-top:8px"><a href="download.php">Browse and download our downloadable material.</a></div>
					</td>
				</tr>
			</table>
		</td>
		<td>
			<table width="100%">

				<tr>
					<td width="50px" valign="top">
						<a href="announcements.php" title="Announcement"><img src="./images/announcement.png" border="0" alt="Announcement"></a>
					</td>
					<td valign="top">
						<div style="height:17px"><a href="announcements.php">
							<b>Announcement</b></a>
						</div>

						<div style="padding-top:8px"><a href="announcements.php">Browse and view our latest information.</a></div>
					</td>
				</tr>
			</table>
		</td>
	</tr>
<?php 
include "./include/footer.php";
?>
