<?php

namespace Atom\Validation;

class RuleBuilder
{
    public $rules = [];
    public function __get($name)
    {
        if (!isset($this->rules[$name])) {
            $this->rules[$name] = new Rule($name);
        }
        return $this->rules[$name];
    }
}
