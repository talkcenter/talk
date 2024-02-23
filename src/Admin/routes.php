<?php

use Talk\Admin\Content\Index;
use Talk\Admin\Controller\UpdateExtensionController;
use Talk\Http\RouteCollection;
use Talk\Http\RouteHandlerFactory;

return function (RouteCollection $map, RouteHandlerFactory $route) {
    $map->get(
        '/',
        'index',
        $route->toAdmin(Index::class)
    );

    $map->post(
        '/extensions/{name}',
        'extensions.update',
        $route->toController(UpdateExtensionController::class)
    );
};
