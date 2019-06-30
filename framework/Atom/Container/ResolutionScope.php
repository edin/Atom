<?php

namespace Atom\Container;

final class ResolutionScope
{
    private $instances = [];
    public $level = 0;

    public function get(string $typeName)
    {
        return $this->instances[$typeName] ?? null;
    }

    public function set(string $typeName, $instance)
    {
        $this->instances[$typeName] = $instance;
    }

    public function contains(string $typeName)
    {
        return isset($this->instances[$typeName]);
    }
}
