<?php

namespace Talk\Extend;

use Talk\Extension\Event\Disabled;
use Talk\Extension\Event\Enabled;
use Talk\Extension\Extension;
use Talk\Foundation\ContainerUtil;
use Talk\Foundation\Event\ClearingCache;
use Talk\Frontend\Assets;
use Talk\Frontend\Compiler\Source\SourceCollector;
use Talk\Frontend\Document;
use Talk\Frontend\Frontend as ActualFrontend;
use Talk\Frontend\RecompileFrontendAssets;
use Talk\Http\RouteCollection;
use Talk\Http\RouteHandlerFactory;
use Talk\Locale\LocaleManager;
use Talk\Settings\Event\Saved;
use Illuminate\Contracts\Container\Container;

class Frontend implements ExtenderInterface
{
    private $frontend;

    private $css = [];
    private $js;
    private $routes = [];
    private $removedRoutes = [];
    private $content = [];
    private $preloadArrs = [];
    private $titleDriver;

    /**
     * @param string $frontend: The name of the frontend.
     */
    public function __construct(string $frontend)
    {
        $this->frontend = $frontend;
    }

    /**
     * Add a CSS file to load in the frontend.
     *
     * @param string $path: The path to the CSS file.
     * @return self
     */
    public function css(string $path): self
    {
        $this->css[] = $path;

        return $this;
    }

    /**
     * Add a JavaScript file to load in the frontend.
     *
     * @param string $path: The path to the JavaScript file.
     * @return self
     */
    public function js(string $path): self
    {
        $this->js = $path;

        return $this;
    }

    /**
     * Add a route to the frontend.
     *
     * @param string $path: The path of the route.
     * @param string $name: The name of the route, must be unique.
     * @param callable|string|null $content
     *
     * The content can be a closure or an invokable class, and should accept:
     * - \Talk\Frontend\Document $document
     * - \Psr\Http\Message\ServerRequestInterface $request
     *
     * The callable should return void.
     *
     * @return self
     */
    public function route(string $path, string $name, $content = null): self
    {
        $this->routes[] = compact('path', 'name', 'content');

        return $this;
    }

    /**
     * Remove a route from the frontend.
     * This is necessary before overriding a route.
     *
     * @param string $name: The name of the route.
     * @return self
     */
    public function removeRoute(string $name): self
    {
        $this->removedRoutes[] = $name;

        return $this;
    }

    /**
     * Modify the content of the frontend.
     *
     * @param callable|string|null $callback
     *
     * The content can be a closure or an invokable class, and should accept:
     * - \Talk\Frontend\Document $document
     * - \Psr\Http\Message\ServerRequestInterface $request
     *
     * The callable should return void.
     *
     * @return self
     */
    public function content($callback): self
    {
        $this->content[] = $callback;

        return $this;
    }

    /**
     * Adds multiple asset preloads.
     *
     * The parameter should be an array of preload arrays, or a callable that returns this.
     *
     * A preload array must contain keys that pertain to the `<link rel="preload">` tag.
     *
     * For example, the following will add preload tags for a script and font file:
     * ```
     * $frontend->preloads([
     *   [
     *     'href' => '/assets/my-script.js',
     *     'as' => 'script',
     *   ],
     *   [
     *     'href' => '/assets/fonts/my-font.woff2',
     *     'as' => 'font',
     *     'type' => 'font/woff2',
     *     'crossorigin' => ''
     *   ]
     * ]);
     * ```
     *
     * @param callable|array $preloads
     * @return self
     */
    public function preloads($preloads): self
    {
        $this->preloadArrs[] = $preloads;

        return $this;
    }

    /**
     * Register a new title driver to change the title of frontend documents.
     */
    public function title(string $driverClass): self
    {
        $this->titleDriver = $driverClass;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $this->registerAssets($container, $this->getModuleName($extension));
        $this->registerRoutes($container);
        $this->registerContent($container);
        $this->registerPreloads($container);
        $this->registerTitleDriver($container);
    }

    private function registerAssets(Container $container, string $moduleName): void
    {
        if (empty($this->css) && empty($this->js)) {
            return;
        }

        $abstract = 'talk.assets.'.$this->frontend;

        $container->resolving($abstract, function (Assets $assets) use ($moduleName) {
            if ($this->js) {
                $assets->js(function (SourceCollector $sources) use ($moduleName) {
                    $sources->addString(function () {
                        return 'var module={};';
                    });
                    $sources->addFile($this->js);
                    $sources->addString(function () use ($moduleName) {
                        return "talk.extensions['$moduleName']=module.exports;";
                    });
                });
            }

            if ($this->css) {
                $assets->css(function (SourceCollector $sources) use ($moduleName) {
                    foreach ($this->css as $path) {
                        $sources->addFile($path, $moduleName);
                    }
                });
            }
        });

        if (! $container->bound($abstract)) {
            $container->bind($abstract, function (Container $container) {
                return $container->make('talk.assets.factory')($this->frontend);
            });

            /** @var \Illuminate\Contracts\Events\Dispatcher $events */
            $events = $container->make('events');

            $events->listen(
                [Enabled::class, Disabled::class, ClearingCache::class],
                function () use ($container, $abstract) {
                    $recompile = new RecompileFrontendAssets(
                        $container->make($abstract),
                        $container->make(LocaleManager::class)
                    );
                    $recompile->flush();
                }
            );

            $events->listen(
                Saved::class,
                function (Saved $event) use ($container, $abstract) {
                    $recompile = new RecompileFrontendAssets(
                        $container->make($abstract),
                        $container->make(LocaleManager::class)
                    );
                    $recompile->whenSettingsSaved($event);
                }
            );
        }
    }

    private function registerRoutes(Container $container): void
    {
        if (empty($this->routes) && empty($this->removedRoutes)) {
            return;
        }

        $container->resolving(
            "talk.{$this->frontend}.routes",
            function (RouteCollection $collection, Container $container) {
                /** @var RouteHandlerFactory $factory */
                $factory = $container->make(RouteHandlerFactory::class);

                foreach ($this->removedRoutes as $routeName) {
                    $collection->removeRoute($routeName);
                }

                foreach ($this->routes as $route) {
                    $collection->get(
                        $route['path'],
                        $route['name'],
                        $factory->toFrontend($this->frontend, $route['content'])
                    );
                }
            }
        );
    }

    private function registerContent(Container $container): void
    {
        if (empty($this->content)) {
            return;
        }

        $container->resolving(
            "talk.frontend.$this->frontend",
            function (ActualFrontend $frontend, Container $container) {
                foreach ($this->content as $content) {
                    $frontend->content(ContainerUtil::wrapCallback($content, $container));
                }
            }
        );
    }

    private function registerPreloads(Container $container): void
    {
        if (empty($this->preloadArrs)) {
            return;
        }

        $container->resolving(
            "talk.frontend.$this->frontend",
            function (ActualFrontend $frontend, Container $container) {
                $frontend->content(function (Document $document) use ($container) {
                    foreach ($this->preloadArrs as $preloadArr) {
                        $preloads = is_callable($preloadArr) ? ContainerUtil::wrapCallback($preloadArr, $container)($document) : $preloadArr;
                        $document->preloads = array_merge($document->preloads, $preloads);
                    }
                });
            }
        );
    }

    private function getModuleName(?Extension $extension): string
    {
        return $extension ? $extension->getId() : 'site-custom';
    }

    private function registerTitleDriver(Container $container): void
    {
        if ($this->titleDriver) {
            $container->extend('talk.frontend.title_driver', function ($driver, Container $container) {
                return $container->make($this->titleDriver);
            });
        }
    }
}
