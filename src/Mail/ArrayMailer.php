<?php

declare(strict_types=1);

namespace Atom\Mail;

final class ArrayMailer implements MailerInterface
{
    /** @var MailMessage[] */
    private array $messages = [];

    public function send(Mailable $mailable): void
    {
        $this->messages[] = $mailable->build();
    }

    /** @return MailMessage[] */
    public function messages(): array
    {
        return $this->messages;
    }
}
