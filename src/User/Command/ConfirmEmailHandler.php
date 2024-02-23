<?php

namespace Talk\User\Command;

use Talk\Foundation\DispatchEventsTrait;
use Talk\User\EmailToken;
use Talk\User\UserRepository;
use Illuminate\Contracts\Events\Dispatcher;

class ConfirmEmailHandler
{
    use DispatchEventsTrait;

    /**
     * @var \Talk\User\UserRepository
     */
    protected $users;

    /**
     * @param \Talk\User\UserRepository $users
     */
    public function __construct(Dispatcher $events, UserRepository $users)
    {
        $this->events = $events;
        $this->users = $users;
    }

    /**
     * @param ConfirmEmail $command
     * @return \Talk\User\User
     */
    public function handle(ConfirmEmail $command)
    {
        /** @var EmailToken $token */
        $token = EmailToken::validOrFail($command->token);

        $user = $token->user;
        $user->changeEmail($token->email);

        $user->activate();

        $user->save();
        $this->dispatchEventsFor($user);

        // Delete *all* tokens for the user, in case other ones were sent first
        $user->emailTokens()->delete();

        return $user;
    }
}
