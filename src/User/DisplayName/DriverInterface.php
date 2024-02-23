<?php

namespace Talk\User\DisplayName;

use Talk\User\User;

/**
 * An interface for a display name driver.
 *
 * @public
 */
interface DriverInterface
{
    /**
     * Return a display name for a user.
     */
    public function displayName(User $user): string;
}
