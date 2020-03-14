<?php

namespace Atom\Database\Command;

final class Command
{
    private $parameters = [];
    private $sql;

    private function add(Parameter $parameter): void 
    {
        $this->parameters[$parameter->name] = $parameter;
    }

    public function addParameter(string $name, $value, int $direction, ?int $type = null): void
    {
        $this->add(new Parameter($name, $value, $direction, $type));
    }

    public function setParameterValue(string $name, $value): void {
        $this->parameters[$name]->value = $value;
    }

    /** @return mixed */
    public function getParameterValue(string $name) {
        return $this->parameters[$name]->value;
    }

    /** @return Parameter[] */
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