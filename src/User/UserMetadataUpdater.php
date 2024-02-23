<?php

namespace Talk\User;

use Talk\Discussion\Discussion;
use Talk\Discussion\Event\Deleted as DiscussionDeleted;
use Talk\Discussion\Event\Started;
use Talk\Post\Event\Deleted as PostDeleted;
use Talk\Post\Event\Posted;
use Illuminate\Contracts\Events\Dispatcher;

class UserMetadataUpdater
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(Posted::class, [$this, 'whenPostWasPosted']);
        $events->listen(PostDeleted::class, [$this, 'whenPostWasDeleted']);
        $events->listen(Started::class, [$this, 'whenDiscussionWasStarted']);
        $events->listen(DiscussionDeleted::class, [$this, 'whenDiscussionWasDeleted']);
    }

    /**
     * @param \Talk\Post\Event\Posted $event
     */
    public function whenPostWasPosted(Posted $event)
    {
        $this->updateCommentsCount($event->post->user);
    }

    /**
     * @param \Talk\Post\Event\Deleted $event
     */
    public function whenPostWasDeleted(PostDeleted $event)
    {
        $this->updateCommentsCount($event->post->user);
    }

    /**
     * @param \Talk\Discussion\Event\Started $event
     */
    public function whenDiscussionWasStarted(Started $event)
    {
        $this->updateDiscussionsCount($event->discussion);
    }

    /**
     * @param \Talk\Discussion\Event\Deleted $event
     */
    public function whenDiscussionWasDeleted(DiscussionDeleted $event)
    {
        $this->updateDiscussionsCount($event->discussion);
        $this->updateCommentsCount($event->discussion->user);
    }

    /**
     * @param \Talk\User\User $user
     */
    private function updateCommentsCount(?User $user)
    {
        if ($user && $user->exists) {
            $user->refreshCommentCount()->save();
        }
    }

    private function updateDiscussionsCount(Discussion $discussion)
    {
        $user = $discussion->user;

        if ($user && $user->exists) {
            $user->refreshDiscussionCount()->save();
        }
    }
}
