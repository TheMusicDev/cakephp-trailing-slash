# TheMusicDev/TrailingSlash

One middleware: a GET/HEAD request for `/about/` gets a **301 to `/about`**, query
string kept. `/` is the only path that keeps its slash.

## Why

Cake compiles every route as `#^/about[/]*$#`, so `/about` and `/about/` both
return 200 and the page is reachable at two URLs — each naming itself canonical.
That is duplicate content, and it is how a trailing slash creeps into
sitemaps and links. Pick one form and redirect the other. We pick **no slash**:
stripping is a pure string rule; adding a slash would need to know which URLs
are pages and which are files or assets. Rationale and decisions:
[`docs/decisions.md`](docs/decisions.md).

## Install

```bash
composer require themusicdev/trailing-slash
bin/cake plugin load TheMusicDev/TrailingSlash
```

Then add the middleware in `Application::middleware()`, **after the asset middleware and before routing**:

```php
use TheMusicDev\TrailingSlash\Middleware\TrailingSlashMiddleware;

$middlewareQueue
    ->add(new AssetMiddleware([...]))
    ->add(TrailingSlashMiddleware::fromConfig())
    ->add(new RoutingMiddleware($this));
```

(After assets so files are never redirected; before routing so even a URL no route matches is redirected
instead of 404ed.)

Requires PHP 8.2+ and CakePHP 5.2+.

## Configure (host `config/app.php`, optional)

```php
'TrailingSlash' => [
    'status' => 301,                 // 301 (default) or 308
    'skip' => ['/downloads'],    // paths, without the slash, to leave alone
],
```

Host values win over `config/app_default.php`. `skip` is for a URL the app
redirects itself or must serve in both forms, so the visitor gets one hop instead of
two. Most sites need none.

## Use

Nothing to call. Build every URL from the router (`$this->Url->build([...])`,
`Router::url([...])`) — routes are defined without a trailing slash, so routed URLs
already come out slash-free; a hand-typed `href="/x/"` is what this plugin exists to
clean up after, and TheMusicDev's convention is never to write one.

## Gotchas

- **Only GET and HEAD.** A POST to `/form/` is not redirected (a redirect would drop
  the body) — it reaches the router, which accepts both forms.
- **The `Location` is relative and always starts with exactly one `/`.** Cake's own
  URI layer already collapses a leading `//` and encodes backslashes; the middleware
  does not rely on that, since a redirect built from the request path must never
  become the off-site `Location: //evil.example`.
- **Permanent and cacheable.** A 301 is cached by browsers and CDNs; changing your
  mind later means waiting them out.
- **Subdirectory installs:** the path the middleware sees includes the base
  directory, so a `skip` entry must include it too.
- **No "add the slash" mode.** Deliberate — see the decisions doc.

## Tests

```bash
composer install
composer check        # phpunit + phpcs (CakePHP standard) + phpstan (level 8)
```

The tests run against a tiny test application in `tests/test_app` that loads the plugin the way a host does.
There is no database.

## License

MIT, see [LICENSE](LICENSE).
