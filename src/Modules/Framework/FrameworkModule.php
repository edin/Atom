<?php

declare(strict_types=1);

namespace Atom\Modules\Framework;

use Atom\Module\ModuleContext;
use Atom\Module\ModuleInterface;

final readonly class FrameworkModule implements ModuleInterface
{
    public function __construct(
        private string $resourcePath = Framework::DEFAULT_RESOURCE_PATH
    ) {
    }

    public function register(ModuleContext $context): void
    {
        Framework::components($context);
        Framework::resources($context, $this->resourcePath);
    }
}
