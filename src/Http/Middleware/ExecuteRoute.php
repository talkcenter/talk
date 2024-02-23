<?php

namespace Talk\Http\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class ExecuteRoute implements Middleware
{
    /**
     * Executes the route handler resolved in ResolveRoute.
     */
    public function process(Request $request, Handler $handler): Response
    {
        $handler = $request->getAttribute('routeHandler');
        $parameters = $request->getAttribute('routeParameters');

        return $handler($request, $parameters);
    }
}
