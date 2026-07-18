<?php

declare(strict_types=1);

namespace Atom\Mail;

final readonly class MailAttachment
{
    public function __construct(
        public string $path,
        public ?string $name = null,
        public ?string $contentType = null
    ) {
    }
}
