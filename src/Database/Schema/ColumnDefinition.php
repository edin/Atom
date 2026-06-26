<?php

declare(strict_types=1);

namespace Atom\Database\Schema;

final class ColumnDefinition
{
    public bool $nullable = false;
    public bool $primary = false;
    public bool $autoIncrement = false;
    public bool $unique = false;
    public bool $indexed = false;

    public function __construct(
        public readonly string $name,
        public readonly ColumnType $type,
        public readonly ?int $length = null,
        public mixed $default = null
    ) {
    }

    public function nullable(bool $nullable = true): self
    {
        $this->nullable = $nullable;
        return $this;
    }

    public function default(mixed $value): self
    {
        $this->default = $value;
        return $this;
    }

    public function primary(bool $primary = true): self
    {
        $this->primary = $primary;
        return $this;
    }

    public function autoIncrement(bool $autoIncrement = true): self
    {
        $this->autoIncrement = $autoIncrement;
        return $this;
    }

    public function unique(bool $unique = true): self
    {
        $this->unique = $unique;
        return $this;
    }

    public function index(bool $indexed = true): self
    {
        $this->indexed = $indexed;
        return $this;
    }
}

