<?php

namespace Talk\Http;

use Talk\Foundation\Application;

class UrlGenerator
{
    /**
     * @var array
     */
    protected $routes = [];

    /**
     * @var Application
     */
    protected $app;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * 注册用于生成 URL 的命名路由集合
     *
     * @param string $key
     * @param RouteCollection $routes
     * @param string $prefix
     * @return static
     */
    public function addCollection($key, RouteCollection $routes, $prefix = null)
    {
        $this->routes[$key] = new RouteCollectionUrlGenerator(
            $this->app->url($prefix),
            $routes
        );

        return $this;
    }

    /**
     * 检索给定命名路由集合的 URL 生成器实例
     *
     * @param string $collection
     * @return RouteCollectionUrlGenerator
     */
    public function to($collection)
    {
        return $this->routes[$collection];
    }
}
