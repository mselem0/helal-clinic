<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class DeploymentWebrootTest extends TestCase
{
    private string $directory;

    private string $base;

    private string $webroot;

    private string $release;

    protected function setUp(): void
    {
        $this->directory = realpath(sys_get_temp_dir()).'/helal-deploy-test-'.bin2hex(random_bytes(6));
        $this->base = $this->directory.'/home/test/apps/helal';
        $this->webroot = $this->directory.'/home/test/public_html';
        $this->release = $this->base.'/releases/'.str_repeat('a', 40);
        foreach ([$this->webroot, $this->release.'/public/assets', $this->base.'/shared/storage/app/public'] as $path) {
            mkdir($path, 0700, true);
        }
        file_put_contents($this->release.'/public/index.php', '<?php echo "candidate";');
        file_put_contents($this->release.'/public/.htaccess', 'RewriteEngine On');
        file_put_contents($this->release.'/public/helal.css', 'body{color:teal}');
        file_put_contents($this->webroot.'/index.php', 'original website');
        file_put_contents($this->webroot.'/.htaccess', "# php -- BEGIN cPanel-generated handler\nAddHandler application/x-httpd-alt-php82 .php\n# php -- END cPanel-generated handler\n");
        mkdir($this->webroot.'/.well-known');
        file_put_contents($this->webroot.'/.well-known/proof', 'keep');
    }

    private function runScript(string $name, array $arguments): Process
    {
        $process = new Process(array_merge([PHP_BINARY, dirname(__DIR__, 2).'/scripts/deploy/'.$name], $arguments));
        $process->run();

        return $process;
    }

    protected function tearDown(): void
    {
        $remove = function ($path) use (&$remove) {
            if (is_link($path) || is_file($path)) {
                unlink($path);

                return;
            }foreach (scandir($path) as $name) {
                if ($name !== '.' && $name !== '..') {
                    $remove($path.'/'.$name);
                }
            }rmdir($path);
        };
        $remove($this->directory);
    }

    public function test_public_bridge_uses_private_current_release_and_keeps_ssl_and_handler(): void
    {
        $process = $this->runScript('install-webroot.php', [$this->base, $this->webroot, $this->release]);
        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
        $this->assertStringContainsString($this->base.'/current/public/index.php', file_get_contents($this->webroot.'/index.php'));
        $this->assertSame($this->base.'/current/public/assets', readlink($this->webroot.'/assets'));
        $this->assertSame(0644, fileperms($this->webroot.'/.htaccess') & 0777);
        $this->assertStringContainsString('alt-php82', file_get_contents($this->webroot.'/.htaccess'));
        $this->assertSame('keep', file_get_contents($this->webroot.'/.well-known/proof'));
        $this->assertFalse(file_exists($this->webroot.'/.env'));
        $state = json_decode(file_get_contents($this->base.'/shared/webroot.json'), true);
        $this->assertSame('original website', file_get_contents($state['backup'].'/index.php'));
        $process = $this->runScript('rollback-webroot.php', [$this->base]);
        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
        $this->assertSame('original website', file_get_contents($this->webroot.'/index.php'));
        $this->assertFalse(is_link($this->webroot.'/assets'));
    }

    public function test_new_assets_are_linked_without_overwriting_unmanaged_files(): void
    {
        $this->assertTrue($this->runScript('install-webroot.php', [$this->base, $this->webroot, $this->release])->isSuccessful());
        file_put_contents($this->release.'/public/favicon.ico', 'icon');
        $this->assertTrue($this->runScript('check-webroot.php', [$this->base, $this->release])->isSuccessful());
        $this->assertSame($this->base.'/current/public/favicon.ico', readlink($this->webroot.'/favicon.ico'));
        file_put_contents($this->release.'/public/another.txt', 'new');
        file_put_contents($this->webroot.'/another.txt', 'owner data');
        $this->assertFalse($this->runScript('check-webroot.php', [$this->base, $this->release])->isSuccessful());
        $this->assertSame('owner data', file_get_contents($this->webroot.'/another.txt'));
    }

    public function test_wrong_root_is_rejected_without_modifying_existing_files(): void
    {
        $process = $this->runScript('install-webroot.php', [$this->base, $this->directory, $this->release]);
        $this->assertFalse($process->isSuccessful());
        $this->assertSame('original website', file_get_contents($this->webroot.'/index.php'));
    }
}
