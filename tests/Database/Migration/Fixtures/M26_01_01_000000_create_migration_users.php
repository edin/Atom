<?php

use Atom\Database\Migration\Migration;
use Atom\Database\Schema\Schema;

return new class extends Migration
{
    public function up(Schema $schema): void
    {
        $schema->create("migration_users", function ($table): void {
            $table->id();
            $table->string("name");
        });

        $schema->create("seed_users", function ($table): void {
            $table->id();
            $table->string("name");
        });
    }

    public function down(Schema $schema): void
    {
        $schema->drop("seed_users");
        $schema->drop("migration_users");
    }
};
