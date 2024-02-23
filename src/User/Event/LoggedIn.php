<?php

namespace Talk\User\Event;

use Talk\Http\AccessToken;
use Talk\User\User;

class LoggedIn
{
    public $user;

    public $token;

    public function __construct(User $user, AccessToken $token)
    {
        $this->user = $user;
        $this->token = $token;
    }
}
