<?php

use Atom\Database\Migration\Migration;
use Atom\Database\Schema\Schema;
use Atom\Database\Schema\Table;

return new class extends Migration
{
    public function up(Schema $schema): void
    {
        $schema->create("password_reset_tokens", function (Table $table): void {
            $table->id();
            $table->string("login", 254);
            $table->string("token_hash", 64);
            $table->timestamp("expires_at");
            $table->timestamp("created_at");
            $table->index(["login", "token_hash"], "password_reset_tokens_lookup");
            $table->index("expires_at", "password_reset_tokens_expiry");
        });
    }

    public function down(Schema $schema): void
    {
        $schema->drop("password_reset_tokens");
    }
};
