<?php

namespace Talk\Group\Filter;

use Talk\Filter\AbstractFilterer;
use Talk\Group\GroupRepository;
use Talk\User\User;
use Illuminate\Database\Eloquent\Builder;

class GroupFilterer extends AbstractFilterer
{
    /**
     * @var GroupRepository
     */
    protected $groups;

    /**
     * @param GroupRepository $groups
     * @param array $filters
     * @param array $filterMutators
     */
    public function __construct(GroupRepository $groups, array $filters, array $filterMutators)
    {
        parent::__construct($filters, $filterMutators);

        $this->groups = $groups;
    }

    protected function getQuery(User $actor): Builder
    {
        return $this->groups->query()->whereVisibleTo($actor);
    }
}
