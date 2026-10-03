<?php
declare(strict_types=1);

namespace TestApp;

use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Routing\RouteBuilder;
use TheMusicDev\TrailingSlash\Middleware\TrailingSlashMiddleware;

/**
 * The smallest application that installs the plugin the way a host does: the middleware
 * before routing, one real route.
 */
class Application extends BaseApplication
{
    /**
     * @inheritDoc
     */
    public function bootstrap(): void
    {
        parent::bootstrap();
        $this->addPlugin('TheMusicDev/TrailingSlash', ['path' => ROOT . DS]);
    }

    /**
     * @inheritDoc
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        return $middlewareQueue
            ->add(TrailingSlashMiddleware::fromConfig())
            ->add(new RoutingMiddleware($this));
    }

    /**
     * @inheritDoc
     */
    public function routes(RouteBuilder $routes): void
    {
        $routes->connect('/ping', ['controller' => 'Ping', 'action' => 'index']);
    }
}
