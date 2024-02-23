<?php

namespace Talk\Group\Event;

use Talk\Group\Group;
use Talk\User\User;

class Created
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
     * @param Group $group
     * @param User $actor
     */
    public function __construct(Group $group, User $actor = null)
    {
        $this->group = $group;
        $this->actor = $actor;
    }
}
