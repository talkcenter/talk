<?php

namespace Talk\Notification\Driver;

use Talk\Notification\Blueprint\BlueprintInterface;
use Talk\User\User;

interface NotificationDriverInterface
{
    /**
     * Conditionally sends a notification to users, generally using a queue.
     *
     * @param BlueprintInterface $blueprint
     * @param User[] $users
     * @return void
     */
    public function send(BlueprintInterface $blueprint, array $users): void;

    /**
     * Logic for registering a notification type, generally used for adding a user preference.
     *
     * @param string $blueprintClass
     * @param array $driversEnabledByDefault
     * @return void
     */
    public function registerType(string $blueprintClass, array $driversEnabledByDefault): void;
}
