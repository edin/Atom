<?php

declare(strict_types=1);

namespace Atom\Queue;

use Throwable;

final readonly class FileQueue implements QueueInterface
{
    public function __construct(private string $directory)
    {
    }

    public function push(JobEnvelope $job): void
    {
        $this->write($this->path("pending", $job), $job->toJson());
    }

    public function reserve(string $queue, int $retryAfter): ?ReservedJob
    {
        $this->recoverExpired($queue, $retryAfter);
        $directory = $this->queueDirectory("pending", $queue);
        if (!is_dir($directory)) {
            return null;
        }

        $files = glob($directory . DIRECTORY_SEPARATOR . "*.json") ?: [];
        sort($files);

        foreach ($files as $source) {
            $job = $this->readJob($source);
            if ($job->availableAt > time()) {
                continue;
            }

            $reservationId = bin2hex(random_bytes(16));
            $destination = $this->path("reserved", $job);
            $this->createDirectory(dirname($destination));
            if (!@rename($source, $destination)) {
                continue;
            }

            $reserved = $job->reserved($reservationId, time());
            $this->write($destination, $reserved->toJson());
            return new ReservedJob($reserved, $reservationId);
        }

        return null;
    }

    public function complete(ReservedJob $job): void
    {
        $this->deleteReserved($job);
    }

    public function release(ReservedJob $job, int $delay = 0): void
    {
        $source = $this->reservedPath($job);
        $released = $job->job->released(time() + max(0, $delay));
        $this->write($source, $released->toJson());
        $destination = $this->path("pending", $released);
        $this->createDirectory(dirname($destination));

        if (!rename($source, $destination)) {
            throw new QueueException("Cannot release queued job '{$job->job->id}'.");
        }
    }

    public function fail(ReservedJob $job, Throwable $exception): void
    {
        $source = $this->reservedPath($job);
        $destination = $this->path("failed", $job->job);
        $failure = json_encode([
            "job" => $job->job->toArray(),
            "exception" => $exception::class . ": " . $exception->getMessage(),
            "failedAt" => time(),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $this->write($source, $failure);
        $this->createDirectory(dirname($destination));

        if (!rename($source, $destination)) {
            throw new QueueException("Cannot fail queued job '{$job->job->id}'.");
        }
    }

    public function failed(string $queue = "default"): array
    {
        $directory = $this->queueDirectory("failed", $queue);
        $failures = [];
        foreach (glob($directory . DIRECTORY_SEPARATOR . "*.json") ?: [] as $file) {
            $data = json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
            if (!is_array($data) || !is_array($data["job"] ?? null)) {
                continue;
            }
            $failures[] = new FailedJob(
                JobEnvelope::fromJson(json_encode($data["job"], JSON_THROW_ON_ERROR)),
                (string) ($data["exception"] ?? ""),
                (int) ($data["failedAt"] ?? 0)
            );
        }

        return $failures;
    }

    private function recoverExpired(string $queue, int $retryAfter): void
    {
        $directory = $this->queueDirectory("reserved", $queue);
        foreach (glob($directory . DIRECTORY_SEPARATOR . "*.json") ?: [] as $source) {
            $job = $this->readJob($source);
            if (($job->reservedAt ?? time()) > time() - $retryAfter) {
                continue;
            }

            $released = $job->released(time());
            $this->write($source, $released->toJson());
            $destination = $this->path("pending", $released);
            $this->createDirectory(dirname($destination));
            @rename($source, $destination);
        }
    }

    private function deleteReserved(ReservedJob $job): void
    {
        $path = $this->reservedPath($job);
        if (!unlink($path)) {
            throw new QueueException("Cannot complete queued job '{$job->job->id}'.");
        }
    }

    private function reservedPath(ReservedJob $job): string
    {
        $path = $this->path("reserved", $job->job);
        $stored = $this->readJob($path);
        if ($stored->reservationId !== $job->reservationId) {
            throw new QueueException("Job '{$job->job->id}' is not reserved by this worker.");
        }
        return $path;
    }

    private function readJob(string $path): JobEnvelope
    {
        $json = file_get_contents($path);
        if ($json === false) {
            throw new QueueException("Cannot read queued job '{$path}'.");
        }
        return JobEnvelope::fromJson($json);
    }

    private function path(string $state, JobEnvelope $job): string
    {
        return $this->queueDirectory($state, $job->queue) . DIRECTORY_SEPARATOR . $job->id . ".json";
    }

    private function queueDirectory(string $state, string $queue): string
    {
        return rtrim($this->directory, "/\\") . DIRECTORY_SEPARATOR . $state . DIRECTORY_SEPARATOR . $queue;
    }

    private function write(string $path, string $contents): void
    {
        $this->createDirectory(dirname($path));
        $temporary = $path . "." . bin2hex(random_bytes(6)) . ".tmp";
        if (file_put_contents($temporary, $contents, LOCK_EX) === false || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new QueueException("Cannot write queued job '{$path}'.");
        }
    }

    private function createDirectory(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new QueueException("Cannot create queue directory '{$directory}'.");
        }
    }
}
