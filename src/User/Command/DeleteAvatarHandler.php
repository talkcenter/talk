<?php

namespace Talk\User\Command;

use Talk\Foundation\DispatchEventsTrait;
use Talk\User\AvatarUploader;
use Talk\User\Event\AvatarDeleting;
use Talk\User\UserRepository;
use Illuminate\Contracts\Events\Dispatcher;

class DeleteAvatarHandler
{
    use DispatchEventsTrait;

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @var AvatarUploader
     */
    protected $uploader;

    /**
     * @param Dispatcher $events
     * @param UserRepository $users
     * @param AvatarUploader $uploader
     */
    public function __construct(Dispatcher $events, UserRepository $users, AvatarUploader $uploader)
    {
        $this->events = $events;
        $this->users = $users;
        $this->uploader = $uploader;
    }

    /**
     * @param DeleteAvatar $command
     * @return \Talk\User\User
     * @throws \Talk\User\Exception\PermissionDeniedException
     */
    public function handle(DeleteAvatar $command)
    {
        $actor = $command->actor;

        $user = $this->users->findOrFail($command->userId);

        if ($actor->id !== $user->id) {
            $actor->assertCan('edit', $user);
        }

        $this->uploader->remove($user);

        $this->events->dispatch(
            new AvatarDeleting($user, $actor)
        );

        $user->save();

        $this->dispatchEventsFor($user, $actor);

        return $user;
    }
}
