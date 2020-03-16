<?php

namespace Atom\Database\Query;

final class Parameter
{
    private $name;
    private $value;

    public function __construct(string $name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public static function from(string $name, $value): self
    {
        return new Parameter($name, $value);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue()
    {
        return $this->value;
    }
}
