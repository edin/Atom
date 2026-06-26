<?php

declare(strict_types=1);

namespace Atom\View\Component;

interface ComponentFactoryInterface
{
    /**
     * @param class-string<ComponentInterface> $className
     */
    public function create(string $className): ComponentInterface;
}
