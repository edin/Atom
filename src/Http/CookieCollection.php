<?php

declare(strict_types=1);

namespace Atom\Http;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use LogicException;
use Traversable;

/**
 * @implements ArrayAccess<string, string>
 * @implements IteratorAggregate<string, string>
 */
final readonly class CookieCollection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @param array<string, string> $items
     */
    public function __construct(private array $items = [])
    {
    }

    public static function fromHeader(string $header): self
    {
        $items = [];

        foreach (explode(";", $header) as $pair) {
            $pair = trim($pair);
            if ($pair === "" || !str_contains($pair, "=")) {
                continue;
            }

            [$name, $value] = explode("=", $pair, 2);
            $name = trim($name);
            if ($name === "" || array_key_exists($name, $items)) {
                continue;
            }

            $value = trim($value);
            if (strlen($value) >= 2 && $value[0] === '"' && $value[strlen($value) - 1] === '"') {
                $value = substr($value, 1, -1);
            }

            $items[$name] = rawurldecode($value);
        }

        return new self($items);
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->items);
    }

    public function get(string $name, ?string $default = null): ?string
    {
        return $this->items[$name] ?? $default;
    }

    public function string(string $name, string $default = ""): string
    {
        return $this->get($name, $default) ?? $default;
    }

    /**
     * @param string[] $names
     * @return array<string, string>
     */
    public function only(array $names): array
    {
        return array_intersect_key($this->items, array_flip($names));
    }

    /**
     * @param string[] $names
     * @return array<string, string>
     */
    public function except(array $names): array
    {
        return array_diff_key($this->items, array_flip($names));
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function jsonSerialize(): mixed
    {
        return $this->items;
    }

    public function offsetExists(mixed $offset): bool
    {
        return is_string($offset) && $this->has($offset);
    }

    public function offsetGet(mixed $offset): ?string
    {
        return is_string($offset) ? $this->get($offset) : null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException("Request cookies are read-only.");
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException("Request cookies are read-only.");
    }
}
