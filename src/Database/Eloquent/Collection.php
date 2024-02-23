<?php

namespace Talk\Database\Eloquent;

use Illuminate\Database\Eloquent\Collection as BaseCollection;

class Collection extends BaseCollection
{
    public function loadAggregate($relations, $column, $function = null)
    {
        if ($this->isEmpty()) {
            return $this;
        }

        return $this->first()->withTableAlias(function () use ($relations, $column, $function) {
            return parent::loadAggregate($relations, $column, $function);
        });
    }
}
