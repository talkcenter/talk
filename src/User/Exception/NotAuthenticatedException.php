<?php

namespace Talk\User\Exception;

use Exception;
use Talk\Foundation\KnownError;

class NotAuthenticatedException extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'not_authenticated';
    }
}
