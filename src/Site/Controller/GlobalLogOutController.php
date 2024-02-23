<?php

namespace Talk\Site\Controller;

use Talk\Http\Rememberer;
use Talk\Http\RequestUtil;
use Talk\Http\SessionAuthenticator;
use Talk\Http\UrlGenerator;
use Talk\User\Event\LoggedOut;
use Illuminate\Contracts\Events\Dispatcher;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class GlobalLogOutController implements RequestHandlerInterface
{
    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var SessionAuthenticator
     */
    protected $authenticator;

    /**
     * @var Rememberer
     */
    protected $rememberer;

    /**
     * @var UrlGenerator
     */
    protected $url;

    public function __construct(
        Dispatcher $events,
        SessionAuthenticator $authenticator,
        Rememberer $rememberer,
        UrlGenerator $url
    ) {
        $this->events = $events;
        $this->authenticator = $authenticator;
        $this->rememberer = $rememberer;
        $this->url = $url;
    }

    public function handle(Request $request): ResponseInterface
    {
        $session = $request->getAttribute('session');
        $actor = RequestUtil::getActor($request);

        $actor->assertRegistered();

        $this->authenticator->logOut($session);

        $actor->accessTokens()->delete();
        $actor->emailTokens()->delete();
        $actor->passwordTokens()->delete();

        $this->events->dispatch(new LoggedOut($actor, true));

        return $this->rememberer->forget(new EmptyResponse());
    }
}
