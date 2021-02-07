<?php

namespace Atom\Bindings;

final class CompositeModelBinder implements ModelBinderInterface
{
    public $binders = [];

    public function getModelBinders() {
        return [];
    }

    public function bindModel(BindingTargetInterface $target, BindingContext $context): ?BindingResult
    {
        $binders = $this->getModelBinders();

        foreach ($binders as $binder) {
            $result = $binder->bindModel($target, $context);
            if ($result instanceof BindingResult) {
                return $result;
            }
        }

        return null;
    }
}
