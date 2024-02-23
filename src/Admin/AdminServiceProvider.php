<?php

namespace Talk\Admin;

use Talk\Extension\Event\Disabled;
use Talk\Extension\Event\Enabled;
use Talk\Foundation\AbstractServiceProvider;
use Talk\Foundation\ErrorHandling\Registry;
use Talk\Foundation\ErrorHandling\Reporter;
use Talk\Foundation\ErrorHandling\ViewFormatter;
use Talk\Foundation\ErrorHandling\WhoopsFormatter;
use Talk\Foundation\Event\ClearingCache;
use Talk\Frontend\AddLocaleAssets;
use Talk\Frontend\AddTranslations;
use Talk\Frontend\Compiler\Source\SourceCollector;
use Talk\Frontend\RecompileFrontendAssets;
use Talk\Http\Middleware as HttpMiddleware;
use Talk\Http\RouteCollection;
use Talk\Http\RouteHandlerFactory;
use Talk\Http\UrlGenerator;
use Talk\Locale\LocaleManager;
use Talk\Settings\Event\Saved;
use Illuminate\Contracts\Container\Container;
use Laminas\Stratigility\MiddlewarePipe;

class AdminServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->extend(UrlGenerator::class, function (UrlGenerator $url, Container $container) {
            return $url->addCollection('admin', $container->make('talk.admin.routes'), 'admin');
        });

        $this->container->singleton('talk.admin.routes', function () {
            $routes = new RouteCollection;
            $this->populateRoutes($routes);

            return $routes;
        });

        $this->container->singleton('talk.admin.middleware', function () {
            return [
                HttpMiddleware\InjectActorReference::class,
                'talk.admin.error_handler',
                HttpMiddleware\ParseJsonBody::class,
                HttpMiddleware\StartSession::class,
                HttpMiddleware\RememberFromCookie::class,
                HttpMiddleware\AuthenticateWithSession::class,
                HttpMiddleware\SetLocale::class,
                'talk.admin.route_resolver',
                HttpMiddleware\CheckCsrfToken::class,
                Middleware\RequireAdministrateAbility::class,
                HttpMiddleware\ReferrerPolicyHeader::class,
                HttpMiddleware\ContentTypeOptionsHeader::class,
                Middleware\DisableBrowserCache::class,
            ];
        });

        $this->container->bind('talk.admin.error_handler', function (Container $container) {
            return new HttpMiddleware\HandleErrors(
                $container->make(Registry::class),
                $container['talk.config']->inDebugMode() ? $container->make(WhoopsFormatter::class) : $container->make(ViewFormatter::class),
                $container->tagged(Reporter::class)
            );
        });

        $this->container->bind('talk.admin.route_resolver', function (Container $container) {
            return new HttpMiddleware\ResolveRoute($container->make('talk.admin.routes'));
        });

        $this->container->singleton('talk.admin.handler', function (Container $container) {
            $pipe = new MiddlewarePipe;

            foreach ($container->make('talk.admin.middleware') as $middleware) {
                $pipe->pipe($container->make($middleware));
            }

            $pipe->pipe(new HttpMiddleware\ExecuteRoute());

            return $pipe;
        });

        $this->container->bind('talk.assets.admin', function (Container $container) {
            /** @var \Talk\Frontend\Assets $assets */
            $assets = $container->make('talk.assets.factory')('admin');

            $assets->js(function (SourceCollector $sources) {
                $sources->addFile(__DIR__.'/../../js/dist/admin.js');
            });

            $assets->css(function (SourceCollector $sources) {
                $sources->addFile(__DIR__.'/../../less/admin.less');
            });

            $container->make(AddTranslations::class)->forFrontend('admin')->to($assets);
            $container->make(AddLocaleAssets::class)->to($assets);

            return $assets;
        });

        $this->container->bind('talk.frontend.admin', function (Container $container) {
            /** @var \Talk\Frontend\Frontend $frontend */
            $frontend = $container->make('talk.frontend.factory')('admin');

            $frontend->content($container->make(Content\AdminPayload::class));

            return $frontend;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../../views', 'talk.admin');

        $events = $this->container->make('events');

        $events->listen(
            [Enabled::class, Disabled::class, ClearingCache::class],
            function () {
                $recompile = new RecompileFrontendAssets(
                    $this->container->make('talk.assets.admin'),
                    $this->container->make(LocaleManager::class)
                );
                $recompile->flush();
            }
        );

        $events->listen(
            Saved::class,
            function (Saved $event) {
                $recompile = new RecompileFrontendAssets(
                    $this->container->make('talk.assets.admin'),
                    $this->container->make(LocaleManager::class)
                );
                $recompile->whenSettingsSaved($event);
            }
        );
    }

    /**
     * @param RouteCollection $routes
     */
    protected function populateRoutes(RouteCollection $routes)
    {
        $factory = $this->container->make(RouteHandlerFactory::class);

        $callback = include __DIR__.'/routes.php';
        $callback($routes, $factory);
    }
}
