<?php

declare(strict_types=1);

namespace Atom\Mail;

interface MailerInterface
{
    public function send(Mailable $mailable): void;
}
