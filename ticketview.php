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
include "./include/settings.php";
include "./include/include.php";

$HD_CURPAGE = $HD_URL_TICKET_VIEW;

$options = array( "header", "footer", "logo", "title", "background", "outsidebackground", "border", "topbar", "menu", "styles", "email", "url", "emailheader", "emailfooter", "tags", "uploads", "autosurvey", "email_ticket_notify", "email_ticket_notify_subject", "email_ticket_created", "email_ticket_created_subject", "email_ticket_survey_subject", "email_ticket_survey", "email_notify_create_subject", "email_notify_create", "email_notify_reply_subject", "email_notify_reply", "floodcontrol", "email_notifysms_create_subject", "email_notifysms_create", "email_notifysms_reply_subject", "email_notifysms_reply", "cc" );
$data = get_options( $options );

if( isset( $_POST['id'] ) )
{
  //Attempt to filter the input
  $_GET['id'] = $_POST['id'];
  $_GET['email'] = $_POST['email'];
  
}

$ticketexists = 0;

if( isset( $_GET['id'], $_GET['email'] ) && $_GET['id'] !== '' && $_GET['email'] !== '' )
{
  $exists = get_row_count( "SELECT COUNT(*) FROM {$pre}ticket WHERE ( ticket_id = '{$_GET['id']}' && email = '{$_GET['email']}' )" );
  if( !$exists )
  {
    $msg = "<div class=\"alert alert-danger\" role=\"alert\">";
    eval( "\$msg .= \"{$LANG['no_find_ticket']}\";" );
    $msg .= "</div>";

    $ticketexists = 0;
  }
  else
    $ticketexists = 1;
}

if( $ticketexists )
{
  // Get row before updates
  $row = mysql_fetch_array( mysql_query( "SELECT * FROM {$pre}ticket WHERE ( ticket_id = '{$_GET['id']}' )" ) );

  if( ($_POST['cmd'] ?? '') == "reply" )
  {
    $_POST['subject'] = $_POST['subject'] ?? '';
    if( trim( $_POST['message'] ?? '' ) == "" )
      $msg = "<div class=\"alert alert-danger\" role=\"alert\">{$LANG['specify_message']}</div>";
    else
    {
      $userid = -1;

    // Checks for a duplicate posting if flood protection is enabled
    $res_check = mysql_query( "SELECT subject, message FROM {$pre}post WHERE ( ticket_id = '{$row['id']}' ) ORDER BY date DESC LIMIT 1" );
    $row_check = mysql_fetch_array( $res_check );
    if( !$data['floodcontrol'] || 
        (trim( $row_check['subject'] ) != trim( stripslashes( $_POST['subject'] ?? '' ) )) ||
        (trim( $row_check['message'] ) != trim( stripslashes( $_POST['message'] ?? '' ) )) )
    {
      mysql_query( "INSERT INTO {$pre}post ( ticket_id, user_id, date, subject, message, ip ) VALUES ( '{$row['id']}', '$userid', '" . time( ) . "', '{$_POST['subject']}', '{$_POST['message']}', '{$_SERVER['REMOTE_ADDR']}' )" );

      mysql_query( "UPDATE {$pre}ticket SET lastactivity = '" . time( ) . "', lastpost = '-1' WHERE ( ticket_id = '{$_GET['id']}' )" );

      // Notification messages
      $res_user = mysql_query( "SELECT DISTINCT user.email, user.sms FROM {$pre}user AS user, {$pre}privilege AS priv, {$pre}post AS post WHERE ( user.id = priv.user_id && (priv.dept_id = '0' || priv.dept_id = '{$row['dept_id']}') && user.notify & {$HD_NOTIFY_REPLY} > '0' && post.user_id = user.id && post.ticket_id = '{$row['id']}' )" );

      while( $row_user = mysql_fetch_array( $res_user ) )
      {
        $message = stripslashes( $_POST['message'] );
        $ticket = $_GET['id'];

        eval( "\$email_subject = \"{$data['email_notify_reply_subject']}\";" );
        eval( "\$email_message = \"{$data['email_notify_reply']}\";" );
        hd_mail( $row_user['email'], $email_subject, $email_message, "From: {$data['email']}" );

        if( trim( $row_user['sms'] ) != "" )
        {
          eval( "\$email_subject = \"{$data['email_notifysms_reply_subject']}\";" );
          eval( "\$email_message = \"{$data['email_notifysms_reply']}\";" );
          hd_mail( $row_user['sms'], $email_subject, $email_message, "From: {$data['email']}" );
        }
      }
    }
    }
  }
  else if( ($_GET['cmd'] ?? '') == "deletepost" )
    mysql_query( "DELETE FROM {$pre}post WHERE ( id = '{$_GET['postid']}' && ticket_id = '{$row['id']}' )" );
  else if( ($_GET['cmd'] ?? '') == "close" )
  {
    mysql_query( "UPDATE {$pre}ticket SET lastactivity = '" . time( ) . "', status = '{$HD_STATUS_CLOSED}' WHERE ( ticket_id = '{$_GET['id']}' )" );

    // Send survey if enabled
    if( $data['autosurvey'] )
      send_survey( $row['id'] );
  }
  else if( ($_GET['cmd'] ?? '') == "open" )
    mysql_query( "UPDATE {$pre}ticket SET lastactivity = '" . time( ) . "', status = '{$HD_STATUS_OPEN}' WHERE ( ticket_id = '{$_GET['id']}' )" );
  else if( ($_POST['cmd'] ?? '') == "attach" && !empty($_FILES['userfile']['name']) )
  {
    if( !is_dir( "{$HD_TICKET_FILES}/{$row['id']}" ) )
    {
      $oldumask = umask( 0 ); 
      mkdir( "{$HD_TICKET_FILES}/{$row['id']}", 0777 );
      umask( $oldumask );
    }

    move_uploaded_file($_FILES['userfile']['tmp_name'], "{$HD_TICKET_FILES}/{$row['id']}/" . basename($_FILES['userfile']['name']));
  }
  else if( ($_POST['cmd'] ?? '') == "cc" )
    mysql_query( "UPDATE {$pre}ticket SET cc = '" . ($_POST['cc'] ?? '') . "' WHERE ( ticket_id = '" . ($_POST['id'] ?? '') . "' )" );
  
  // Get row after possible updates
  $row = mysql_fetch_array( mysql_query( "SELECT * FROM {$pre}ticket WHERE ( ticket_id = '{$_GET['id']}' )" ) );

  $res_others = mysql_query( "SELECT * FROM {$pre}ticket WHERE ( email = '{$row['email']}' && id != '{$row['id']}' ) ORDER BY date DESC" );
}


if( trim( $data['header'] ) == "" )
{
/********************************************************** PHP */?>
<?php 
include "./include/header.php";
?>
<?php /************************************************************/
}
else
  eval( "?> {$data['header']} <?php" );
/********************************************************** PHP */?>

<?php if (trim($data['styles']) !== ''): ?><style><?php echo $data['styles'] ?></style><?php endif; ?>
<?php echo $msg ?? '' ?>
<?php /************************************************************/
if( $ticketexists )
{
/********************************************************** PHP */?>
<?php /************************************************************/
  $res_dept = mysql_query( "SELECT name FROM {$pre}dept WHERE ( id = '{$row['dept_id']}' )" );
  $row_dept = mysql_fetch_array( $res_dept ) ?: array( 0 => 'Unknown department' );
  $department_name = field( $row_dept[0] );
  if( $row['priority'] == $PRIORITY_LOW ) $priority_name = $LANG['field_priority_low'];
  else if( $row['priority'] == $PRIORITY_MEDIUM ) $priority_name = $LANG['field_priority_medium'];
  else $priority_name = $LANG['field_priority_high'];
/********************************************************** PHP */?>
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3 mb-4">
  <div>
    <div class="d-flex align-items-center gap-2 mb-2">
      <span class="badge <?php echo $row['status'] == $HD_STATUS_OPEN ? 'text-bg-success' : 'text-bg-secondary' ?>"><?php echo $row['status'] == $HD_STATUS_OPEN ? 'Open' : 'Closed' ?></span>
      <span class="text-secondary">Ticket #<?php echo field($_GET['id']) ?></span>
    </div>
    <h2 class="h3 mb-1"><?php echo field($row['subject']) ?></h2>
    <p class="text-secondary mb-0"><?php echo date("F j, Y g:ia T", $row['date']) ?></p>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <?php if ($row['status'] == $HD_STATUS_OPEN): ?><a class="btn btn-primary" href="#reply"><?php echo $LANG['post_reply'] ?></a><?php endif; ?>
    <a class="btn btn-outline-secondary" href="printable.php?id=<?php echo field($_GET['id']) ?>&amp;email=<?php echo field($_GET['email']) ?>" target="_blank" rel="noopener"><?php echo $LANG['printable'] ?></a>
    <?php if ($data['uploads'] && $row['status'] == $HD_STATUS_OPEN): ?><a class="btn btn-outline-secondary" href="#attach"><?php echo $LANG['attach_file'] ?></a><?php endif; ?>
    <?php if ($data['cc'] && $row['status'] == $HD_STATUS_OPEN): ?><a class="btn btn-outline-secondary" href="#cc"><?php echo $LANG['carbon_copy'] ?></a><?php endif; ?>
    <?php if ($row['status'] == $HD_STATUS_OPEN): ?><a class="btn btn-outline-danger" href="<?php echo $HD_CURPAGE ?>?cmd=close&amp;id=<?php echo field($_GET['id']) ?>&amp;email=<?php echo field($_GET['email']) ?>" onclick="return confirm('<?php echo field($LANG['confirm_close_ticket']) ?>')"><?php echo $LANG['close_ticket'] ?></a><?php endif; ?>
  </div>
</div>
<?php if ($row['status'] != $HD_STATUS_OPEN): ?>
  <div class="alert alert-warning" role="alert"><?php eval("echo \"{$LANG['ticket_no_longer_open']}\";") ?></div>
<?php endif; ?>
<div class="ticket-meta row g-3 mb-4">
  <div class="col-sm-6 col-lg-3"><div class="border rounded-3 p-3 h-100"><small class="text-secondary d-block"><?php echo $LANG['field_department'] ?></small><strong><?php echo $department_name ?></strong></div></div>
  <div class="col-sm-6 col-lg-3"><div class="border rounded-3 p-3 h-100"><small class="text-secondary d-block"><?php echo $LANG['field_priority'] ?></small><strong><?php echo $priority_name ?></strong></div></div>
  <div class="col-sm-6 col-lg-3"><div class="border rounded-3 p-3 h-100"><small class="text-secondary d-block"><?php echo $LANG['field_email'] ?></small><strong><?php echo field($row['email']) ?></strong></div></div>
  <?php if (mysql_num_rows($res_others)): ?><div class="col-sm-6 col-lg-3"><a class="border rounded-3 p-3 h-100 d-block text-decoration-none" href="<?php echo $HD_URL_TICKET_LOST ?>?cmd=lost&amp;email=<?php echo field($_GET['email']) ?>" target="_blank"><small class="text-secondary d-block">Account</small><strong><?php echo $LANG['other_tickets'] ?> &rarr;</strong></a></div><?php endif; ?>
</div>
<?php /************************************************************/

  if( $dir = @opendir( "{$HD_TICKET_FILES}/{$row['id']}" ) )
  {
    $files = array( );
    while( $file = readdir( $dir ) )
    {
      if( $file != "." && $file != ".." )
        array_push( $files, array( filectime( "{$HD_TICKET_FILES}/{$row['id']}/{$file}" ), $file ) );
    }

    usort( $files, "attach_sort" );
    if (count($files)) {
      echo '<div class="alert alert-light border"><strong class="d-block mb-2">' . $LANG['field_attachments'] . '</strong><div class="d-flex flex-wrap gap-2">';
      for( $i = 0; $i < count( $files ); $i++ )
        echo '<a class="btn btn-sm btn-outline-primary" href="' . $HD_URL_ATTACHMENT . '?id=' . field($_GET['id']) . '&amp;email=' . field($row['email']) . '&amp;file=' . urlencode($files[$i][1]) . '" target="_blank">' . field($files[$i][1]) . '</a>';
      echo '</div></div>';
    }
  }
/********************************************************** PHP */?>
<h3 class="h5 mb-3">Conversation</h3>
<div class="ticket-thread d-grid gap-3 mb-5">
<?php /************************************************************/
  $res_temp = mysql_query( "SELECT id FROM {$pre}post WHERE ( ticket_id = '{$row['id']}' ) ORDER BY date LIMIT 1" );
  $row_temp = mysql_fetch_array( $res_temp ) ?: array( 0 => 0 );
  $first_id = $row_temp[0];

  $res_post = mysql_query( "SELECT * FROM {$pre}post WHERE ( ticket_id = '{$row['id']}' && private = '0' ) ORDER BY date DESC" );

  while( $row_post = mysql_fetch_array( $res_post ) )
  {
    if( trim( $row_post['subject'] ) == "" )
      $row_post['subject'] = $LANG['no_subject'];

    if( $row_post['user_id'] == -1 )
      $author = '<a href="mailto:' . field($row['email']) . '">' . field($row['name']) . '</a>';
    else
    {
      $res_user = mysql_query( "SELECT name, signature FROM {$pre}user WHERE ( id = '{$row_post['user_id']}' )" );
      $row_user = mysql_fetch_array( $res_user ) ?: array( 'name' => 'Unknown user', 'signature' => '' );

      if( trim( $row_user['signature'] ) != "" )
        $row_post['message'] .= "\n\n{$row_user['signature']}";

      $author = field($row_user['name']) . ' <span class="badge text-bg-primary">Staff</span>';
    }

    if( $data['tags'] )
      $row_post['message'] = parse_tags( $row_post['message'] );
    else
      $row_post['message'] = parse_no_tags( $row_post['message'] );

    echo '<article class="ticket-message card border shadow-sm"><div class="card-header bg-white d-flex flex-column flex-sm-row justify-content-between gap-2 p-3"><div><strong>' . field($row_post['subject']) . '</strong><small class="d-block text-secondary">' . $LANG['posted_by'] . ' ' . $author . '</small></div><time class="small text-secondary">' . date("M j, Y g:ia T", $row_post['date']) . '</time></div><div class="card-body ticket-copy">' . $row_post['message'] . '</div>';
    if( ($row_post['user_id'] == -1) && ($row_post['id'] != $first_id) )
      echo '<div class="card-footer bg-white"><a class="text-danger small" href="' . $HD_CURPAGE . '?cmd=deletepost&amp;postid=' . (int) $row_post['id'] . '&amp;id=' . field($_GET['id']) . '&amp;email=' . field($_GET['email']) . '" onclick="return confirm(\'' . field($LANG['confirm_delete_post']) . '\')">' . $LANG['delete_post'] . '</a></div>';
    echo '</article>';
  }
/********************************************************** PHP */?>
</div>

<?php /************************************************************/
  if( $row['status'] == $HD_STATUS_OPEN )
  {
/********************************************************** PHP */?>
<section id="reply" class="ticket-panel border rounded-3 p-4 mb-4">
<h3 class="h4 mb-3"><?php echo $LANG['post_reply'] ?></h3>
<form action="<?php echo $HD_CURPAGE ?>" method="post" class="row g-3">
<input type="hidden" name="id" value="<?php echo field($_GET['id']) ?>" />
<input type="hidden" name="email" value="<?php echo field($_GET['email']) ?>" />
<input type="hidden" name="cmd" value="reply" />
<div class="col-12"><label class="form-label" for="reply-subject"><?php echo $LANG['field_subject'] ?></label><input class="form-control" id="reply-subject" type="text" name="subject" value="<?php echo field($_POST['subject'] ?? '') ?>"></div>
<div class="col-12"><div class="d-flex justify-content-between"><label class="form-label" for="reply-message"><?php echo $LANG['field_message'] ?></label><?php if ($data['tags']): ?><a class="small" href="<?php echo $HD_URL_TICKET_TAGS ?>" target="_blank">Formatting help</a><?php endif; ?></div><textarea class="form-control" id="reply-message" name="message" rows="7" required><?php echo field($_POST['message'] ?? '') ?></textarea></div>
<div class="col-12 d-flex justify-content-end gap-2"><button type="reset" class="btn btn-outline-secondary">Reset</button><button type="submit" class="btn btn-primary">Post reply</button></div>
</form>
</section>
<?php /************************************************************/
    if( $data['uploads'] )
    {
/********************************************************** PHP */?>
<section id="attach" class="ticket-panel border rounded-3 p-4 mb-4">
<h3 class="h4 mb-3"><?php echo $LANG['attach_file'] ?></h3>
<form enctype="multipart/form-data" action="<?php echo $HD_CURPAGE ?>" method="post" class="row g-3">
  <input type="hidden" name="cmd" value="attach">
  <input type="hidden" name="id" value="<?php echo $_GET['id'] ?>">
  <input type="hidden" name="email" value="<?php echo $_GET['email'] ?>">
  <div class="col-md-9"><label class="form-label" for="ticket-file"><?php echo $LANG['field_file'] ?></label><input class="form-control" id="ticket-file" name="userfile" type="file" required></div><div class="col-md-3 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit">Attach</button></div>
</form>
</section>
<?php /************************************************************/
    }
/********************************************************** PHP */?>

<?php /************************************************************/
    if( $data['cc'] )
    {
/********************************************************** PHP */?>
<section id="cc" class="ticket-panel border rounded-3 p-4 mb-4">
<h3 class="h4 mb-3"><?php echo $LANG['carbon_copy'] ?></h3>
<form action="<?php echo $HD_CURPAGE ?>" method="post" class="row g-3">
<input type="hidden" name="cmd" value="cc" />
<input type="hidden" name="id" value="<?php echo $_GET['id'] ?>" />
<input type="hidden" name="email" value="<?php echo $_GET['email'] ?>">
<div class="col-md-9"><label class="form-label" for="ticket-cc"><?php echo $LANG['field_email'] ?></label><input class="form-control" id="ticket-cc" type="text" name="cc" value="<?php echo field($row['cc']) ?>"><div class="form-text"><?php echo $LANG['separate_by_space'] ?></div></div><div class="col-md-3 d-flex align-items-end"><button type="submit" class="btn btn-primary w-100">Save CC</button></div>
</form>
</section>
<?php /************************************************************/
    }
  }
}
else
{
/********************************************************** PHP */?>
<div class="ticket-lookup mx-auto py-3">
  <div class="text-center mb-4"><span class="badge text-bg-primary mb-3">Ticket lookup</span><h2 class="h3"><?php echo $LANG['viewing_ticket'] ?></h2><p class="text-secondary"><?php eval("echo \"{$LANG['view_ticket_help']}\";") ?></p></div>
<form action="<?php echo $HD_CURPAGE ?>" method="get" class="row g-4">
<div class="col-12"><label class="form-label" for="lookup-email"><?php echo $LANG['field_email'] ?></label><input class="form-control form-control-lg" id="lookup-email" type="email" name="email" value="<?php echo field($_GET['email'] ?? '') ?>" required autocomplete="email"></div>
<div class="col-12"><label class="form-label" for="lookup-id"><?php echo $LANG['field_ticket_id'] ?></label><input class="form-control form-control-lg" id="lookup-id" type="text" name="id" value="<?php echo field($_GET['id'] ?? '') ?>" required></div>
<div class="col-12"><button type="submit" class="btn btn-primary btn-lg w-100">View ticket</button></div>
</form>
</div>
<?php /************************************************************/
}
if( trim( $data['header'] ) == "" )
{
/********************************************************** PHP */?>
<?php 
include "./include/footer.php";
?>
<?php /************************************************************/
}
else
  eval( "?> {$data['footer']} <?php" );
/********************************************************** PHP */?>
