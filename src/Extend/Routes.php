<?php

namespace Talk\Extend;

use Talk\Extension\Extension;
use Talk\Http\RouteCollection;
use Talk\Http\RouteHandlerFactory;
use Illuminate\Contracts\Container\Container;

class Routes implements ExtenderInterface
{
    private $appName;

    private $routes = [];
    private $removedRoutes = [];

    /**
     * @param string $appName: Name of the app (api, site, admin).
     */
    public function __construct(string $appName)
    {
        $this->appName = $appName;
    }

    /**
     * Add a GET route.
     *
     * @param string $path: The path of the route
     * @param string $name: The name of the route, must be unique.
     * @param callable|string $handler: ::class attribute of the controller class, or a closure.
     *
     * If the handler is a controller class, it should implement \Psr\Http\Server\RequestHandlerInterface,
     * or extend one of the Talk Api controllers within \Talk\Api\Controller.
     *
     * The handler should accept:
     * - \Psr\Http\Message\ServerRequestInterface $request
     * - \Tobscure\JsonApi\Document $document: If it extends one of the Talk Api controllers.
     *
     * The handler should return:
     * - \Psr\Http\Message\ResponseInterface $response
     *
     * @return self
     */
    public function get(string $path, string $name, $handler): self
    {
        return $this->route('GET', $path, $name, $handler);
    }

    /**
     * Add a POST route.
     *
     * @param string $path: The path of the route
     * @param string $name: The name of the route, must be unique.
     * @param callable|string $handler: ::class attribute of the controller class, or a closure.
     *
     * If the handler is a controller class, it should implement \Psr\Http\Server\RequestHandlerInterface,
     * or extend one of the Talk Api controllers within \Talk\Api\Controller.
     *
     * The handler should accept:
     * - \Psr\Http\Message\ServerRequestInterface $request
     * - \Tobscure\JsonApi\Document $document: If it extends one of the Talk Api controllers.
     *
     * The handler should return:
     * - \Psr\Http\Message\ResponseInterface $response
     *
     * @return self
     */
    public function post(string $path, string $name, $handler): self
    {
        return $this->route('POST', $path, $name, $handler);
    }

    /**
     * Add a PUT route.
     *
     * @param string $path: The path of the route
     * @param string $name: The name of the route, must be unique.
     * @param callable|string $handler: ::class attribute of the controller class, or a closure.
     *
     * If the handler is a controller class, it should implement \Psr\Http\Server\RequestHandlerInterface,
     * or extend one of the Talk Api controllers within \Talk\Api\Controller.
     *
     * The handler should accept:
     * - \Psr\Http\Message\ServerRequestInterface $request
     * - \Tobscure\JsonApi\Document $document: If it extends one of the Talk Api controllers.
     *
     * The handler should return:
     * - \Psr\Http\Message\ResponseInterface $response
     *
     * @return self
     */
    public function put(string $path, string $name, $handler): self
    {
        return $this->route('PUT', $path, $name, $handler);
    }

    /**
     * Add a PATCH route.
     *
     * @param string $path: The path of the route
     * @param string $name: The name of the route, must be unique.
     * @param callable|string $handler: ::class attribute of the controller class, or a closure.
     *
     * If the handler is a controller class, it should implement \Psr\Http\Server\RequestHandlerInterface,
     * or extend one of the Talk Api controllers within \Talk\Api\Controller.
     *
     * The handler should accept:
     * - \Psr\Http\Message\ServerRequestInterface $request
     * - \Tobscure\JsonApi\Document $document: If it extends one of the Talk Api controllers.
     *
     * The handler should return:
     * - \Psr\Http\Message\ResponseInterface $response
     *
     * @return self
     */
    public function patch(string $path, string $name, $handler): self
    {
        return $this->route('PATCH', $path, $name, $handler);
    }

    /**
     * Add a DELETE route.
     *
     * @param string $path: The path of the route
     * @param string $name: The name of the route, must be unique.
     * @param callable|string $handler: ::class attribute of the controller class, or a closure.
     *
     * If the handler is a controller class, it should implement \Psr\Http\Server\RequestHandlerInterface,
     * or extend one of the Talk Api controllers within \Talk\Api\Controller.
     *
     * The handler should accept:
     * - \Psr\Http\Message\ServerRequestInterface $request
     * - \Tobscure\JsonApi\Document $document: If it extends one of the Talk Api controllers.
     *
     * The handler should return:
     * - \Psr\Http\Message\ResponseInterface $response
     *
     * @return self
     */
    public function delete(string $path, string $name, $handler): self
    {
        return $this->route('DELETE', $path, $name, $handler);
    }

    private function route(string $httpMethod, string $path, string $name, $handler): self
    {
        $this->routes[] = [
            'method' => $httpMethod,
            'path' => $path,
            'name' => $name,
            'handler' => $handler
        ];

        return $this;
    }

    /**
     * Remove an existing route.
     * Necessary before overriding a route.
     *
     * @param string $name: The name of the route.
     * @return self
     */
    public function remove(string $name): self
    {
        $this->removedRoutes[] = $name;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        if (empty($this->routes) && empty($this->removedRoutes)) {
            return;
        }

        $container->resolving(
            "talk.{$this->appName}.routes",
            function (RouteCollection $collection, Container $container) {
                /** @var RouteHandlerFactory $factory */
                $factory = $container->make(RouteHandlerFactory::class);

                foreach ($this->removedRoutes as $routeName) {
                    $collection->removeRoute($routeName);
                }

                foreach ($this->routes as $route) {
                    $collection->addRoute(
                        $route['method'],
                        $route['path'],
                        $route['name'],
                        $factory->toController($route['handler'])
                    );
                }
            }
        );
    }
}
