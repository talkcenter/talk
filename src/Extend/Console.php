<?php

namespace Talk\Extend;

use Talk\Extension\Extension;
use Talk\Foundation\ContainerUtil;
use Illuminate\Contracts\Container\Container;

class Console implements ExtenderInterface
{
    protected $addCommands = [];
    protected $scheduled = [];

    /**
     * Add a command to the console.
     *
     * @param string $command: ::class attribute of command class, which must extend Talk\Console\AbstractCommand.
     * @return self
     */
    public function command(string $command): self
    {
        $this->addCommands[] = $command;

        return $this;
    }

    /**
     * Schedule a command to run on an interval.
     *
     * @param string $command: ::class attribute of command class, which must extend Talk\Console\AbstractCommand.
     * @param callable|string $callback
     *
     * The callback can be a closure or invokable class, and should accept:
     * - \Illuminate\Console\Scheduling\Event $event
     *
     * The callback should apply relevant methods to $event, and does not need to return anything.
     *
     * @see https://laravel.com/api/8.x/Illuminate/Console/Scheduling/Event.html
     * @see https://laravel.com/docs/8.x/scheduling#schedule-frequency-options
     * for more information on available methods and what they do.
     *
     * @param array $args An array of args to call the command with.
     * @return self
     */
    public function schedule(string $command, $callback, $args = []): self
    {
        $this->scheduled[] = compact('args', 'callback', 'command');

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('talk.console.commands', function ($existingCommands) {
            return array_merge($existingCommands, $this->addCommands);
        });

        $container->extend('talk.console.scheduled', function ($existingScheduled) use ($container) {
            foreach ($this->scheduled as &$schedule) {
                $schedule['callback'] = ContainerUtil::wrapCallback($schedule['callback'], $container);
            }

            return array_merge($existingScheduled, $this->scheduled);
        });
    }
}
