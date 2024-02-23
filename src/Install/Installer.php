<?php

namespace Talk\Install;

use Talk\Foundation\AppInterface;
use Talk\Foundation\ErrorHandling\Registry;
use Talk\Foundation\ErrorHandling\Reporter;
use Talk\Foundation\ErrorHandling\WhoopsFormatter;
use Talk\Http\Middleware as HttpMiddleware;
use Talk\Install\Console\InstallCommand;
use Illuminate\Contracts\Container\Container;
use Laminas\Stratigility\MiddlewarePipe;

class Installer implements AppInterface
{
    /**
     * @var Container
     */
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return \Psr\Http\Server\RequestHandlerInterface
     */
    public function getRequestHandler()
    {
        $pipe = new MiddlewarePipe;
        $pipe->pipe(new HttpMiddleware\HandleErrors(
            $this->container->make(Registry::class),
            $this->container->make(WhoopsFormatter::class),
            $this->container->tagged(Reporter::class)
        ));
        $pipe->pipe($this->container->make(HttpMiddleware\StartSession::class));
        $pipe->pipe(
            new HttpMiddleware\ResolveRoute($this->container->make('talk.install.routes'))
        );
        $pipe->pipe(new HttpMiddleware\ExecuteRoute());

        return $pipe;
    }

    /**
     * @return \Symfony\Component\Console\Command\Command[]
     */
    public function getConsoleCommands()
    {
        return [
            new InstallCommand(
                $this->container->make(Installation::class)
            ),
        ];
    }
}
