<?php

namespace Talk\Notification\Job;

use Talk\Notification\Blueprint\BlueprintInterface;
use Talk\Notification\Notification;
use Talk\Queue\AbstractJob;
use Talk\User\User;

class SendNotificationsJob extends AbstractJob
{
    /**
     * @var BlueprintInterface
     */
    private $blueprint;

    /**
     * @var User[]
     */
    private $recipients;

    public function __construct(BlueprintInterface $blueprint, array $recipients = [])
    {
        $this->blueprint = $blueprint;
        $this->recipients = $recipients;
    }

    public function handle()
    {
        Notification::notify($this->recipients, $this->blueprint);
    }
}
