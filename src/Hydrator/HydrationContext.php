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
        return $this->resolve($source, $name)["value"];
    }

    /** @return array{found: bool, value: mixed} */
    public function resolve(string $source, string $name): array
    {
        return match ($source) {
            "body" => $this->arrayValue($this->body, $name),
            "query" => $this->arrayValue($this->query, $name),
            "route" => $this->arrayValue($this->route, $name),
            "header" => $this->request !== null && $this->request->headers()->has($name)
                ? ["found" => true, "value" => $this->request->headers()->get($name)]
                : ["found" => false, "value" => null],
            "file" => $this->request !== null && $this->request->files()->has($name)
                ? ["found" => true, "value" => $this->request->files()->get($name)]
                : ["found" => false, "value" => null],
            default => $this->automaticValue($name),
        };
    }

    /** @param array<string, mixed> $values @return array{found: bool, value: mixed} */
    private function arrayValue(array $values, string $name): array
    {
        return array_key_exists($name, $values)
            ? ["found" => true, "value" => $values[$name]]
            : ["found" => false, "value" => null];
    }

    /** @return array{found: bool, value: mixed} */
    private function automaticValue(string $name): array
    {
        foreach ([$this->body, $this->query, $this->route] as $values) {
            if (array_key_exists($name, $values)) {
                return ["found" => true, "value" => $values[$name]];
            }
        }
        return ["found" => false, "value" => null];
    }
}
