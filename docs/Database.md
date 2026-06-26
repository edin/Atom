# Database

[Atom Framework](Index.md)

Atom's database layer has three parts:

- drivers and `DatabaseConnection` for PDO access
- SQL query builders under `Atom\Database\Sql`
- `Db` for bound queries, entity hydration, persistence, and relation loading

## Services

Register database services in the application service provider hook:

```php
use Atom\Database\DatabaseServices;
use Atom\Database\Driver\MySqlDriver;
use Atom\Di\ServiceProviderRegistry;

protected function services(ServiceProviderRegistry $providers): void
{
    $providers->add(new DatabaseServices(
        new MySqlDriver(database: "app", username: "root", password: "root")
    ));
}
```

SQLite is useful for tests and small sample apps:

```php
use Atom\Database\DatabaseServices;
use Atom\Database\Driver\SqliteDriver;

$providers->add(new DatabaseServices(new SqliteDriver(__DIR__ . "/app.sqlite")));
$providers->add(new DatabaseServices(SqliteDriver::memory()));
```

## Query Builder

The query builder creates SQL objects only. It does not know about connections until passed to `Db` or `DatabaseConnection`.

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

Simple join expressions can also be parsed from the table definition:

```php
Query::select("users u")
    ->leftJoin("profiles p on p.user_id = u.id");
```

Scalar values become equality conditions, arrays become `IN`, and `null` becomes `IS NULL`:

```php
Query::select("users")
    ->where("active", true)
    ->where("id", [1, 2, 3])
    ->where("deleted_at", null);
```

Use `whereExp()` for small raw criteria expressions with named parameters:

```php
Query::select("users u")
    ->whereExp("u.id = :id and u.id < 100", ["id" => 2]);
```

Grouping, aggregate columns, and having are supported:

```php
Query::select("orders")
    ->columns("customer_id")
    ->count("*", "total")
    ->groupBy("customer_id")
    ->having("total", Op::gt(1));
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

## Connections

`DatabaseConnection` compiles and executes query objects through the configured driver:

```php
use Atom\Database\DatabaseConnection;
use Atom\Database\Driver\SqliteDriver;
use Atom\Database\Sql\Query;

$connection = new DatabaseConnection(SqliteDriver::memory());

$connection->execute(Query::insert("users")->values(["name" => "Edin"]));

$row = $connection->first(Query::select("users")->columns("id", "name"));
$rows = $connection->all(Query::select("users"));
$count = $connection->scalar(Query::select("users")->count());
```

Transactions wrap a callback:

```php
$connection->transaction(function (DatabaseConnection $connection): void {
    $connection->execute(Query::insert("users")->values(["name" => "Edin"]));
});
```

## Bound Queries

`Db` creates queries bound to a connection and provides convenience result methods.

```php
use Atom\Database\Db;

$users = $db
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

## Entities

Use attributes to describe table and column metadata:

```php
use Atom\Database\Orm\Attributes\Column;
use Atom\Database\Orm\Attributes\PrimaryKey;
use Atom\Database\Orm\Attributes\Table;
use Atom\Database\Orm\Provider\NowProvider;
use DateTimeImmutable;

#[Table("users")]
final class User
{
    #[PrimaryKey("id")]
    public int $id;

    #[Column("name")]
    public string $name;

    #[Column("email")]
    public string $email;

    #[Column("created_at", onInsert: NowProvider::class)]
    public DateTimeImmutable $createdAt;

    #[Column("updated_at", onInsert: NowProvider::class, onUpdate: NowProvider::class)]
    public DateTimeImmutable $updatedAt;
}
```

Selecting by class hydrates entities:

```php
/** @var list<User> $users */
$users = $db
    ->select(User::class)
    ->where("active", true)
    ->all();

/** @var User|null $user */
$user = $db
    ->select(User::class)
    ->where("id", 1)
    ->first();
```

Persist entities with `insert`, `update`, `save`, and `delete`:

```php
$user = new User();
$user->name = "Edin";
$user->email = "edin@example.com";

$db->save($user);

$user->name = "Amar";
$db->save($user);

$db->delete($user);
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
final class Category
{
    #[PrimaryKey("id")]
    public int $id;

    #[Column("name")]
    public string $name;
}

#[Table("articles")]
final class Article
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

Load relations:

```php
$articles = $db
    ->select(Article::class)
    ->with("category")
    ->all();
```

Supported relation attributes:

- `BelongsTo`
- `HasOne`
- `HasMany`

Multiple relations can be loaded:

```php
$articles = $db
    ->select(Article::class)
    ->with("category", "comments")
    ->all();
```
