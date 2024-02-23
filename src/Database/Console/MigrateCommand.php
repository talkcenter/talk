<?php

namespace Talk\Database\Console;

use Talk\Console\AbstractCommand;
use Talk\Database\Migrator;
use Talk\Extension\ExtensionManager;
use Talk\Foundation\Application;
use Talk\Foundation\Paths;
use Talk\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Schema\Builder;

class MigrateCommand extends AbstractCommand
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var Paths
     */
    protected $paths;

    /**
     * @param Container $container
     * @param Paths $paths
     */
    public function __construct(Container $container, Paths $paths)
    {
        $this->container = $container;
        $this->paths = $paths;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('migrate')
            ->setDescription('Run outstanding migrations');
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $this->info('Migrating Talk...');

        $this->upgrade();

        $this->info('DONE.');
    }

    public function upgrade()
    {
        $this->container->bind(Builder::class, function ($container) {
            return $container->make(ConnectionInterface::class)->getSchemaBuilder();
        });

        $migrator = $this->container->make(Migrator::class);
        $migrator->setOutput($this->output);

        $migrator->run(__DIR__.'/../../../migrations');

        $extensions = $this->container->make(ExtensionManager::class);
        $extensions->getMigrator()->setOutput($this->output);

        foreach ($extensions->getEnabledExtensions() as $name => $extension) {
            if ($extension->hasMigrations()) {
                $this->info('Migrating extension: '.$name);

                $extensions->migrate($extension);
            }
        }

        $this->container->make(SettingsRepositoryInterface::class)->set('version', Application::VERSION);
    }
}
