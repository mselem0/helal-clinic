<?php

// First installation only. This installer can never target the main public_html.
$base = realpath($argv[1] ?? '');
$webroot = realpath($argv[2] ?? '');
if (! $base || ! $webroot || basename($base) !== 'helal-ads' || basename(dirname($base)) !== 'apps') {
    throw new RuntimeException('Expected a private apps/helal-ads application directory.');
}
$home = dirname($base, 2);
if ($webroot !== $home.'/public_html/ads' || is_link($argv[2]) || ! is_file($base.'/public/index.php') || ! is_file($base.'/.env')) {
    throw new RuntimeException('Only the separate public_html/ads webroot is permitted.');
}
$names = array_values(array_diff(scandir($base.'/public'), ['.', '..', '.htaccess', 'index.php', 'storage', 'hot']));
foreach (array_merge(['index.php', 'storage'], $names) as $name) {
    if (file_exists($webroot.'/'.$name) || is_link($webroot.'/'.$name)) {
        throw new RuntimeException('Webroot already contains managed files; no files were changed.');
    }
}
$originalRules = is_file($webroot.'/.htaccess') ? file_get_contents($webroot.'/.htaccess') : '';
if (is_link($webroot.'/.htaccess') || str_contains($originalRules, 'RewriteRule') || str_contains($originalRules, 'DirectoryIndex')) {
    throw new RuntimeException('Existing routing rules must be reviewed before installing ads.');
}
file_put_contents($webroot.'/index.php', "<?php\nrequire ".var_export($base.'/public/index.php', true).";\n");
file_put_contents($webroot.'/.htaccess', "DirectoryIndex index.php\n".file_get_contents($base.'/public/.htaccess')."\n".$originalRules);
foreach ($names as $name) {
    if (! symlink($base.'/public/'.$name, $webroot.'/'.$name)) {
        throw new RuntimeException('Unable to link an ads public asset.');
    }
}
if (! symlink($base.'/storage/app/public', $webroot.'/storage')) {
    throw new RuntimeException('Unable to link ads uploads.');
}
echo "Ads-only webroot installed. Main site untouched.\n";
