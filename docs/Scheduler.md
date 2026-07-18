# Task Scheduler

[Atom Framework](Index.md)

Atom's scheduler evaluates application and module schedules from one CLI command. It does not run a
daemon inside PHP-FPM or mod_php.

Define application tasks with the `schedule()` hook:

```php
use Atom\Scheduler\Schedule;

protected function schedule(Schedule $schedule): void
{
    $schedule->command("cache:prune")->daily();

    $schedule->job(new GenerateDailyReport())
        ->weekdays()
        ->at("08:00")
        ->timezone("Europe/Sarajevo");
}
```

Command arguments are passed as CLI tokens:

```php
$schedule->command("queue:failed", ["--queue=mail"])->hourly();
```

Scheduled jobs use the configured queue driver. With `sync` they run during `schedule:run`; with
`file` or `database` they are dispatched for a queue worker.

## Frequencies

Tasks support explicit cron expressions and common fluent frequencies:

```php
$task->cron("15 6 * * 1-5");
$task->everyMinute();
$task->everyFiveMinutes();
$task->everyTenMinutes();
$task->everyFifteenMinutes();
$task->everyThirtyMinutes();
$task->hourly();
$task->daily();
$task->weekly();
$task->monthly();
$task->weekdays();
$task->weekends();
$task->at("14:30");
$task->timezone("Europe/Sarajevo");
$task->name("Readable diagnostic name");
```

`at()` uses 24-hour `HH:MM` format. Tasks default to UTC unless a timezone is selected explicitly.

## Module Schedules

Modules contribute their own tasks through `ModuleContext`:

```php
public function register(ModuleContext $context): void
{
    $context->schedule(static function (Schedule $schedule): void {
        $schedule->command("accounts:prune-reset-tokens")->daily();
    });
}
```

Application and module tasks appear together in `schedule:list`.

## Running the Scheduler

Show configured tasks, cron expressions, timezones, and next run times:

```powershell
php atom schedule:list
```

Run every task due in the current minute:

```powershell
php atom schedule:run
```

Configure one system cron entry to invoke it each minute:

```cron
* * * * * cd /path/to/application && php atom schedule:run
```

The command continues through all due tasks and returns a failure status when any scheduled task
fails. Command output is included beneath its task in the scheduler diagnostics.
