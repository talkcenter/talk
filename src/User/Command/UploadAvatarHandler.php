<?php

namespace Talk\User\Command;

use Talk\Foundation\DispatchEventsTrait;
use Talk\User\AvatarUploader;
use Talk\User\AvatarValidator;
use Talk\User\Event\AvatarSaving;
use Talk\User\UserRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Intervention\Image\ImageManager;

class UploadAvatarHandler
{
    use DispatchEventsTrait;

    /**
     * @var \Talk\User\UserRepository
     */
    protected $users;

    /**
     * @var AvatarUploader
     */
    protected $uploader;

    /**
     * @var \Talk\User\AvatarValidator
     */
    protected $validator;

    /**
     * @var ImageManager
     */
    protected $imageManager;

    /**
     * @param Dispatcher $events
     * @param UserRepository $users
     * @param AvatarUploader $uploader
     * @param AvatarValidator $validator
     */
    public function __construct(Dispatcher $events, UserRepository $users, AvatarUploader $uploader, AvatarValidator $validator, ImageManager $imageManager)
    {
        $this->events = $events;
        $this->users = $users;
        $this->uploader = $uploader;
        $this->validator = $validator;
        $this->imageManager = $imageManager;
    }

    /**
     * @param UploadAvatar $command
     * @return \Talk\User\User
     * @throws \Talk\User\Exception\PermissionDeniedException
     * @throws \Talk\Foundation\ValidationException
     */
    public function handle(UploadAvatar $command)
    {
        $actor = $command->actor;

        $user = $this->users->findOrFail($command->userId);

        $actor->assertCan('uploadAvatar', $user);

        $this->validator->assertValid(['avatar' => $command->file]);

        $image = $this->imageManager->make($command->file->getStream()->getMetadata('uri'));

        $this->events->dispatch(
            new AvatarSaving($user, $actor, $image)
        );

        $this->uploader->upload($user, $image);

        $user->save();

        $this->dispatchEventsFor($user, $actor);

        return $user;
    }
}
