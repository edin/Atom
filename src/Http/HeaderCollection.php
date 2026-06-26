<?php

declare(strict_types=1);

namespace Atom\Http;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * @implements IteratorAggregate<string, string[]>
 */
final class HeaderCollection implements Countable, IteratorAggregate, JsonSerializable
{
    /** @var array<string, array{name: string, values: string[]}> */
    private array $headers = [];

    /**
     * @param array<string, string|string[]|int|float|bool|null> $headers
     */
    public function __construct(array $headers = [])
    {
        foreach ($headers as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    $this->add($name, $item);
                }
            } else {
                $this->set($name, $value);
            }
        }
    }

    /**
     * @param array<string, string|string[]|int|float|bool|null> $headers
     */
    public static function from(array $headers): self
    {
        return new self($headers);
    }

    public static function fromServer(array $server): self
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (!is_scalar($value)) {
                continue;
            }

            if (str_starts_with($key, "HTTP_")) {
                $name = str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($key, 5)))));
                $headers[$name] = (string) $value;
                continue;
            }

            if ($key === "CONTENT_TYPE" || $key === "CONTENT_LENGTH") {
                $name = str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", $key))));
                $headers[$name] = (string) $value;
            }
        }

        return new self($headers);
    }

    public function has(string $name): bool
    {
        return isset($this->headers[$this->normalize($name)]);
    }

    public function get(string $name, ?string $default = null): ?string
    {
        $values = $this->all($name);
        return $values === [] ? $default : implode(", ", $values);
    }

    /**
     * @return string[]
     */
    public function all(?string $name = null): array
    {
        if ($name === null) {
            return $this->toArray();
        }

        return $this->headers[$this->normalize($name)]["values"] ?? [];
    }

    public function set(string $name, string|int|float|bool|null $value): self
    {
        if ($value === null || $value === "") {
            $this->remove($name);
            return $this;
        }

        $this->headers[$this->normalize($name)] = [
            "name" => $name,
            "values" => [(string) $value],
        ];

        return $this;
    }

    public function add(string $name, string|int|float|bool $value): self
    {
        $key = $this->normalize($name);
        if (!isset($this->headers[$key])) {
            $this->headers[$key] = [
                "name" => $name,
                "values" => [],
            ];
        }

        $this->headers[$key]["values"][] = (string) $value;
        return $this;
    }

    public function remove(string $name): self
    {
        unset($this->headers[$this->normalize($name)]);
        return $this;
    }

    /**
     * @return array<string, string[]>
     */
    public function toArray(): array
    {
        $headers = [];
        foreach ($this->headers as $header) {
            $headers[$header["name"]] = $header["values"];
        }
        return $headers;
    }

    public function count(): int
    {
        return count($this->headers);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->toArray());
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    private function normalize(string $name): string
    {
        return strtolower($name);
    }
}
