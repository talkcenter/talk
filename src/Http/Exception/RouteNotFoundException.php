<?php

namespace Talk\Http\Exception;

use Exception;
use Talk\Foundation\KnownError;

class RouteNotFoundException extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'not_found';
    }
}
