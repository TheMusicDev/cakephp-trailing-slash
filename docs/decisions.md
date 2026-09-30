# TrailingSlash plugin — decisions & record

- **Slash-free is the canonical form; there is no "add a slash" mode.** Removing a
  trailing slash is a pure string operation on the request path. Adding one needs
  to know whether the URL is a page (`/about` → `/about/`) or not (`/sitemap.xml`,
  an asset, an API, `/admin` actions), which means a routing check or an exclusion
  list that rots. Cake itself generates slash-free URLs from route templates, so
  the slash-free form is also the framework's natural one. Decided 2026-09-30
  (maintainer's call), reversing the earlier "mirror Astro's trailing slashes".
- **A plugin, not host code.** Every Cake site we ship has the duplicate-URL
  problem, and the plugin is ~40 lines. Per the build-for-reuse rule
  (docs/conventions.md) that makes it a plugin. No existing Cake package did this
  (searched Packagist/web, and the packages we already use, 2026-09-30).
- **A middleware, not a server rule.** A web-server rewrite works but is per host,
  is skipped by `bin/cake server` and CI, and cannot be tested in phpunit. The
  middleware is portable and tested.
- **Runs before routing.** The redirect needs no route, so a URL nothing matches is
  redirected rather than 404ed, and the router is not asked to resolve a URL that
  is about to be replaced.
- **GET and HEAD only, 301 by default.** Other methods would lose their body on a
  redirect.
- **`skip` is a spare.** It was added for one URL the host redirected itself (the
  old shared apply page), so that URL took one hop instead of two. That redirect was
  removed the same day and nothing uses `skip` now; it is kept only because it is
  small, generic and tested. Delete it if it is still unused when a second site ships.
- **Defense in depth on the `Location`.** Leading slashes are collapsed and a raw
  backslash disables the redirect, although Cake's URI layer already normalizes
  both. A redirect target derived from the request must never be protocol-relative.

## Gotchas

- **Test harness vs real requests.** `ServerRequestFactory::fromGlobals()` only
  sees the query string when `QUERY_STRING` is passed; `REQUEST_URI` alone leaves it
  empty. And a test cannot build a request whose path starts with `//` or holds a
  raw backslash: Cake normalizes them first (which is why those guards are
  defensive, not the only protection).
