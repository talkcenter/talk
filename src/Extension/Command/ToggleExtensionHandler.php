<?php

namespace Talk\Extension\Command;

use Talk\Extension\ExtensionManager;

class ToggleExtensionHandler
{
    /**
     * @var ExtensionManager
     */
    protected $extensions;

    public function __construct(ExtensionManager $extensions)
    {
        $this->extensions = $extensions;
    }

    /**
     * @throws \Talk\User\Exception\PermissionDeniedException
     * @throws \Talk\Extension\Exception\MissingDependenciesException
     * @throws \Talk\Extension\Exception\DependentExtensionsException
     */
    public function handle(ToggleExtension $command)
    {
        $command->actor->assertAdmin();

        if ($command->enabled) {
            $this->extensions->enable($command->name);
        } else {
            $this->extensions->disable($command->name);
        }
    }
}
