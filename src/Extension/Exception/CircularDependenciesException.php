<?php

namespace Talk\Extension\Exception;

use Exception;
use Talk\Extension\ExtensionManager;

class CircularDependenciesException extends Exception
{
    public $circular_dependencies;

    public function __construct(array $circularDependencies)
    {
        $this->circular_dependencies = $circularDependencies;

        parent::__construct('Circular dependencies detected: '.implode(', ', ExtensionManager::pluckTitles($circularDependencies)).' - aborting. Please fix this by disabling the extensions that are causing the circular dependencies.');
    }
}
