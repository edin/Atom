<?php

declare(strict_types=1);

namespace Atom\Database\Schema;

use Atom\Database\Schema\Operation\SchemaOperation;

final class SchemaBatch
{
    /**
     * @param SchemaOperation[] $operations
     */
    public function __construct(private array $operations = [])
    {
    }

    public function add(SchemaOperation $operation): self
    {
        $this->operations[] = $operation;
        return $this;
    }

    public function isEmpty(): bool
    {
        return $this->operations === [];
    }

    /**
     * @return SchemaOperation[]
     */
    public function operations(): array
    {
        return $this->operations;
    }
}

