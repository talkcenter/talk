<?php

namespace Talk\Settings;

interface SettingsRepositoryInterface
{
    public function all(): array;

    /**
     * @param $key
     * @param mixed $default: Deprecated
     * @return mixed
     */
    public function get($key, $default = null);

    public function set($key, $value);

    public function delete($keyLike);
}
