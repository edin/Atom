<?php

declare(strict_types=1);

namespace Atom\Database\Migration;

use Atom\Database\Schema\Schema;

abstract class Migration
{
    abstract public function up(Schema $schema): void;

    public function down(Schema $schema): void
    {
    }
}
