<?php

namespace Talk\Settings\Event;

class Saved
{
    /**
     * @var array
     */
    public $settings;

    /**
     * @param array $settings
     */
    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }
}
