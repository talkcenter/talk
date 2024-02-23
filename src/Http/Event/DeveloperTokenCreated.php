<?php

namespace Talk\Http\Event;

use Talk\Http\AccessToken;

class DeveloperTokenCreated
{
    /**
     * @var AccessToken
     */
    public $token;

    public function __construct(AccessToken $token)
    {
        $this->token = $token;
    }
}
