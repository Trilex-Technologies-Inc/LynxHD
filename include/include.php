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
include __DIR__ . "/../lang/language.php"; // Language pack file
require_once __DIR__ . "/mysql-compat.php";

// Optional request values and page messages are absent on an initial page load.
// Give legacy pages safe defaults so PHP 8 does not emit undefined-key warnings.
$msg = "";
$_GET['cmd'] = $_GET['cmd'] ?? '';
$_POST['cmd'] = $_POST['cmd'] ?? '';

$website_name = "LynxHD";
$script_name = "";
$script_info = "Help Desk";

$LOGIN_INVALID = 0;
$LOGIN_USER = 1;
$LOGIN_CUST = 2;

$PRIORITY_LOW = 0;
$PRIORITY_MEDIUM = 1;
$PRIORITY_HIGH = 2;

$HD_STATUS_OPEN = 0;
$HD_STATUS_CLOSED = 1;
$HD_STATUS_HELD = 2;

$HD_NOTIFY_CREATION = 1;
$HD_NOTIFY_REPLY = 2;
$HD_NOTIFY_SAVELOGIN = 4;

$HD_DEPARTMENT_INVISIBLE = 1;

$HD_TICKET_FILES = "files";

$HD_URL_LOGIN = "index.php";
$HD_URL_USER = "user.php";
$HD_URL_DEPARTMENT = "department.php";
$HD_URL_PROFILE = "profile.php";
$HD_URL_FORM = "form.php";
$HD_URL_GENERAL = "general.php";
$HD_URL_REPLIES = "replies.php";
$HD_URL_REPLIESVIEW = "repliesview.php";
$HD_URL_BROWSE = "browse.php";
$HD_URL_ADMINVIEW = "adminview.php";
$HD_URL_SETUP = "setup.php";
$HD_URL_EMAIL = "email.php";
$HD_URL_MANUAL = "manual.php";
$HD_URL_EMAILS = "emails.php";
$HD_URL_FAQADMIN = "faqadmin.php";
$HD_URL_BACKUP = "backup.php";
$HD_URL_MASSREPLY = "massreply.php";
$HD_URL_STATS = "stats.php";
$HD_URL_ADMINTICKET = "adminticket.php";
$HD_URL_SURVEY = "adminsurvey.php";
$HD_URL_PRINTABLE = "printable.php";
$HD_URL_MESSAGES = "messages.php";
$HD_URL_ATTACHMENT = "attachment.php";
$HD_URL_PASSWORD = "password.php";

$HD_URL_TICKET_HOME = "newticket.php";
$HD_URL_TICKET_TAGS = "tickettags.php";
$HD_URL_TICKET_VIEW = "ticketview.php";
$HD_URL_TICKET_LOST = "ticket.php";
$HD_URL_TICKET_SURVEY = "survey.php";
$HD_URL_FAQ = "faq.php";

$ENCRYPT_KEY = "IV";

$CODEFROM = array(
  "/([^=a-z0-9\._-]|^)([a-z_-][a-z0-9\._-]*@[a-z0-9_-]+(\.[a-z0-9_-]+)+)/is",
  "/([^=\]]|^)(https?:\/\/[^<>()\s]+)/is",
  "/\[url\](.+?)\[\/url\]/is",
  "/\[url=(.+?)\](.+?)\[\/url\]/is",
  "/\[b\](.+?)\[\/b\]/is",
  "/\[i\](.+?)\[\/i\]/is",
  "/\[u\](.+?)\[\/u\]/is",
  "/\[s\](.+?)\[\/s\]/is",
  "/\[img\](.+?)\[\/img\]/i",
  "/\[color=([\w#]+)\](.*?)\[\/color\]/is",
  "/\[black\](.+?)\[\/black\]/is",
  "/\[white\](.+?)\[\/white\]/is",
  "/\[red\](.+?)\[\/red\]/is",
  "/\[green\](.+?)\[\/green\]/is",
  "/\[blue\](.+?)\[\/blue\]/is",
  "/\[font=(.+?)\](.+?)\[\/font\]/is",
  "/\[size=(.+?)\](.+?)\[\/size\]/is",
	"/\[pre\](.+?)\[\/pre\]/is",
	"/\[left\](.+?)\[\/left\]/is",
	"/\[right\](.+?)\[\/right\]/is",
	"/\[center\](.+?)\[\/center\]/is",
	"/\[sub\](.+?)\[\/sub\]/is",
	"/\[sup\](.+?)\[\/sup\]/is",
	"/\[table\](.+?)\[\/table\]/is",
	"/\[tr\](.+?)\[\/tr\]/is",
	"/\[td\](.+?)\[\/td\]/is",
	"/\[ftp\](.+?)\[\/ftp\]/is",
	"/\[ftp=(.+?)\](.+?)\[\/ftp\]/is",
  "/\[email\](.+?)\[\/email\]/is",
  "/\[hr\]/i",
  "/\[list\]/",
  "/\[\/list\]/",
  "/\[\*\](.+?)/s",
  "/\[code\](.+?)\[\/code\]/is"
);

$CODETO = array(
  "$1<a href=\"mailto:$2\">$2</a>",
  "$1<a href=\"$2\" target=\"_blank\">$2</a>",
  "<a href=\"$1\" target=\"_blank\">$1</a>",
  "<a href=\"$1\" target=\"_blank\">$2</a>",
  "<b>$1</b>",
  "<i>$1</i>",
  "<u>$1</u>",
  "<s>$1</s>",
  "<img src=\"$1\">",
  "<font color=\"$1\">$2</font>",
  "<font color=\"#000000\">$1</font>",
  "<font color=\"#FFFFFF\">$1</font>",
  "<font color=\"#FF0000\">$1</font>",
  "<font color=\"#00FF00\">$1</font>",
  "<font color=\"#0000FF\">$1</font>",
  "<font face=\"$1\">$2</font>",
  "<font size=$1>$2</font>",
  "<pre>$1</pre>",
  "<div align=\"left\">$1</div>",
  "<div align=\"right\">$1</div>",
  "<div align=\"center\">$1</div>",
	"<sub>$1</sub>",
	"<sup>$</sub>",
	"<table>$1</table>",
	"<tr>$1</tr>",
	"<td>$1</td>",
	"<a href=\"$1\" target=\"_blank\">$1</a>",
  "<a href=\"$1\" target=\"_blank\">$2</a>",
  "<a href=\"$1\" target=\"_blank\">$1</a>",
  "<hr>",
  "<ul>",
  "</ul>",
  "<li>$1",
  "<br><table width=\"100%\" border=0 cellspacing=0 cellpadding=5><tr><td><font face=\"Courier New\" size=2>$1</font></td></tr></table><br>"
);

$version = phpversion( );
if( version_compare( $version, '8.0.0', '<' ) )
{
  echo "You are running version $version of PHP. The Help Desk requires PHP 8.0.0 or newer.";
  exit;
}

$pre = $db_prefix;

foreach( $_POST as $key => $val )
  $_POST[$key] = is_array( $val ) ? $val : addslashes( $val );
foreach( $_GET as $key => $val )
  $_GET[$key] = is_array( $val ) ? $val : addslashes( $val );
foreach( $_COOKIE as $key => $val )
  $_COOKIE[$key] = is_array( $val ) ? $val : addslashes( $val );
// If trying to connect...
if( !mysql_connect( $db_host, $db_user, $db_password ) )
{
  // Work out the application's URL from the currently executing script so this
  // also works when LynxHD is installed in a subdirectory or an admin page fails.
  $application_root = dirname( __DIR__ );
  $executing_file = realpath( $_SERVER['SCRIPT_FILENAME'] ?? '' );
  $script_url = str_replace( '\\', '/', $_SERVER['SCRIPT_NAME'] ?? '' );
  $application_url = '';

  if( $executing_file && str_starts_with( $executing_file, $application_root . DIRECTORY_SEPARATOR ) )
  {
    $relative_file = str_replace( '\\', '/', substr( $executing_file, strlen( $application_root ) + 1 ) );
    if( str_ends_with( $script_url, $relative_file ) )
      $application_url = substr( $script_url, 0, -strlen( $relative_file ) );
  }

  header( 'Location: ' . $application_url . 'install/?step=1&error=db_connection', true, 302 );
  exit;
}

mysql_select_db( $db_name );

// If trying to install...
if( !mysql_query( "SELECT COUNT(*) FROM {$pre}user" ) )
{
  if( strtoupper( basename( $_SERVER['PHP_SELF'] ?? '' ) ) != strtoupper( $HD_URL_SETUP ) )
  {
    
	//header("Location: /helpdesk/setup.php");
	echo "Please use setup.php to install the help desk";
    exit;
  }

  $INSTALLED = 0;
}
else // Otherwise, setup sessions and help desk path
{
  $INSTALLED = 1;
  
  if( !headers_sent( ) )
    session_start( );

  $_SESSION['login_type'] = $_SESSION['login_type'] ?? $LOGIN_INVALID;
  $_SESSION['user'] = (isset($_SESSION['user']) && is_array($_SESSION['user'])) ? $_SESSION['user'] : array();
  $_SESSION['time'] = $_SESSION['time'] ?? 0;
 
  if( !isset( $_SESSION['user']['password'] ) && isset( $_COOKIE['iv_helpdesk_login'], $_COOKIE['iv_helpdesk_password'] ) )
  {
    $res = mysql_query( "SELECT * FROM {$pre}user WHERE ( email = '{$_COOKIE['iv_helpdesk_login']}' && password = '{$_COOKIE['iv_helpdesk_password']}' )" );
    $row = mysql_fetch_array( $res );
    if( $row && ($row['notify'] & $HD_NOTIFY_SAVELOGIN) )
    {
      $_SESSION['login'] = $row['email'];
      $_SESSION['password'] = $row['password'];
      $_SESSION['login_type'] = $LOGIN_USER;
      $_SESSION['user'] = $row;
      $_SESSION['time'] = time( );
    }
  }

  $session_user_id = $_SESSION['user']['id'] ?? '';
  $session_password = $_SESSION['user']['password'] ?? '';

  if( $session_user_id === '' || $session_password === '' || !get_row_count( "SELECT COUNT(*) FROM {$pre}user WHERE ( id = '$session_user_id' && password = '$session_password' )" ) )
    $_SESSION['login_type'] = $LOGIN_INVALID;
  else if( (time( ) - $_SESSION['time']) > 1800 )
    $_SESSION['login_type'] = $LOGIN_INVALID;
  else
    $_SESSION['time'] = time( );

  get_helpdesk_path( );
}

function get_helpdesk_path( )
{
  global $pre, $PATH_TO_HELPDESK; 

  $res = mysql_query( "SELECT text FROM {$pre}options WHERE ( name = 'helpdeskurl' )" );
  $row = mysql_fetch_array( $res );
  $helpdesk_url = isset( $row[0] ) ? $row[0] : "";

  if( trim( $helpdesk_url ) != "" )
  {
    $PATH_TO_HELPDESK = $helpdesk_url;
    if( $PATH_TO_HELPDESK[strlen( $PATH_TO_HELPDESK ) - 1] != "/" )
      $PATH_TO_HELPDESK .= "/";
  }
  else
    $PATH_TO_HELPDESK = "";
}

function field( $data )
{
  return htmlspecialchars( stripslashes( $data ?? "" ) );
}

function get_row_count( $query )
{
  $res = mysql_query( $query );
  $row = mysql_fetch_array( $res );
  return (is_array($row) && isset($row[0])) ? $row[0] : 0;
}

function get_options( $options )
{
  global $pre;

  $data = array( );
  for( $i = 0; $i < count( $options ); $i++ )
  {
    $res = mysql_query( "SELECT text FROM {$pre}options WHERE ( name = '{$options[$i]}' )" );
    $row = mysql_fetch_array( $res );

    // A missing option row is valid during partial setup or after an option
    // has been removed; expose it as an empty value instead of indexing null.
    $data[$options[$i]] = isset( $row[0] ) ? $row[0] : "";
  }
  return $data;
}

/**
 * Render legacy email placeholders without executing template text as PHP.
 * Supports the variable formats used by the bundled templates, including
 * $ticket, {$data[emailheader]}, and {$GLOBALS[PATH_TO_HELPDESK]}.
 */
function render_email_template( $template, $variables = array(), $options = array() )
{
  $replacements = array();

  foreach( $variables as $name => $value )
  {
    if( !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', (string)$name) )
      continue;
    $value = (string)$value;
    $replacements['{$' . $name . '}'] = $value;
    $replacements['$' . $name] = $value;
  }

  foreach( $options as $name => $value )
  {
    if( !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', (string)$name) )
      continue;
    $value = (string)$value;
    $replacements['{$data[' . $name . ']}'] = $value;
    $replacements['{$data[\'' . $name . '\']}'] = $value;
    $replacements['{$data["' . $name . '"]}'] = $value;
  }

  foreach( array('PATH_TO_HELPDESK', 'HD_URL_TICKET_VIEW', 'HD_URL_ADMINVIEW', 'HD_URL_TICKET_SURVEY') as $name )
  {
    $value = (string)($GLOBALS[$name] ?? '');
    $replacements['{$GLOBALS[' . $name . ']}'] = $value;
    $replacements['{$GLOBALS[\'' . $name . '\']}'] = $value;
    $replacements['{$GLOBALS["' . $name . '"]}'] = $value;
  }

  return strtr( (string)$template, $replacements );
}

function hd_smtp_read( $socket, $expected_codes )
{
  $response = "";
  while( ($line = fgets($socket, 515)) !== false )
  {
    $response .= $line;
    if( strlen($line) < 4 || $line[3] === ' ' )
      break;
  }

  $code = (int)substr($response, 0, 3);
  if( !in_array($code, (array)$expected_codes, true) )
    throw new Exception(trim($response) ?: 'SMTP server returned an empty response.');

  return $response;
}

function hd_smtp_command( $socket, $command, $expected_codes )
{
  if( fwrite($socket, $command . "\r\n") === false )
    throw new Exception('Could not write to the SMTP server.');
  return hd_smtp_read($socket, $expected_codes);
}

function hd_email_address( $value )
{
  if( preg_match('/<([^>]+)>/', (string)$value, $matches) )
    return trim($matches[1]);
  return trim((string)$value);
}

function hd_smtp_mail( $to, $subject, $message, $headers, $settings, &$error = null )
{
  $socket = null;
  try
  {
    $host = trim(stripslashes($settings['smtp_host'] ?? ''));
    $port = (int)($settings['smtp_port'] ?? 587);
    $encryption = strtolower(trim($settings['smtp_encryption'] ?? 'starttls'));
    $timeout = 15;
    if( $host === '' || $port < 1 || $port > 65535 )
      throw new Exception('SMTP host or port is invalid.');

    $remote = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $socket = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT);
    if( !$socket )
      throw new Exception("Connection failed: $errstr ($errno)");

    stream_set_timeout($socket, $timeout);
    hd_smtp_read($socket, array(220));
    $hostname = $_SERVER['SERVER_NAME'] ?? 'localhost';
    hd_smtp_command($socket, 'EHLO ' . $hostname, array(250));

    if( $encryption === 'starttls' )
    {
      hd_smtp_command($socket, 'STARTTLS', array(220));
      if( !stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) )
        throw new Exception('Could not establish the TLS connection.');
      hd_smtp_command($socket, 'EHLO ' . $hostname, array(250));
    }

    $username = stripslashes((string)($settings['smtp_username'] ?? ''));
    $password = stripslashes((string)($settings['smtp_password'] ?? ''));
    if( $username !== '' )
    {
      hd_smtp_command($socket, 'AUTH LOGIN', array(334));
      hd_smtp_command($socket, base64_encode($username), array(334));
      hd_smtp_command($socket, base64_encode($password), array(235));
    }

    $from_header = $settings['email'] ?? '';
    if( preg_match('/^From:\s*(.+)$/mi', (string)$headers, $matches) )
      $from_header = trim($matches[1]);
    $from = hd_email_address($from_header);
    $recipient = hd_email_address($to);
    if( !filter_var($from, FILTER_VALIDATE_EMAIL) || !filter_var($recipient, FILTER_VALIDATE_EMAIL) )
      throw new Exception('The sender or recipient email address is invalid.');

    hd_smtp_command($socket, 'MAIL FROM:<' . $from . '>', array(250));
    hd_smtp_command($socket, 'RCPT TO:<' . $recipient . '>', array(250, 251));
    hd_smtp_command($socket, 'DATA', array(354));

    $is_html = preg_match('/<[a-z][\s\S]*>/i', (string)$message) === 1;
    $header_lines = array(
      'Date: ' . date(DATE_RFC2822),
      'From: ' . $from_header,
      'To: ' . $to,
      'Subject: ' . $subject,
      'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $hostname . '>',
      'MIME-Version: 1.0',
      'Content-Type: ' . ($is_html ? 'text/html' : 'text/plain') . '; charset=UTF-8',
      'Content-Transfer-Encoding: 8bit'
    );
    foreach( preg_split('/\r\n|\r|\n/', (string)$headers) as $header )
      if( trim($header) !== '' && stripos($header, 'From:') !== 0 )
        $header_lines[] = trim($header);

    $body = preg_replace('/\r\n|\r|\n/', "\r\n", (string)$message);
    $body = preg_replace('/^\./m', '..', $body);
    fwrite($socket, implode("\r\n", $header_lines) . "\r\n\r\n" . $body . "\r\n.\r\n");
    hd_smtp_read($socket, array(250));
    hd_smtp_command($socket, 'QUIT', array(221));
    fclose($socket);
    return true;
  }
  catch( Throwable $exception )
  {
    if( is_resource($socket) )
      fclose($socket);
    $error = $exception->getMessage();
    return false;
  }
}

function hd_mail( $to, $subject, $message, $headers = '', &$error = null )
{
  $settings = get_options(array(
    'email', 'smtp_enabled', 'smtp_host', 'smtp_port', 'smtp_encryption',
    'smtp_username', 'smtp_password'
  ));
  if( !empty($settings['smtp_enabled']) )
    return hd_smtp_mail($to, $subject, $message, $headers, $settings, $error);

  if( preg_match('/<[a-z][\s\S]*>/i', (string)$message) === 1 )
  {
    $headers = rtrim((string)$headers);
    if( $headers !== '' )
      $headers .= "\r\n";
    $headers .= "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit";
  }
  return mail($to, $subject, $message, $headers);
}

function parse_tags( $text )
{
  global $CODEFROM, $CODETO;

  $text = htmlspecialchars( $text );

  $text = str_replace( "  ", "&nbsp;", $text );
  $text = str_replace( "\t", "&nbsp;&nbsp;&nbsp;", $text );
  $text = str_replace( "\r", "", $text );
  $text = str_replace( "\n", "<br>", $text );  

  $text = preg_replace( $CODEFROM, $CODETO, $text );

  return $text;
}

function parse_no_tags( $text )
{
  $text = htmlspecialchars( $text );

  $text = str_replace( "  ", "&nbsp;", $text );
  $text = str_replace( "\t", "&nbsp;&nbsp;&nbsp;", $text );
  $text = str_replace( "\r", "", $text );
  $text = str_replace( "\n", "<br>", $text );  
  
  return $text;
}

function render_editor_content( $text, $parse_legacy_tags = true )
{
  // TinyMCE stores HTML, while older knowledge-base entries use message tags.
  if( preg_match( '/<\/?(?:p|div|br|strong|b|em|i|u|s|ul|ol|li|blockquote|h[1-6]|pre|code)\b/i', $text ) )
  {
    $text = strip_tags( $text, '<p><div><br><strong><b><em><i><u><s><ul><ol><li><blockquote><h1><h2><h3><h4><h5><h6><pre><code>' );

    // Formatting does not require attributes; removing them also prevents
    // event handlers and unsafe URLs from reaching the public FAQ page.
    return preg_replace( '/<([a-z][a-z0-9]*)(?:\s[^>]*)?>/i', '<$1>', $text );
  }

  return $parse_legacy_tags ? parse_tags( $text ) : parse_no_tags( $text );
}

function send_survey( $id )
{
  global $pre;

  $res = mysql_query( "SELECT text FROM {$pre}options WHERE ( name = 'repeatsurvey' )" );
  $row = mysql_fetch_array( $res );
  $repeat = (is_array($row) && isset($row[0])) ? $row[0] : 0;

  $res = mysql_query( "SELECT * FROM {$pre}ticket WHERE ( id = '$id' )" );
  $row = mysql_fetch_array( $res );
  
  if( $row )
  {
    if( $repeat )     // Allow repeat surveys, so don't check for email, only same ticket
      $exists = get_row_count( "SELECT COUNT(*) FROM {$pre}survey WHERE ( ticket_id = '$id' )" );
    else
      $exists = get_row_count( "SELECT COUNT(*) FROM {$pre}survey WHERE ( email = '{$row['email']}' || ticket_id = '$id' )" );

    if( !$exists )
    {
      $options = array( "email", "url", "title", "emailheader", "emailfooter", "email_ticket_survey", "email_ticket_survey_subject" );
      $data = get_options( $options );

      $subject = $row['subject'];
      $name = $row['name'];
      $ticket = $row['ticket_id'];
      $email = $row['email'];

      eval( "\$sub = \"{$data['email_ticket_survey_subject']}\";" );
      eval( "\$mes = \"{$data['email_ticket_survey']}\";" );
      hd_mail( $row['email'], $sub, $mes, "From: {$data['email']}" );
    }
  }
}

function new_ticket_id( )
{
  global $pre;

  $ticket = strtoupper( base_convert( time( ), 10, 16 ) );
  if( get_row_count( "SELECT COUNT(*) FROM {$pre}ticket WHERE ( ticket_id = '$ticket' )" ) )
  {
    $res = mysql_query( "SELECT ticket_id FROM {$pre}ticket WHERE ( ticket_id NOT LIKE 'M%' ) ORDER BY ticket_id DESC LIMIT 1" );
    $row = mysql_fetch_array( $res );
    if( is_array($row) && isset($row[0]) )
      $ticket = strtoupper( base_convert( base_convert( $row[0], 16, 10 ) + 1, 10, 16 ) );
  }
 
  return $ticket;
}

/********************************************************** PHP */?>
