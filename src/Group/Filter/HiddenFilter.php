<?php

namespace Talk\Group\Filter;

use Talk\Filter\FilterInterface;
use Talk\Filter\FilterState;
use Talk\Filter\ValidateFilterTrait;

class HiddenFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'hidden';
    }

    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        $hidden = $this->asBool($filterValue);

        $filterState->getQuery()->where('is_hidden', $negate ? '!=' : '=', $hidden);
    }
}
