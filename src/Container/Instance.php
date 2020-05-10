<?php

namespace Atom\Container;

final class Instance
{
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public static function of($name): Instance
    {
        return new Instance($name);
    }

    public function getName(): string
    {
        return $this->name;
    }
}