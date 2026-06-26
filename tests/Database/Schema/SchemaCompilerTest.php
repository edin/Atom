<?php

declare(strict_types=1);

namespace Atom\Tests\Database\Schema;

use Atom\Database\Schema\Compiler\MySqlSchemaCompiler;
use Atom\Database\Schema\Compiler\SqliteSchemaCompiler;
use Atom\Database\Schema\Schema;
use PHPUnit\Framework\TestCase;

final class SchemaCompilerTest extends TestCase
{
    public function testSqliteCompilerCreatesTableAndIndexesInSeparateBatches(): void
    {
        $schema = new Schema();
        $schema->create("users", function ($table): void {
            $table->id();
            $table->string("email", 120)->unique();
            $table->string("name")->nullable();
            $table->boolean("active")->default(true);
            $table->timestamps();
            $table->index("name");
        });

        $plan = (new SqliteSchemaCompiler())->compile($schema);

        $this->assertCount(2, $plan->batches());
        $this->assertSame([
            'CREATE TABLE "users" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "email" VARCHAR(120) NOT NULL, "name" VARCHAR(255), "active" INTEGER NOT NULL DEFAULT 1, "created_at" DATETIME, "updated_at" DATETIME)',
            'CREATE INDEX "users_name_index" ON "users" ("name")',
            'CREATE UNIQUE INDEX "users_email_unique" ON "users" ("email")',
        ], $plan->sql());
    }

    public function testMysqlCompilerCreatesTableAndIndexesInSeparateBatches(): void
    {
        $schema = new Schema();
        $schema->create("users", function ($table): void {
            $table->id();
            $table->string("email", 120)->unique();
            $table->text("bio")->nullable();
        });

        $plan = (new MySqlSchemaCompiler())->compile($schema);

        $this->assertCount(2, $plan->batches());
        $this->assertSame([
            'CREATE TABLE `users` (`id` INT PRIMARY KEY AUTO_INCREMENT, `email` VARCHAR(120) NOT NULL, `bio` TEXT)',
            'CREATE UNIQUE INDEX `users_email_unique` ON `users` (`email`)',
        ], $plan->sql());
    }

    public function testCompilerAltersAndDropsTables(): void
    {
        $schema = new Schema();
        $schema
            ->table("users", function ($table): void {
                $table->string("status", 30)->default("active");
                $table->dropColumn("legacy_status");
                $table->unique(["email", "status"], "users_email_status_unique");
            })
            ->drop("old_users");

        $plan = (new MySqlSchemaCompiler())->compile($schema);

        $this->assertCount(3, $plan->batches());
        $this->assertSame([
            "ALTER TABLE `users` ADD COLUMN `status` VARCHAR(30) NOT NULL DEFAULT 'active'",
            "ALTER TABLE `users` DROP COLUMN `legacy_status`",
            "CREATE UNIQUE INDEX `users_email_status_unique` ON `users` (`email`, `status`)",
            "DROP TABLE `old_users`",
        ], $plan->sql());
    }

    public function testSqliteCompilerMapsExpandedColumnTypes(): void
    {
        $schema = new Schema();
        $schema->create("typed", function ($table): void {
            $table->bigInteger("views");
            $table->float("rating");
            $table->decimal("price", 12, 4);
            $table->date("published_on");
            $table->json("metadata");
            $table->binary("payload");
            $table->uuid("public_id");
        });

        $plan = (new SqliteSchemaCompiler())->compile($schema);

        $this->assertSame([
            'CREATE TABLE "typed" ("views" INTEGER NOT NULL, "rating" REAL NOT NULL, "price" NUMERIC(12, 4) NOT NULL, "published_on" DATE NOT NULL, "metadata" TEXT NOT NULL, "payload" BLOB NOT NULL, "public_id" CHAR(36) NOT NULL)',
        ], $plan->sql());
    }

    public function testMySqlCompilerMapsExpandedColumnTypes(): void
    {
        $schema = new Schema();
        $schema->create("typed", function ($table): void {
            $table->bigInteger("views");
            $table->float("rating");
            $table->decimal("price", 12, 4);
            $table->date("published_on");
            $table->json("metadata");
            $table->binary("payload");
            $table->uuid("public_id");
            $table->timestamp("created_at");
        });

        $plan = (new MySqlSchemaCompiler())->compile($schema);

        $this->assertSame([
            'CREATE TABLE `typed` (`views` BIGINT NOT NULL, `rating` DOUBLE NOT NULL, `price` DECIMAL(12, 4) NOT NULL, `published_on` DATE NOT NULL, `metadata` JSON NOT NULL, `payload` BLOB NOT NULL, `public_id` CHAR(36) NOT NULL, `created_at` TIMESTAMP NOT NULL)',
        ], $plan->sql());
    }

    public function testExplicitSchemaBatchesArePreservedAndIndexesAreSplitPerBatch(): void
    {
        $schema = new Schema();
        $schema->batch(function (Schema $schema): void {
            $schema->create("users", function ($table): void {
                $table->id();
                $table->index("id");
            });
            $schema->create("posts", function ($table): void {
                $table->id();
            });
        });

        $plan = (new SqliteSchemaCompiler())->compile($schema);

        $this->assertCount(2, $plan->batches());
        $this->assertCount(2, $plan->batches()[0]->commands());
        $this->assertCount(1, $plan->batches()[1]->commands());
    }
}
