# Mail

[Atom Framework](Index.md)

Atom wraps Symfony Mailer behind framework-owned messages and mailables. Application code does
not need to depend on Symfony types.

The default mail service is registered by `Application`. Configure its transport and sender with
environment values:

```env
MAIL_DSN=smtp://username:password@smtp.example.com:587
MAIL_FROM_ADDRESS=no-reply@example.com
MAIL_FROM_NAME="Example App"
```

The default DSN is `null://null`, which discards messages safely until a transport is configured.
The development sender defaults to `no-reply@localhost`; configure a real sender with any actual
transport.

## Mailables

Create a mailable that builds a framework `MailMessage`:

```php
use Atom\Mail\Mailable;
use Atom\Mail\MailMessage;

final class WelcomeMail extends Mailable
{
    public function __construct(private readonly User $user)
    {
    }

    public function build(): MailMessage
    {
        return MailMessage::create()
            ->to($this->user->email, $this->user->name)
            ->subject("Welcome")
            ->text("Welcome to the application.")
            ->htmlTemplate(__DIR__ . "/welcome.php", ["user" => $this->user]);
    }
}
```

Mail templates are plain PHP files. Variables passed to `htmlTemplate()` or `textTemplate()` are
available by name in the template. Escape untrusted output with `htmlspecialchars()`.

Inject `MailerInterface` and send the mailable:

```php
public function __construct(private readonly MailerInterface $mailer)
{
}

$this->mailer->send(new WelcomeMail($user));
```

`MailMessage` also supports explicit senders, CC, BCC, reply-to addresses, HTML or text content,
and file attachments. `ArrayMailer` records built messages for unit tests without contacting a
transport.
