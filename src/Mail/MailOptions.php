<?php

declare(strict_types=1);

namespace Atom\Mail;

use Atom\Config\Options;

#[Options("MAIL_")]
final readonly class MailOptions
{
    public function __construct(
        public string $dsn = "null://null",
        public string $fromAddress = "no-reply@localhost",
        public string $fromName = ""
    ) {
    }
}
