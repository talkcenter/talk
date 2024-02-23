<?php

namespace Talk\Http\Filter;

use Talk\Filter\AbstractFilterer;
use Talk\Http\AccessToken;
use Talk\User\User;
use Illuminate\Database\Eloquent\Builder;

class AccessTokenFilterer extends AbstractFilterer
{
    protected function getQuery(User $actor): Builder
    {
        return AccessToken::query()->whereVisibleTo($actor);
    }
}
