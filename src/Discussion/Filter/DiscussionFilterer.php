<?php

namespace Talk\Discussion\Filter;

use Talk\Discussion\DiscussionRepository;
use Talk\Filter\AbstractFilterer;
use Talk\User\User;
use Illuminate\Database\Eloquent\Builder;

class DiscussionFilterer extends AbstractFilterer
{
    /**
     * @var DiscussionRepository
     */
    protected $discussions;

    /**
     * @param DiscussionRepository $discussions
     * @param array $filters
     * @param array $filterMutators
     */
    public function __construct(DiscussionRepository $discussions, array $filters, array $filterMutators)
    {
        parent::__construct($filters, $filterMutators);

        $this->discussions = $discussions;
    }

    protected function getQuery(User $actor): Builder
    {
        return $this->discussions->query()->select('discussions.*')->whereVisibleTo($actor);
    }
}
