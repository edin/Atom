<?php

namespace Atom\Validation;

class Validation
{
    protected $errorMessage = "maxValueError";
    protected $maxValue = 0;

    public function __construct(float $maxValue)
    {
        $this->maxValue = $maxValue;
    }

    public static function create(callable $builder)
    {
        $v = new static;
        $v->builder = new ValidationBuilder();
        $builder($v->builder);
        return $v;
    }

    public function validate($model)
    {
        $result = [];

        foreach ($this->builder->getValidators() as $validator) {
            $result[] = $validator->validate($model->{$validator->getFieldName()});
        }
        return $result;
    }
}
