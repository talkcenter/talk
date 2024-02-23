<?php

namespace Talk\User\Throttler;

use Carbon\Carbon;
use Talk\Http\RequestUtil;
use Talk\User\EmailToken;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Unactivated users can request a confirmation email,
 * this throttler applies a timeout of 5 minutes between confirmation requests.
 */
class EmailActivationThrottler
{
    public static $timeout = 300;

    /**
     * @return bool|void
     */
    public function __invoke(ServerRequestInterface $request)
    {
        if ($request->getAttribute('routeName') !== 'users.confirmation.send') {
            return;
        }

        $actor = RequestUtil::getActor($request);

        if (EmailToken::query()
            ->where('user_id', $actor->id)
            ->where('email', $actor->email)
            ->where('created_at', '>=', Carbon::now()->subSeconds(self::$timeout))
            ->exists()) {
            return true;
        }
    }
}
