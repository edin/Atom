<?php

use Atom\Database\Migration\Migration;
use Atom\Database\Schema\Schema;
use Atom\Database\Schema\Table;

return new class extends Migration
{
    public function up(Schema $schema): void
    {
        $schema->create("users", function (Table $table): void {
            $table->id();
            $table->string("email", 254);
            $table->string("name")->default("");
            $table->string("password_hash");
            $table->timestamps();
            $table->unique("email", "users_email_unique");
        });
    }

    public function down(Schema $schema): void
    {
        $schema->drop("users");
    }
};
