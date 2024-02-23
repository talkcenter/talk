<?php

namespace Talk\Post\Filter;

use Talk\Filter\FilterInterface;
use Talk\Filter\FilterState;
use Talk\Filter\ValidateFilterTrait;

class TypeFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'type';
    }

    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        $type = $this->asString($filterValue);

        $filterState->getQuery()->where('posts.type', $negate ? '!=' : '=', $type);
    }
}
