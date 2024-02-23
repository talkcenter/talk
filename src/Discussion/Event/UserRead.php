<?php

namespace Talk\Discussion\Event;

use Talk\Discussion\UserState;
use Talk\User\User;

class UserRead
{
    /**
     * @var UserState
     */
    public $state;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param UserState $state
     */
    public function __construct(UserState $state)
    {
        $this->state = $state;
    }
}
