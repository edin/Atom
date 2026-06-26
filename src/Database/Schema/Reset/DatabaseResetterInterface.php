<?php

declare(strict_types=1);

namespace Atom\Database\Schema\Reset;

use Atom\Database\DatabaseConnection;

interface DatabaseResetterInterface
{
    public function reset(DatabaseConnection $connection): void;
}
