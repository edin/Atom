<?php

namespace Atom\Container\Resolver;

use Atom\Container\ResolutionContext;
use Atom\Container\ComponentRegistration;

interface IDependencyResolver
{
    public function resolve(?ResolutionContext $context = null, array $params = []);
    public function getDependencies(): array;
    public function resolveType(): ?string;
    public function getRegistration(): ComponentRegistration;
}
