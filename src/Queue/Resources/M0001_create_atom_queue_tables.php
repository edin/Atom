<?php

use Atom\Database\Migration\Migration;
use Atom\Database\Schema\Schema;
use Atom\Database\Schema\Table;

return new class extends Migration
{
    public function up(Schema $schema): void
    {
        $schema->create("atom_jobs", function (Table $table): void {
            $table->string("id", 64)->primary();
            $table->string("queue", 100);
            $table->string("type", 190);
            $table->text("payload");
            $table->integer("attempts")->default(0);
            $table->bigInteger("available_at");
            $table->bigInteger("reserved_at")->nullable();
            $table->string("reservation_id", 64)->nullable();
            $table->bigInteger("created_at");
            $table->index(["queue", "available_at", "reserved_at"], "atom_jobs_available");
        });

        $schema->create("atom_failed_jobs", function (Table $table): void {
            $table->string("id", 64)->primary();
            $table->string("queue", 100);
            $table->string("type", 190);
            $table->text("payload");
            $table->integer("attempts");
            $table->text("exception");
            $table->bigInteger("failed_at");
            $table->index(["queue", "failed_at"], "atom_failed_jobs_queue");
        });
    }

    public function down(Schema $schema): void
    {
        $schema->drop("atom_failed_jobs");
        $schema->drop("atom_jobs");
    }
};
