<?php

declare(strict_types=1);

namespace Atom\Mail;

use Symfony\Component\Mailer\MailerInterface as SymfonyMailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Throwable;

final readonly class SymfonyMailer implements MailerInterface
{
    public function __construct(
        private SymfonyMailerInterface $mailer,
        private MailTemplateRenderer $templates,
        private MailOptions $options
    ) {
    }

    public function send(Mailable $mailable): void
    {
        try {
            $this->mailer->send($this->email($mailable->build()));
        } catch (Throwable $exception) {
            throw new MailException("Failed to send mail.", previous: $exception);
        }
    }

    private function email(MailMessage $message): Email
    {
        $email = (new Email())->subject($message->subjectLine());
        $from = $message->fromAddress();
        if ($from !== null) {
            $email->from($this->address($from));
        } elseif ($this->options->fromAddress !== "") {
            $email->from(new Address($this->options->fromAddress, $this->options->fromName));
        }

        $email->to(...$this->addresses($message->recipients()));
        $email->cc(...$this->addresses($message->carbonCopies()));
        $email->bcc(...$this->addresses($message->blindCarbonCopies()));
        $email->replyTo(...$this->addresses($message->replyToAddresses()));

        $text = $message->textView();
        if ($text !== null) {
            $email->text($this->templates->render($text));
        } elseif ($message->textContent() !== null) {
            $email->text($message->textContent());
        }

        $html = $message->htmlView();
        if ($html !== null) {
            $email->html($this->templates->render($html));
        } elseif ($message->htmlContent() !== null) {
            $email->html($message->htmlContent());
        }

        foreach ($message->attachments() as $attachment) {
            $email->attachFromPath($attachment->path, $attachment->name, $attachment->contentType);
        }

        return $email;
    }

    /**
     * @param MailAddress[] $addresses
     * @return Address[]
     */
    private function addresses(array $addresses): array
    {
        return array_map($this->address(...), $addresses);
    }

    private function address(MailAddress $address): Address
    {
        return new Address($address->address, $address->name);
    }
}
