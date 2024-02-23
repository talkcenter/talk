<?php

namespace Talk\Foundation;

interface SiteInterface
{
    /**
     * Create and boot a Talk application instance.
     *
     * @return AppInterface
     */
    public function bootApp(): AppInterface;
}
