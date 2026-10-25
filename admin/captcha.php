<?php
/**
 * Self-hosted image CAPTCHA for the admin login (GD + FreeType, no external service).
 * Stores the answer in the session; captcha_verify() in inc/auth.php consumes it.
 */
require __DIR__ . '/inc/auth.php';
admin_session_start();

// Unambiguous alphabet (no 0/O/1/I/L).
$alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
$len  = 5;
$code = '';
for ($i = 0; $i < $len; $i++) {
    $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
}
$_SESSION['captcha'] = $code;

$fonts = [
    '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
    '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
    '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
];
$font = '';
foreach ($fonts as $f) {
    if (is_file($f)) { $font = $f; break; }
}

$w = 180;
$h = 58;
$img = imagecreatetruecolor($w, $h);
imagefilledrectangle($img, 0, 0, $w, $h, imagecolorallocate($img, 245, 247, 251));

// Speckle + line noise to frustrate naive OCR.
for ($i = 0; $i < 700; $i++) {
    imagesetpixel($img, random_int(0, $w), random_int(0, $h),
        imagecolorallocate($img, random_int(160, 220), random_int(160, 220), random_int(160, 220)));
}
for ($i = 0; $i < 6; $i++) {
    imageline($img, random_int(0, $w), random_int(0, $h), random_int(0, $w), random_int(0, $h),
        imagecolorallocate($img, random_int(120, 200), random_int(120, 200), random_int(120, 200)));
}

if ($font !== '') {
    $x = 16;
    for ($i = 0; $i < $len; $i++) {
        $col   = imagecolorallocate($img, random_int(10, 70), random_int(20, 60), random_int(70, 130));
        $angle = random_int(-24, 24);
        $size  = random_int(21, 27);
        $y     = random_int(38, 46);
        imagettftext($img, $size, $angle, $x, $y, $col, $font, $code[$i]);
        $x += random_int(27, 33);
    }
} else {
    // Extremely unlikely fallback (no TTF): built-in bitmap font, still session-gated.
    $col = imagecolorallocate($img, 20, 40, 90);
    imagestring($img, 5, 30, 20, $code, $col);
}

header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
imagepng($img);
imagedestroy($img);
