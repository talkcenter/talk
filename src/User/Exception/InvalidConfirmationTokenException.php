<?php

namespace Talk\User\Exception;

use Exception;
use Talk\Foundation\KnownError;

class InvalidConfirmationTokenException extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'invalid_confirmation_token';
    }
}
