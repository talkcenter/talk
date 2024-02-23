<?php

namespace Talk\Filesystem;

use Talk\Foundation\AbstractServiceProvider;
use Talk\Foundation\Config;
use Talk\Foundation\Paths;
use Talk\Http\UrlGenerator;
use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Intervention\Image\ImageManager;
use RuntimeException;

class FilesystemServiceProvider extends AbstractServiceProvider
{
    protected const INTERVENTION_DRIVERS = ['gd' => 'gd', 'imagick' => 'imagick'];

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->singleton('files', function () {
            return new Filesystem;
        });

        $this->container->singleton('talk.filesystem.disks', function () {
            return [
                'talk-assets' => function (Paths $paths, UrlGenerator $url) {
                    return [
                        'root'   => "$paths->public/assets",
                        'url'    => $url->to('site')->path('assets')
                    ];
                },
                'talk-avatars' => function (Paths $paths, UrlGenerator $url) {
                    return [
                        'root'   => "$paths->public/assets/avatars",
                        'url'    => $url->to('site')->path('assets/avatars')
                    ];
                },
            ];
        });

        $this->container->singleton('talk.filesystem.drivers', function () {
            return [];
        });

        $this->container->singleton('talk.filesystem.resolved_drivers', function (Container $container) {
            return array_map(function ($driverClass) use ($container) {
                return $container->make($driverClass);
            }, $container->make('talk.filesystem.drivers'));
        });

        $this->container->singleton('filesystem', function (Container $container) {
            return new FilesystemManager(
                $container,
                $container->make('talk.filesystem.disks'),
                $container->make('talk.filesystem.resolved_drivers')
            );
        });

        $this->container->singleton(ImageManager::class, function (Container $container) {
            /** @var Config $config */
            $config = $this->container->make(Config::class);

            $intervention = $config->offsetGet('intervention');
            $driver = Arr::get($intervention, 'driver', self::INTERVENTION_DRIVERS['gd']);

            // Check that the imagick library is actually available, else default back to gd.
            if ($driver === self::INTERVENTION_DRIVERS['imagick'] && ! extension_loaded(self::INTERVENTION_DRIVERS['imagick'])) {
                $driver = self::INTERVENTION_DRIVERS['gd'];
            }

            if (! Arr::has(self::INTERVENTION_DRIVERS, $driver)) {
                throw new RuntimeException("intervention/image: $driver is not valid");
            }

            return new ImageManager([
                'driver' => $driver
            ]);
        });
    }
}
