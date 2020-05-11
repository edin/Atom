<?php

declare(strict_types=1);

namespace Atom\Container;

final class ResolutionContext
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
