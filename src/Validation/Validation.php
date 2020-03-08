<?php

namespace Atom\Validation;

class Validation
{
    private static $localisation;

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

    public static function setLocalisation(ILocalisation $localisation): void
    {
        self::$localisation = $localisation;
    }

    public static function getLocalisation(): ILocalisation
    {
        return self::$localisation;
    }
}
