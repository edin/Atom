<?php

declare(strict_types=1);

namespace Atom\Validation;

final readonly class ValidationContext
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public string $field,
        public mixed $target,
        public array $data = []
    ) {
    }
}

