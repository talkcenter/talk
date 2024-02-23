<?php

namespace Talk\Http\Exception;

use Exception;
use Talk\Foundation\KnownError;

class MethodNotAllowedException extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'method_not_allowed';
    }
}
