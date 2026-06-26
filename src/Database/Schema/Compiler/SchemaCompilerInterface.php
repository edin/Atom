<?php

declare(strict_types=1);

namespace Atom\Database\Schema\Compiler;

use Atom\Database\Schema\Schema;
use Atom\Database\Schema\SchemaPlan;

interface SchemaCompilerInterface
{
    public function compile(Schema $schema): SchemaPlan;
}

