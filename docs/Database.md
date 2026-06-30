# Database

[Atom Framework](Index.md)

Atom's database layer has four parts:

- drivers and `DatabaseConnection` for PDO access
- SQL query builders under `Atom\Database\Sql`
- `Db` for connection-bound querying, hydration, persistence, and relation loading
- `Model` as a lightweight base class for application models

## Services

Register database services from the application:

```php
use Atom\Database\DatabaseConfig;
use Atom\Database\DatabasePaths;
use Atom\Database\DatabaseServices;

protected function services(ServiceProviderRegistry $providers): void
{
    $providers->add(DatabaseServices::fromConfig($this->getConfig(), $this->getPaths()));
}
```

Connection settings come from the typed `DatabaseConfig` options class.

The sample app reads its SQLite path from `.env`:

```env
DB_DRIVER=sqlite
DB_DATABASE=storage/atom_sample.sqlite
DB_HOST=localhost
DB_PORT=
DB_USERNAME=
DB_PASSWORD=
DB_CHARSET=utf8mb4
```

Supported driver values:

- `sqlite`
- `mysql`
- `mariadb`
- `pgsql`
- `postgres`
- `postgresql`

PostgreSQL has unit-level driver, SQL compiler, schema compiler, migration repository, inspector, resetter, and lock support. Real PostgreSQL integration tests are still optional and not part of the default test suite.

Database structure paths come from `DatabasePaths` and can use path aliases:

```php
$config->set(new DatabasePaths(
    root: "@root",
    migrations: "@app/Database/Migrations",
    seeders: "@app/Database/Seeders",
));
```

They can also be hydrated from environment values:

```env
DB_PATH_MIGRATIONS=@root/database/migrations
DB_PATH_SEEDERS=@root/database/seeders
```

`DatabaseServices` configures the `Model` base class during application startup, so model classes can use `query()`, `find()`, `save()`, and `delete()` without manual bootstrap code.

## Query Builder

The SQL query builder creates query objects. It does not execute anything by itself.

```php
use Atom\Database\Sql\Op;
use Atom\Database\Sql\Query;

$query = Query::select("users u")
    ->columns("u.id", "u.name", "p.name profileName")
    ->leftJoin("profiles p", function ($join): void {
        $join->on("p.user_id", Op::column("u.id"));
    })
    ->where("u.active", true)
    ->where("u.age", Op::gte(18))
    ->orderBy("u.name")
    ->limit(20);
```

Simple join strings are supported:

```php
Query::select("users u")
    ->leftJoin("profiles p on p.user_id = u.id");
```

Condition shortcuts:

```php
Query::select("users")
    ->where("active", true)       // active = true
    ->where("id", [1, 2, 3])      // id in (...)
    ->where("deleted_at", null);  // deleted_at is null
```

Raw criteria expressions can be used when needed:

```php
Query::select("users u")
    ->whereExp("u.id = :id and u.id < 100", ["id" => 2]);
```

Mutation builders:

```php
Query::insert("users")->values(["name" => "Edin"]);

Query::update("users")
    ->set(["name" => "Amar"])
    ->where("id", 1);

Query::delete("users")
    ->where("id", Op::gte(10));
```

## Db

`Db` binds query objects to a configured connection.

```php
$rows = $db
    ->select("users")
    ->columns("id", "name")
    ->where("active", true)
    ->orderBy("name")
    ->all();

$first = $db
    ->select("users")
    ->where("id", 1)
    ->first();

$total = $db
    ->select("users")
    ->where("active", true)
    ->total();
```

Selecting by class hydrates models/entities:

```php
$articles = $db
    ->select(Article::class)
    ->where("is_published", true)
    ->with("category")
    ->all();
```

## Models

Models extend `Atom\Database\Model` and use ORM attributes for metadata.

```php
namespace App\Models;

use Atom\Database\Model;
use Atom\Database\Orm\Attributes\Column;
use Atom\Database\Orm\Attributes\PrimaryKey;
use Atom\Database\Orm\Attributes\Table;
use Atom\Database\Orm\Provider\NowProvider;
use DateTimeImmutable;

#[Table("articles")]
final class Article extends Model
{
    #[PrimaryKey("id")]
    public int $id;

    #[Column("title")]
    public string $title;

    #[Column("body")]
    public string $body;

    #[Column("created_at", onInsert: NowProvider::class)]
    public DateTimeImmutable $createdAt;

    #[Column("updated_at", onInsert: NowProvider::class, onUpdate: NowProvider::class)]
    public DateTimeImmutable $updatedAt;
}
```

Query:

```php
$articles = Article::query()
    ->where("is_published", true)
    ->orderByDesc("created_at")
    ->limit(10)
    ->all();
```

Find by primary key:

```php
$article = Article::find(1);
```

Count:

```php
$total = Article::count();
```

Save and delete:

```php
$article = new Article();
$article->title = "Hello";
$article->body = "Body";
$article->save();

$article->title = "Updated";
$article->save();

$article->delete();
```

`save()` inserts when the primary key is empty and updates otherwise.

## Relations

Relations are configured with attributes and loaded explicitly with `with()`.

```php
use Atom\Database\Orm\Attributes\BelongsTo;
use Atom\Database\Orm\Attributes\Column;
use Atom\Database\Orm\Attributes\PrimaryKey;
use Atom\Database\Orm\Attributes\Table;

#[Table("categories")]
final class Category extends Model
{
    #[PrimaryKey("id")]
    public int $id;

    #[Column("name")]
    public string $name;
}

#[Table("articles")]
final class Article extends Model
{
    #[PrimaryKey("id")]
    public int $id;

    #[Column("category_id")]
    public int $categoryId;

    #[Column("title")]
    public string $title;

    #[BelongsTo(Category::class, foreignKey: "category_id")]
    public ?Category $category = null;
}
```

Load relation:

```php
$articles = Article::query()
    ->with("category")
    ->all();
```

Supported relation attributes:

- `BelongsTo`
- `HasOne`
- `HasMany`

## Migrations and Seeders

Database services register console commands automatically.

```powershell
php atom migrate:status
php atom migrate
php atom migrate:fresh
php atom migrate:rollback
php atom migrate:sql
php atom db:seed
php atom make:migration create_articles
php atom make:seeder seed_articles
```

Migration files may return anonymous migration classes:

```php
<?php

use Atom\Database\Migration\Migration;
use Atom\Database\Schema\Schema;

return new class extends Migration {
    public function up(Schema $schema): void
    {
        $schema->create("articles", function ($table): void {
            $table->id();
            $table->string("title");
            $table->timestamps();
        });
    }

    public function down(Schema $schema): void
    {
        $schema->drop("articles");
    }
};
```
