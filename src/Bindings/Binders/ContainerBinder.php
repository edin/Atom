<?php

namespace Atom\Bindings\Binders;

use Atom\Bindings\BindingContext;
use Atom\Bindings\BindingResult;
use Atom\Bindings\BindingTargetInterface;
use Atom\Bindings\ModelBinderInterface;

final class ContainerBinder implements ModelBinderInterface
{
    public function bindModel(BindingTargetInterface $target, BindingContext $context): ?BindingResult
    {
    }
}
