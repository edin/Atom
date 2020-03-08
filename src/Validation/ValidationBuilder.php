<?php

namespace Atom\Validation;

class ValidationBuilder
{
    private $validators = [];

    public function __get($name)
    {
        if (!isset($this->validators[$name])) {
            $this->validators[$name] = new ValidationGroup($name);
        }
        return $this->validators[$name];
    }

    public function getValidators(): array
    {
        return $this->validators;
    }
}
