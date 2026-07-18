<?php

declare(strict_types=1);

namespace Atom\Tests\Mail;

use Atom\Mail\ArrayMailer;
use Atom\Mail\MailException;
use Atom\Mail\Mailable;
use Atom\Mail\MailMessage;
use Atom\Mail\MailOptions;
use Atom\Mail\MailTemplate;
use Atom\Mail\MailTemplateRenderer;
use Atom\Mail\SymfonyMailer;
use Atom\Modules\Accounts\Mail\PasswordResetMail;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

final class MailTest extends TestCase
{
    public function testArrayMailerBuildsAndRecordsMailables(): void
    {
        $mailer = new ArrayMailer();
        $mailer->send(new TestMailable());

        $message = $mailer->messages()[0];
        $this->assertSame("Welcome", $message->subjectLine());
        $this->assertSame("person@example.com", $message->recipients()[0]->address);
        $this->assertSame("Hello", $message->textContent());
    }

    public function testSymfonyAdapterMapsFrameworkMessageAndRendersPhpTemplate(): void
    {
        $directory = sys_get_temp_dir() . "/atom_mail_" . uniqid();
        mkdir($directory);
        $template = $directory . "/message.php";
        file_put_contents($template, "<strong><?= htmlspecialchars(\$name) ?></strong>");
        $attachment = $directory . "/report.txt";
        file_put_contents($attachment, "report");

        try {
            $transport = new CapturingTransport();
            $mailer = new SymfonyMailer(
                new Mailer($transport),
                new MailTemplateRenderer(),
                new MailOptions(fromAddress: "sender@example.com", fromName: "Atom")
            );
            $mailer->send(new ConfiguredMailable($template, $attachment));

            $email = $transport->message;
            $this->assertInstanceOf(Email::class, $email);
            $this->assertSame("sender@example.com", $email->getFrom()[0]->getAddress());
            $this->assertSame("person@example.com", $email->getTo()[0]->getAddress());
            $this->assertSame("Message subject", $email->getSubject());
            $this->assertSame("Plain message", $email->getTextBody());
            $this->assertSame("<strong>Edin</strong>", $email->getHtmlBody());
            $this->assertCount(1, $email->getAttachments());
        } finally {
            @unlink($template);
            @unlink($attachment);
            @rmdir($directory);
        }
    }

    public function testMissingMailTemplateRaisesFrameworkException(): void
    {
        $this->expectException(MailException::class);
        $this->expectExceptionMessage("does not exist");

        (new MailTemplateRenderer())->render(new MailTemplate("missing-template.php"));
    }

    public function testDefaultNullTransportCanSafelyAcceptMail(): void
    {
        $mailer = new SymfonyMailer(
            new Mailer(Transport::fromDsn("null://null")),
            new MailTemplateRenderer(),
            new MailOptions()
        );

        $mailer->send(new TestMailable());
        $this->addToAssertionCount(1);
    }

    public function testAccountsPasswordResetMailProvidesTextAndHtmlContent(): void
    {
        $message = (new PasswordResetMail(
            "person@example.com",
            "https://example.com/account/reset-password?token=secret",
            "Edin"
        ))->build();

        $this->assertSame("Reset your password", $message->subjectLine());
        $this->assertSame("Edin", $message->recipients()[0]->name);
        $this->assertStringContainsString("https://example.com", $message->textContent() ?? "");

        $template = $message->htmlView();
        $this->assertNotNull($template);
        $html = (new MailTemplateRenderer())->render($template);
        $this->assertStringContainsString("Reset password", $html);
        $this->assertStringContainsString("token=secret", $html);
    }
}

final class TestMailable extends Mailable
{
    public function build(): MailMessage
    {
        return MailMessage::create()
            ->to("person@example.com")
            ->subject("Welcome")
            ->text("Hello");
    }
}

final class ConfiguredMailable extends Mailable
{
    public function __construct(
        private readonly string $template,
        private readonly string $attachment
    ) {
    }

    public function build(): MailMessage
    {
        return MailMessage::create()
            ->to("person@example.com", "Person")
            ->replyTo("help@example.com")
            ->subject("Message subject")
            ->text("Plain message")
            ->htmlTemplate($this->template, ["name" => "Edin"])
            ->attach($this->attachment, "report.txt", "text/plain");
    }
}

final class CapturingTransport implements TransportInterface
{
    public ?RawMessage $message = null;

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        $this->message = $message;
        return null;
    }

    public function __toString(): string
    {
        return "capture://";
    }
}
