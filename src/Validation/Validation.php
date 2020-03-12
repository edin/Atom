<?php

namespace Atom\Validation;

use Closure;

final class Validation
{
    private static $localisation;
    private $validators = [];

    public function __get($name)
    {
        if (!isset($this->validators[$name])) {
            $this->validators[$name] = new ValidationGroup($name);
        }
        return $this->validators[$name];
    }

    public static function create(Closure $builder): self
    {
        $validation = new self();
        $builder($validation);
        return $validation;
    }

    public function validate($model): array
    {
        $result = []; //new ValidationResult();

        foreach ($this->validators as $validator) {
            $property = $validator->getProperty();
            $value = is_array($model)
                       ? $model[$property]
                       : $model->{$property};

            $errors = $validator->validate($value);
            if (count($errors)) {
                $errorList = array_map(function ($it) {
                    return $it->getErrorMessage();
                }, $errors);

                $result[$property] =  $errorList[0];
            }
        }

        return $result;
    }

    public static function setLocalization(ILocalisation $localisation): void
    {
        self::$localisation = $localisation;
    }

    public static function getLocalization(): ILocalisation
    {
        return self::$localisation;
    }
}
