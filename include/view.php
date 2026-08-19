<?php
include 'Captcha.php';
$options['sessionName'] = 'vihash';
$options['fontPath'] = '.';
$options['fontFile'] = 'anonymous.gdf';
$options['imageWidth'] = 240;
$options['imageHeight'] = 70;
$options['allowedChars'] = '1234567890';
$options['stringLength'] = 5;
$options['charWidth'] = 42;
$options['blurRadius'] = 0;
$options['secretKey'] = 'mySecRetkEy';

$captcha = new Captcha($options);
$captcha->getCaptcha();
