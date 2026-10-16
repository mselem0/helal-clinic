<?php

use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('production') || config('app.debug') || ! config('app.key') || config('app.url') !== 'https://drmohamedhelal.com' || config('database.default') !== 'mysql' || ! config('database.connections.mysql.database') || ! config('database.connections.mysql.username') || ! config('database.connections.mysql.password')) {
    throw new RuntimeException('Production environment, HTTPS URL, stable APP_KEY and MySQL must be configured before deployment.');
}
if (config('clinic-integrations.ai') || config('clinic-integrations.google') || config('clinic-integrations.whatsapp')) {
    throw new RuntimeException('External integrations must remain disabled for this deployment setup.');
}
echo "Production configuration passed.\n";
