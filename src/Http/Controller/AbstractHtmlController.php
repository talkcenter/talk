<?php

namespace Talk\Http\Controller;

use Illuminate\Contracts\Support\Renderable;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

abstract class AbstractHtmlController implements RequestHandlerInterface
{
    /**
     * @param Request $request
     * @return HtmlResponse
     */
    public function handle(Request $request): ResponseInterface
    {
        $view = $this->render($request);

        if ($view instanceof Renderable) {
            $view = $view->render();
        }

        return new HtmlResponse($view);
    }

    /**
     * @param Request $request
     * @return string|Renderable
     */
    abstract protected function render(Request $request);
}
