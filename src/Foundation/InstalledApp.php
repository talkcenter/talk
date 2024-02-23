<?php

namespace Talk\Foundation;

use Talk\Http\Middleware as HttpMiddleware;
use Talk\Settings\SettingsRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\Container;
use Laminas\Stratigility\Middleware\OriginalMessages;
use Laminas\Stratigility\MiddlewarePipe;
use Middlewares\BasePath;
use Middlewares\BasePathRouter;
use Middlewares\RequestHandler;

class InstalledApp implements AppInterface
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var Config
     */
    protected $config;

    public function __construct(Container $container, Config $config)
    {
        $this->container = $container;
        $this->config = $config;
    }

    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @return \Psr\Http\Server\RequestHandlerInterface
     */
    public function getRequestHandler()
    {
        if ($this->config->inMaintenanceMode()) {
            return $this->container->make('talk.maintenance.handler');
        } elseif ($this->needsUpdate()) {
            return $this->getUpdaterHandler();
        }

        $pipe = new MiddlewarePipe;

        $pipe->pipe(new HttpMiddleware\ProcessIp());
        $pipe->pipe(new BasePath($this->basePath()));
        $pipe->pipe(new OriginalMessages);
        $pipe->pipe(
            new BasePathRouter([
                $this->subPath('api') => 'talk.api.handler',
                $this->subPath('admin') => 'talk.admin.handler',
                '/' => 'talk.site.handler',
            ])
        );
        $pipe->pipe(new RequestHandler($this->container));

        return $pipe;
    }

    protected function needsUpdate(): bool
    {
        $settings = $this->container->make(SettingsRepositoryInterface::class);
        $version = $settings->get('version');

        return $version !== Application::VERSION;
    }

    /**
     * @return \Psr\Http\Server\RequestHandlerInterface
     */
    protected function getUpdaterHandler()
    {
        $pipe = new MiddlewarePipe;
        $pipe->pipe(new BasePath($this->basePath()));
        $pipe->pipe(
            new HttpMiddleware\ResolveRoute($this->container->make('talk.update.routes'))
        );
        $pipe->pipe(new HttpMiddleware\ExecuteRoute());

        return $pipe;
    }

    protected function basePath(): string
    {
        return $this->config->url()->getPath() ?: '/';
    }

    protected function subPath($pathName): string
    {
        return '/'.($this->config['paths'][$pathName] ?? $pathName);
    }

    /**
     * @return \Symfony\Component\Console\Command\Command[]
     */
    public function getConsoleCommands()
    {
        return array_map(function ($command) {
            $command = $this->container->make($command);

            if ($command instanceof Command) {
                $command->setLaravel($this->container);
            }

            return $command;
        }, $this->container->make('talk.console.commands'));
    }
}
