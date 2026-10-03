<?php
declare(strict_types=1);

namespace TheMusicDev\TrailingSlash\Test\TestCase;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use TestApp\Application;

/**
 * The middleware as a host installs it: in the application's queue, answering
 * before routing (so even a URL no route matches is redirected, not 404ed).
 */
final class TrailingSlashIntegrationTest extends TestCase
{
    use IntegrationTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configApplication(Application::class, [CONFIG]);
    }

    public function testASlashURLIsRedirectedBeforeRouting(): void
    {
        $this->get('/no-such-page/?x=1');

        $this->assertResponseCode(301);
        $this->assertHeader('Location', '/no-such-page?x=1');
    }

    public function testASlashFreeURLIsServedNormally(): void
    {
        $this->get('/ping');

        $this->assertResponseOk();
    }
}
