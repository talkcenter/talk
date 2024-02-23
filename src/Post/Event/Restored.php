<?php

namespace Talk\Post\Event;

use Talk\Post\CommentPost;
use Talk\User\User;

class Restored
{
    /**
     * @var \Talk\Post\CommentPost
     */
    public $post;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param \Talk\Post\CommentPost $post
     */
    public function __construct(CommentPost $post, User $actor = null)
    {
        $this->post = $post;
        $this->actor = $actor;
    }
}
