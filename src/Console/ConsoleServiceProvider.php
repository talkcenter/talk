<?php

namespace Talk\Console;

use Talk\Console\Cache\Factory;
use Talk\Database\Console\MigrateCommand;
use Talk\Database\Console\ResetCommand;
use Talk\Extension\Console\ToggleExtensionCommand;
use Talk\Foundation\AbstractServiceProvider;
use Talk\Foundation\Console\AssetsPublishCommand;
use Talk\Foundation\Console\CacheClearCommand;
use Talk\Foundation\Console\InfoCommand;
use Talk\Foundation\Console\ScheduleRunCommand;
use Illuminate\Console\Scheduling\CacheEventMutex;
use Illuminate\Console\Scheduling\CacheSchedulingMutex;
use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Console\Scheduling\Schedule as LaravelSchedule;
use Illuminate\Console\Scheduling\ScheduleListCommand;
use Illuminate\Console\Scheduling\SchedulingMutex;
use Illuminate\Contracts\Container\Container;

class ConsoleServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        // Used by Laravel to proxy artisan commands to its binary.
        // Talk uses a similar binary, but it's called talk.
        if (! defined('ARTISAN_BINARY')) {
            define('ARTISAN_BINARY', 'talk');
        }

        // Talk doesn't fully use Laravel's cache system, but rather
        // creates and binds a single cache store.
        // See \Talk\Foundation\InstalledSite::registerCache
        // Since certain config options (e.g. withoutOverlapping, onOneServer)
        // need the cache, we must override the cache factory we give to the scheduling
        // mutexes so it returns our single custom cache.
        $this->container->bind(EventMutex::class, function ($container) {
            return new CacheEventMutex($container->make(Factory::class));
        });
        $this->container->bind(SchedulingMutex::class, function ($container) {
            return new CacheSchedulingMutex($container->make(Factory::class));
        });

        $this->container->singleton(LaravelSchedule::class, function (Container $container) {
            return $container->make(Schedule::class);
        });

        $this->container->singleton('talk.console.commands', function () {
            return [
                AssetsPublishCommand::class,
                CacheClearCommand::class,
                InfoCommand::class,
                MigrateCommand::class,
                ResetCommand::class,
                ScheduleListCommand::class,
                ScheduleRunCommand::class,
                ToggleExtensionCommand::class
                // Used internally to create DB dumps before major releases.
                // \Talk\Database\Console\GenerateDumpCommand::class
            ];
        });

        $this->container->singleton('talk.console.scheduled', function () {
            return [];
        });
    }

    /**
     * {@inheritDoc}
     */
    public function boot(Container $container)
    {
        $schedule = $container->make(LaravelSchedule::class);

        foreach ($container->make('talk.console.scheduled') as $scheduled) {
            $event = $schedule->command($scheduled['command'], $scheduled['args']);
            $scheduled['callback']($event);
        }
    }
}
