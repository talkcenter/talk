<?php

namespace Talk\Api\Controller;

use Talk\Api\Serializer\AccessTokenSerializer;
use Talk\Http\Filter\AccessTokenFilterer;
use Talk\Http\RequestUtil;
use Talk\Http\UrlGenerator;
use Talk\Query\QueryCriteria;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListAccessTokensController extends AbstractListController
{
    public $serializer = AccessTokenSerializer::class;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @var AccessTokenFilterer
     */
    protected $filterer;

    public function __construct(UrlGenerator $url, AccessTokenFilterer $filterer)
    {
        $this->url = $url;
        $this->filterer = $filterer;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);

        $actor->assertRegistered();

        $offset = $this->extractOffset($request);
        $limit = $this->extractLimit($request);
        $filter = $this->extractFilter($request);

        $tokens = $this->filterer->filter(new QueryCriteria($actor, $filter), $limit, $offset);

        $document->addPaginationLinks(
            $this->url->to('api')->route('access-tokens.index'),
            $request->getQueryParams(),
            $offset,
            $limit,
            $tokens->areMoreResults() ? null : 0
        );

        return $tokens->getResults();
    }
}
