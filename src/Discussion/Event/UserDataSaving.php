<?php

namespace Talk\Discussion\Event;

use Talk\Discussion\UserState;

class UserDataSaving
{
    /**
     * @var \Talk\Discussion\UserState
     */
    public $state;

    /**
     * @param \Talk\Discussion\UserState $state
     */
    public function __construct(UserState $state)
    {
        $this->state = $state;
    }
}
