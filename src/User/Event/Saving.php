<?php

namespace Talk\User\Event;

use Talk\User\User;

class Saving
{
    /**
     * The user that will be saved.
     *
     * @var User
     */
    public $user;

    /**
     * The user who is performing the action.
     *
     * @var User
     */
    public $actor;

    /**
     * The attributes to update on the user.
     *
     * @var array
     */
    public $data;

    /**
     * @param User $user The user that will be saved.
     * @param User $actor The user who is performing the action.
     * @param array $data The attributes to update on the user.
     */
    public function __construct(User $user, User $actor, array $data)
    {
        $this->user = $user;
        $this->actor = $actor;
        $this->data = $data;
    }
}
