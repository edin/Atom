<?php

declare(strict_types=1);

namespace Atom\Module;

final readonly class ModuleRegistration
{
    public function __construct(
        public ModuleInterface $module,
        public string $basePath = ""
    ) {
    }
}
