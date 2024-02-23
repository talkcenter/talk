<?php

namespace Talk\Notification\Event;

use DateTime;
use Talk\User\User;

class DeletedAll
{
    /**
     * @var User
     */
    public $actor;

    /**
     * @var DateTime
     */
    public $timestamp;

    public function __construct(User $user, DateTime $timestamp)
    {
        $this->actor = $user;
        $this->timestamp = $timestamp;
    }
}
