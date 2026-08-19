<?php 
////////////////////////////////////////////////////////////////////
// ColdBrew Help Desk
// -----------------------------------------------------------------
//
// License info can be found in license.txt.
// You must leave this notice as is.
// 
// ColdBrew Helpdesk is based off of:
//
// Application: PMOS Help Desk
//      Author: John Heathco
//         Web: http://www.h2desk.com/pmos
//
// ColdBrew Helpdesk has been modified and mantained by:
//
//      Author: James Paige
//         Web: http://www.GetColdBrew.com
// -----------------------------------------------------------------
////////////////////////////////////////////////////////////////////
$LANG = array( );

$LANG['fields_not_filled'] = "<li id=\"errorLi\">
<h3 id=\"errorMsgLbl\">One or more required fields was invalid or not filled in.</h3>
<p id=\"errorMsg\">Errors have been <strong>highlighted</strong> below.</p>
</li>";
$LANG['create_new_ticket'] = "Create New Ticket";
$LANG['ticket_created'] = "Your ticket has been created. An email has been sent to you containing the ticket information. If you would like to view your ticket and/or attach files you can do so: <a href=\\\"{\$HD_URL_TICKET_VIEW}?id=\$ticket&email={\$_POST['email']}\\\">\$ticket</a>";
$LANG['fill_in_form'] = "To create a new support ticket, please fill out the form below.";
$LANG['required_field'] = "* Denotes a required field";
$LANG['field_name'] = "Name:";
$LANG['field_email'] = "Email:";
$LANG['field_subject'] = "Subject:";
$LANG['field_message'] = "Message:";
$LANG['field_department'] = "Department:";
$LANG['field_priority'] = "Priority:";
$LANG['field_priority_low'] = "Low";
$LANG['field_priority_medium'] = "Medium";
$LANG['field_priority_high'] = "High";
$LANG['field_created_on'] = "Created On:";
$LANG['field_attachments'] = "Attachments:";
$LANG['field_file'] = "File:";
$LANG['field_ticket_id'] = "Ticket ID#:";
$LANG['field_date'] = "Date:";
$LANG['field_cc'] = "CC:";
$LANG['ticket_notify'] = "Notify me when my ticket is responded to.";
$LANG['select_department'] = "Please select the department that you would like this ticket to be dispatched to:";
$LANG['next_step'] = "Next &gt;&gt;";

$LANG['no_find_ticket'] = "Could not find a ticket with that ID.  You can <a href=\\\"{\$HD_URL_TICKET_LOST}?cmd=lost\\\">lookup</a> all your tickets or enter another below.";
$LANG['no_subject'] = "No subject";
$LANG['delete_post'] = "Delete Post";
$LANG['confirm_delete_post'] = "Are you sure you want to delete this post?";
$LANG['posted_by'] = "Posted by";
$LANG['specify_message'] = "You must specify a message in your reply (subjects are optional).";
$LANG['viewing_ticket'] = "Viewing Ticket";
$LANG['post_reply'] = "Post Reply";
$LANG['attach_file'] = "Attach File";
$LANG['close_ticket'] = "Close Ticket";
$LANG['carbon_copy'] = "Carbon Copy";
$LANG['separate_by_space'] = "(separate emails by a space)";
$LANG['confirm_close_ticket'] = "Are you sure you want to close this ticket?";
$LANG['ticket_no_longer_open'] = "This ticket is no longer open.  You can <a href=\\\"{\$HD_CURPAGE}?cmd=open&id={\$_GET['id']}&email={\$_GET['email']}\\\">re-open</a> it.";
$LANG['view_ticket_help'] = "To view a ticket, please enter your email address and ticket ID.<br />  If you forgot your ticket ID, you can use the <a href=\\\"{\$HD_URL_TICKET_LOST}?cmd=lost\\\">ticket lookup</a>.";
$LANG['printable'] = "Print";

$LANG['no_ticket_address'] = "Could not find any tickets associated with that email address.";
$LANG['retrieve_lost_ticket'] = "Retrieve Lost Ticket";
$LANG['ticket_info_sent'] = "Your ticket information has been successfully sent to your email address.";
$LANG['email_address_used'] = "Please enter your email address you used when creating your ticket.<br />  All tickets ID numbers will be sent to that email address.";
$LANG['retrieve_lookup_button'] = "Lookup";

$LANG['knowledge_base'] = "Knowledge Base";
$LANG['faq_browsing'] = "Browsing";
$LANG['faq_main_category'] = "Main Category";
$LANG['faq_parent_category'] = "Parent Category";
$LANG['search_for'] = "Search for:";
$LANG['faq_subcategories'] = "subcategories";
$LANG['faq_no_description'] = "No description";
$LANG['faq_entries'] = "entries";
$LANG['faq_symptoms'] = "SYMPTOMS";
$LANG['faq_no_symptoms'] = "No symptoms.";
$LANG['faq_solution'] = "SOLUTION";
$LANG['faq_no_solution'] = "No solution.";
$LANG['faq_no_results'] = "No results found for your search.";
$LANG['faq_categories'] = "Back to categories";
$LANG['faq_search_button'] = "Search";

$LANG['link_home'] = "Home";
$LANG['link_view_ticket'] = "View Ticket";
$LANG['link_lost_ticket'] = "Lost Ticket?";
$LANG['link_faq'] = "Knowledge Base";
$LANG['link_staff_login'] = "Staff Login";

$LANG['survey'] = "Survey Our Support";
$LANG['survey_header'] = "Thank you for choosing to fill out this brief survey.  Your feedback will help us to better serve you and others in the future.  You can view the original ticket <a href=\\\"{\$HD_URL_TICKET_VIEW}?cmd=view&id={\$_GET['id']}&email={\$_GET['email']}\\\" target=\\\"_blank\\\">here</a>.  Please rate us in the following categories:";
$LANG['survey_poor'] = "(poor)";
$LANG['survey_excellent'] = "(excellent)";
$LANG['survey_comments'] = "Comments:";
$LANG['survey_submit'] = "Submit Survey";
$LANG['survey_thanks'] = "Thank you for taking the time to fill out the survey!  Your feedback is much appreciated.";

$LANG['banned'] = "This email address and/or IP address has been banned from the help desk.";

$LANG['other_tickets'] = "Email me my other tickets";

/********************************************************** PHP */?>