<?php
// Download manager: publishes files to the public /downloads library.
include dirname(__DIR__, 2) . "/include/settings.php";
include dirname(__DIR__, 2) . "/include/include.php";

$HD_CURPAGE = "upload/index.php";

if( $_SESSION['login_type'] == $LOGIN_INVALID )
  Header( "Location: ../{$HD_URL_LOGIN}?redirect=" . urlencode( $HD_CURPAGE ) );

$global_priv = get_row_count( "SELECT COUNT(*) FROM {$pre}privilege WHERE ( user_id = '{$_SESSION['user']['id']}' && dept_id = '0' && admin = '1' )" );
if( !$global_priv )
{
  Header( "Location: ../{$HD_URL_BROWSE}" );
  exit;
}

// The base element makes the standard admin header's relative links work here.
$EXTRA_HEADER = '<base href="../">';
$download_directory = dirname(__DIR__, 2) . "/downloads";
$hidden_files = array("index.php", ".htaccess", ".htpasswd", ".DS_Store");
$max_upload_size = 25 * 1024 * 1024;

function admin_download_size( $bytes )
{
  $units = array( "B", "KB", "MB", "GB" );
  $size = max( 0, (float)$bytes );
  $unit = 0;
  while( $size >= 1024 && $unit < count($units) - 1 )
  {
    $size /= 1024;
    $unit++;
  }
  return ($unit ? number_format($size, 1) : (int)$size) . " " . $units[$unit];
}

if( !is_dir($download_directory) )
  @mkdir( $download_directory, 0775, true );

if( ($_POST['cmd'] ?? '') == "upload" )
{
  if( !isset($_FILES['download_file']) || $_FILES['download_file']['error'] != UPLOAD_ERR_OK )
    $msg = '<div class="errorbox">Choose a file to upload.</div>';
  else if( $_FILES['download_file']['size'] > $max_upload_size )
    $msg = '<div class="errorbox">Files must be 25 MB or smaller.</div>';
  else
  {
    $file_name = basename( $_FILES['download_file']['name'] );
    $file_name = preg_replace( '/[^A-Za-z0-9._ -]/', '_', $file_name );
    $destination = $download_directory . DIRECTORY_SEPARATOR . $file_name;

    if( $file_name == '' || $file_name == '.' || $file_name == '..' || in_array($file_name, $hidden_files, true) )
      $msg = '<div class="errorbox">That filename is not allowed.</div>';
    else if( file_exists($destination) )
      $msg = '<div class="errorbox">A download with that filename already exists. Rename the file and try again.</div>';
    else if( !move_uploaded_file($_FILES['download_file']['tmp_name'], $destination) )
      $msg = '<div class="errorbox">The file could not be saved. Check that the downloads folder is writable.</div>';
    else
      $msg = '<div class="successbox">' . field($file_name) . ' is now available in the public download library.</div>';
  }
}
else if( ($_POST['cmd'] ?? '') == "delete" )
{
  $file_name = basename( (string)$_POST['file'] );
  $file_path = $download_directory . DIRECTORY_SEPARATOR . $file_name;
  if( $file_name == '' || in_array($file_name, $hidden_files, true) || !is_file($file_path) )
    $msg = '<div class="errorbox">The selected download could not be found.</div>';
  else if( !unlink($file_path) )
    $msg = '<div class="errorbox">The file could not be deleted. Check folder permissions.</div>';
  else
    $msg = '<div class="successbox">' . field($file_name) . ' has been removed from the public library.</div>';
}

$files = array();
if( is_dir($download_directory) && ($directory = opendir($download_directory)) )
{
  while( ($name = readdir($directory)) !== false )
  {
    $path = $download_directory . DIRECTORY_SEPARATOR . $name;
    if( $name[0] === '.' || in_array($name, $hidden_files, true) || !is_file($path) )
      continue;
    $files[] = array( "name" => $name, "size" => filesize($path), "modified" => filemtime($path), "extension" => strtolower(pathinfo($name, PATHINFO_EXTENSION)) );
  }
  closedir($directory);
}
usort( $files, function($left, $right) { return $right['modified'] <=> $left['modified']; } );

include dirname(__DIR__) . "/include/header.php";
?>
<div class="d-sm-flex align-items-center justify-content-between mb-4">
  <div><h1 class="h3 mb-1 text-gray-800">Download manager</h1><p class="mb-0 text-gray-600">Publish files for customers and staff to download.</p></div>
  <a class="btn btn-light btn-sm shadow-sm mt-3 mt-sm-0" href="../downloads/index.php" target="_blank" rel="noopener"><i class="fas fa-external-link-alt fa-sm mr-1"></i> View public library</a>
</div>
<?php echo $msg ?? '' ?>

<div class="row">
  <div class="col-lg-4 mb-4"><div class="card shadow-sm h-100"><div class="card-header py-3"><h2 class="h6 m-0 font-weight-bold text-primary"><i class="fas fa-cloud-upload-alt mr-2"></i>Upload a file</h2></div><div class="card-body"><form action="upload/index.php" method="post" enctype="multipart/form-data" class="download-upload-form"><input type="hidden" name="cmd" value="upload"><div class="form-group"><label for="download-file">File</label><div class="custom-file"><input class="custom-file-input" id="download-file" type="file" name="download_file" required><label class="custom-file-label" for="download-file">Choose a file</label></div><small class="form-text text-muted">Maximum file size: 25 MB.</small></div><button class="btn btn-primary" type="submit"><i class="fas fa-upload mr-1"></i> Upload file</button></form></div></div></div>
  <div class="col-lg-8 mb-4"><div class="card shadow-sm h-100"><div class="card-header py-3"><h2 class="h6 m-0 font-weight-bold text-primary">Publishing notes</h2></div><div class="card-body"><div class="download-note"><span class="download-note-icon bg-primary text-white"><i class="fas fa-globe"></i></span><div><strong>Public immediately</strong><p>Uploaded files appear in the public download library as soon as the upload completes.</p></div></div><div class="download-note"><span class="download-note-icon bg-warning text-white"><i class="fas fa-file-alt"></i></span><div><strong>Use descriptive filenames</strong><p>Customers see the filename, size, and last updated date. Upload a renamed version rather than overwriting a file.</p></div></div></div></div></div>
</div>

<div class="card shadow-sm mb-4 download-list-admin"><div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between"><div><h2 class="h6 m-0 font-weight-bold text-primary">Published files</h2><small class="text-muted"><?php echo number_format(count($files)) ?> file<?php echo count($files) == 1 ? '' : 's' ?> in the library</small></div></div><div class="table-responsive"><table class="table table-hover mb-0 download-table"><thead><tr><th>File</th><th>Type</th><th>Size</th><th>Updated</th><th class="text-right">Actions</th></tr></thead><tbody>
<?php if( $files ): foreach( $files as $file ): $public_url = '../downloads/index.php?download=' . rawurlencode($file['name']); ?>
  <tr><td><div class="download-file-name"><span class="download-file-icon"><i class="fas fa-file"></i></span><a href="<?php echo field($public_url) ?>" target="_blank" rel="noopener"><?php echo field($file['name']) ?></a></div></td><td><?php if($file['extension']): ?><span class="badge badge-light border text-uppercase"><?php echo field($file['extension']) ?></span><?php else: ?><span class="text-muted">—</span><?php endif; ?></td><td class="text-nowrap"><?php echo admin_download_size($file['size']) ?></td><td class="text-nowrap"><time datetime="<?php echo date('c', $file['modified']) ?>"><?php echo date('M j, Y g:i a', $file['modified']) ?></time></td><td class="text-right text-nowrap"><a class="btn btn-sm btn-light" href="<?php echo field($public_url) ?>" target="_blank" rel="noopener"><i class="fas fa-download"></i><span class="sr-only"> Download <?php echo field($file['name']) ?></span></a> <form action="upload/index.php" method="post" class="d-inline"><input type="hidden" name="cmd" value="delete"><input type="hidden" name="file" value="<?php echo field($file['name']) ?>"><button class="btn btn-sm btn-outline-danger" type="submit" onclick="return window.confirm('Remove this file from the public library?');"><i class="fas fa-trash"></i><span class="sr-only"> Delete <?php echo field($file['name']) ?></span></button></form></td></tr>
<?php endforeach; else: ?>
  <tr><td colspan="5"><div class="download-empty"><i class="fas fa-cloud-upload-alt"></i><h3>No files published yet</h3><p>Upload a file to make it available in the public library.</p></div></td></tr>
<?php endif; ?>
</tbody></table></div></div>
<script>
(function () {
  var input = document.getElementById('download-file');
  if (!input) return;
  input.addEventListener('change', function () { var label = input.nextElementSibling; if (label && input.files.length) label.textContent = input.files[0].name; });
}());
</script>
<?php include dirname(__DIR__) . "/include/footer.php"; ?>
