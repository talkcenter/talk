<?php

namespace Talk\Http\Exception;

use Exception;
use Talk\Foundation\KnownError;

class TokenMismatchException extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'csrf_token_mismatch';
    }
}
