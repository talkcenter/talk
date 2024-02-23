<?php

namespace Talk\Console;

use Talk\Foundation\ErrorHandling\Registry;
use Talk\Foundation\ErrorHandling\Reporter;
use Talk\Foundation\SiteInterface;
use Illuminate\Container\Container;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Server
{
    private $site;

    public function __construct(SiteInterface $site)
    {
        $this->site = $site;
    }

    public function listen()
    {
        $app = $this->site->bootApp();

        $console = new Application('Talk', \Talk\Foundation\Application::VERSION);

        foreach ($app->getConsoleCommands() as $command) {
            $console->add($command);
        }

        $this->handleErrors($console);

        exit($console->run());
    }

    private function handleErrors(Application $console)
    {
        $dispatcher = new EventDispatcher();

        $dispatcher->addListener(ConsoleEvents::ERROR, function (ConsoleErrorEvent $event) {
            $container = Container::getInstance();

            /** @var Registry $registry */
            $registry = $container->make(Registry::class);
            $error = $registry->handle($event->getError());

            /** @var Reporter[] $reporters */
            $reporters = $container->tagged(Reporter::class);

            if ($error->shouldBeReported()) {
                foreach ($reporters as $reporter) {
                    $reporter->report($error->getException());
                }
            }
        });

        $console->setDispatcher($dispatcher);
    }
}
