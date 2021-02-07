<?php

namespace Atom\Bindings\Binders;

use Atom\Bindings\BindingContext;
use Atom\Bindings\BindingResult;
use Atom\Bindings\BindingTargetInterface;
use Atom\Bindings\ModelBinderInterface;

final class BuiltinTypeBinder implements ModelBinderInterface
{
    public function bindModel(BindingTargetInterface $target, BindingContext $context): ?BindingResult
    {
        $value = $target->getValue();
        $isArray = $target->isArray();
        $typeName = $target->getTypeName();
        $isBuiltin = $target->isBuiltin();
        $allowsNull = $target->allowsNull();

        if (!$isBuiltin) {
            return null;
        }

        if ($isArray) {
            return new BindingResult((array)$value);
        }

        if (is_array($value)) {
            return null;
        }

        if ($isBuiltin && (($value !== null) || !$allowsNull)) {
            $value = $this->filterValue($target, $typeName, $value);
            if ($value !== null) {
                return new BindingResult($value);
            }
        }

        if ($value == null && $target->hasDefaultValue()) {
            $value = $target->getDefaultValue();
            return new BindingResult($value);
        }

        return null;
    }

    private function filterValue(BindingTargetInterface $target, ?string $typeName, $value)
    {
        switch ($typeName) {
            case 'int':
                return filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
            case 'float':
                return filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
            case 'bool':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }
        return $value;
    }
}
