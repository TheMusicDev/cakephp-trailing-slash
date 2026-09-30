<?php
declare(strict_types=1);

/**
 * TheMusicDev/TrailingSlash ship-with defaults. Merged UNDER host values by the
 * plugin's config/bootstrap.php — hosts win (IdentityBridge convention,
 * docs/conventions.md).
 */
return [
    'TrailingSlash' => [
        // Redirect status. 301 is right for a permanent canonical form.
        'status' => 301,
        // Paths (without the trailing slash) to leave alone, e.g. a URL the app
        // redirects itself so the visitor gets one hop instead of two.
        'skip' => [],
    ],
];
