<?php

namespace Talk\Formatter;

use Talk\Foundation\AbstractServiceProvider;
use Talk\Foundation\Paths;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Container\Container;

class FormatterServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->singleton('talk.formatter', function (Container $container) {
            return new Formatter(
                new Repository($container->make('cache.filestore')),
                $container[Paths::class]->storage.'/formatter'
            );
        });

        $this->container->alias('talk.formatter', Formatter::class);
    }
}
