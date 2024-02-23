<?php

namespace Talk\User\Command;

use Talk\Foundation\DispatchEventsTrait;
use Talk\User\Event\Deleting;
use Talk\User\Exception\PermissionDeniedException;
use Talk\User\UserRepository;
use Illuminate\Contracts\Events\Dispatcher;

class DeleteUserHandler
{
    use DispatchEventsTrait;

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @param Dispatcher $events
     * @param UserRepository $users
     */
    public function __construct(Dispatcher $events, UserRepository $users)
    {
        $this->events = $events;
        $this->users = $users;
    }

    /**
     * @param DeleteUser $command
     * @return \Talk\User\User
     * @throws PermissionDeniedException
     */
    public function handle(DeleteUser $command)
    {
        $actor = $command->actor;
        $user = $this->users->findOrFail($command->userId, $actor);

        $actor->assertCan('delete', $user);

        $this->events->dispatch(
            new Deleting($user, $actor, $command->data)
        );

        $user->delete();

        $this->dispatchEventsFor($user, $actor);

        return $user;
    }
}
