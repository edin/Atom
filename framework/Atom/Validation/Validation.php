<?php

namespace Atom\Validation;

class Validation
{
    public static function create(callable $builder)
    {
        $v = new static;
        $v->builder = new RuleBuilder();
        $builder($v->builder);
        return $v;
    }

    public function validate($model)
    {
        $result = [];

        foreach ($this->builder->rules as $rule) {
            $result[] = $rule->validate($model->{$rule->getFieldName()});
        }
        return $result;
    }
}
