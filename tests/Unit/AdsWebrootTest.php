<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class AdsWebrootTest extends TestCase
{
    public function test_ads_installer_rejects_main_root_and_preserves_cpanel_handler_on_separate_root(): void
    {
        $home = sys_get_temp_dir().'/helal-ads-install-'.bin2hex(random_bytes(4));
        $base = $home.'/apps/helal-ads';
        $root = $home.'/public_html/ads';
        mkdir($base.'/public/assets', 0700, true);
        mkdir($base.'/storage/app/public', 0700, true);
        mkdir($root, 0700, true);
        file_put_contents($base.'/.env', 'APP_DEBUG=false');
        file_put_contents($base.'/public/index.php', '<?php');
        file_put_contents($base.'/public/.htaccess', file_get_contents(dirname(__DIR__, 2).'/public/.htaccess'));
        file_put_contents($home.'/public_html/index.php', 'MAIN SITE');
        file_put_contents($root.'/.htaccess', "# cPanel PHP handler\nAddHandler application/x-httpd-alt-php83 .php\n");
        $script = dirname(__DIR__, 2).'/scripts/deploy/install-ads-webroot.php';
        try {
            $reject = new Process([PHP_BINARY, $script, $base, $home.'/public_html']);
            $reject->run();
            $this->assertFalse($reject->isSuccessful());
            $this->assertSame('MAIN SITE', file_get_contents($home.'/public_html/index.php'));
            $install = new Process([PHP_BINARY, $script, $base, $root]);
            $install->run();
            $this->assertTrue($install->isSuccessful(), $install->getErrorOutput());
            $this->assertStringContainsString($base.'/public/index.php', file_get_contents($root.'/index.php'));
            $this->assertStringContainsString('X-Robots-Tag', file_get_contents($root.'/.htaccess'));
            $this->assertStringContainsString('application/x-httpd-alt-php83', file_get_contents($root.'/.htaccess'));
            $this->assertSame(realpath($base.'/storage/app/public'), readlink($root.'/storage'));
            $install->run();
            $this->assertFalse($install->isSuccessful());
            $this->assertSame('MAIN SITE', file_get_contents($home.'/public_html/index.php'));
        } finally {
            (new \Illuminate\Filesystem\Filesystem)->deleteDirectory($home);
        }
    }
}
