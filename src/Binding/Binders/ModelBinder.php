<?php

namespace Atom\Bindings\Binders;

use Atom\Bindings\BindingContext;
use Atom\Bindings\BindingResult;
use Atom\Bindings\BindingTargetInterface;
use Atom\Bindings\ModelBinderInterface;

final class ModelBinder implements ModelBinderInterface
{
    public function bindModel(BindingTargetInterface $target, BindingContext $context): ?BindingResult
    {
        throw new \RuntimeException("Not implemented");
    }
}
