<?php

namespace Talk\Install;

use Talk\Foundation\AbstractServiceProvider;
use Talk\Http\RouteCollection;
use Talk\Http\RouteHandlerFactory;
use Illuminate\Contracts\Container\Container;

class InstallServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->singleton('talk.install.routes', function () {
            return new RouteCollection;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Container $container, RouteHandlerFactory $route)
    {
        $this->loadViewsFrom(__DIR__.'/../../views/install', 'talk.install');

        $this->populateRoutes($container->make('talk.install.routes'), $route);
    }

    /**
     * @param RouteCollection     $routes
     * @param RouteHandlerFactory $route
     */
    protected function populateRoutes(RouteCollection $routes, RouteHandlerFactory $route)
    {
        $routes->get(
            '/{path:.*}',
            'index',
            $route->toController(Controller\IndexController::class)
        );

        $routes->post(
            '/{path:.*}',
            'install',
            $route->toController(Controller\InstallController::class)
        );
    }
}
