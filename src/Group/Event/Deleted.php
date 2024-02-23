<?php

namespace Talk\Group\Event;

use Talk\Group\Group;
use Talk\User\User;

class Deleted
{
    /**
     * @var \Talk\Group\Group
     */
    public $group;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param \Talk\Group\Group $group
     * @param User $actor
     */
    public function __construct(Group $group, User $actor = null)
    {
        $this->group = $group;
        $this->actor = $actor;
    }
}
