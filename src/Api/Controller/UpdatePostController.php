<?php

namespace Talk\Api\Controller;

use Talk\Api\Serializer\PostSerializer;
use Talk\Http\RequestUtil;
use Talk\Post\Command\EditPost;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class UpdatePostController extends AbstractShowController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = PostSerializer::class;

    /**
     * {@inheritdoc}
     */
    public $include = [
        'editedUser',
        'discussion'
    ];

    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @param Dispatcher $bus
     */
    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $id = Arr::get($request->getQueryParams(), 'id');
        $actor = RequestUtil::getActor($request);
        $data = Arr::get($request->getParsedBody(), 'data', []);

        $post = $this->bus->dispatch(
            new EditPost($id, $actor, $data)
        );

        $this->loadRelations($post->newCollection([$post]), $this->extractInclude($request), $request);

        return $post;
    }
}
