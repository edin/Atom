<?php

declare(strict_types=1);

namespace Atom\Hydrator;

use Atom\Http\Request;

final readonly class HydrationContext
{
    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $query
     * @param array<string, mixed> $route
     */
    public function __construct(
        public array $body = [],
        public array $query = [],
        public array $route = [],
        public ?Request $request = null
    ) {
    }

    /**
     * @param array<string, mixed> $route
     */
    public static function fromRequest(Request $request, array $route = []): self
    {
        return new self(
            $request->post()->toArray(),
            $request->query()->toArray(),
            $route,
            $request
        );
    }

    public function get(string $source, string $name): mixed
    {
        return match ($source) {
            "body" => $this->body[$name] ?? null,
            "query" => $this->query[$name] ?? null,
            "route" => $this->route[$name] ?? null,
            "header" => $this->request?->headers()->get($name),
            "file" => $this->request?->files()->get($name),
            default => $this->body[$name] ?? $this->query[$name] ?? $this->route[$name] ?? null,
        };
    }
}
