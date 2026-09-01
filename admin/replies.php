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

$HD_CURPAGE = $HD_URL_REPLIES;

if( $_SESSION['login_type'] == $LOGIN_INVALID )
  Header( "Location: {$HD_URL_LOGIN}?redirect=" . urlencode( $HD_CURPAGE ) );

$global_priv = get_row_count( "SELECT COUNT(*) FROM {$pre}privilege WHERE ( user_id = '{$_SESSION['user']['id']}' && dept_id = '0' && admin = '1' )" );

if( $_GET['cmd'] == "del" )
{
  $priv = get_row_count( "SELECT COUNT(*) FROM {$pre}privilege WHERE ( user_id = '{$_SESSION['user']['id']}' && dept_id = '{$_GET['dept_id']}' && admin = '1' )" );
  if( $priv || $global_priv )
  {
    mysql_query( "DELETE FROM {$pre}reply WHERE ( id = '{$_GET['id']}' && dept_id = '{$_GET['dept_id']}' )" );
    $msg = "<div class=\"successbox\">Successfully deleted auto-reply.</div><br />";
  }
}

include "./include/header.php";
/********************************************************** PHP */?>
<div class="title"><?php echo $script_name ?> Auto-Replies</div><br /><?php echo $msg ?>

  <div class="clean-gray">
    You can view auto-replies in this area.  Below are a list of the departments and their corresponding auto-replies.  You
    can create or delete auto-replies for departments in which you have administrative privileges.
  </div>

<br />
<div class="buttons">
    <a class="positive" href="<?php echo $HD_URL_REPLIESVIEW ?>"> 
        Create New Auto-Reply
    </a>
</div>

<?php /************************************************************/
$res = mysql_query( "SELECT * FROM {$pre}dept ORDER BY sortnum" );
while( $row = mysql_fetch_array( $res ) )
{
/********************************************************** PHP */?>
<section class="card shadow-sm mb-4"><div class="card-header"><h2 class="h6 mb-0"><?php echo field( $row['name'] ) ?></h2></div><div class="card-body">
  <p class="text-muted">Auto-replies for the following phrases will be used in this department:</p>
  <div class="list-group list-group-flush">
<?php /************************************************************/
  $res_reply = mysql_query( "SELECT * FROM {$pre}reply WHERE ( dept_id = '{$row['id']}' )" );
  $priv = get_row_count( "SELECT COUNT(id) FROM {$pre}privilege WHERE ( dept_id = '{$row['id']}' && admin = '1' && user_id = '{$_SESSION['user']['id']}' )" );

  if( mysql_num_rows( $res_reply ) )
  {
    while( $row_reply = mysql_fetch_array( $res_reply ) )
    {
      if( $global_priv || $priv )
        echo "<div class=\"list-group-item d-flex align-items-center justify-content-between\"><a href=\"$HD_URL_REPLIESVIEW?cmd=edit&id={$row_reply[0]}\">" . (($row_reply['phrase'] == "") ? "Global auto-reply — all phrases" : field( $row_reply['phrase'] )) . "</a><div><a class=\"btn btn-sm btn-outline-primary mr-2\" href=\"$HD_URL_REPLIESVIEW?cmd=edit&id={$row_reply[0]}\">Edit</a><a class=\"btn btn-sm btn-outline-danger\" href=\"javascript:if(confirm('Are you sure you want to delete this auto-reply?')) window.location.href = '$HD_CURPAGE?cmd=del&id={$row_reply[0]}&dept_id={$row_reply['dept_id']}'\">Delete</a></div></div>";
      else
        echo "<div class=\"list-group-item\"><a href=\"{$HD_URL_REPLIESVIEW}?cmd=edit&id={$row_reply[0]}\">" . (($row_reply['phrase'] == "") ? "Global auto-reply — all phrases" : field( $row_reply['phrase'] )) . "</a></div>";
    }
  }

  if( $priv || $global_priv )
    echo "";
/********************************************************** PHP */?>
  </div>
</div></section>
<?php /************************************************************/
}

/********************************************************** PHP */?>
<?php /************************************************************/
include "./include/footer.php";
/********************************************************** PHP */?>
