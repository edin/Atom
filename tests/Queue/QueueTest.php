<?php

declare(strict_types=1);

namespace Atom\Tests\Queue;

use Atom\Database\DatabaseConnection;
use Atom\Database\Driver\SqliteDriver;
use Atom\Database\Driver\MySqlDriver;
use Atom\Database\Driver\PostgresDriver;
use Atom\Database\Migration\Migration;
use Atom\Database\Schema\Schema;
use Atom\Console\BufferedConsoleOutput;
use Atom\Console\ConsoleApplication;
use Atom\Console\ConsoleServices;
use Atom\Di\Bindings;
use Atom\Di\Injector;
use Atom\Di\ServiceProviderRegistry;
use Atom\Publish\PublishServices;
use Atom\Queue\ArrayQueue;
use Atom\Queue\DatabaseQueue;
use Atom\Queue\FailedJob;
use Atom\Queue\FileQueue;
use Atom\Queue\JobEnvelope;
use Atom\Queue\Job;
use Atom\Queue\JobExecutor;
use Atom\Queue\JobInterface;
use Atom\Queue\JobRegistry;
use Atom\Queue\QueueJobDispatcher;
use Atom\Queue\QueueOptions;
use Atom\Queue\QueueException;
use Atom\Queue\QueueServices;
use Atom\Queue\QueueWorker;
use Atom\Queue\WorkerResult;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Atom\Support\Paths;

final class QueueTest extends TestCase
{
    public function testJobConventionRoundTripsConstructorProperties(): void
    {
        $job = new ConventionalJob("hello", attempts: 2, label: null, metadata: ["source" => "test"]);

        $payload = $job->payload();
        $restored = ConventionalJob::fromPayload($payload);

        $this->assertSame([
            "value" => "hello",
            "attempts" => 2,
            "label" => null,
            "metadata" => ["source" => "test"],
        ], $payload);
        $this->assertSame("hello", $restored->value);
        $this->assertSame(2, $restored->attempts);
        $this->assertNull($restored->label);
        $this->assertSame(["source" => "test"], $restored->metadata);
    }

    public function testJobConventionUsesDefaultsForMissingOptionalFields(): void
    {
        $job = ConventionalJob::fromPayload(["value" => "hello"]);

        $this->assertSame(1, $job->attempts);
        $this->assertNull($job->label);
        $this->assertSame([], $job->metadata);
    }

    public function testJobConventionRejectsMissingUnknownAndInvalidFields(): void
    {
        foreach ([
            [[], "missing required payload field 'value'"],
            [["value" => "hello", "extra" => true], "unknown payload field 'extra'"],
            [["value" => 42], "must be string"],
        ] as [$payload, $message]) {
            try {
                ConventionalJob::fromPayload($payload);
                $this->fail("Expected invalid conventional job payload to fail.");
            } catch (QueueException $exception) {
                $this->assertStringContainsString($message, $exception->getMessage());
            }
        }
    }

    public function testJobConventionRejectsNonJsonSafeValues(): void
    {
        $this->expectException(QueueException::class);
        $this->expectExceptionMessage("must be JSON-safe");

        (new MixedPayloadJob(new \stdClass()))->payload();
    }

    public function testEnvelopeRoundTripsAsJsonWithoutSerializingObjects(): void
    {
        $envelope = JobEnvelope::for(new RecordingJob("hello"), delay: 10);
        $decoded = JobEnvelope::fromJson($envelope->toJson());

        $this->assertSame($envelope->id, $decoded->id);
        $this->assertSame("test.record", $decoded->type);
        $this->assertSame(["value" => "hello"], $decoded->payload);
        $this->assertGreaterThan(time(), $decoded->availableAt);
    }

    public function testArrayQueueWorkerExecutesRegisteredJobThroughInjector(): void
    {
        $handler = new RecordingJobHandler();
        [$queue, $dispatcher, $worker] = $this->runtime($handler);

        $id = $dispatcher->dispatch(new RecordingJob("hello"));
        $result = $worker->runOnce();

        $this->assertNotSame("", $id);
        $this->assertSame(WorkerResult::Completed, $result);
        $this->assertSame(["hello"], $handler->handled);
        $this->assertSame([], $queue->pending());
    }

    public function testWorkerReleasesThenPermanentlyFailsAJob(): void
    {
        $handler = new RecordingJobHandler(failuresRemaining: 2);
        [$queue, $dispatcher, $worker] = $this->runtime($handler, new QueueOptions(
            retryDelay: 0,
            maxAttempts: 2
        ));
        $dispatcher->dispatch(new RecordingJob("fail"));

        $this->assertSame(WorkerResult::Released, $worker->runOnce());
        $this->assertSame(WorkerResult::Failed, $worker->runOnce());
        $this->assertCount(1, $queue->failed());
        $this->assertSame(2, $queue->failed()[0]->job->attempts);
        $this->assertStringContainsString("Job failed", $queue->failed()[0]->exception);
    }

    public function testFileQueueAllowsOnlyOneWorkerToClaimAJob(): void
    {
        $directory = $this->tempDirectory();
        try {
            $first = new FileQueue($directory);
            $second = new FileQueue($directory);
            $job = JobEnvelope::for(new RecordingJob("file"));
            $first->push($job);

            $reservation = $first->reserve("default", 90);

            $this->assertNotNull($reservation);
            $this->assertNull($second->reserve("default", 90));
            $first->complete($reservation);
            $this->assertNull($first->reserve("default", 90));
        } finally {
            $this->removeDirectory($directory);
        }
    }

    public function testFileQueueStoresPermanentFailures(): void
    {
        $directory = $this->tempDirectory();
        try {
            $queue = new FileQueue($directory);
            $queue->push(JobEnvelope::for(new RecordingJob("file")));
            $reservation = $queue->reserve("default", 90);
            $this->assertNotNull($reservation);

            $queue->fail($reservation, new RuntimeException("broken"));
            $failures = $queue->failed();

            $this->assertCount(1, $failures);
            $this->assertInstanceOf(FailedJob::class, $failures[0]);
            $this->assertStringContainsString("broken", $failures[0]->exception);
        } finally {
            $this->removeDirectory($directory);
        }
    }

    public function testDatabaseQueueClaimsCompletesAndFailsJobs(): void
    {
        $connection = new DatabaseConnection(SqliteDriver::memory());
        $this->migrateQueue($connection);
        $first = new DatabaseQueue($connection);
        $second = new DatabaseQueue($connection);

        $first->push(JobEnvelope::for(new RecordingJob("database")));
        $reservation = $first->reserve("default", 90);

        $this->assertNotNull($reservation);
        $this->assertNull($second->reserve("default", 90));
        $first->complete($reservation);
        $this->assertSame(0, (int) $connection->scalar("SELECT COUNT(*) FROM atom_jobs"));

        $first->push(JobEnvelope::for(new RecordingJob("failure")));
        $failedReservation = $first->reserve("default", 90);
        $this->assertNotNull($failedReservation);
        $first->fail($failedReservation, new RuntimeException("database broken"));

        $failures = $first->failed();
        $this->assertCount(1, $failures);
        $this->assertSame("test.record", $failures[0]->job->type);
        $this->assertStringContainsString("database broken", $failures[0]->exception);
        $this->assertSame(0, (int) $connection->scalar("SELECT COUNT(*) FROM atom_jobs"));
    }

    public function testPublishedMigrationCompilesForEverySupportedDatabase(): void
    {
        $migration = require __DIR__ . "/../../src/Queue/Resources/M0001_create_atom_queue_tables.php";
        $this->assertInstanceOf(Migration::class, $migration);

        foreach ([
            new SqliteDriver(":memory:"),
            new MySqlDriver("atom"),
            new PostgresDriver("atom"),
        ] as $driver) {
            $schema = new Schema();
            $migration->up($schema);
            $sql = implode("\n", $driver->schemaCompiler()->compile($schema)->sql());

            $this->assertStringContainsString("atom_jobs", $sql);
            $this->assertStringContainsString("atom_failed_jobs", $sql);
        }
    }

    public function testDelayedJobsAreNotReservedEarly(): void
    {
        $queue = new ArrayQueue();
        $queue->push(JobEnvelope::for(new RecordingJob("later"), delay: 60));

        $this->assertNull($queue->reserve("default", 90));
    }

    public function testQueueServicesExposeCommandsAndPublishDatabaseMigration(): void
    {
        $root = $this->tempDirectory();
        mkdir($root);
        $providers = ServiceProviderRegistry::create()
            ->add(ConsoleServices::class)
            ->add(PublishServices::class)
            ->add(QueueServices::class);
        $paths = (new Paths($root))
            ->alias("migrations", $root . "/app/Database/Migrations");
        $bindings = $providers->bindings()
            ->value(ServiceProviderRegistry::class, $providers)
            ->value(Paths::class, $paths)
            ->value(QueueOptions::class, new QueueOptions());
        $console = Injector::create($bindings)->get(ConsoleApplication::class);

        try {
            $this->assertTrue($console->commands()->has("queue:once"));
            $this->assertTrue($console->commands()->has("queue:work"));
            $this->assertTrue($console->commands()->has("queue:failed"));
            $this->assertTrue($console->commands()->has("queue:publish"));

            $output = new BufferedConsoleOutput();
            $code = $console->run(["atom", "queue:publish"], $output);

            $this->assertSame(0, $code);
            $this->assertFileExists($root . "/app/Database/Migrations/M0001_create_atom_queue_tables.php");
            $this->assertStringContainsString("Published:", $output->output());
        } finally {
            $this->removeDirectory($root);
        }
    }

    /** @return array{ArrayQueue, QueueJobDispatcher, QueueWorker} */
    private function runtime(
        RecordingJobHandler $handler,
        ?QueueOptions $options = null
    ): array {
        $options ??= new QueueOptions(retryDelay: 0);
        $registry = (new JobRegistry())->register(RecordingJob::class);
        $bindings = Bindings::create()->value(RecordingJobHandler::class, $handler);
        $executor = new JobExecutor($registry, Injector::create($bindings));
        $queue = new ArrayQueue();

        return [
            $queue,
            new QueueJobDispatcher($queue, $options),
            new QueueWorker($queue, $executor, $options),
        ];
    }

    private function migrateQueue(DatabaseConnection $connection): void
    {
        $migration = require __DIR__ . "/../../src/Queue/Resources/M0001_create_atom_queue_tables.php";
        $this->assertInstanceOf(Migration::class, $migration);
        $schema = new Schema($connection->driver()->schemaInspector($connection));
        $migration->up($schema);
        $plan = $connection->driver()->schemaCompiler()->compile($schema);
        foreach ($plan->commands() as $command) {
            $connection->execute($command);
        }
    }

    private function tempDirectory(): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . "atom_queue_" . bin2hex(random_bytes(6));
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if (!$file instanceof \SplFileInfo) {
                continue;
            }
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($directory);
    }
}

final readonly class RecordingJob implements JobInterface
{
    public function __construct(private string $value)
    {
    }

    public static function type(): string
    {
        return "test.record";
    }

    public function payload(): array
    {
        return ["value" => $this->value];
    }

    public static function fromPayload(array $payload): self
    {
        return new self((string) ($payload["value"] ?? ""));
    }

    public function handle(RecordingJobHandler $handler): void
    {
        $handler->handle($this->value);
    }
}

final readonly class ConventionalJob extends Job
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public string $value,
        public int $attempts = 1,
        public ?string $label = null,
        public array $metadata = []
    ) {
    }

    public static function type(): string
    {
        return "test.conventional";
    }
}

final readonly class MixedPayloadJob extends Job
{
    public function __construct(public mixed $value)
    {
    }

    public static function type(): string
    {
        return "test.mixed-payload";
    }
}

final class RecordingJobHandler
{
    /** @var string[] */
    public array $handled = [];

    public function __construct(public int $failuresRemaining = 0)
    {
    }

    public function handle(string $value): void
    {
        if ($this->failuresRemaining > 0) {
            $this->failuresRemaining--;
            throw new RuntimeException("Job failed");
        }
        $this->handled[] = $value;
    }
}
