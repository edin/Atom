<?php

declare(strict_types=1);

namespace Atom\Mail;

abstract class Mailable
{
    abstract public function build(): MailMessage;
}
