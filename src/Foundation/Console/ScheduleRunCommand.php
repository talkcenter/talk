<?php

namespace Talk\Foundation\Console;

use Talk\Settings\SettingsRepositoryInterface;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;

class ScheduleRunCommand extends \Illuminate\Console\Scheduling\ScheduleRunCommand
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * {@inheritdoc}
     */
    public function __construct(SettingsRepositoryInterface $settings)
    {
        parent::__construct();

        $this->settings = $settings;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Schedule $schedule, Dispatcher $dispatcher, ExceptionHandler $handler)
    {
        parent::handle($schedule, $dispatcher, $handler);

        $this->settings->set('schedule.last_run', $this->startedAt);
    }
}
