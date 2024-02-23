<?php

namespace Talk\Update;

use Talk\Foundation\AbstractServiceProvider;
use Talk\Http\RouteCollection;
use Talk\Http\RouteHandlerFactory;
use Illuminate\Contracts\Container\Container;

class UpdateServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->singleton('talk.update.routes', function (Container $container) {
            $routes = new RouteCollection;
            $factory = $container->make(RouteHandlerFactory::class);
            $this->populateRoutes($routes, $factory);

            return $routes;
        });
    }

    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../../views/install', 'talk.update');
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
            'update',
            $route->toController(Controller\UpdateController::class)
        );
    }
}
