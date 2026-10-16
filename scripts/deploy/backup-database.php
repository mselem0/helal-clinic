<?php

// Called only by an explicitly enabled production deployment. Never prints credentials.
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Process\Process;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$directory = $argv[1] ?? '';
if (! is_dir($directory) || ! is_writable($directory)) {
    throw new RuntimeException('Backup directory is unavailable.');
}
$connection = config('database.connections.'.config('database.default'));
if (($connection['driver'] ?? '') !== 'mysql') {
    throw new RuntimeException('Production deployment requires a MySQL backup configuration.');
}
$binary = is_executable('/usr/bin/mariadb-dump') ? '/usr/bin/mariadb-dump' : '/usr/bin/mysqldump';
$option = static fn ($value) => '"'.str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '\\n', '\\r'], (string) $value).'"';
$credentials = tempnam($directory, '.mysql-');
chmod($credentials, 0600);
$destination = $directory.'/database-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(3)).'.sql';
try {
    $settings = "[client]\n";
    foreach (['host' => 'host', 'port' => 'port', 'user' => 'username', 'password' => 'password', 'socket' => 'unix_socket'] as $optionName => $key) {
        if (isset($connection[$key]) && $connection[$key] !== '') {
            $settings .= $optionName.'='.$option($connection[$key])."\n";
        }
    }
    file_put_contents($credentials, $settings, LOCK_EX);
    $arguments = [$binary, '--defaults-extra-file='.$credentials, '--single-transaction', '--quick', '--skip-lock-tables', '--no-tablespaces', '--result-file='.$destination, '--', $connection['database']];
    $process = new Process($arguments);
    $process->setTimeout(300);
    $process->run();
    if (! $process->isSuccessful() || ! is_file($destination) || filesize($destination) === 0) {
        @unlink($destination);
        throw new RuntimeException('Database backup failed; deployment stopped before migrations.');
    }
    chmod($destination, 0600);
    echo "Database backup completed.\n";
} finally {
    @unlink($credentials);
}
