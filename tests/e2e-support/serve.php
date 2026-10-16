<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

// The E2E runner owns a disposable database and storage tree, never the developer database.
$root = dirname(__DIR__, 2);
if (getenv('APP_ENV') !== 'testing' || getenv('CLINIC_E2E') !== '1') {
    exit(1);
}
$db = getenv('DB_DATABASE');
if (! str_contains($db, 'helal-e2e-')) {
    exit(1);
}
@mkdir(dirname($db), 0700, true);
touch($db);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
Artisan::call('migrate', ['--force' => true]);
Artisan::call('db:seed', ['--force' => true]);
foreach (['admin' => 'super_admin', 'visitor' => null] as $name => $role) {
    $user = User::firstOrCreate(['email' => $name.'@example.test'], ['name' => 'E2E '.$name, 'role' => $name === 'admin' ? 'admin' : 'patient', 'password' => 'E2E-Only-Password-123!']);
    if ($role) {
        $user->assignRole($role);
    }
}
$port = (int) ($argv[1] ?? 0);
if ($port < 1) {
    exit(1);
}
passthru(escapeshellarg(PHP_BINARY).' -d opcache.enable_cli=0 -d pcre.jit=0 -S 127.0.0.1:'.$port.' -t '.escapeshellarg($root.'/public').' '.escapeshellarg(__DIR__.'/router.php'), $code);
exit($code);
