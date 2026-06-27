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
use Atom\Database\DatabaseServices;
use Atom\Database\DatabaseDriverFactory;
use Atom\Database\Driver\SqliteDriver;
use Atom\Database\Migration\MigrationOptions;
use Atom\Database\Seeder\SeederOptions;

protected function services(ServiceProviderRegistry $providers): void
{
    $providers->add(new DatabaseServices(
        new SqliteDriver(__DIR__ . "/../storage/app.sqlite"),
        new MigrationOptions(__DIR__ . "/Database/Migrations"),
        new SeederOptions(__DIR__ . "/Database/Seeders")
    ));
}
```

Or build the driver from environment configuration:

```php
use Atom\Config\Env;
use Atom\Database\DatabaseDriverFactory;

Env::loadIfExists(dirname(__DIR__) . "/.env");

$driver = DatabaseDriverFactory::fromEnv(dirname(__DIR__));
```

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

PostgreSQL is planned, but the framework currently throws a clear error for `pgsql`, `postgres`, or `postgresql`.

Configure the model base during bootstrap:

```php
use Atom\Database\Db;
use Atom\Database\Model;

protected function bootstrap(Injector $injector): void
{
    Model::useDb($injector->get(Db::class));
}
```

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
