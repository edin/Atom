# Background Jobs and Queues

[Atom Framework](Index.md)

Atom queues explicit JSON job payloads and resolves job dependencies through the injector. PHP
objects are never serialized into queue storage.

## Defining Jobs

A job declares a stable type, its JSON-safe payload, and how to rebuild itself. Its public
`handle()` method can request framework or application services:

```php
use Atom\Mail\MailerInterface;
use Atom\Modules\Accounts\Mail\PasswordResetMail;
use Atom\Queue\JobInterface;

final readonly class SendPasswordResetJob implements JobInterface
{
    public function __construct(
        private string $email,
        private string $resetUrl
    ) {
    }

    public static function type(): string
    {
        return "accounts.send-password-reset";
    }

    public function payload(): array
    {
        return ["email" => $this->email, "resetUrl" => $this->resetUrl];
    }

    public static function fromPayload(array $payload): self
    {
        return new self((string) $payload["email"], (string) $payload["resetUrl"]);
    }

    public function handle(MailerInterface $mailer): void
    {
        $mailer->send(new PasswordResetMail($this->email, $this->resetUrl));
    }
}
```

Register job classes from the application:

```php
protected function jobs(JobRegistry $jobs): void
{
    $jobs->register(SendPasswordResetJob::class);
}
```

Modules register the jobs they own through their module context, so applications do not repeat
module registrations:

```php
public function register(ModuleContext $context): void
{
    $context->jobs(
        SendPasswordResetJob::class,
        SendVerificationMailJob::class
    );
}
```

Application and module jobs share one registry. Registering two different classes with the same
stable type fails during initialization.

Dispatch immediately or with a delay:

```php
$id = $dispatcher->dispatch(new SendPasswordResetJob($email, $url));
$id = $dispatcher->dispatch(new SendReportJob($reportId), delay: 300, queue: "reports");
```

## Drivers

The default `sync` driver executes jobs in the current process and is suitable during development.
It intentionally ignores delays.

The file driver stores one JSON document per job under `storage/queue`. Atomic moves between
`pending`, `reserved`, and `failed` directories prevent two local workers from claiming the same
job:

```env
QUEUE_DRIVER=file
QUEUE_NAME=default
```

The database driver uses the conventional `atom_jobs` and `atom_failed_jobs` tables. It claims jobs
with guarded updates and reservation identifiers, which works with SQLite, MySQL, and PostgreSQL:

```env
QUEUE_DRIVER=database
QUEUE_NAME=default
```

Publish and run its migration once:

```powershell
php atom queue:publish
php atom migrate
```

Existing migration files are not overwritten unless `queue:publish --force` is used.

## Workers and Retries

Process one available job:

```powershell
php atom queue:once
```

Run a persistent CLI worker under a process supervisor:

```powershell
php atom queue:work
php atom queue:work --queue=reports
```

The worker releases failed attempts after `QUEUE_RETRY_DELAY` seconds. After
`QUEUE_MAX_ATTEMPTS`, it moves the job to failed storage. A reservation older than
`QUEUE_RETRY_AFTER` can be claimed by another worker, recovering jobs abandoned by a terminated
process.

```env
QUEUE_RETRY_AFTER=90
QUEUE_RETRY_DELAY=5
QUEUE_MAX_ATTEMPTS=3
QUEUE_SLEEP=1
```

List permanent failures with `php atom queue:failed`. `ArrayQueue` is available for isolated unit
tests where storage state needs to be inspected directly.

File queues are intended for a single application server with persistent local storage. Use the
database driver when workers run on multiple servers or application filesystems are ephemeral.

Jobs can also be dispatched on recurring cron expressions through the [task scheduler](Scheduler.md).
