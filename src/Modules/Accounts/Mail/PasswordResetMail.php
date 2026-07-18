<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts\Mail;

use Atom\Mail\Mailable;
use Atom\Mail\MailMessage;

final class PasswordResetMail extends Mailable
{
    public function __construct(
        private readonly string $recipient,
        private readonly string $resetUrl,
        private readonly string $recipientName = ""
    ) {
    }

    public function build(): MailMessage
    {
        return MailMessage::create()
            ->to($this->recipient, $this->recipientName)
            ->subject("Reset your password")
            ->text("Use this link to reset your password: {$this->resetUrl}")
            ->htmlTemplate(__DIR__ . "/Templates/password-reset.php", [
                "resetUrl" => $this->resetUrl,
            ]);
    }
}
