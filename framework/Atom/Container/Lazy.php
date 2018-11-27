<?php

namespace Atom\Container;

final class Lazy
{
    private $value = null;
    private $factory;

    public function __construct($factory)
    {
        $this->factory = $factory;
    }

    public function getValue($container)
    {
        if ($this->value === null) {
            $this->value = call_user_func($this->factory, $container);
        }
        return $this->value;
    }
}
