<?php

declare(strict_types=1);

namespace Atom\Mail;

final readonly class MailTemplate
{
    /**
     * @param array<string, mixed> $variables
     */
    public function __construct(
        public string $path,
        public array $variables = []
    ) {
    }
}
