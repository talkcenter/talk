<?php

namespace Talk\Post\Filter;

use Talk\Filter\FilterInterface;
use Talk\Filter\FilterState;
use Talk\Filter\ValidateFilterTrait;

class NumberFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'number';
    }

    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        $number = $this->asInt($filterValue);

        $filterState->getQuery()->where('posts.number', $negate ? '!=' : '=', $number);
    }
}
