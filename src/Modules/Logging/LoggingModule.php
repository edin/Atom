<?php

declare(strict_types=1);

namespace Atom\Modules\Logging;

use Atom\Logging\FileLogger;
use Atom\Logging\LoggerInterface;
use Atom\Module\ModuleContext;
use Atom\Module\ModuleInterface;

final readonly class LoggingModule implements ModuleInterface
{
    public function __construct(private ?LoggingOptions $options = null)
    {
    }

    public function register(ModuleContext $context): void
    {
        $options = $this->options ?? $context->config->options(LoggingOptions::class);

        $context->bind(LoggerInterface::class)
            ->toFactory(fn(): LoggerInterface => new FileLogger($options->path))
            ->singleton();
    }
}
