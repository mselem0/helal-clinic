<?php

$base = $argv[1] ?? '';
$metadata = $base.'/shared/webroot.json';
if (! is_file($metadata)) {
    exit(0);
}
$state = json_decode(file_get_contents($metadata), true, flags: JSON_THROW_ON_ERROR);
$webroot = $state['webroot'];
$backup = $state['backup'];
if ($base !== dirname($webroot).'/apps/helal' || ! str_starts_with($backup, $base.'/shared/webroot-backups/')) {
    throw new RuntimeException('Invalid rollback paths.');
}
foreach ($state['installed'] as $name) {
    if (basename($name) !== $name || in_array($name, ['.', '..'], true)) {
        throw new RuntimeException('Invalid rollback file.');
    }
    $target = $webroot.'/'.$name;
    if (is_link($target) || is_file($target)) {
        unlink($target);
    }
    if (file_exists($backup.'/'.$name) || is_link($backup.'/'.$name)) {
        rename($backup.'/'.$name, $target);
    }
}
unlink($metadata);
echo "Original public root restored from the private backup.\n";
