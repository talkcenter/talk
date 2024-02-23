<?php

namespace Talk\Discussion\Event;

use Talk\Discussion\Discussion;
use Talk\User\User;

class Deleted
{
    /**
     * @var \Talk\Discussion\Discussion
     */
    public $discussion;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param \Talk\Discussion\Discussion $discussion
     * @param User $actor
     */
    public function __construct(Discussion $discussion, User $actor = null)
    {
        $this->discussion = $discussion;
        $this->actor = $actor;
    }
}
