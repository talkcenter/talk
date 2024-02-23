<?php

namespace Talk\Post\Event;

use Talk\Post\Post;
use Talk\User\User;

class Deleted
{
    /**
     * @var \Talk\Post\Post
     */
    public $post;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param \Talk\Post\Post $post
     */
    public function __construct(Post $post, User $actor = null)
    {
        $this->post = $post;
        $this->actor = $actor;
    }
}
