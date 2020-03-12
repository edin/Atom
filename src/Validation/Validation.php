<?php

namespace Atom\Validation;

final class Validation
{
    private static $localisation;
    private $builder;

    public static function create(callable $builder)
    {
        $v = new static;
        $v->builder = new ValidationBuilder();
        $builder($v->builder);
        return $v;
    }

    public function validate($model): ValidationResult
    {
        $result = new ValidationResult();

        // foreach ($this->builder->getValidators() as $validator) {
        //     $result[] = $validator->validate($model->{$validator->getFieldName()});
        // }
        // return $result;

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
