<?php

namespace Talk\Extension\Exception;

use Talk\Extension\ExtensionManager;
use Talk\Foundation\ErrorHandling\HandledError;

class CircularDependenciesExceptionHandler
{
    public function handle(CircularDependenciesException $e): HandledError
    {
        return (new HandledError(
            $e,
            'circular_dependencies',
            409
        ))->withDetails($this->errorDetails($e));
    }

    protected function errorDetails(CircularDependenciesException $e): array
    {
        return [[
            'extensions' => ExtensionManager::pluckTitles($e->circular_dependencies),
        ]];
    }
}
