<?php

namespace Talk\Post\Event;

use Talk\Post\CommentPost;
use Talk\User\User;

class Hidden
{
    /**
     * @var CommentPost
     */
    public $post;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param CommentPost $post
     */
    public function __construct(CommentPost $post, User $actor = null)
    {
        $this->post = $post;
        $this->actor = $actor;
    }
}
