<?php

namespace Talk\Api;

use Talk\Api\Controller\AbstractSerializeController;
use Talk\Api\Serializer\AbstractSerializer;
use Talk\Api\Serializer\BasicDiscussionSerializer;
use Talk\Api\Serializer\NotificationSerializer;
use Talk\Foundation\AbstractServiceProvider;
use Talk\Foundation\ErrorHandling\JsonApiFormatter;
use Talk\Foundation\ErrorHandling\Registry;
use Talk\Foundation\ErrorHandling\Reporter;
use Talk\Http\Middleware as HttpMiddleware;
use Talk\Http\RouteCollection;
use Talk\Http\RouteHandlerFactory;
use Talk\Http\UrlGenerator;
use Illuminate\Contracts\Container\Container;
use Laminas\Stratigility\MiddlewarePipe;

class ApiServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->extend(UrlGenerator::class, function (UrlGenerator $url, Container $container) {
            return $url->addCollection('api', $container->make('talk.api.routes'), 'api');
        });

        $this->container->singleton('talk.api.routes', function () {
            $routes = new RouteCollection;
            $this->populateRoutes($routes);

            return $routes;
        });

        $this->container->singleton('talk.api.throttlers', function () {
            return [
                'bypassThrottlingAttribute' => function ($request) {
                    if ($request->getAttribute('bypassThrottling')) {
                        return false;
                    }
                }
            ];
        });

        $this->container->bind(Middleware\ThrottleApi::class, function (Container $container) {
            return new Middleware\ThrottleApi($container->make('talk.api.throttlers'));
        });

        $this->container->singleton('talk.api.middleware', function () {
            return [
                HttpMiddleware\InjectActorReference::class,
                'talk.api.error_handler',
                HttpMiddleware\ParseJsonBody::class,
                Middleware\FakeHttpMethods::class,
                HttpMiddleware\StartSession::class,
                HttpMiddleware\RememberFromCookie::class,
                HttpMiddleware\AuthenticateWithSession::class,
                HttpMiddleware\AuthenticateWithHeader::class,
                HttpMiddleware\SetLocale::class,
                'talk.api.route_resolver',
                HttpMiddleware\CheckCsrfToken::class,
                Middleware\ThrottleApi::class
            ];
        });

        $this->container->bind('talk.api.error_handler', function (Container $container) {
            return new HttpMiddleware\HandleErrors(
                $container->make(Registry::class),
                new JsonApiFormatter($container['talk.config']->inDebugMode()),
                $container->tagged(Reporter::class)
            );
        });

        $this->container->bind('talk.api.route_resolver', function (Container $container) {
            return new HttpMiddleware\ResolveRoute($container->make('talk.api.routes'));
        });

        $this->container->singleton('talk.api.handler', function (Container $container) {
            $pipe = new MiddlewarePipe;

            foreach ($this->container->make('talk.api.middleware') as $middleware) {
                $pipe->pipe($container->make($middleware));
            }

            $pipe->pipe(new HttpMiddleware\ExecuteRoute());

            return $pipe;
        });

        $this->container->singleton('talk.api.notification_serializers', function () {
            return [
                'discussionRenamed' => BasicDiscussionSerializer::class
            ];
        });

        $this->container->singleton('talk.api_client.exclude_middleware', function () {
            return [
                HttpMiddleware\InjectActorReference::class,
                HttpMiddleware\ParseJsonBody::class,
                Middleware\FakeHttpMethods::class,
                HttpMiddleware\StartSession::class,
                HttpMiddleware\AuthenticateWithSession::class,
                HttpMiddleware\AuthenticateWithHeader::class,
                HttpMiddleware\CheckCsrfToken::class,
                HttpMiddleware\RememberFromCookie::class,
            ];
        });

        $this->container->singleton(Client::class, function ($container) {
            $pipe = new MiddlewarePipe;

            $exclude = $container->make('talk.api_client.exclude_middleware');

            $middlewareStack = array_filter($container->make('talk.api.middleware'), function ($middlewareClass) use ($exclude) {
                return ! in_array($middlewareClass, $exclude);
            });

            foreach ($middlewareStack as $middleware) {
                $pipe->pipe($container->make($middleware));
            }

            $pipe->pipe(new HttpMiddleware\ExecuteRoute());

            return new Client($pipe);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Container $container)
    {
        $this->setNotificationSerializers();

        AbstractSerializeController::setContainer($container);

        AbstractSerializer::setContainer($container);
    }

    /**
     * Register notification serializers.
     */
    protected function setNotificationSerializers()
    {
        $serializers = $this->container->make('talk.api.notification_serializers');

        foreach ($serializers as $type => $serializer) {
            NotificationSerializer::setSubjectSerializer($type, $serializer);
        }
    }

    /**
     * Populate the API routes.
     *
     * @param RouteCollection $routes
     */
    protected function populateRoutes(RouteCollection $routes)
    {
        $factory = $this->container->make(RouteHandlerFactory::class);

        $callback = include __DIR__.'/routes.php';
        $callback($routes, $factory);
    }
}
