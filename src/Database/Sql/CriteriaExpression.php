<?php

declare(strict_types=1);

namespace Atom\Database\Sql;

final readonly class CriteriaExpression
{
    /**
     * @param array<string, mixed> $parameters
     */
    private function __construct(
        public string $boolean,
        public string $expression,
        public array $parameters = []
    ) {
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public static function and(string $expression, array $parameters = []): self
    {
        return new self("AND", $expression, $parameters);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public static function or(string $expression, array $parameters = []): self
    {
        return new self("OR", $expression, $parameters);
    }
}
