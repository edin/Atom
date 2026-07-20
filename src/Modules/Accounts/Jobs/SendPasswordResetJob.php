<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts\Jobs;

use Atom\Mail\MailerInterface;
use Atom\Modules\Accounts\Mail\PasswordResetMail;
use Atom\Queue\Job;

final readonly class SendPasswordResetJob extends Job
{
    public function __construct(
        private string $recipient,
        private string $resetUrl,
        private string $recipientName = ""
    ) {
    }

    public static function type(): string
    {
        return "atom.accounts.send-password-reset";
    }

    public function handle(MailerInterface $mailer): void
    {
        $mailer->send(new PasswordResetMail($this->recipient, $this->resetUrl, $this->recipientName));
    }
}
