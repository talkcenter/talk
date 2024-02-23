<?php

namespace Talk\Group;

use Talk\Foundation\AbstractServiceProvider;
use Talk\Group\Access\ScopeGroupVisibility;

class GroupServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        Group::registerVisibilityScoper(new ScopeGroupVisibility(), 'view');
    }
}
