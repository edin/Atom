<?php

namespace Atom\Database;

final class Parameter
{
    public $name;
    public $value;
    public $type;
    public $direction;

    public function __construct(string $name, $value, $type)
    {
        $this->name = $name;
        $this->value = $value;
        $this->type = $type;
    }
}

final class Command
{
    private $parameters = [];
    private $sql;

    private function add(Parameter $parameter)
    {
        $this->parameters[$parameter->name] = $parameter;
    }

    public function addParameter(string $name, $value, $type)
    {
        $this->add(new Parameter($name, $value, $type));
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setSql(string $sql): void
    {
        $this->sql = $sql;
    }

    public function getSql(): string
    {
        return $this->sql;
    }
}


final class Database
{
}
