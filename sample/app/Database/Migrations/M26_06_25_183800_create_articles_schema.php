<?php

use Atom\Database\Migration\Migration;
use Atom\Database\Schema\Schema;

return new class extends Migration
{
    public function up(Schema $schema): void
    {
        $schema->create("categories", function ($table): void {
            $table->id();
            $table->string("name");
            $table->string("slug");
        });

        $schema->create("articles", function ($table): void {
            $table->id();
            $table->integer("category_id");
            $table->string("title");
            $table->text("summary");
            $table->text("body");
            $table->boolean("is_published")->default(true);
            $table->timestamp("created_at");
            $table->timestamp("updated_at");
        });
    }

    public function down(Schema $schema): void
    {
        $schema->drop("articles");
        $schema->drop("categories");
    }
};
