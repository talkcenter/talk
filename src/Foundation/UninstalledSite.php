<?php

namespace Talk\Foundation;

use Talk\Install\Installer;
use Talk\Install\InstallServiceProvider;
use Talk\Locale\LocaleServiceProvider;
use Talk\Settings\SettingsRepositoryInterface;
use Talk\Settings\UninstalledSettingsRepository;
use Talk\User\SessionServiceProvider;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\FileViewFinder;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class UninstalledSite implements SiteInterface
{
    /**
     * @var Paths
     */
    protected $paths;

    /**
     * @var string
     */
    private $baseUrl;

    public function __construct(Paths $paths, string $baseUrl)
    {
        $this->paths = $paths;
        $this->baseUrl = $baseUrl;
    }

    /**
     * 创建并引导 Talkcenter 应用程序实例。
     *
     * @return AppInterface
     */
    public function bootApp(): AppInterface
    {
        return new Installer(
            $this->bootLaravel()
        );
    }

    protected function bootLaravel(): Container
    {
        $container = new \Illuminate\Container\Container;
        $laravel = new Application($container, $this->paths);

        $container->instance('env', 'production');
        $container->instance('talk.config', new Config(['url' => $this->baseUrl]));
        $container->alias('talk.config', Config::class);
        $container->instance('talk.debug', true);
        $container->instance('config', $config = $this->getIlluminateConfig());

        $this->registerLogger($container);

        $laravel->register(ErrorServiceProvider::class);
        $laravel->register(LocaleServiceProvider::class);
        $laravel->register(FilesystemServiceProvider::class);
        $laravel->register(SessionServiceProvider::class);
        $laravel->register(ValidationServiceProvider::class);

        $laravel->register(InstallServiceProvider::class);

        $container->singleton(
            SettingsRepositoryInterface::class,
            UninstalledSettingsRepository::class
        );

        $container->singleton('view', function ($container) {
            $engines = new EngineResolver();
            $engines->register('php', function () use ($container) {
                return $container->make(PhpEngine::class);
            });
            $finder = new FileViewFinder($container->make('files'), []);
            $dispatcher = $container->make(Dispatcher::class);

            return new \Illuminate\View\Factory(
                $engines,
                $finder,
                $dispatcher
            );
        });

        $laravel->boot();

        return $container;
    }

    /**
     * @return ConfigRepository
     */
    protected function getIlluminateConfig()
    {
        return new ConfigRepository([
            'session' => [
                'lifetime' => 120,
                'files' => $this->paths->storage.'/sessions',
                'cookie' => 'session'
            ],
            'view' => [
                'paths' => [],
            ],
        ]);
    }

    protected function registerLogger(Container $container)
    {
        $logPath = $this->paths->storage.'/logs/talk-installer.log';
        $handler = new StreamHandler($logPath, Logger::DEBUG);
        $handler->setFormatter(new LineFormatter(null, null, true, true));

        $container->instance('log', new Logger('Talk Installer', [$handler]));
        $container->alias('log', LoggerInterface::class);
    }
}
