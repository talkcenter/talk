<?php

namespace Talk\Http;

use Talk\Discussion\Discussion;
use Talk\Discussion\IdWithTransliteratedSlugDriver;
use Talk\Discussion\Utf8SlugDriver;
use Talk\Foundation\AbstractServiceProvider;
use Talk\Http\Access\ScopeAccessTokenVisibility;
use Talk\Settings\SettingsRepositoryInterface;
use Talk\User\IdSlugDriver;
use Talk\User\User;
use Talk\User\UsernameSlugDriver;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;

class HttpServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->singleton('talk.http.csrfExemptPaths', function () {
            return ['token'];
        });

        $this->container->bind(Middleware\CheckCsrfToken::class, function (Container $container) {
            return new Middleware\CheckCsrfToken($container->make('talk.http.csrfExemptPaths'));
        });

        $this->container->singleton('talk.http.slugDrivers', function () {
            return [
                Discussion::class => [
                    'default' => IdWithTransliteratedSlugDriver::class,
                    'utf8' => Utf8SlugDriver::class,
                ],
                User::class => [
                    'default' => UsernameSlugDriver::class,
                    'id' => IdSlugDriver::class
                ],
            ];
        });

        $this->container->singleton('talk.http.selectedSlugDrivers', function (Container $container) {
            $settings = $container->make(SettingsRepositoryInterface::class);

            $compiledDrivers = [];

            foreach ($container->make('talk.http.slugDrivers') as $resourceClass => $resourceDrivers) {
                $driverKey = $settings->get("slug_driver_$resourceClass", 'default');

                $driverClass = Arr::get($resourceDrivers, $driverKey, $resourceDrivers['default']);

                $compiledDrivers[$resourceClass] = $container->make($driverClass);
            }

            return $compiledDrivers;
        });
        $this->container->bind(SlugManager::class, function (Container $container) {
            return new SlugManager($container->make('talk.http.selectedSlugDrivers'));
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->setAccessTokenTypes();

        AccessToken::registerVisibilityScoper(new ScopeAccessTokenVisibility(), 'view');
    }

    protected function setAccessTokenTypes()
    {
        $models = [
            DeveloperAccessToken::class,
            RememberAccessToken::class,
            SessionAccessToken::class
        ];

        foreach ($models as $model) {
            AccessToken::setModel($model::$type, $model);
        }
    }
}
