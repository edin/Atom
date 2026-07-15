<?php

declare(strict_types=1);

namespace Atom\Database\Schema\Operation;

use Atom\Database\Schema\Table;

final readonly class AlterTableOperation implements SchemaOperationInterface
{
    public function __construct(public Table $table)
    {
    }
}

