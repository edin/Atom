<?php

declare(strict_types=1);

namespace Atom\Module;

interface ModuleInterface
{
    public function register(ModuleContext $context): void;
}
