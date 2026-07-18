<?php

declare(strict_types=1);

namespace Atom\Queue;

use InvalidArgumentException;
use JsonException;

final readonly class JobEnvelope
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public string $id,
        public string $queue,
        public string $type,
        public array $payload,
        public int $attempts,
        public int $availableAt,
        public int $createdAt,
        public ?int $reservedAt = null,
        public ?string $reservationId = null
    ) {
    }

    public static function for(JobInterface $job, string $queue = "default", int $delay = 0): self
    {
        $now = time();

        return new self(
            bin2hex(random_bytes(16)),
            self::validName($queue, "queue"),
            self::validName($job::type(), "job type"),
            $job->payload(),
            0,
            $now + max(0, $delay),
            $now
        );
    }

    public function reserved(string $reservationId, int $at): self
    {
        return new self(
            $this->id,
            $this->queue,
            $this->type,
            $this->payload,
            $this->attempts + 1,
            $this->availableAt,
            $this->createdAt,
            $at,
            $reservationId
        );
    }

    public function released(int $availableAt): self
    {
        return new self(
            $this->id,
            $this->queue,
            $this->type,
            $this->payload,
            $this->attempts,
            $availableAt,
            $this->createdAt
        );
    }

    public function toJson(): string
    {
        try {
            return json_encode($this->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException $exception) {
            throw new QueueException("Cannot encode queued job '{$this->id}'.", previous: $exception);
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "queue" => $this->queue,
            "type" => $this->type,
            "payload" => $this->payload,
            "attempts" => $this->attempts,
            "availableAt" => $this->availableAt,
            "createdAt" => $this->createdAt,
            "reservedAt" => $this->reservedAt,
            "reservationId" => $this->reservationId,
        ];
    }

    public static function fromJson(string $json): self
    {
        try {
            $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new QueueException("Cannot decode queued job.", previous: $exception);
        }

        if (!is_array($data) || !is_array($data["payload"] ?? null)) {
            throw new QueueException("Queued job payload is invalid.");
        }

        return new self(
            (string) ($data["id"] ?? ""),
            self::validName((string) ($data["queue"] ?? ""), "queue"),
            self::validName((string) ($data["type"] ?? ""), "job type"),
            $data["payload"],
            (int) ($data["attempts"] ?? 0),
            (int) ($data["availableAt"] ?? 0),
            (int) ($data["createdAt"] ?? 0),
            isset($data["reservedAt"]) ? (int) $data["reservedAt"] : null,
            isset($data["reservationId"]) ? (string) $data["reservationId"] : null
        );
    }

    private static function validName(string $name, string $label): string
    {
        if (preg_match('/^[A-Za-z0-9_.:-]+$/', $name) !== 1) {
            throw new InvalidArgumentException("Invalid {$label} '{$name}'.");
        }

        return $name;
    }
}
