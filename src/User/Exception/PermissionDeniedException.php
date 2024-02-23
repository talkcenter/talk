<?php

namespace Talk\User\Exception;

use Exception;
use Talk\Foundation\KnownError;

class PermissionDeniedException extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'permission_denied';
    }
}
