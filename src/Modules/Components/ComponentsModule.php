<?php

declare(strict_types=1);

namespace Atom\Modules\Components;

use Atom\Module\ModuleContext;
use Atom\Module\ModuleInterface;

final readonly class ComponentsModule implements ModuleInterface
{
    public function __construct(
        private string $resourcePath = Components::DEFAULT_RESOURCE_PATH
    ) {
    }

    public function register(ModuleContext $context): void
    {
        Components::components($context);
        Components::resources($context, $this->resourcePath);
    }
}
