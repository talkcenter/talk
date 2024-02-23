<?php

namespace Talk\Site;

use Talk\Extension\Event\Disabled;
use Talk\Extension\Event\Enabled;
use Talk\Formatter\Formatter;
use Talk\Foundation\AbstractServiceProvider;
use Talk\Foundation\ErrorHandling\Registry;
use Talk\Foundation\ErrorHandling\Reporter;
use Talk\Foundation\ErrorHandling\ViewFormatter;
use Talk\Foundation\ErrorHandling\WhoopsFormatter;
use Talk\Foundation\Event\ClearingCache;
use Talk\Frontend\AddLocaleAssets;
use Talk\Frontend\AddTranslations;
use Talk\Frontend\Assets;
use Talk\Frontend\Compiler\Source\SourceCollector;
use Talk\Frontend\RecompileFrontendAssets;
use Talk\Http\Middleware as HttpMiddleware;
use Talk\Http\RouteCollection;
use Talk\Http\RouteHandlerFactory;
use Talk\Http\UrlGenerator;
use Talk\Locale\LocaleManager;
use Talk\Settings\Event\Saved;
use Talk\Settings\Event\Saving;
use Talk\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;
use Laminas\Stratigility\MiddlewarePipe;
use Symfony\Contracts\Translation\TranslatorInterface;

class SiteServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->extend(UrlGenerator::class, function (UrlGenerator $url, Container $container) {
            return $url->addCollection('site', $container->make('talk.site.routes'));
        });

        $this->container->singleton('talk.site.routes', function (Container $container) {
            $routes = new RouteCollection;
            $this->populateRoutes($routes, $container);

            return $routes;
        });

        $this->container->afterResolving('talk.site.routes', function (RouteCollection $routes, Container $container) {
            $this->setDefaultRoute($routes, $container);
        });

        $this->container->singleton('talk.site.middleware', function () {
            return [
                HttpMiddleware\InjectActorReference::class,
                'talk.site.error_handler',
                HttpMiddleware\ParseJsonBody::class,
                HttpMiddleware\CollectGarbage::class,
                HttpMiddleware\StartSession::class,
                HttpMiddleware\RememberFromCookie::class,
                HttpMiddleware\AuthenticateWithSession::class,
                HttpMiddleware\SetLocale::class,
                'talk.site.route_resolver',
                HttpMiddleware\CheckCsrfToken::class,
                HttpMiddleware\ShareErrorsFromSession::class,
                HttpMiddleware\TalkPromotionHeader::class,
                HttpMiddleware\ReferrerPolicyHeader::class,
                HttpMiddleware\ContentTypeOptionsHeader::class
            ];
        });

        $this->container->bind('talk.site.error_handler', function (Container $container) {
            return new HttpMiddleware\HandleErrors(
                $container->make(Registry::class),
                $container['talk.config']->inDebugMode() ? $container->make(WhoopsFormatter::class) : $container->make(ViewFormatter::class),
                $container->tagged(Reporter::class)
            );
        });

        $this->container->bind('talk.site.route_resolver', function (Container $container) {
            return new HttpMiddleware\ResolveRoute($container->make('talk.site.routes'));
        });

        $this->container->singleton('talk.site.handler', function (Container $container) {
            $pipe = new MiddlewarePipe;

            foreach ($container->make('talk.site.middleware') as $middleware) {
                $pipe->pipe($container->make($middleware));
            }

            $pipe->pipe(new HttpMiddleware\ExecuteRoute());

            return $pipe;
        });

        $this->container->bind('talk.assets.site', function (Container $container) {
            /** @var Assets $assets */
            $assets = $container->make('talk.assets.factory')('site');

            $assets->js(function (SourceCollector $sources) use ($container) {
                $sources->addFile(__DIR__.'/../../js/dist/site.js');
                $sources->addString(function () use ($container) {
                    return $container->make(Formatter::class)->getJs();
                });
            });

            $assets->css(function (SourceCollector $sources) use ($container) {
                $sources->addFile(__DIR__.'/../../less/site.less');
                $sources->addString(function () use ($container) {
                    return $container->make(SettingsRepositoryInterface::class)->get('custom_less', '');
                });
            });

            $container->make(AddTranslations::class)->forFrontend('site')->to($assets);
            $container->make(AddLocaleAssets::class)->to($assets);

            return $assets;
        });

        $this->container->bind('talk.frontend.site', function (Container $container) {
            return $container->make('talk.frontend.factory')('site');
        });

        $this->container->singleton('talk.site.discussions.sortmap', function () {
            return [
                'latest' => '-lastPostedAt',
                'top' => '-commentCount',
                'newest' => '-createdAt',
                'oldest' => 'createdAt'
            ];
        });
    }

    public function boot(Container $container, Dispatcher $events, Factory $view)
    {
        $this->loadViewsFrom(__DIR__.'/../../views', 'talk.site');

        $view->share([
            'translator' => $container->make(TranslatorInterface::class),
            'settings' => $container->make(SettingsRepositoryInterface::class)
        ]);

        $events->listen(
            [Enabled::class, Disabled::class, ClearingCache::class],
            function () use ($container) {
                $recompile = new RecompileFrontendAssets(
                    $container->make('talk.assets.site'),
                    $container->make(LocaleManager::class)
                );
                $recompile->flush();
            }
        );

        $events->listen(
            Saved::class,
            function (Saved $event) use ($container) {
                $recompile = new RecompileFrontendAssets(
                    $container->make('talk.assets.site'),
                    $container->make(LocaleManager::class)
                );
                $recompile->whenSettingsSaved($event);

                $validator = new ValidateCustomLess(
                    $container->make('talk.assets.site'),
                    $container->make('talk.locales'),
                    $container,
                    $container->make('talk.less.config')
                );
                $validator->whenSettingsSaved($event);
            }
        );

        $events->listen(
            Saving::class,
            function (Saving $event) use ($container) {
                $validator = new ValidateCustomLess(
                    $container->make('talk.assets.site'),
                    $container->make('talk.locales'),
                    $container,
                    $container->make('talk.less.config')
                );
                $validator->whenSettingsSaving($event);
            }
        );
    }

    /**
     * Populate the site client routes.
     *
     * @param RouteCollection $routes
     * @param Container       $container
     */
    protected function populateRoutes(RouteCollection $routes, Container $container)
    {
        $factory = $container->make(RouteHandlerFactory::class);

        $callback = include __DIR__.'/routes.php';
        $callback($routes, $factory);
    }

    /**
     * Determine the default route.
     *
     * @param RouteCollection $routes
     * @param Container       $container
     */
    protected function setDefaultRoute(RouteCollection $routes, Container $container)
    {
        $factory = $container->make(RouteHandlerFactory::class);
        $defaultRoute = $container->make('talk.settings')->get('default_route');

        if (isset($routes->getRouteData()[0]['GET'][$defaultRoute]['handler'])) {
            $toDefaultController = $routes->getRouteData()[0]['GET'][$defaultRoute]['handler'];
        } else {
            $toDefaultController = $factory->toSite(Content\Index::class);
        }

        $routes->get(
            '/',
            'default',
            $toDefaultController
        );
    }
}
