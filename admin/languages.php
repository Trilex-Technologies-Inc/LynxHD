<?php
include '../include/settings.php';
include '../include/include.php';

$HD_CURPAGE = 'languages.php';
if( ($_SESSION['login_type'] ?? $LOGIN_INVALID) == $LOGIN_INVALID )
{
  header('Location: index.php?redirect=languages.php');
  exit;
}
$user_id = (int)($_SESSION['user']['id'] ?? 0);
$global_priv = get_row_count("SELECT COUNT(*) FROM {$pre}privilege WHERE user_id='$user_id' AND dept_id='0' AND admin='1'");
if( !$global_priv )
{
  header("Location: $HD_URL_BROWSE");
  exit;
}

if( empty($_SESSION['language_csrf']) ) $_SESSION['language_csrf'] = bin2hex(random_bytes(24));
$csrf = $_SESSION['language_csrf'];
$notice = '';

if( $_SERVER['REQUEST_METHOD'] === 'POST' )
{
  if( !hash_equals($csrf, (string)($_POST['csrf_token'] ?? '')) )
    $notice = '<div class="alert alert-danger">The security token expired. Reload the page and try again.</div>';
  else
  {
    $action = (string)($_POST['action'] ?? '');
    if( $action === 'add_language' )
    {
      $code = strtolower(trim((string)($_POST['code'] ?? '')));
      $name = trim((string)($_POST['name'] ?? ''));
      $direction = ($_POST['direction'] ?? '') === 'rtl' ? 'rtl' : 'ltr';
      if( !preg_match('/^[a-z]{2,3}(?:-[a-z]{2})?$/', $code) || $name === '' )
        $notice = '<div class="alert alert-danger">Enter a valid language name and code, such as fa or pt-br.</div>';
      else if( isset(hd_i18n_languages(false)[$code]) )
        $notice = '<div class="alert alert-danger">That language code already exists.</div>';
      else
      {
        mysql_query("INSERT INTO {$pre}language (code,name,direction,enabled,is_default) VALUES ('" . hd_i18n_escape($code) . "','" . hd_i18n_escape($name) . "','$direction',1,0)");
        $notice = '<div class="alert alert-success">Language added. You can now enter its translations.</div>';
      }
    }
    else if( $action === 'save_languages' )
    {
      $all = hd_i18n_languages(false);
      $default = (string)($_POST['default_language'] ?? 'en');
      if( !isset($all[$default]) ) $default = 'en';
      foreach( $all as $code => $language )
      {
        $enabled = ($code === 'en' || isset($_POST['enabled'][$code]) || $code === $default) ? 1 : 0;
        $is_default = $code === $default ? 1 : 0;
        $direction = (($_POST['direction'][$code] ?? 'ltr') === 'rtl') ? 'rtl' : 'ltr';
        $escaped = hd_i18n_escape($code);
        mysql_query("UPDATE {$pre}language SET enabled='$enabled',is_default='$is_default',direction='$direction' WHERE code='$escaped'");
      }
      $notice = '<div class="alert alert-success">Language settings saved.</div>';
    }
    else if( $action === 'delete_language' )
    {
      $code = strtolower((string)($_GET['delete_language'] ?? ''));
      $all = hd_i18n_languages(false);
      if( $code !== 'en' && isset($all[$code]) && empty($all[$code]['is_default']) )
      {
        $escaped = hd_i18n_escape($code);
        mysql_query("DELETE FROM {$pre}translation WHERE language_code='$escaped'");
        mysql_query("DELETE FROM {$pre}language WHERE code='$escaped'");
        if( ($_SESSION['hd_language'] ?? '') === $code ) $_SESSION['hd_language'] = 'en';
        $notice = '<div class="alert alert-success">Language and its translations were deleted.</div>';
      }
      else $notice = '<div class="alert alert-danger">English and the default language cannot be deleted.</div>';
    }
    else if( $action === 'save_translations' )
    {
      $code = strtolower((string)($_POST['language'] ?? ''));
      $all = hd_i18n_languages(false);
      if( $code !== 'en' && isset($all[$code]) )
      {
        foreach( (array)($_POST['translation'] ?? array()) as $encoded_source => $translated )
        {
          $source = base64_decode(strtr((string)$encoded_source, '-_', '+/'), true);
          if( $source === false || $source === '' ) continue;
          $hash = sha1($source);
          $source_sql = hd_i18n_escape($source);
          $translated_sql = hd_i18n_escape(trim((string)$translated));
          $code_sql = hd_i18n_escape($code);
          if( get_row_count("SELECT COUNT(*) FROM {$pre}translation WHERE language_code='$code_sql' AND source_hash='$hash'") )
            mysql_query("UPDATE {$pre}translation SET source_text='$source_sql',translated_text='$translated_sql' WHERE language_code='$code_sql' AND source_hash='$hash'");
          else
            mysql_query("INSERT INTO {$pre}translation (language_code,source_hash,source_text,translated_text) VALUES ('$code_sql','$hash','$source_sql','$translated_sql')");
        }
        $notice = '<div class="alert alert-success">Translations saved.</div>';
      }
    }
  }
}

$languages = hd_i18n_languages(false);
$edit_language = strtolower((string)($_GET['language'] ?? ''));
if( $edit_language === 'en' || !isset($languages[$edit_language]) ) $edit_language = '';
$search = trim((string)($_GET['q'] ?? ''));
$catalog = hd_i18n_scan_catalog();
if( $search !== '' ) $catalog = array_values(array_filter($catalog, function($text) use ($search) { return stripos($text, $search) !== false; }));
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 40;
$total = count($catalog);
$page_count = max(1, (int)ceil($total / $per_page));
if( $page > $page_count ) $page = $page_count;
$page_catalog = array_slice($catalog, ($page - 1) * $per_page, $per_page);
$saved_translations = array();
if( $edit_language !== '' )
{
  $result = mysql_query("SELECT source_text,translated_text FROM {$pre}translation WHERE language_code='" . hd_i18n_escape($edit_language) . "'");
  while( $result && ($row = mysql_fetch_array($result, MYSQLI_ASSOC)) ) $saved_translations[$row['source_text']] = $row['translated_text'];
}
function hd_language_source_token($source) { return rtrim(strtr(base64_encode($source), '+/', '-_'), '='); }
function hd_language_page_url($number, $language, $search) { return 'languages.php?' . http_build_query(array('language' => $language, 'q' => $search, 'page' => $number)); }

$script_name = 'Languages';
include './include/header.php';
?>
<div class="d-sm-flex align-items-center justify-content-between mb-4"><div><h1 class="h3 mb-1 text-gray-800"><i class="fas fa-language text-primary mr-2"></i>Languages</h1><p class="mb-0 text-muted">Add interface languages, choose the default, and translate labels.</p></div></div>
<?php echo $notice ?>

<div class="row">
  <div class="col-xl-5 mb-4">
    <section class="card shadow-sm mb-4"><div class="card-header bg-white py-3"><h2 class="h6 font-weight-bold text-primary mb-0">Installed languages</h2></div><div class="card-body">
      <form method="post"><input type="hidden" name="csrf_token" value="<?php echo field($csrf) ?>"><input type="hidden" name="action" value="save_languages">
        <?php foreach( $languages as $code => $language ): ?><div class="border rounded p-3 mb-3"><div class="d-flex justify-content-between align-items-center"><div><strong><?php echo field($language['name']) ?></strong> <span class="badge badge-light"><?php echo field($code) ?></span></div><div class="custom-control custom-switch"><input class="custom-control-input" type="checkbox" id="enabled-<?php echo field($code) ?>" name="enabled[<?php echo field($code) ?>]" <?php echo !empty($language['enabled']) ? 'checked' : '' ?> <?php echo $code === 'en' ? 'disabled' : '' ?>><label class="custom-control-label" for="enabled-<?php echo field($code) ?>">Enabled</label></div></div><div class="form-row mt-3"><div class="col"><div class="custom-control custom-radio"><input class="custom-control-input" type="radio" id="default-<?php echo field($code) ?>" name="default_language" value="<?php echo field($code) ?>" <?php echo !empty($language['is_default']) ? 'checked' : '' ?>><label class="custom-control-label" for="default-<?php echo field($code) ?>">Default</label></div></div><div class="col"><select class="form-control form-control-sm" name="direction[<?php echo field($code) ?>]"><option value="ltr" <?php echo $language['direction'] === 'ltr' ? 'selected' : '' ?>>Left to right</option><option value="rtl" <?php echo $language['direction'] === 'rtl' ? 'selected' : '' ?>>Right to left</option></select></div></div>
          <?php if( $code !== 'en' ): ?><div class="mt-3 d-flex justify-content-between"><a class="btn btn-sm btn-outline-primary" href="languages.php?language=<?php echo urlencode($code) ?>">Translate labels</a><?php if( empty($language['is_default']) ): ?><button class="btn btn-sm btn-outline-danger" type="submit" name="action" value="delete_language" formaction="languages.php?delete_language=<?php echo urlencode($code) ?>" onclick="return confirm('Delete this language and all its translations?')">Delete</button><?php endif; ?></div><?php endif; ?>
        </div><?php endforeach; ?>
        <button class="btn btn-primary" type="submit"><i class="fas fa-save mr-1"></i>Save language settings</button>
      </form>
    </div></section>

    <section class="card shadow-sm"><div class="card-header bg-white py-3"><h2 class="h6 font-weight-bold text-primary mb-0">Add a language</h2></div><div class="card-body"><form method="post"><input type="hidden" name="csrf_token" value="<?php echo field($csrf) ?>"><input type="hidden" name="action" value="add_language"><div class="form-group"><label for="language-name">Language name</label><input class="form-control" id="language-name" name="name" required placeholder="For example, French"></div><div class="form-row"><div class="form-group col-6"><label for="language-code">Code</label><input class="form-control" id="language-code" name="code" required maxlength="6" pattern="[A-Za-z]{2,3}(-[A-Za-z]{2})?" placeholder="For example, fr"></div><div class="form-group col-6"><label for="language-direction">Direction</label><select class="form-control" id="language-direction" name="direction"><option value="ltr">Left to right</option><option value="rtl">Right to left</option></select></div></div><button class="btn btn-primary" type="submit"><i class="fas fa-plus mr-1"></i>Add language</button></form></div></section>
  </div>

  <div class="col-xl-7 mb-4">
    <section class="card shadow-sm"><div class="card-header bg-white py-3"><h2 class="h6 font-weight-bold text-primary mb-0">Label translations<?php echo $edit_language ? ' — ' . field($languages[$edit_language]['name']) : '' ?></h2></div>
      <?php if( !$edit_language ): ?><div class="card-body text-center text-muted py-5"><i class="fas fa-language fa-3x mb-3 text-gray-300"></i><p class="mb-0">Add or select a language to translate interface labels.</p></div>
      <?php else: ?><div class="card-body border-bottom"><form method="get" class="form-inline"><input type="hidden" name="language" value="<?php echo field($edit_language) ?>"><label class="sr-only" for="label-search">Search labels</label><input class="form-control mr-2 flex-grow-1" id="label-search" name="q" value="<?php echo field($search) ?>" placeholder="Search English labels"><button class="btn btn-outline-primary">Search</button></form><p class="small text-muted mb-0 mt-2"><?php echo (int)$total ?> labels found. Blank translations fall back to English.</p></div><form method="post"><input type="hidden" name="csrf_token" value="<?php echo field($csrf) ?>"><input type="hidden" name="action" value="save_translations"><input type="hidden" name="language" value="<?php echo field($edit_language) ?>"><div class="table-responsive"><table class="table mb-0"><thead><tr><th style="width:45%">English label</th><th>Translation</th></tr></thead><tbody><?php foreach( $page_catalog as $source ): $token = hd_language_source_token($source); ?><tr><td class="small"><?php echo field($source) ?></td><td><textarea class="form-control form-control-sm no-tinymce" rows="2" name="translation[<?php echo field($token) ?>]" dir="<?php echo field($languages[$edit_language]['direction']) ?>"><?php echo field($saved_translations[$source] ?? '') ?></textarea></td></tr><?php endforeach; ?></tbody></table></div><div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center"><button class="btn btn-primary" type="submit"><i class="fas fa-save mr-1"></i>Save translations</button><nav aria-label="Translation pages"><ul class="pagination pagination-sm mb-0"><?php if( $page > 1 ): ?><li class="page-item"><a class="page-link" href="<?php echo field(hd_language_page_url($page - 1, $edit_language, $search)) ?>">Previous</a></li><?php endif; ?><li class="page-item disabled"><span class="page-link"><?php echo $page ?> / <?php echo $page_count ?></span></li><?php if( $page < $page_count ): ?><li class="page-item"><a class="page-link" href="<?php echo field(hd_language_page_url($page + 1, $edit_language, $search)) ?>">Next</a></li><?php endif; ?></ul></nav></div></form><?php endif; ?>
    </section>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var codeInput = document.getElementById('language-code');
  var directionInput = document.getElementById('language-direction');
  if (!codeInput || !directionInput) return;
  codeInput.addEventListener('input', function () {
    var code = this.value.toLowerCase().split('-')[0];
    directionInput.value = ['ar', 'fa', 'he', 'ur', 'ps', 'sd', 'dv', 'ku', 'yi'].indexOf(code) !== -1 ? 'rtl' : 'ltr';
  });
});
</script>
<?php include './include/footer.php'; ?>
