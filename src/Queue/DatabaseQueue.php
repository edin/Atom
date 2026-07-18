<?php

declare(strict_types=1);

namespace Atom\Queue;

use Atom\Database\DatabaseConnection;
use JsonException;
use Throwable;

final readonly class DatabaseQueue implements QueueInterface
{
    public function __construct(
        private DatabaseConnection $connection,
        private string $table = "atom_jobs",
        private string $failedTable = "atom_failed_jobs"
    ) {
        $this->identifier($this->table);
        $this->identifier($this->failedTable);
    }

    public function push(JobEnvelope $job): void
    {
        $this->connection->execute(
            "INSERT INTO {$this->table} (id, queue, type, payload, attempts, available_at, reserved_at, reservation_id, created_at) " .
            "VALUES (:id, :queue, :type, :payload, :attempts, :available_at, NULL, NULL, :created_at)",
            [
                "id" => $job->id,
                "queue" => $job->queue,
                "type" => $job->type,
                "payload" => $this->payloadJson($job),
                "attempts" => $job->attempts,
                "available_at" => $job->availableAt,
                "created_at" => $job->createdAt,
            ]
        );
    }

    public function reserve(string $queue, int $retryAfter): ?ReservedJob
    {
        $now = time();
        $expired = $now - max(1, $retryAfter);

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $row = $this->connection->first(
                "SELECT id, queue, type, payload, attempts, available_at, reserved_at, reservation_id, created_at " .
                "FROM {$this->table} WHERE queue = :queue AND available_at <= :now " .
                "AND (reserved_at IS NULL OR reserved_at <= :expired) ORDER BY available_at, id LIMIT 1",
                ["queue" => $queue, "now" => $now, "expired" => $expired]
            );
            if ($row === null) {
                return null;
            }

            $reservationId = bin2hex(random_bytes(16));
            $affected = $this->connection->execute(
                "UPDATE {$this->table} SET reserved_at = :now, reservation_id = :reservation_id, attempts = attempts + 1 " .
                "WHERE id = :id AND (reserved_at IS NULL OR reserved_at <= :expired)",
                [
                    "now" => $now,
                    "reservation_id" => $reservationId,
                    "id" => (string) $row["id"],
                    "expired" => $expired,
                ]
            );
            if ($affected === 1) {
                $job = $this->job($row)->reserved($reservationId, $now);
                return new ReservedJob($job, $reservationId);
            }
        }

        return null;
    }

    public function complete(ReservedJob $job): void
    {
        $this->guardedExecute(
            "DELETE FROM {$this->table} WHERE id = :id AND reservation_id = :reservation_id",
            $job,
            "complete"
        );
    }

    public function release(ReservedJob $job, int $delay = 0): void
    {
        $this->guardedExecute(
            "UPDATE {$this->table} SET available_at = :available_at, reserved_at = NULL, reservation_id = NULL " .
            "WHERE id = :id AND reservation_id = :reservation_id",
            $job,
            "release",
            ["available_at" => time() + max(0, $delay)]
        );
    }

    public function fail(ReservedJob $job, Throwable $exception): void
    {
        $this->connection->transaction(function () use ($job, $exception): void {
            $this->connection->execute(
                "INSERT INTO {$this->failedTable} (id, queue, type, payload, attempts, exception, failed_at) " .
                "VALUES (:id, :queue, :type, :payload, :attempts, :exception, :failed_at)",
                [
                    "id" => $job->job->id,
                    "queue" => $job->job->queue,
                    "type" => $job->job->type,
                    "payload" => $this->payloadJson($job->job),
                    "attempts" => $job->job->attempts,
                    "exception" => $exception::class . ": " . $exception->getMessage(),
                    "failed_at" => time(),
                ]
            );
            $this->guardedExecute(
                "DELETE FROM {$this->table} WHERE id = :id AND reservation_id = :reservation_id",
                $job,
                "fail"
            );
        });
    }

    public function failed(string $queue = "default"): array
    {
        $rows = $this->connection->all(
            "SELECT id, queue, type, payload, attempts, exception, failed_at FROM {$this->failedTable} " .
            "WHERE queue = :queue ORDER BY failed_at DESC, id",
            ["queue" => $queue]
        );

        return array_map(function (array $row): FailedJob {
            $job = new JobEnvelope(
                (string) $row["id"],
                (string) $row["queue"],
                (string) $row["type"],
                $this->decodePayload((string) $row["payload"]),
                (int) $row["attempts"],
                0,
                0
            );
            return new FailedJob($job, (string) $row["exception"], (int) $row["failed_at"]);
        }, $rows);
    }

    /** @param array<string, mixed> $row */
    private function job(array $row): JobEnvelope
    {
        return new JobEnvelope(
            (string) $row["id"],
            (string) $row["queue"],
            (string) $row["type"],
            $this->decodePayload((string) $row["payload"]),
            (int) $row["attempts"],
            (int) $row["available_at"],
            (int) $row["created_at"],
            $row["reserved_at"] === null ? null : (int) $row["reserved_at"],
            $row["reservation_id"] === null ? null : (string) $row["reservation_id"]
        );
    }

    /** @return array<string, mixed> */
    private function decodePayload(string $payload): array
    {
        try {
            $decoded = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new QueueException("Cannot decode database job payload.", previous: $exception);
        }

        if (!is_array($decoded)) {
            throw new QueueException("Database job payload must be an object.");
        }
        return $decoded;
    }

    private function payloadJson(JobEnvelope $job): string
    {
        try {
            return json_encode($job->payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException $exception) {
            throw new QueueException("Cannot encode database job '{$job->id}'.", previous: $exception);
        }
    }

    /** @param array<string, mixed> $parameters */
    private function guardedExecute(
        string $sql,
        ReservedJob $job,
        string $operation,
        array $parameters = []
    ): void {
        $affected = $this->connection->execute($sql, [
            ...$parameters,
            "id" => $job->job->id,
            "reservation_id" => $job->reservationId,
        ]);
        if ($affected !== 1) {
            throw new QueueException("Cannot {$operation} job '{$job->job->id}'; its reservation is no longer owned.");
        }
    }

    private function identifier(string $identifier): void
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier) !== 1) {
            throw new QueueException("Invalid queue table '{$identifier}'.");
        }
    }
}
