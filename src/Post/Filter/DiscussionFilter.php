<?php

namespace Talk\Post\Filter;

use Talk\Filter\FilterInterface;
use Talk\Filter\FilterState;
use Talk\Filter\ValidateFilterTrait;

class DiscussionFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'discussion';
    }

    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        $discussionId = $this->asInt($filterValue);

        $filterState->getQuery()->where('posts.discussion_id', $negate ? '!=' : '=', $discussionId);
    }
}
