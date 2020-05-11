<?php

declare(strict_types=1);

namespace Atom\Container\Resolver;

use Atom\Container\ComponentRegistration;
use Atom\Container\ResolutionContext;

final class TypeFactoryResolver implements IDependencyResolver
{
    private $registration;

    public function __construct(ComponentRegistration $registration)
    {
        $this->registration = $registration;
    }

    public function resolve(ResolutionContext $context, array $params = [])
    {
        return $this->registration->factory->createType($this->registration->getContainer(), $this->registration->targetType);
    }

    public function getRegistration(): ComponentRegistration
    {
        return $this->registration;
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function resolveType(): ?string
    {
        return $this->registration->targetType;
    }
}
