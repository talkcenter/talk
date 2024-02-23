<?php

namespace Talk\Discussion;

use Talk\Discussion\Access\ScopeDiscussionVisibility;
use Talk\Discussion\Event\Renamed;
use Talk\Foundation\AbstractServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;

class DiscussionServiceProvider extends AbstractServiceProvider
{
    public function boot(Dispatcher $events)
    {
        $events->subscribe(DiscussionMetadataUpdater::class);
        $events->subscribe(UserStateUpdater::class);

        $events->listen(
            Renamed::class,
            DiscussionRenamedLogger::class
        );

        Discussion::registerVisibilityScoper(new ScopeDiscussionVisibility(), 'view');
    }
}
