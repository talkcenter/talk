<?php

namespace Talk\Api\Controller;

use Talk\Extension\ExtensionManager;
use Talk\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;

class UninstallExtensionController extends AbstractDeleteController
{
    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * @param \Talk\Extension\ExtensionManager $extensions
     */
    public function __construct(ExtensionManager $extensions)
    {
        $this->extensions = $extensions;
    }

    protected function delete(ServerRequestInterface $request)
    {
        RequestUtil::getActor($request)->assertAdmin();

        $name = Arr::get($request->getQueryParams(), 'name');

        if ($this->extensions->getExtension($name) == null) {
            return;
        }

        $this->extensions->uninstall($name);
    }
}
