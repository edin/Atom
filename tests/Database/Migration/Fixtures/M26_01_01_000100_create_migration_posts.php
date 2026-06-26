<?php

use Atom\Database\Migration\Migration;
use Atom\Database\Schema\Schema;

return new class extends Migration
{
    public function up(Schema $schema): void
    {
        $schema->create("migration_posts", function ($table): void {
            $table->id();
            $table->string("title");
        });
    }

    public function down(Schema $schema): void
    {
        $schema->drop("migration_posts");
    }
};
