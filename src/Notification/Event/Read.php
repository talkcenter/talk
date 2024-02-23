<?php

namespace Talk\Notification\Event;

use DateTime;
use Talk\Notification\Notification;
use Talk\User\User;

class Read
{
    /**
     * @var User
     */
    public $actor;

    /**
     * @var Notification
     */
    public $notification;

    /**
     * @var DateTime
     */
    public $timestamp;

    public function __construct(User $user, Notification $notification, DateTime $timestamp)
    {
        $this->actor = $user;
        $this->notification = $notification;
        $this->timestamp = $timestamp;
    }
}
