<?php

namespace Talk\Api\Controller;

use Talk\Api\Serializer\DiscussionSerializer;
use Talk\Discussion\Discussion;
use Talk\Discussion\Filter\DiscussionFilterer;
use Talk\Discussion\Search\DiscussionSearcher;
use Talk\Http\RequestUtil;
use Talk\Http\UrlGenerator;
use Talk\Query\QueryCriteria;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListDiscussionsController extends AbstractListController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = DiscussionSerializer::class;

    /**
     * {@inheritdoc}
     */
    public $include = [
        'user',
        'lastPostedUser',
        'mostRelevantPost',
        'mostRelevantPost.user'
    ];

    /**
     * {@inheritdoc}
     */
    public $optionalInclude = [
        'firstPost',
        'lastPost'
    ];

    /**
     * {@inheritDoc}
     */
    public $sort = ['lastPostedAt' => 'desc'];

    /**
     * {@inheritdoc}
     */
    public $sortFields = ['lastPostedAt', 'commentCount', 'createdAt'];

    /**
     * @var DiscussionFilterer
     */
    protected $filterer;

    /**
     * @var DiscussionSearcher
     */
    protected $searcher;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @param DiscussionFilterer $filterer
     * @param DiscussionSearcher $searcher
     * @param UrlGenerator $url
     */
    public function __construct(DiscussionFilterer $filterer, DiscussionSearcher $searcher, UrlGenerator $url)
    {
        $this->filterer = $filterer;
        $this->searcher = $searcher;
        $this->url = $url;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $filters = $this->extractFilter($request);
        $sort = $this->extractSort($request);
        $sortIsDefault = $this->sortIsDefault($request);

        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);
        $include = array_merge($this->extractInclude($request), ['state']);

        $criteria = new QueryCriteria($actor, $filters, $sort, $sortIsDefault);
        if (array_key_exists('q', $filters)) {
            $results = $this->searcher->search($criteria, $limit, $offset);
        } else {
            $results = $this->filterer->filter($criteria, $limit, $offset);
        }

        $document->addPaginationLinks(
            $this->url->to('api')->route('discussions.index'),
            $request->getQueryParams(),
            $offset,
            $limit,
            $results->areMoreResults() ? null : 0
        );

        Discussion::setStateUser($actor);

        // Eager load groups for use in the policies (isAdmin check)
        if (in_array('mostRelevantPost.user', $include)) {
            $include[] = 'mostRelevantPost.user.groups';

            // If the first level of the relationship wasn't explicitly included,
            // add it so the code below can look for it
            if (! in_array('mostRelevantPost', $include)) {
                $include[] = 'mostRelevantPost';
            }
        }

        $results = $results->getResults();

        $this->loadRelations($results, $include, $request);

        if ($relations = array_intersect($include, ['firstPost', 'lastPost', 'mostRelevantPost'])) {
            foreach ($results as $discussion) {
                foreach ($relations as $relation) {
                    if ($discussion->$relation) {
                        $discussion->$relation->discussion = $discussion;
                    }
                }
            }
        }

        return $results;
    }
}
