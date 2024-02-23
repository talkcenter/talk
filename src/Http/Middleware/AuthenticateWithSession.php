<?php

namespace Talk\Http\Middleware;

use Talk\Http\AccessToken;
use Talk\Http\RequestUtil;
use Talk\User\Guest;
use Illuminate\Contracts\Session\Session;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class AuthenticateWithSession implements Middleware
{
    public function process(Request $request, Handler $handler): Response
    {
        $session = $request->getAttribute('session');

        $actor = $this->getActor($session, $request);

        $request = RequestUtil::withActor($request, $actor);

        return $handler->handle($request);
    }

    private function getActor(Session $session, Request $request)
    {
        if ($session->has('access_token')) {
            $token = AccessToken::findValid($session->get('access_token'));

            if ($token) {
                $actor = $token->user;
                $actor->updateLastSeen()->save();

                $token->touch($request);

                return $actor;
            }

            // If this session used to have a token which is no longer valid we properly refresh the session
            $session->invalidate();
            $session->regenerateToken();
        }

        return new Guest;
    }
}
