<?php

namespace Talk\Group\Access;

use Talk\User\User;
use Illuminate\Database\Eloquent\Builder;

class ScopeGroupVisibility
{
    /**
     * @param User $actor
     * @param Builder $query
     */
    public function __invoke(User $actor, $query)
    {
        if ($actor->cannot('viewHiddenGroups')) {
            $query->where('is_hidden', false);
        }
    }
}
