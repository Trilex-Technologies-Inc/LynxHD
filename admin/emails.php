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

$HD_CURPAGE = $HD_URL_EMAILS;

if( $_SESSION['login_type'] == $LOGIN_INVALID )
  Header( "Location: {$HD_URL_LOGIN}?redirect=" . urlencode( $HD_CURPAGE ) );

$global_priv = get_row_count( "SELECT COUNT(*) FROM {$pre}privilege WHERE ( user_id = '{$_SESSION['user']['id']}' && dept_id = '0' )" );
if( !$global_priv )
  Header( "Location: {$HD_URL_LOGIN}?redirect=" . urlencode( $HD_CURPAGE ) );

$options = array( "emailheader", "emailfooter", "email_ticket_notify", "email_ticket_notify_subject", "email_ticket_created", "email_ticket_created_subject", "email_ticket_survey_subject", "email_ticket_survey", "email_notify_create_subject", "email_notify_create", "email_notify_reply_subject", "email_notify_reply", "email_notifysms_create_subject", "email_notifysms_create", "email_notifysms_reply_subject", "email_notifysms_reply", "email_ticket_lookup", "email_ticket_lookup_subject" );

if( isset( $_POST['emailheader'] ) )
{
  for( $i = 0; $i < count( $options ); $i++ )
  {
    $exists = get_row_count( "SELECT COUNT(*) FROM {$pre}options WHERE ( name = '{$options[$i]}' )" );
    if( $exists )
      mysql_query( "UPDATE {$pre}options SET text = '" . $_POST[$options[$i]] . "' WHERE ( name = '{$options[$i]}' )" );
    else
      mysql_query( "INSERT INTO {$pre}options ( name, text ) VALUES ( '{$options[$i]}', '" . $_POST[$options[$i]] . "' )" );
  }
  $msg = '<div class="alert alert-success shadow-sm"><i class="fas fa-check-circle mr-2"></i>Email templates updated successfully.</div>';
}

$_POST = get_options( $options );

include "./include/header.php";
/********************************************************** PHP */?>
<?php
$template_groups = array(
  'Customer emails' => array(
    array('email_ticket_created', 'Ticket created', 'Sent to a customer after a new ticket is created.', 'fa-ticket-alt'),
    array('email_ticket_notify', 'Ticket reply', 'Sent to a customer when a reply is added to their ticket.', 'fa-reply'),
    array('email_ticket_lookup', 'Ticket lookup', 'Sent when a customer requests a list of their tickets.', 'fa-search'),
    array('email_ticket_survey', 'Ticket survey', 'Sent when feedback is requested from a customer.', 'fa-poll')
  ),
  'Staff notifications' => array(
    array('email_notify_create', 'New ticket notification', 'Sent to assigned staff when a customer creates a ticket.', 'fa-user-plus'),
    array('email_notify_reply', 'Customer reply notification', 'Sent to assigned staff when a customer replies.', 'fa-comment-dots')
  ),
  'SMS gateway notifications' => array(
    array('email_notifysms_create', 'New ticket SMS', "Sent to a staff member's SMS email gateway for a new ticket.", 'fa-mobile-alt'),
    array('email_notifysms_reply', 'Customer reply SMS', "Sent to a staff member's SMS email gateway after a reply.", 'fa-mobile-alt')
  )
);
?>
<div class="d-lg-flex align-items-start justify-content-between mb-4">
  <div><h1 class="h3 mb-1 text-gray-800">Email templates</h1><p class="mb-0 text-muted">Customize the messages LynxHD sends to customers and staff.</p></div>
  <a class="btn btn-sm btn-outline-primary mt-3 mt-lg-0" href="general.php"><i class="fas fa-cog mr-1"></i>Email delivery settings</a>
</div>
<?php echo $msg ?? '' ?>

<div class="alert alert-info border-left-info shadow-sm email-template-help">
  <div class="d-flex"><i class="fas fa-info-circle mt-1 mr-3"></i><div><strong>Template variables are supported.</strong><br><span class="small">Keep existing variables such as <code>$ticket</code>, <code>$subject</code>, and <code>$name</code> intact. They are replaced with ticket details when email is sent.</span></div></div>
</div>

<form action="<?php echo field($HD_CURPAGE) ?>" method="post" class="email-templates-form">
  <input type="hidden" name="cmd" value="add">

  <section class="card shadow-sm mb-4">
    <div class="card-header py-3"><h2 class="h6 m-0 font-weight-bold text-primary"><i class="fas fa-layer-group mr-2"></i>Shared email content</h2></div>
    <div class="card-body"><p class="text-muted">This content is added before and after most outgoing email messages.</p><div class="row"><div class="col-12 mb-4"><label for="emailheader">Global header</label><textarea class="form-control" id="emailheader" name="emailheader" rows="6"><?php echo field($_POST['emailheader']) ?></textarea></div><div class="col-12"><label for="emailfooter">Global footer</label><textarea class="form-control" id="emailfooter" name="emailfooter" rows="6"><?php echo field($_POST['emailfooter']) ?></textarea></div></div></div>
  </section>

  <?php foreach($template_groups as $group_name => $templates): ?>
    <div class="email-template-section-heading d-flex align-items-center mb-3 mt-4"><h2 class="h5 mb-0 text-gray-800"><?php echo field($group_name) ?></h2><span class="badge badge-light border ml-2"><?php echo count($templates) ?></span></div>
    <div class="row">
      <?php foreach($templates as $template): $body_name = $template[0]; $subject_name = $body_name . '_subject'; ?>
        <div class="col-12 mb-4">
          <section class="card shadow-sm email-template-card">
            <div class="card-header py-3 d-flex align-items-center"><span class="email-template-icon mr-3"><i class="fas <?php echo field($template[3]) ?>"></i></span><div><h3 class="h6 font-weight-bold text-gray-800 mb-1"><?php echo field($template[1]) ?></h3><p class="small text-muted mb-0"><?php echo field($template[2]) ?></p></div></div>
            <div class="card-body">
              <div class="form-group"><label for="<?php echo field($subject_name) ?>">Subject line</label><input class="form-control" id="<?php echo field($subject_name) ?>" type="text" name="<?php echo field($subject_name) ?>" value="<?php echo field($_POST[$subject_name]) ?>"></div>
              <div class="form-group mb-0"><label for="<?php echo field($body_name) ?>">Message</label><textarea class="form-control" id="<?php echo field($body_name) ?>" name="<?php echo field($body_name) ?>" rows="7"><?php echo field($_POST[$body_name]) ?></textarea></div>
            </div>
          </section>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>

  <div class="card shadow-sm mb-4 email-template-actions"><div class="card-body d-flex flex-column flex-sm-row align-items-sm-center justify-content-between"><span class="small text-muted mb-3 mb-sm-0"><i class="fas fa-exclamation-circle mr-1"></i>Changes apply to future messages only.</span><div><button type="reset" class="btn btn-light mr-2">Reset changes</button><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Save templates</button></div></div></div>
</form>
<?php /************************************************************/
include "./include/footer.php";
/********************************************************** PHP */?>
