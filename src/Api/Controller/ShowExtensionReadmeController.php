<?php

namespace Talk\Api\Controller;

use Talk\Api\Serializer\ExtensionReadmeSerializer;
use Talk\Extension\ExtensionManager;
use Talk\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ShowExtensionReadmeController extends AbstractShowController
{
    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * {@inheritdoc}
     */
    public $serializer = ExtensionReadmeSerializer::class;

    public function __construct(ExtensionManager $extensions)
    {
        $this->extensions = $extensions;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $extensionName = Arr::get($request->getQueryParams(), 'name');

        RequestUtil::getActor($request)->assertAdmin();

        return $this->extensions->getExtension($extensionName);
    }
}
