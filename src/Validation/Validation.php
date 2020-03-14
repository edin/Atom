<?php

namespace Atom\Validation;

use Atom\Helpers\ArrayPropertyAccessor;
use Atom\Helpers\ObjectPropertyAccessor;
use Closure;

final class Validation
{
    private static $localisation;
    private $validators = [];

    public function __get(string $name): Validator
    {
        return $this->field($name);
    }

    public function field(string $name): Validator
    {
        if (!isset($this->validators[$name])) {
            $this->validators[$name] = new Validator($name);
        }
        return $this->validators[$name];
    }

    public static function create(Closure $builder): self
    {
        $validation = new self();
        $builder($validation);
        return $validation;
    }

    public function validate($model): ValidationResult
    {
        $propertyAccessor = is_array($model) ?
            new ArrayPropertyAccessor($model):
            new ObjectPropertyAccessor($model);

        $result = new ValidationResult();

        foreach ($this->validators as $validator) {
            $property = $validator->getProperty();

            $value = $propertyAccessor->getProperty($property);
            $validatorResult = $validator->validate($value);

            if ($validatorResult->isValid()) {
                $propertyAccessor->setProperty($property, $value);
            } else {
                $result->addValidationResult($property, $validatorResult);
            }
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
