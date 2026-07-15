<?php

declare(strict_types=1);

namespace Atom\View\Component;

use Atom\Di\InjectionContext;
use Atom\Di\Injector;

final readonly class InjectorComponentFactory implements ComponentFactoryInterface
{
    public function __construct(
        private Injector $injector,
        private ?InjectionContext $context = null
    )
    {
    }

    /**
     * @param class-string<ComponentInterface> $className
     */
    public function create(string $className): ComponentInterface
    {
        $component = $this->injector->get($className, $this->context);

        if (!$component instanceof ComponentInterface) {
            throw new \RuntimeException("Component '{$className}' must implement " . ComponentInterface::class . ".");
        }

        return $component;
    }
}
