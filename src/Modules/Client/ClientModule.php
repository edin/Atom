<?php

declare(strict_types=1);

namespace Atom\Modules\Client;

use Atom\Module\ModuleContext;
use Atom\Module\ModuleInterface;

final readonly class ClientModule implements ModuleInterface
{
    public function __construct(private string $resourcePath = Client::DEFAULT_RESOURCE_PATH)
    {
    }

    public function register(ModuleContext $context): void
    {
        $context->importComponents(Client::definitions());
        Client::resources($context, $this->resourcePath);
    }
}
