<?php

// Deterministic size variants: originals remain untouched. PHP GD is available locally.
foreach (glob(__DIR__.'/../public/assets/*') as $path) {
    if (! in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'webp', 'png'], true)) {
        continue;
    }
    if (preg_match('/-(320|640|960)\.webp$/', $path)) {
        continue;
    }
    $image = @imagecreatefromstring(file_get_contents($path));
    if (! $image) {
        continue;
    }
    $width = imagesx($image);
    $height = imagesy($image);
    if ($width < 320) {
        imagedestroy($image);

        continue;
    }
    foreach ([320, 640, 960] as $size) {
        if ($size > $width) {
            continue;
        }
        $target = imagescale($image, $size, (int) round($height * $size / $width));
        imagewebp($target, dirname($path).'/'.pathinfo($path, PATHINFO_FILENAME).'-'.$size.'.webp', 80);
        imagedestroy($target);
    }
    imagedestroy($image);
}

$logo = imagecreatefrompng(__DIR__.'/../public/assets/logo.png');
$small = imagescale($logo, 112);
imagewebp($small, __DIR__.'/../public/assets/logo-small.webp', 85);
imagedestroy($small);
imagedestroy($logo);
