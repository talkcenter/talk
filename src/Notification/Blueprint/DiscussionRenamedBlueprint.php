<?php

namespace Talk\Notification\Blueprint;

use Talk\Discussion\Discussion;
use Talk\Post\DiscussionRenamedPost;

class DiscussionRenamedBlueprint implements BlueprintInterface
{
    /**
     * @var \Talk\Post\DiscussionRenamedPost
     */
    protected $post;

    /**
     * @param DiscussionRenamedPost $post
     */
    public function __construct(DiscussionRenamedPost $post)
    {
        $this->post = $post;
    }

    /**
     * {@inheritdoc}
     */
    public function getFromUser()
    {
        return $this->post->user;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return $this->post->discussion;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return ['postNumber' => (int) $this->post->number];
    }

    /**
     * {@inheritdoc}
     */
    public static function getType()
    {
        return 'discussionRenamed';
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubjectModel()
    {
        return Discussion::class;
    }
}
