<?php

namespace Talk\Post\Command;

use Talk\Foundation\DispatchEventsTrait;
use Talk\Post\Event\Deleting;
use Talk\Post\PostRepository;
use Illuminate\Contracts\Events\Dispatcher;

class DeletePostHandler
{
    use DispatchEventsTrait;

    /**
     * @var \Talk\Post\PostRepository
     */
    protected $posts;

    /**
     * @param Dispatcher $events
     * @param \Talk\Post\PostRepository $posts
     */
    public function __construct(Dispatcher $events, PostRepository $posts)
    {
        $this->events = $events;
        $this->posts = $posts;
    }

    /**
     * @param DeletePost $command
     * @return \Talk\Post\Post
     * @throws \Talk\User\Exception\PermissionDeniedException
     */
    public function handle(DeletePost $command)
    {
        $actor = $command->actor;

        $post = $this->posts->findOrFail($command->postId, $actor);

        $actor->assertCan('delete', $post);

        $this->events->dispatch(
            new Deleting($post, $actor, $command->data)
        );

        $post->delete();

        $this->dispatchEventsFor($post, $actor);

        return $post;
    }
}
