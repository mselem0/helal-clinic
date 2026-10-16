<?php

// Run once, only after approving the first publication. Existing files are backed up.
$base = $argv[1] ?? '';
$webroot = $argv[2] ?? '';
$release = $argv[3] ?? '';
if ($base !== dirname($webroot).'/apps/helal' || realpath($base) !== $base || ! is_dir($release.'/public') || realpath($webroot) !== $webroot || is_link($webroot)) {
    throw new RuntimeException('Invalid application or web-root path.');
}
if (! is_dir($base.'/shared/webroot-backups')) {
    mkdir($base.'/shared/webroot-backups', 0700, true);
}
$backup = $base.'/shared/webroot-backups/'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(3));
mkdir($backup, 0700);
$bridge = "<?php\n// Helal managed front controller. Application files remain outside public_html.\nrequire ".var_export($base.'/current/public/index.php', true).";\n";
$changes = [];
try {
    $assets = array_values(array_filter(scandir($release.'/public'), fn ($name) => ! in_array($name, ['.', '..', '.htaccess', 'index.php', 'storage'], true)));
    foreach (array_merge(['index.php', '.htaccess', 'storage'], $assets) as $name) {
        if ($name[0] === '.' && $name !== '.htaccess') {
            throw new RuntimeException('Unexpected hidden file in the public build.');
        }
        $target = $webroot.'/'.$name;
        if (file_exists($target) || is_link($target)) {
            if (! rename($target, $backup.'/'.$name)) {
                throw new RuntimeException('Unable to back up an existing public file.');
            }
        }
        $changes[] = $name;
        if ($name === 'index.php') {
            if (file_put_contents($target, $bridge, LOCK_EX) === false) {
                throw new RuntimeException('Unable to write front controller.');
            }
            chmod($target, 0644);
        } elseif ($name === '.htaccess') {
            $rules = "DirectoryIndex index.php\n".file_get_contents($release.'/public/.htaccess');
            // Keep cPanel's PHP handler/selector directives from the previous root file.
            if (is_file($backup.'/.htaccess')) {
                $previous = file_get_contents($backup.'/.htaccess');
                if (preg_match_all('/# (?:php|BEGIN cPanel)[^\n]*.*?# (?:php[^\n]*end|END cPanel)[^\n]*/si', $previous, $matches)) {
                    $rules .= "\n".implode("\n", $matches[0])."\n";
                }
            }
            if (file_put_contents($target, $rules, LOCK_EX) === false) {
                throw new RuntimeException('Unable to write rewrite rules.');
            }
            chmod($target, 0644);
        } else {
            $source = $name === 'storage' ? $base.'/shared/storage/app/public' : $base.'/current/public/'.$name;
            if (! symlink($source, $target)) {
                throw new RuntimeException('Unable to link public assets.');
            }
        }
    }
    $written = file_put_contents($base.'/shared/webroot.json', json_encode(['webroot' => $webroot, 'assets' => $assets, 'installed' => array_merge(['index.php', '.htaccess', 'storage'], $assets), 'backup' => $backup], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    if ($written === false) {
        throw new RuntimeException('Unable to save web-root recovery metadata.');
    }
    chmod($base.'/shared/webroot.json', 0600);
    echo "Web root prepared; original files retained in the private backup directory.\n";

} catch (Throwable $exception) {
    foreach (array_reverse($changes) as $name) {
        $target = $webroot.'/'.$name;
        if (is_link($target) || is_file($target)) {
            @unlink($target);
        }
        if (file_exists($backup.'/'.$name) || is_link($backup.'/'.$name)) {
            @rename($backup.'/'.$name, $target);
        }
    }
    throw $exception;
}
