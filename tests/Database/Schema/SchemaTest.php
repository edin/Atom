<?php

declare(strict_types=1);

namespace Atom\Tests\Database\Schema;

use Atom\Database\Schema\ColumnType;
use Atom\Database\Schema\Operation\AddTableOperation;
use Atom\Database\Schema\Operation\AlterTableOperation;
use Atom\Database\Schema\Operation\DropTableOperation;
use Atom\Database\Schema\Schema;
use Atom\Database\Schema\Inspector\SchemaInspectorInterface;
use PHPUnit\Framework\TestCase;

final class SchemaTest extends TestCase
{
    public function testCreateTableCollectsColumnsAndIndexes(): void
    {
        $schema = new Schema();

        $schema->create("users", function ($table): void {
            $table->id();
            $table->string("email", 120)->unique();
            $table->string("name")->nullable();
            $table->boolean("active")->default(true);
            $table->timestamps();
            $table->index("email");
        });

        $this->assertCount(1, $schema->batches());
        $operation = $schema->operations()[0];

        $this->assertInstanceOf(AddTableOperation::class, $operation);
        $this->assertSame("users", $operation->table->name);
        $this->assertCount(6, $operation->table->columns());

        [$id, $email, $name, $active, $createdAt, $updatedAt] = $operation->table->columns();

        $this->assertSame("id", $id->name);
        $this->assertSame(ColumnType::Integer, $id->type);
        $this->assertTrue($id->primary);
        $this->assertTrue($id->autoIncrement);

        $this->assertSame("email", $email->name);
        $this->assertSame(120, $email->length);
        $this->assertTrue($email->unique);

        $this->assertTrue($name->nullable);
        $this->assertTrue($active->default);
        $this->assertSame("created_at", $createdAt->name);
        $this->assertSame("updated_at", $updatedAt->name);

        $this->assertCount(1, $operation->table->indexes());
        $this->assertSame(["email"], $operation->table->indexes()[0]->columns);
    }

    public function testAlterTableCollectsColumnChanges(): void
    {
        $schema = new Schema();

        $schema->table("users", function ($table): void {
            $table->string("status", 30)->default("active");
            $table->dropColumn("legacy_status");
            $table->unique(["email", "status"], "users_email_status_unique");
        });

        $operation = $schema->operations()[0];

        $this->assertInstanceOf(AlterTableOperation::class, $operation);
        $this->assertSame("users", $operation->table->name);
        $this->assertSame("status", $operation->table->columns()[0]->name);
        $this->assertSame("active", $operation->table->columns()[0]->default);
        $this->assertSame(["legacy_status"], $operation->table->droppedColumns());
        $this->assertTrue($operation->table->indexes()[0]->unique);
        $this->assertSame(["email", "status"], $operation->table->indexes()[0]->columns);
    }

    public function testTableCollectsExpandedColumnTypes(): void
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

        $columns = $schema->operations()[0]->table->columns();

        $this->assertSame(ColumnType::BigInteger, $columns[0]->type);
        $this->assertSame(ColumnType::Float, $columns[1]->type);
        $this->assertSame(ColumnType::Decimal, $columns[2]->type);
        $this->assertSame(12, $columns[2]->precision);
        $this->assertSame(4, $columns[2]->scale);
        $this->assertSame(ColumnType::Date, $columns[3]->type);
        $this->assertSame(ColumnType::Json, $columns[4]->type);
        $this->assertSame(ColumnType::Binary, $columns[5]->type);
        $this->assertSame(ColumnType::Uuid, $columns[6]->type);
    }

    public function testDropTableCollectsDropOperation(): void
    {
        $schema = new Schema();

        $schema->drop("users");

        $operation = $schema->operations()[0];

        $this->assertInstanceOf(DropTableOperation::class, $operation);
        $this->assertSame("users", $operation->table);
    }

    public function testTopLevelOperationsCreateImplicitBatches(): void
    {
        $schema = new Schema();

        $schema
            ->create("users", fn($table) => $table->id())
            ->table("users", fn($table) => $table->string("email"))
            ->drop("old_users");

        $this->assertCount(3, $schema->batches());
        $this->assertInstanceOf(AddTableOperation::class, $schema->batches()[0]->operations()[0]);
        $this->assertInstanceOf(AlterTableOperation::class, $schema->batches()[1]->operations()[0]);
        $this->assertInstanceOf(DropTableOperation::class, $schema->batches()[2]->operations()[0]);
    }

    public function testExplicitBatchCanGroupOperations(): void
    {
        $schema = new Schema();

        $schema->batch(function (Schema $schema): void {
            $schema->create("users", fn($table) => $table->id());
            $schema->create("posts", fn($table) => $table->id());
        });

        $this->assertCount(1, $schema->batches());
        $this->assertCount(2, $schema->batches()[0]->operations());
    }

    public function testSchemaInspectorDefaultsToMissingObjects(): void
    {
        $schema = new Schema();

        $this->assertFalse($schema->hasTable("users"));
        $this->assertFalse($schema->hasColumn("users", "email"));
        $this->assertSame([], $schema->columns("users"));
    }

    public function testSchemaCanInspectExistingTablesAndColumns(): void
    {
        $schema = new Schema(new FakeSchemaInspector([
            "users" => ["id", "email"],
        ]));

        $this->assertTrue($schema->hasTable("users"));
        $this->assertTrue($schema->hasColumn("users", "email"));
        $this->assertFalse($schema->hasColumn("users", "missing"));
        $this->assertSame(["id", "email"], $schema->columns("users"));
    }

    public function testInspectorCanBeUsedToConditionallyCollectChanges(): void
    {
        $schema = new Schema(new FakeSchemaInspector([
            "users" => ["id"],
        ]));

        if (!$schema->hasColumn("users", "email")) {
            $schema->table("users", fn($table) => $table->string("email"));
        }

        if (!$schema->hasTable("users")) {
            $schema->create("users", fn($table) => $table->id());
        }

        $this->assertCount(1, $schema->operations());
        $this->assertInstanceOf(AlterTableOperation::class, $schema->operations()[0]);
    }
}

final readonly class FakeSchemaInspector implements SchemaInspectorInterface
{
    /**
     * @param array<string, string[]> $tables
     */
    public function __construct(private array $tables)
    {
    }

    public function hasTable(string $table): bool
    {
        return array_key_exists($table, $this->tables);
    }

    public function hasColumn(string $table, string $column): bool
    {
        return in_array($column, $this->tables[$table] ?? [], true);
    }

    public function columns(string $table): array
    {
        return $this->tables[$table] ?? [];
    }
}
