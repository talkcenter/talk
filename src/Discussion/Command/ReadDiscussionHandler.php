<?php

namespace Talk\Discussion\Command;

use Talk\Discussion\DiscussionRepository;
use Talk\Discussion\Event\UserDataSaving;
use Talk\Foundation\DispatchEventsTrait;
use Illuminate\Contracts\Events\Dispatcher;

class ReadDiscussionHandler
{
    use DispatchEventsTrait;

    /**
     * @var DiscussionRepository
     */
    protected $discussions;

    /**
     * @param Dispatcher $events
     * @param DiscussionRepository $discussions
     */
    public function __construct(Dispatcher $events, DiscussionRepository $discussions)
    {
        $this->events = $events;
        $this->discussions = $discussions;
    }

    /**
     * @param ReadDiscussion $command
     * @return \Talk\Discussion\UserState
     * @throws \Talk\User\Exception\PermissionDeniedException
     */
    public function handle(ReadDiscussion $command)
    {
        $actor = $command->actor;

        $actor->assertRegistered();

        $discussion = $this->discussions->findOrFail($command->discussionId, $actor);

        $state = $discussion->stateFor($actor);
        $state->read($command->lastReadPostNumber);

        $this->events->dispatch(
            new UserDataSaving($state)
        );

        $state->save();

        $this->dispatchEventsFor($state);

        return $state;
    }
}
