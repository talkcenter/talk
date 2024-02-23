<?php

namespace Talk\Post\Command;

use Talk\Foundation\DispatchEventsTrait;
use Talk\Post\CommentPost;
use Talk\Post\Event\Saving;
use Talk\Post\PostRepository;
use Talk\Post\PostValidator;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;

class EditPostHandler
{
    use DispatchEventsTrait;

    /**
     * @var \Talk\Post\PostRepository
     */
    protected $posts;

    /**
     * @var \Talk\Post\PostValidator
     */
    protected $validator;

    /**
     * @param Dispatcher $events
     * @param PostRepository $posts
     * @param \Talk\Post\PostValidator $validator
     */
    public function __construct(Dispatcher $events, PostRepository $posts, PostValidator $validator)
    {
        $this->events = $events;
        $this->posts = $posts;
        $this->validator = $validator;
    }

    /**
     * @param EditPost $command
     * @return \Talk\Post\Post
     * @throws \Talk\User\Exception\PermissionDeniedException
     */
    public function handle(EditPost $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        $post = $this->posts->findOrFail($command->postId, $actor);

        if ($post instanceof CommentPost) {
            $attributes = Arr::get($data, 'attributes', []);

            if (isset($attributes['content'])) {
                $actor->assertCan('edit', $post);

                $post->revise($attributes['content'], $actor);
            }

            if (isset($attributes['isHidden'])) {
                $actor->assertCan('hide', $post);

                if ($attributes['isHidden']) {
                    $post->hide($actor);
                } else {
                    $post->restore();
                }
            }
        }

        $this->events->dispatch(
            new Saving($post, $actor, $data)
        );

        $this->validator->assertValid($post->getDirty());

        $post->save();

        $this->dispatchEventsFor($post, $actor);

        return $post;
    }
}
