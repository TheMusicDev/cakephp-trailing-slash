<?php
declare(strict_types=1);

namespace TheMusicDev\TrailingSlash\Middleware;

use Cake\Core\Configure;
use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Redirects a GET/HEAD request whose path ends in `/` to the same path without
 * it, keeping the query string. `/` itself is left alone. Runs before routing,
 * so it needs no knowledge of which URLs are pages — stripping is a pure string
 * rule (adding a slash would have to know what is a page and what is a file).
 *
 * The `Location` is relative and always starts with exactly one `/`: a request
 * for `//evil.example/` must not become the protocol-relative (off-site)
 * `Location: //evil.example`. A path containing a backslash is left alone,
 * since browsers read `/\evil.example` as `//evil.example`.
 */
final class TrailingSlashMiddleware implements MiddlewareInterface
{
    /**
     * @param int $status Redirect status code.
     * @param list<string> $skip Paths, without the trailing slash, to leave alone.
     */
    public function __construct(private int $status = 301, private array $skip = [])
    {
    }

    /**
     * Build from the `TrailingSlash` Configure block (`status`, `skip`).
     */
    public static function fromConfig(): self
    {
        /** @var list<string> $skip */
        $skip = array_values((array)Configure::read('TrailingSlash.skip', []));

        return new self((int)Configure::read('TrailingSlash.status', 301), $skip);
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $target = $this->target($request);
        if ($target === null) {
            return $handler->handle($request);
        }

        return (new Response())->withStatus($this->status)->withHeader('Location', $target);
    }

    /**
     * Where to redirect, or null when the request is left alone.
     */
    private function target(ServerRequestInterface $request): ?string
    {
        if (!in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return null;
        }

        $uri = $request->getUri();
        $path = $uri->getPath();
        if ($path === '/' || !str_ends_with($path, '/') || str_contains($path, '\\')) {
            return null;
        }

        // One leading slash, no trailing ones: `///x//` → `/x`, `//` → `/`.
        $stripped = '/' . trim($path, '/');
        if (in_array($stripped, $this->skip, true)) {
            return null;
        }

        $query = $uri->getQuery();

        return $stripped . ($query === '' ? '' : '?' . $query);
    }
}
