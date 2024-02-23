<?php

namespace Talk\Http;

use Talk\Database\AbstractModel;
use Talk\User\User;

/**
 * @template T of AbstractModel
 */
interface SlugDriverInterface
{
    /**
     * @param T $instance
     */
    public function toSlug(AbstractModel $instance): string;

    /**
     * @return T
     */
    public function fromSlug(string $slug, User $actor): AbstractModel;
}
