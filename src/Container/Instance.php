<?php

declare(strict_types=1);

namespace Atom\Container;

final class Instance
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function of(string $name): Instance
    {
        return new Instance($name);
    }

    public function getName(): string
    {
        return $this->name;
    }
}
