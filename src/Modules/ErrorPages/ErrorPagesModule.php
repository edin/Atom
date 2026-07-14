<?php

declare(strict_types=1);

namespace Atom\Modules\ErrorPages;

use Atom\Module\ModuleContext;
use Atom\Module\ModuleInterface;

final readonly class ErrorPagesModule implements ModuleInterface
{
    public function __construct(private ?ErrorPagesOptions $options = null)
    {
    }

    public function register(ModuleContext $context): void
    {
        if ($this->options !== null) {
            $context->config->set($this->options);
        }

        $context->bind(ErrorPageHandlerInterface::class)
            ->to(DefaultErrorPageHandler::class)
            ->scoped();
    }
}
