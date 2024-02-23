<?php

namespace Talk\Filter;

interface FilterInterface
{
    /**
     * This filter will only be run when a query contains a filter param with this key.
     */
    public function getFilterKey(): string;

    /**
     * Filters a query.
     */
    public function filter(FilterState $filterState, string $filterValue, bool $negate);
}
