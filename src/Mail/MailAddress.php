<?php

declare(strict_types=1);

namespace Atom\Mail;

final readonly class MailAddress
{
    public function __construct(
        public string $address,
        public string $name = ""
    ) {
    }
}
