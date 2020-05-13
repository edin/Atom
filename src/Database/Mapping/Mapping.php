<?php

declare(strict_types=1);

namespace Atom\Database\Mapping;

use Closure;
use phpDocumentor\Reflection\Types\Callable_;

final class Mapping
{
    private array $mapping = [];
    private ?string $table = null;

    public static function create($callable)
    {
        $mapping = new Mapping();
        $callable($mapping);
        return $mapping;
    }

    public function __get($name)
    {
        return $this->property($name);
    }

    public function property(string $name): FieldMapping
    {
        if (!isset($this->mapping[$name])) {
            return $this->mapping[$name] = new FieldMapping($name);
        }
        return $this->mapping[$name];
    }

    public function table(string $name)
    {
        $this->table = $name;
    }

    public function getTableName(): string
    {
        return $this->table;
    }

    public function hasSinglePrimaryKey(): bool
    {
        return count($this->getPrimaryKeys()) === 1;
    }

    public function getPrimaryKey(): ?FieldMapping
    {
        $keys = $this->getPrimaryKeys();
        if (count($keys) === 1) {
            return $keys[0];
        }
        return null;
    }

    /**
     * @return FieldMapping[]
     */
    public function getPrimaryKeys(): array
    {
        return array_values(
            array_filter($this->mapping, function ($it) {
                return $it->isPrimaryKey();
            })
        );
    }

    /**
     * @return FieldMapping[]
     */
    public function getFieldMapping(): array
    {
        return $this->mapping;
    }

    public function filter(Closure $filter): array
    {
        return array_filter($this->mapping, $filter);
    }
}
