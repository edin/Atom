<?php

declare(strict_types=1);

namespace Atom\Mail;

final class MailMessage
{
    private ?MailAddress $from = null;
    /** @var MailAddress[] */
    private array $to = [];
    /** @var MailAddress[] */
    private array $cc = [];
    /** @var MailAddress[] */
    private array $bcc = [];
    /** @var MailAddress[] */
    private array $replyTo = [];
    private string $subject = "";
    private ?string $text = null;
    private ?string $html = null;
    private ?MailTemplate $textTemplate = null;
    private ?MailTemplate $htmlTemplate = null;
    /** @var MailAttachment[] */
    private array $attachments = [];

    public static function create(): self
    {
        return new self();
    }

    public function from(string|MailAddress $address, string $name = ""): self
    {
        $this->from = self::address($address, $name);
        return $this;
    }

    public function to(string|MailAddress $address, string $name = ""): self
    {
        $this->to[] = self::address($address, $name);
        return $this;
    }

    public function cc(string|MailAddress $address, string $name = ""): self
    {
        $this->cc[] = self::address($address, $name);
        return $this;
    }

    public function bcc(string|MailAddress $address, string $name = ""): self
    {
        $this->bcc[] = self::address($address, $name);
        return $this;
    }

    public function replyTo(string|MailAddress $address, string $name = ""): self
    {
        $this->replyTo[] = self::address($address, $name);
        return $this;
    }

    public function subject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function text(string $content): self
    {
        $this->text = $content;
        $this->textTemplate = null;
        return $this;
    }

    /** @param array<string, mixed> $variables */
    public function textTemplate(string $path, array $variables = []): self
    {
        $this->textTemplate = new MailTemplate($path, $variables);
        $this->text = null;
        return $this;
    }

    public function html(string $content): self
    {
        $this->html = $content;
        $this->htmlTemplate = null;
        return $this;
    }

    /** @param array<string, mixed> $variables */
    public function htmlTemplate(string $path, array $variables = []): self
    {
        $this->htmlTemplate = new MailTemplate($path, $variables);
        $this->html = null;
        return $this;
    }

    public function attach(string $path, ?string $name = null, ?string $contentType = null): self
    {
        $this->attachments[] = new MailAttachment($path, $name, $contentType);
        return $this;
    }

    public function fromAddress(): ?MailAddress
    {
        return $this->from;
    }

    /** @return MailAddress[] */
    public function recipients(): array
    {
        return $this->to;
    }

    /** @return MailAddress[] */
    public function carbonCopies(): array
    {
        return $this->cc;
    }

    /** @return MailAddress[] */
    public function blindCarbonCopies(): array
    {
        return $this->bcc;
    }

    /** @return MailAddress[] */
    public function replyToAddresses(): array
    {
        return $this->replyTo;
    }

    public function subjectLine(): string
    {
        return $this->subject;
    }

    public function textContent(): ?string
    {
        return $this->text;
    }

    public function htmlContent(): ?string
    {
        return $this->html;
    }

    public function textView(): ?MailTemplate
    {
        return $this->textTemplate;
    }

    public function htmlView(): ?MailTemplate
    {
        return $this->htmlTemplate;
    }

    /** @return MailAttachment[] */
    public function attachments(): array
    {
        return $this->attachments;
    }

    private static function address(string|MailAddress $address, string $name): MailAddress
    {
        return $address instanceof MailAddress ? $address : new MailAddress($address, $name);
    }
}
