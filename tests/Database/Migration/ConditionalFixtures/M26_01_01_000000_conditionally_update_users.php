<?php

use Atom\Database\Migration\Migration;
use Atom\Database\Schema\Schema;

return new class extends Migration
{
    public function up(Schema $schema): void
    {
        if (!$schema->hasTable("conditional_users")) {
            $schema->create("conditional_users", function ($table): void {
                $table->id();
            });
        }

        if (!$schema->hasColumn("conditional_users", "email")) {
            $schema->table("conditional_users", function ($table): void {
                $table->string("email");
            });
        }
    }
};
