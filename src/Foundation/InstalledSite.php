<?php

namespace Talk\Foundation;

use Talk\Admin\AdminServiceProvider;
use Talk\Api\ApiServiceProvider;
use Talk\Bus\BusServiceProvider;
use Talk\Console\ConsoleServiceProvider;
use Talk\Database\DatabaseServiceProvider;
use Talk\Discussion\DiscussionServiceProvider;
use Talk\Extension\ExtensionServiceProvider;
use Talk\Filesystem\FilesystemServiceProvider;
use Talk\Filter\FilterServiceProvider;
use Talk\Formatter\FormatterServiceProvider;
use Talk\Site\SiteServiceProvider;
use Talk\Frontend\FrontendServiceProvider;
use Talk\Group\GroupServiceProvider;
use Talk\Http\HttpServiceProvider;
use Talk\Locale\LocaleServiceProvider;
use Talk\Mail\MailServiceProvider;
use Talk\Notification\NotificationServiceProvider;
use Talk\Post\PostServiceProvider;
use Talk\Queue\QueueServiceProvider;
use Talk\Search\SearchServiceProvider;
use Talk\Settings\SettingsServiceProvider;
use Talk\Update\UpdateServiceProvider;
use Talk\User\SessionServiceProvider;
use Talk\User\UserServiceProvider;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Hashing\HashServiceProvider;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\View\ViewServiceProvider;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class InstalledSite implements SiteInterface
{
    /**
     * @var Paths
     */
    protected $paths;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var \Talk\Extend\ExtenderInterface[]
     */
    protected $extenders = [];

    public function __construct(Paths $paths, Config $config)
    {
        $this->paths = $paths;
        $this->config = $config;
    }

    /**
     * 创建并引导 Talkcenter 应用程序实例。
     *
     * @return InstalledApp
     */
    public function bootApp(): AppInterface
    {
        return new InstalledApp(
            $this->bootLaravel(),
            $this->config
        );
    }

    /**
     * @param \Talk\Extend\ExtenderInterface[] $extenders
     * @return InstalledSite
     */
    public function extendWith(array $extenders): self
    {
        $this->extenders = $extenders;

        return $this;
    }

    protected function bootLaravel(): Container
    {
        $container = new \Illuminate\Container\Container;
        $laravel = new Application($container, $this->paths);

        $container->instance('env', 'production');
        $container->instance('talk.config', $this->config);
        $container->alias('talk.config', Config::class);
        $container->instance('talk.debug', $this->config->inDebugMode());
        $container->instance('config', $config = $this->getIlluminateConfig());
        $container->instance('talk.maintenance.handler', new MaintenanceModeHandler);

        $this->registerLogger($container);
        $this->registerCache($container);

        $laravel->register(AdminServiceProvider::class);
        $laravel->register(ApiServiceProvider::class);
        $laravel->register(BusServiceProvider::class);
        $laravel->register(ConsoleServiceProvider::class);
        $laravel->register(DatabaseServiceProvider::class);
        $laravel->register(DiscussionServiceProvider::class);
        $laravel->register(ExtensionServiceProvider::class);
        $laravel->register(ErrorServiceProvider::class);
        $laravel->register(FilesystemServiceProvider::class);
        $laravel->register(FilterServiceProvider::class);
        $laravel->register(FormatterServiceProvider::class);
        $laravel->register(SiteServiceProvider::class);
        $laravel->register(FrontendServiceProvider::class);
        $laravel->register(GroupServiceProvider::class);
        $laravel->register(HashServiceProvider::class);
        $laravel->register(HttpServiceProvider::class);
        $laravel->register(LocaleServiceProvider::class);
        $laravel->register(MailServiceProvider::class);
        $laravel->register(NotificationServiceProvider::class);
        $laravel->register(PostServiceProvider::class);
        $laravel->register(QueueServiceProvider::class);
        $laravel->register(SearchServiceProvider::class);
        $laravel->register(SessionServiceProvider::class);
        $laravel->register(SettingsServiceProvider::class);
        $laravel->register(UpdateServiceProvider::class);
        $laravel->register(UserServiceProvider::class);
        $laravel->register(ValidationServiceProvider::class);
        $laravel->register(ViewServiceProvider::class);

        $laravel->booting(function () use ($container) {
            // Run all local-site extenders before booting service providers
            // (but after those from "real" extensions, which have been set up
            // in a service provider above).
            foreach ($this->extenders as $extension) {
                $extension->extend($container);
            }
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
            'app' => [
                'timezone' => 'UTC'
            ],
            'view' => [
                'paths' => [],
                'compiled' => $this->paths->storage.'/views',
            ],
            'session' => [
                'lifetime' => 120,
                'files' => $this->paths->storage.'/sessions',
                'cookie' => 'session'
            ]
        ]);
    }

    protected function registerLogger(Container $container)
    {
        $logPath = $this->paths->storage.'/logs/talk.log';
        $logLevel = $this->config->inDebugMode() ? Logger::DEBUG : Logger::INFO;
        $handler = new RotatingFileHandler($logPath, 0, $logLevel);
        $handler->setFormatter(new LineFormatter(null, null, true, true));

        $container->instance('log', new Logger('talk', [$handler]));
        $container->alias('log', LoggerInterface::class);
    }

    protected function registerCache(Container $container)
    {
        $container->singleton('cache.store', function ($container) {
            return new CacheRepository($container->make('cache.filestore'));
        });
        $container->alias('cache.store', Repository::class);

        $container->singleton('cache.filestore', function () {
            return new FileStore(new Filesystem, $this->paths->storage.'/cache');
        });
        $container->alias('cache.filestore', Store::class);
    }
}
