<?php

declare(strict_types=1);

namespace Atom\View\Component;

final readonly class NewComponentFactory implements ComponentFactoryInterface
{
    /**
     * @param class-string<ComponentInterface> $className
     */
    public function create(string $className): ComponentInterface
    {
        return new $className();
    }
}
