<?php

namespace Talk\User\Access;

use Talk\User\User;
use Illuminate\Database\Eloquent\Builder;

class ScopeUserVisibility
{
    /**
     * @param User $actor
     * @param Builder $query
     */
    public function __invoke(User $actor, $query)
    {
        if ($actor->cannot('viewSite')) {
            if ($actor->isGuest()) {
                $query->whereRaw('FALSE');
            } else {
                $query->where('id', $actor->id);
            }
        }
    }
}
