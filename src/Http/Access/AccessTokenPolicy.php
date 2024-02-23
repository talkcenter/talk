<?php

namespace Talk\Http\Access;

use Talk\Http\AccessToken;
use Talk\User\Access\AbstractPolicy;
use Talk\User\User;

class AccessTokenPolicy extends AbstractPolicy
{
    public function revoke(User $actor, AccessToken $token)
    {
        if ($token->user_id === $actor->id || $actor->hasPermission('moderateAccessTokens')) {
            return $this->allow();
        }
    }
}
