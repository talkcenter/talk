<?php

namespace Talk\Notification\Command;

use Talk\Notification\Event\ReadAll;
use Talk\Notification\NotificationRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;

class ReadAllNotificationsHandler
{
    /**
     * @var NotificationRepository
     */
    protected $notifications;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @param NotificationRepository $notifications
     * @param Dispatcher $events
     */
    public function __construct(NotificationRepository $notifications, Dispatcher $events)
    {
        $this->notifications = $notifications;
        $this->events = $events;
    }

    /**
     * @param ReadAllNotifications $command
     * @throws \Talk\User\Exception\PermissionDeniedException
     */
    public function handle(ReadAllNotifications $command)
    {
        $actor = $command->actor;

        $actor->assertRegistered();

        $this->notifications->markAllAsRead($actor);

        $this->events->dispatch(new ReadAll($actor, Carbon::now()));
    }
}
