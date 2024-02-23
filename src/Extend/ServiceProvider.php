<?php

namespace Talk\Extend;

use Talk\Extension\Extension;
use Illuminate\Contracts\Container\Container;

class ServiceProvider implements ExtenderInterface
{
    private $providers = [];

    /**
     * Register a service provider.
     *
     * @param string $serviceProviderClass The ::class attribute of the service provider class.
     * @return self
     */
    public function register(string $serviceProviderClass): self
    {
        $this->providers[] = $serviceProviderClass;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $app = $container->make('talk');

        foreach ($this->providers as $provider) {
            $app->register($provider);
        }
    }
}
