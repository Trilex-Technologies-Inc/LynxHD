<?php
// Generate a crisp CAPTCHA without the legacy bitmap font, blur, or GD filters.

ini_set('display_errors', '0');
@session_start();

$session_name = 'vihash';
$secret_key = 'mySecRetkEy';
$digits = '';

for ($index = 0; $index < 5; $index++) {
    $digits .= (string) random_int(0, 9);
}

$_SESSION[$session_name] = md5($digits . $secret_key);

header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

$digit_elements = '';
for ($index = 0; $index < strlen($digits); $index++) {
    $x = 36 + ($index * 45);
    $y = 56 + random_int(-2, 2);
    $rotation = random_int(-3, 3);
    $digit_elements .= '<text x="' . $x . '" y="' . $y . '" transform="rotate(' . $rotation . ' ' . $x . ' ' . $y . ')">' . $digits[$index] . '</text>';
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<svg xmlns="http://www.w3.org/2000/svg" width="260" height="80" viewBox="0 0 260 80" role="img" aria-label="Five digit security code">
  <rect width="260" height="80" rx="10" fill="#f8fafc"/>
  <path d="M12 21 C65 4, 108 35, 160 18 S225 13, 249 26" fill="none" stroke="#cbd5e1" stroke-width="2"/>
  <path d="M10 65 C59 45, 115 75, 166 57 S222 52, 250 63" fill="none" stroke="#dbe4f0" stroke-width="2"/>
  <g fill="#0f2f5f" font-family="Arial, Helvetica, sans-serif" font-size="44" font-weight="700" letter-spacing="2">
    <?php echo $digit_elements ?>
  </g>
</svg>
