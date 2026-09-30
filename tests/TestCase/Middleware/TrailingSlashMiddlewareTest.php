<?php
declare(strict_types=1);

namespace TheMusicDev\TrailingSlash\Test\TestCase\Middleware;

use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\ServerRequestFactory;
use Cake\TestSuite\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TheMusicDev\TrailingSlash\Middleware\TrailingSlashMiddleware;

/**
 * The redirect rule, exercised on the middleware alone (no app, no routes).
 */
final class TrailingSlashMiddlewareTest extends TestCase
{
    /**
     * Run a request through the middleware; the handler answers 200 "reached".
     */
    private function through(string $uri, string $method = 'GET', ?TrailingSlashMiddleware $middleware = null): ResponseInterface
    {
        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_URI' => $uri,
            'QUERY_STRING' => (string)parse_url($uri, PHP_URL_QUERY),
            'REQUEST_METHOD' => $method,
            'HTTP_HOST' => 'example.test',
        ]);
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return (new Response())->withStringBody('reached');
            }
        };

        return ($middleware ?? new TrailingSlashMiddleware())->process($request, $handler);
    }

    private function assertRedirectsTo(string $expected, ResponseInterface $response): void
    {
        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame($expected, $response->getHeaderLine('Location'));
    }

    private function assertReached(ResponseInterface $response): void
    {
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('reached', (string)$response->getBody());
    }

    public function testATrailingSlashIsRedirectedToTheSlashFreePath(): void
    {
        $this->assertRedirectsTo('/about', $this->through('/about/'));
        $this->assertRedirectsTo('/careers/some-role', $this->through('/careers/some-role/'));
    }

    public function testTheQueryStringIsKept(): void
    {
        $this->assertRedirectsTo('/careers?location=Remote&page=2', $this->through('/careers/?location=Remote&page=2'));
    }

    public function testRepeatedTrailingSlashesAreAllRemoved(): void
    {
        $this->assertRedirectsTo('/a', $this->through('/a///'));
    }

    public function testFilesWithAnExtensionAreCoveredToo(): void
    {
        $this->assertRedirectsTo('/sitemap.xml', $this->through('/sitemap.xml/'));
    }

    public function testTheRootAndSlashFreePathsAreLeftAlone(): void
    {
        $this->assertReached($this->through('/'));
        $this->assertReached($this->through('/about'));
        $this->assertReached($this->through('/careers?page=2'));
    }

    public function testHeadIsRedirectedButOtherMethodsAreNot(): void
    {
        $this->assertRedirectsTo('/about', $this->through('/about/', 'HEAD'));
        foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $this->assertReached($this->through('/about/', $method));
        }
    }

    /**
     * A redirect built from the request path must never become off-site: a
     * leading `//` would be read by browsers as a protocol-relative URL. Cake's
     * own URI parsing already collapses leading slashes before any middleware
     * runs; the middleware also collapses them itself, so the guarantee does not
     * depend on that.
     */
    public function testTheLocationNeverStartsWithADoubleSlash(): void
    {
        $this->assertRedirectsTo('/evil.example', $this->through('//evil.example/'));
        $this->assertRedirectsTo('/evil.example', $this->through('///evil.example//'));
        $this->assertReached($this->through('//')); // Cake reads it as the root
    }

    public function testABackslashStaysEncodedAndOnSite(): void
    {
        // Cake encodes "\" as %5C, so the redirect stays a same-site path.
        $response = $this->through('/\\evil.example/');

        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('/%5Cevil.example', $response->getHeaderLine('Location'));
    }

    public function testSkippedPathsAreLeftAloneWithOrWithoutTheSlash(): void
    {
        $middleware = new TrailingSlashMiddleware(301, ['/downloads']);

        $this->assertReached($this->through('/downloads/', 'GET', $middleware));
        $this->assertReached($this->through('/downloads', 'GET', $middleware));
        $this->assertRedirectsTo('/downloads/extra', $this->through('/downloads/extra/', 'GET', $middleware));
    }

    public function testTheStatusIsConfigurable(): void
    {
        $response = $this->through('/about/', 'GET', new TrailingSlashMiddleware(308));

        $this->assertSame(308, $response->getStatusCode());
        $this->assertSame('/about', $response->getHeaderLine('Location'));
    }

    public function testFromConfigReadsTheConfigureBlock(): void
    {
        $original = Configure::read('TrailingSlash');
        Configure::write('TrailingSlash', ['status' => 308, 'skip' => ['/skipme']]);
        try {
            $middleware = TrailingSlashMiddleware::fromConfig();

            $this->assertSame(308, $this->through('/about/', 'GET', $middleware)->getStatusCode());
            $this->assertReached($this->through('/skipme/', 'GET', $middleware));
        } finally {
            Configure::write('TrailingSlash', $original);
        }
    }
}
