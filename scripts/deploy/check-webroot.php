<?php

$base = $argv[1] ?? '';
$release = $argv[2] ?? '';
$metadata = $base.'/shared/webroot.json';
if (! is_file($metadata)) {
    exit(0);
}
$state = json_decode(file_get_contents($metadata), true, flags: JSON_THROW_ON_ERROR);
$webroot = $state['webroot'];
if ($base !== dirname($webroot).'/apps/helal') {
    throw new RuntimeException('Invalid web root.');
}
$assets = array_values(array_filter(scandir($release.'/public'), fn ($name) => ! in_array($name, ['.', '..', '.htaccess', 'index.php', 'storage'], true)));
foreach ($assets as $name) {
    if (str_starts_with($name, '.')) {
        throw new RuntimeException('Unexpected hidden public asset.');
    }
    $path = $webroot.'/'.$name;
    $expected = $base.'/current/public/'.$name;
    if (is_link($path)) {
        if (readlink($path) !== $expected) {
            throw new RuntimeException('Public asset link was modified; review before deploying.');
        }
    } elseif (file_exists($path)) {
        throw new RuntimeException('An unmanaged public asset would be overwritten; review before deploying.');
    } else {
        if (! symlink($expected, $path)) {
            throw new RuntimeException('Unable to add a public asset link.');
        }
    }
}
$state['assets'] = array_values(array_unique(array_merge($state['assets'], $assets)));
$state['installed'] = array_values(array_unique(array_merge($state['installed'], $assets)));
file_put_contents($metadata, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
