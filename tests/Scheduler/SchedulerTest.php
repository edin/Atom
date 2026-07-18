<?php

declare(strict_types=1);

namespace Atom\Tests\Scheduler;

use Atom\Application;
use Atom\Console\BufferedConsoleOutput;
use Atom\Console\CommandInterface;
use Atom\Console\ConsoleApplication;
use Atom\Console\ConsoleInput;
use Atom\Console\ConsoleOutput;
use Atom\Di\Injector;
use Atom\Queue\JobDispatcherInterface;
use Atom\Queue\JobInterface;
use Atom\Queue\JobRegistry;
use Atom\Scheduler\ClockInterface;
use Atom\Scheduler\Commands\ScheduleCommands;
use Atom\Scheduler\Schedule;
use Atom\Scheduler\ScheduleRunner;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SchedulerTest extends TestCase
{
    protected function tearDown(): void
    {
        Application::$app = null;
    }

    public function testFluentFrequenciesBuildCronExpressions(): void
    {
        $schedule = new Schedule();

        $this->assertSame("*/5 * * * *", $schedule->command("one")->everyFiveMinutes()->expression());
        $this->assertSame("0 * * * *", $schedule->command("two")->hourly()->expression());
        $this->assertSame("30 8 * * 1-5", $schedule->command("three")->weekdays()->at("08:30")->expression());
        $this->assertSame("0 0 1 * *", $schedule->command("four")->monthly()->expression());
    }

    public function testInvalidScheduledTimeIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Schedule())->command("test")->at("25:00");
    }

    public function testRunnerExecutesDueCommandsAndJobsButSkipsOtherTasks(): void
    {
        $schedule = new Schedule();
        $schedule->command("test:record", ["hello"])->weekdays()->at("08:00")->timezone("Europe/Sarajevo");
        $schedule->job(new ScheduledFixtureJob("mail"))->weekdays()->at("08:00")->timezone("Europe/Sarajevo");
        $schedule->command("test:record", ["later"])->daily()->at("09:00")->timezone("Europe/Sarajevo");

        $command = new RecordingCommand();
        $console = (new ConsoleApplication(Injector::create()))->command("test:record", $command);
        $jobs = new RecordingJobDispatcher();
        $clock = new FrozenClock(new DateTimeImmutable("2026-07-20 06:00:00 UTC"));
        $result = (new ScheduleRunner($schedule, $clock, $console, $jobs))->run();

        $this->assertSame(2, $result->count());
        $this->assertSame(0, $result->failed());
        $this->assertSame([["hello"]], $command->arguments);
        $this->assertCount(1, $jobs->jobs);
        $this->assertSame("mail", $jobs->jobs[0]->payload()["value"]);
    }

    public function testRunnerContinuesAfterACommandFailure(): void
    {
        $schedule = new Schedule();
        $schedule->command("test:fail");
        $schedule->command("test:record");
        $recording = new RecordingCommand();
        $console = (new ConsoleApplication(Injector::create()))
            ->command("test:fail", new FailingCommand())
            ->command("test:record", $recording);
        $runner = new ScheduleRunner(
            $schedule,
            new FrozenClock(new DateTimeImmutable("2026-07-20 12:00:00 UTC")),
            $console,
            new RecordingJobDispatcher()
        );

        $result = $runner->run();

        $this->assertSame(2, $result->count());
        $this->assertSame(1, $result->failed());
        $this->assertCount(1, $recording->arguments);
    }

    public function testScheduleCommandsRenderRunAndListDiagnostics(): void
    {
        $schedule = new Schedule();
        $schedule->command("test:record")->daily()->at("08:00")->name("Daily record");
        $clock = new FrozenClock(new DateTimeImmutable("2026-07-20 08:00:00 UTC"));
        $console = (new ConsoleApplication(Injector::create()))
            ->command("test:record", new RecordingCommand());
        $runner = new ScheduleRunner($schedule, $clock, $console, new RecordingJobDispatcher());

        $listOutput = new BufferedConsoleOutput();
        $this->assertSame(0, (new ScheduleCommands($schedule, $runner, $clock, $listOutput))->list());
        $this->assertStringContainsString("0 8 * * *  UTC  Daily record", $listOutput->output());
        $this->assertStringContainsString("next:", $listOutput->output());

        $runOutput = new BufferedConsoleOutput();
        $this->assertSame(0, (new ScheduleCommands($schedule, $runner, $clock, $runOutput))->run());
        $this->assertStringContainsString("Daily record  completed", $runOutput->output());
    }

    public function testApplicationHookRegistersScheduleAndConsoleCommands(): void
    {
        ScheduledFixtureJob::$handled = false;
        $app = new SchedulerApplication();
        $app->initialize();

        $this->assertTrue($app->getConsole()->commands()->has("schedule:run"));
        $this->assertTrue($app->getConsole()->commands()->has("schedule:list"));

        $list = new BufferedConsoleOutput();
        $this->assertSame(0, $app->getConsole()->run(["atom", "schedule:list"], $list));
        $this->assertStringContainsString("Scheduled fixture", $list->output());

        $run = new BufferedConsoleOutput();
        $this->assertSame(0, $app->getConsole()->run(["atom", "schedule:run"], $run));
        $this->assertTrue(ScheduledFixtureJob::$handled);
    }
}

final readonly class FrozenClock implements ClockInterface
{
    public function __construct(private DateTimeImmutable $time)
    {
    }

    public function now(): DateTimeImmutable
    {
        return $this->time;
    }
}

final class RecordingCommand implements CommandInterface
{
    /** @var array<int, string[]> */
    public array $arguments = [];

    public function handle(ConsoleInput $input, ConsoleOutput $output): int
    {
        $this->arguments[] = $input->arguments();
        $output->line("recorded");
        return 0;
    }
}

final readonly class FailingCommand implements CommandInterface
{
    public function handle(ConsoleInput $input, ConsoleOutput $output): int
    {
        $output->errorLine("failed");
        return 1;
    }
}

final class ScheduledFixtureJob implements JobInterface
{
    public static bool $handled = false;

    public function __construct(private string $value)
    {
    }

    public static function type(): string
    {
        return "schedule.fixture";
    }

    public function payload(): array
    {
        return ["value" => $this->value];
    }

    public static function fromPayload(array $payload): self
    {
        return new self((string) ($payload["value"] ?? ""));
    }

    public function handle(): void
    {
        self::$handled = true;
    }
}

final class SchedulerApplication extends Application
{
    protected function jobs(JobRegistry $jobs): void
    {
        $jobs->register(ScheduledFixtureJob::class);
    }

    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new ScheduledFixtureJob("application"))
            ->everyMinute()
            ->name("Scheduled fixture");
    }
}

final class RecordingJobDispatcher implements JobDispatcherInterface
{
    /** @var JobInterface[] */
    public array $jobs = [];

    public function dispatch(JobInterface $job, int $delay = 0, ?string $queue = null): string
    {
        $this->jobs[] = $job;
        return "scheduled-job";
    }
}
