<?php

namespace Talk\Extend;

use Talk\Extension\Extension;
use Illuminate\Contracts\Container\Container;

class Csrf implements ExtenderInterface
{
    protected $csrfExemptRoutes = [];

    /**
     * Exempt a named route from CSRF checks.
     *
     * @param string $routeName
     * @return self
     */
    public function exemptRoute(string $routeName): self
    {
        $this->csrfExemptRoutes[] = $routeName;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('talk.http.csrfExemptPaths', function ($existingExemptPaths) {
            return array_merge($existingExemptPaths, $this->csrfExemptRoutes);
        });
    }
}
