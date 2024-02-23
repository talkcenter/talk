<?php

namespace Talk\User\Event;

use Talk\User\User;

class LoggedOut
{
    /**
     * @var User
     */
    public $user;

    /**
     * @var bool
     */
    public $isGlobal;

    public function __construct(User $user, bool $isGlobal = false)
    {
        $this->user = $user;
        $this->isGlobal = $isGlobal;
    }
}
