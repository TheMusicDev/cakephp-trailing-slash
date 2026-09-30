<?php
declare(strict_types=1);

namespace TheMusicDev\TrailingSlash;

use Cake\Core\BasePlugin;

/**
 * TheMusicDev/TrailingSlash — one middleware that 301s `/about/` to `/about`.
 * The host adds it to its middleware queue (see README). Configuration lives in
 * config/app_default.php + config/bootstrap.php, merged UNDER the host's
 * `TrailingSlash.*` values.
 */
final class TrailingSlashPlugin extends BasePlugin
{
}
