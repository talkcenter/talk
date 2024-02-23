<?php

namespace Talk\Api\Controller;

use Talk\Api\Serializer\GroupSerializer;
use Talk\Group\GroupRepository;
use Talk\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ShowGroupController extends AbstractShowController
{
    /**
     * @var GroupRepository
     */
    protected $groups;

    /**
     * {@inheritdoc}
     */
    public $serializer = GroupSerializer::class;

    /**
     * @param \Talk\Group\GroupRepository $groups
     */
    public function __construct(GroupRepository $groups)
    {
        $this->groups = $groups;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $id = Arr::get($request->getQueryParams(), 'id');
        $actor = RequestUtil::getActor($request);

        $group = $this->groups->findOrFail($id, $actor);

        return $group;
    }
}
