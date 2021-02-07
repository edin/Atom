<?php

namespace Atom\Bindings;

interface ModelBinderInterface
{
    public function bindModel(BindingTargetInterface $target, BindingContext $context): ?BindingResult;
}
