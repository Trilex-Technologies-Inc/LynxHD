<?php

/** LynxHD multilingual support. */

function hd_i18n_escape($value)
{
  $connection = $GLOBALS['_lynxhd_mysql_connection'] ?? null;
  return $connection ? mysqli_real_escape_string($connection, (string)$value) : addslashes((string)$value);
}

function hd_i18n_install()
{
  global $pre;

  $connection = $GLOBALS['_lynxhd_mysql_connection'] ?? null;
  if( $connection ) mysqli_set_charset($connection, 'utf8mb4');

  mysql_query("CREATE TABLE IF NOT EXISTS {$pre}language (
    id int(11) NOT NULL auto_increment,
    code varchar(12) NOT NULL,
    name varchar(80) NOT NULL,
    direction varchar(3) NOT NULL default 'ltr',
    enabled tinyint(1) NOT NULL default '1',
    is_default tinyint(1) NOT NULL default '0',
    PRIMARY KEY (id), UNIQUE KEY code (code)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4");

  mysql_query("CREATE TABLE IF NOT EXISTS {$pre}translation (
    id int(11) NOT NULL auto_increment,
    language_code varchar(12) NOT NULL,
    source_hash char(40) NOT NULL,
    source_text text NOT NULL,
    translated_text text NOT NULL,
    PRIMARY KEY (id), UNIQUE KEY language_phrase (language_code, source_hash), KEY language_code (language_code)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4");

  if( !get_row_count("SELECT COUNT(*) FROM {$pre}language") )
    mysql_query("INSERT INTO {$pre}language (code,name,direction,enabled,is_default) VALUES ('en','English','ltr',1,1)");
}

function hd_i18n_languages($enabled_only = true)
{
  global $pre;
  $items = array();
  $where = $enabled_only ? " WHERE enabled='1'" : '';
  $result = mysql_query("SELECT * FROM {$pre}language{$where} ORDER BY is_default DESC, name");
  while( $result && ($row = mysql_fetch_array($result, MYSQLI_ASSOC)) )
    $items[$row['code']] = $row;
  return $items;
}

function hd_i18n_boot()
{
  global $pre, $LANG;
  hd_i18n_install();
  $GLOBALS['HD_SOURCE_LANGUAGE'] = $LANG;
  $languages = hd_i18n_languages(true);
  $default = 'en';
  foreach( $languages as $code => $language )
    if( !empty($language['is_default']) ) $default = $code;

  $requested = isset($_GET['lang']) ? strtolower(trim((string)$_GET['lang'])) : '';
  if( $requested !== '' && isset($languages[$requested]) )
    $_SESSION['hd_language'] = $requested;
  $locale = $_SESSION['hd_language'] ?? $default;
  if( !isset($languages[$locale]) ) $locale = $default;

  $GLOBALS['HD_LANGUAGES'] = $languages;
  $GLOBALS['HD_LOCALE'] = $locale;
  $GLOBALS['HD_DIRECTION'] = $languages[$locale]['direction'] ?? 'ltr';
  $GLOBALS['HD_TRANSLATIONS'] = array();

  if( $locale !== 'en' )
  {
    $escaped = hd_i18n_escape($locale);
    $result = mysql_query("SELECT source_text,translated_text FROM {$pre}translation WHERE language_code='$escaped' AND translated_text<>''");
    while( $result && ($row = mysql_fetch_array($result, MYSQLI_ASSOC)) )
      $GLOBALS['HD_TRANSLATIONS'][$row['source_text']] = $row['translated_text'];

    foreach( $LANG as $key => $text )
      if( isset($GLOBALS['HD_TRANSLATIONS'][$text]) )
        $LANG[$key] = $GLOBALS['HD_TRANSLATIONS'][$text];

    ob_start('hd_i18n_translate_html');
  }
}

function hd_t($source, $replacements = array())
{
  $text = $GLOBALS['HD_TRANSLATIONS'][(string)$source] ?? (string)$source;
  foreach( $replacements as $name => $value )
    $text = str_replace('{' . $name . '}', (string)$value, $text);
  return $text;
}

function hd_i18n_translate_html($html)
{
  $translations = $GLOBALS['HD_TRANSLATIONS'] ?? array();
  if( !$translations || stripos($html, '<html') === false ) return $html;

  // Translate complete visible text nodes without touching markup or user data.
  $html = preg_replace_callback('/>([^<>]+)</u', function($match) use ($translations) {
    $value = $match[1];
    $trimmed = trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if( $trimmed === '' || !isset($translations[$trimmed]) ) return $match[0];
    $leading = substr($value, 0, strlen($value) - strlen(ltrim($value)));
    $trailing = substr($value, strlen(rtrim($value)));
    return '>' . $leading . htmlspecialchars($translations[$trimmed], ENT_QUOTES, 'UTF-8') . $trailing . '<';
  }, $html);

  $html = preg_replace_callback('/\b(placeholder|title|aria-label)=("|\')(.*?)\2/iu', function($match) use ($translations) {
    $source = html_entity_decode($match[3], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if( !isset($translations[$source]) ) return $match[0];
    return $match[1] . '=' . $match[2] . htmlspecialchars($translations[$source], ENT_QUOTES, 'UTF-8') . $match[2];
  }, $html);
  return $html;
}

function hd_i18n_url($code)
{
  $query = $_GET;
  $query['lang'] = $code;
  return ($_SERVER['PHP_SELF'] ?? '') . '?' . http_build_query($query);
}

function hd_i18n_scan_catalog()
{
  $base_catalog = array_values($GLOBALS['HD_SOURCE_LANGUAGE'] ?? array());
  $catalog = $base_catalog;
  $root = dirname(__DIR__);
  $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
  foreach( $iterator as $file )
  {
    $path = $file->getPathname();
    if( $file->getExtension() !== 'php' || strpos($path, DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'tinymce') !== false || strpos($path, DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR) !== false ) continue;
    $source = @file_get_contents($path);
    if( $source === false ) continue;

    if( preg_match_all('/>([^<>\r\n]*[A-Za-z][^<>\r\n]*)</u', $source, $matches) )
      foreach( $matches[1] as $text )
      {
        $text = trim(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if( $text !== '' && strpos($text, '<?') === false && strpos($text, '?>') === false && strpos($text, ' . ') === false && strpos($text, '$') === false ) $catalog[] = $text;
      }
    if( preg_match_all('/\b(?:placeholder|title|aria-label)=(?:"([^"<]+)"|\'([^\'<]+)\')/iu', $source, $matches, PREG_SET_ORDER) )
      foreach( $matches as $match ) $catalog[] = html_entity_decode($match[1] !== '' ? $match[1] : $match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Pick up labels held in PHP arrays (dashboard cards, status messages,
    // button captions) while excluding paths, SQL, markup, and identifiers.
    foreach( token_get_all($source) as $token )
    {
      if( !is_array($token) || $token[0] !== T_CONSTANT_ENCAPSED_STRING ) continue;
      $raw = $token[1];
      $quote = $raw[0] ?? '';
      $text = substr($raw, 1, -1);
      if( $quote === '"' ) $text = stripcslashes($text); else $text = str_replace(array("\\'", "\\\\"), array("'", "\\"), $text);
      $text = trim(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
      if( strlen($text) < 2 || strlen($text) > 240 || !preg_match('/[A-Za-z]/', $text) ) continue;
      if( preg_match('/[<>{}$]|\.php\b|^[a-z0-9_.-]+$/i', $text) ) continue;
      if( preg_match('/\b(SELECT|INSERT|UPDATE|DELETE|CREATE|ALTER|DROP|FROM|WHERE|JOIN|VALUES|TABLE|COUNT)\b/i', $text) ) continue;
      if( strpos($text, '/') !== false || strpos($text, '=') !== false ) continue;
      $catalog[] = $text;
    }
  }
  $base_lookup = array_fill_keys($base_catalog, true);
  $catalog = array_filter(array_map('trim', $catalog), function($text) use ($base_lookup) {
    if( isset($base_lookup[$text]) ) return true;
    return $text !== '' && strlen($text) <= 500 && strpos($text, ' . ') === false && strpos($text, '<?') === false && strpos($text, '?>') === false
      && preg_match('/^[\p{L}\p{N}(*]/u', $text)
      && !preg_match('~["\[\]{}$]|^#[0-9a-f]{3,8}$|[\\/]|\b(?:SELECT|INSERT|UPDATE|DELETE|CREATE|ALTER|DROP|FROM|WHERE|JOIN|VALUES|TABLE|ORDER BY|class|href|name|value|onclick|style|data-[a-z-]+)=?~i', $text);
  });
  $catalog = array_values(array_unique($catalog));
  natcasesort($catalog);
  return array_values($catalog);
}
