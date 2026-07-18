<?php

declare(strict_types=1);

namespace Atom\Queue;

interface JobInterface
{
    public static function type(): string;

    /** @return array<string, mixed> */
    public function payload(): array;

    /** @param array<string, mixed> $payload */
    public static function fromPayload(array $payload): self;
}
