<?php

namespace Talk\Frontend;

use Talk\Foundation\AbstractServiceProvider;
use Talk\Foundation\Paths;
use Talk\Frontend\Compiler\Source\SourceCollector;
use Talk\Frontend\Driver\BasicTitleDriver;
use Talk\Frontend\Driver\TitleDriverInterface;
use Talk\Http\SlugManager;
use Talk\Http\UrlGenerator;
use Talk\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\View\Factory as ViewFactory;

class FrontendServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->container->singleton('talk.assets.factory', function (Container $container) {
            return function (string $name) use ($container) {
                $paths = $container[Paths::class];

                $assets = new Assets(
                    $name,
                    $container->make('filesystem')->disk('talk-assets'),
                    $paths->storage,
                    null,
                    $container->make('talk.frontend.custom_less_functions')
                );

                $assets->setLessImportDirs([
                    $paths->vendor.'/components/font-awesome/less' => ''
                ]);

                $assets->css([$this, 'addBaseCss']);
                $assets->localeCss([$this, 'addBaseCss']);

                return $assets;
            };
        });

        $this->container->singleton('talk.frontend.factory', function (Container $container) {
            return function (string $name) use ($container) {
                $frontend = $container->make(Frontend::class);

                $frontend->content(function (Document $document) use ($name) {
                    $document->layoutView = 'talk::frontend.'.$name;
                });

                $frontend->content($container->make(Content\Assets::class)->forFrontend($name));
                $frontend->content($container->make(Content\CorePayload::class));
                $frontend->content($container->make(Content\Meta::class));

                $frontend->content(function (Document $document) use ($container) {
                    $default_preloads = $container->make('talk.frontend.default_preloads');

                    // Add preloads for base CSS and JS assets. Extensions should add their own via the extender.
                    $js_preloads = [];
                    $css_preloads = [];

                    foreach ($document->css as $url) {
                        $css_preloads[] = [
                            'href' => $url,
                            'as' => 'style'
                        ];
                    }
                    foreach ($document->js as $url) {
                        $css_preloads[] = [
                            'href' => $url,
                            'as' => 'script'
                        ];
                    }

                    $document->preloads = array_merge(
                        $css_preloads,
                        $js_preloads,
                        $default_preloads,
                        $document->preloads,
                    );
                });

                return $frontend;
            };
        });

        $this->container->singleton(
            'talk.frontend.default_preloads',
            function (Container $container) {
                $filesystem = $container->make('filesystem')->disk('talk-assets');

                return [
                    [
                        'href' => $filesystem->url('fonts/fa-solid-900.woff2'),
                        'as' => 'font',
                        'type' => 'font/woff2',
                        'crossorigin' => ''
                    ], [
                        'href' => $filesystem->url('fonts/fa-regular-400.woff2'),
                        'as' => 'font',
                        'type' => 'font/woff2',
                        'crossorigin' => ''
                    ]
                ];
            }
        );

        $this->container->singleton(
            'talk.frontend.custom_less_functions',
            function (Container $container) {
                $extensionsEnabled = json_decode($container->make(SettingsRepositoryInterface::class)->get('extensions_enabled'));

                // Please note that these functions do not go through the same transformation which the Theme extender's
                // `addCustomLessFunction` method does. You'll need to use the correct Less tree return type, and get
                // parameter values with `$arg->value`.
                return [
                    'is-extension-enabled' => function (\Less_Tree_Quoted $extensionId) use ($extensionsEnabled) {
                        return new \Less_Tree_Quoted('', in_array($extensionId->value, $extensionsEnabled) ? 'true' : 'false');
                    }
                ];
            }
        );

        $this->container->singleton(TitleDriverInterface::class, function (Container $container) {
            return $container->make(BasicTitleDriver::class);
        });

        $this->container->alias(TitleDriverInterface::class, 'talk.frontend.title_driver');

        $this->container->singleton('talk.less.config', function (Container $container) {
            return [
                'config-primary-color'   => [
                    'key' => 'theme_primary_color',
                ],
                'config-secondary-color' => [
                    'key' => 'theme_secondary_color',
                ],
                'config-dark-mode'       => [
                    'key' => 'theme_dark_mode',
                    'callback' => function ($value) {
                        return $value ? 'true' : 'false';
                    },
                ],
                'config-colored-header'  => [
                    'key' => 'theme_colored_header',
                    'callback' => function ($value) {
                        return $value ? 'true' : 'false';
                    },
                ],
            ];
        });

        $this->container->singleton(
            'talk.less.custom_variables',
            function (Container $container) {
                return [];
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Container $container, ViewFactory $views)
    {
        $this->loadViewsFrom(__DIR__.'/../../views', 'talk');

        $views->share([
            'translator' => $container->make('translator'),
            'url' => $container->make(UrlGenerator::class),
            'slugManager' => $container->make(SlugManager::class)
        ]);
    }

    public function addBaseCss(SourceCollector $sources)
    {
        $sources->addFile(__DIR__.'/../../less/common/variables.less');
        $sources->addFile(__DIR__.'/../../less/common/mixins.less');

        $this->addLessVariables($sources);
    }

    private function addLessVariables(SourceCollector $sources)
    {
        $sources->addString(function () {
            $vars = $this->container->make('talk.less.config');
            $extDefinedVars = $this->container->make('talk.less.custom_variables');

            $settings = $this->container->make(SettingsRepositoryInterface::class);

            $customLess = array_reduce(array_keys($vars), function ($string, $name) use ($vars, $settings) {
                $var = $vars[$name];
                $value = $settings->get($var['key'], $var['default'] ?? null);

                if (isset($var['callback'])) {
                    $value = $var['callback']($value);
                }

                return $string."@$name: {$value};";
            }, '');

            foreach ($extDefinedVars as $name => $value) {
                $customLess .= "@$name: {$value()};";
            }

            return $customLess;
        });
    }
}
