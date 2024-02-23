<?php

namespace Talk\Site\Controller;

use Talk\Http\SessionAccessToken;
use Talk\Http\SessionAuthenticator;
use Talk\Http\UrlGenerator;
use Talk\User\Command\ConfirmEmail;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class ConfirmEmailController implements RequestHandlerInterface
{
    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @var SessionAuthenticator
     */
    protected $authenticator;

    /**
     * @param Dispatcher $bus
     * @param UrlGenerator $url
     * @param SessionAuthenticator $authenticator
     */
    public function __construct(Dispatcher $bus, UrlGenerator $url, SessionAuthenticator $authenticator)
    {
        $this->bus = $bus;
        $this->url = $url;
        $this->authenticator = $authenticator;
    }

    /**
     * @param Request $request
     * @return ResponseInterface
     */
    public function handle(Request $request): ResponseInterface
    {
        $token = Arr::get($request->getQueryParams(), 'token');

        $user = $this->bus->dispatch(
            new ConfirmEmail($token)
        );

        $session = $request->getAttribute('session');
        $token = SessionAccessToken::generate($user->id);
        $this->authenticator->logIn($session, $token);

        return new RedirectResponse($this->url->to('site')->base());
    }
}
