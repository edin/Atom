<?php

declare(strict_types=1);

namespace Atom\Database\Schema;

use Atom\Database\Schema\Operation\SchemaOperationInterface;

final class SchemaBatch
{
    /**
     * @param SchemaOperationInterface[] $operations
     */
    public function __construct(private array $operations = [])
    {
    }

    public function add(SchemaOperationInterface $operation): self
    {
        $this->operations[] = $operation;
        return $this;
    }

    public function isEmpty(): bool
    {
        return $this->operations === [];
    }

    /**
     * @return SchemaOperationInterface[]
     */
    public function operations(): array
    {
        return $this->operations;
    }
}

