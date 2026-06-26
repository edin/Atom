<?php

declare(strict_types=1);

namespace Atom\Tests\Database;

use Atom\Database\DatabaseConnection;
use Atom\Database\DatabaseDriver;
use Atom\Database\DatabaseServices;
use Atom\Database\Db;
use Atom\Database\Driver\SqliteDriver;
use Atom\Database\Model;
use Atom\Database\Orm\Attributes\BelongsTo;
use Atom\Database\Orm\Attributes\Column;
use Atom\Database\Orm\Attributes\HasMany;
use Atom\Database\Orm\Attributes\HasOne;
use Atom\Database\Orm\Attributes\PrimaryKey;
use Atom\Database\Orm\Attributes\Table;
use Atom\Database\Orm\Provider\NowProvider;
use Atom\Database\Sql\Query;
use DateTimeImmutable;
use Atom\Di\ServiceProviderRegistry;
use PHPUnit\Framework\TestCase;

final class DbTest extends TestCase
{
    public function testDbServiceExecutesQueries(): void
    {
        $db = new Db(new DatabaseConnection(SqliteDriver::memory()));
        $db->connection()->pdo()->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)");

        $db->execute(Query::insert("users")->values(["name" => "Edin"]));

        $this->assertSame(["name" => "Edin"], $db->first(Query::select("users")->columns("name")));
        $this->assertSame(1, (int) $db->scalar(Query::select("users")->count()));
    }

    public function testDbServiceExecutesRawSqlWithParameters(): void
    {
        $db = new Db(new DatabaseConnection(SqliteDriver::memory()));
        $db->connection()->pdo()->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)");

        $db->execute(
            "INSERT INTO users (name) VALUES (:name)",
            [":name" => "Edin"]
        );

        $this->assertSame("Edin", $db->scalar(
            "SELECT name FROM users WHERE name = :name",
            [":name" => "Edin"]
        ));
    }

    public function testDbCanHydrateRowsAsEntities(): void
    {
        $db = new Db(new DatabaseConnection(SqliteDriver::memory()));
        $db->connection()->pdo()->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)");
        $db->execute(Query::insert("users")->values(["name" => "Edin"]));

        $user = $db->firstAs(DbHydratedUser::class, Query::select("users")->columns("id", "name"));

        $this->assertInstanceOf(DbHydratedUser::class, $user);
        $this->assertSame(1, $user->id);
        $this->assertSame("Edin", $user->name);
    }

    public function testDbSelectCanReturnRows(): void
    {
        $db = new Db(new DatabaseConnection(SqliteDriver::memory()));
        $db->connection()->pdo()->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, active INTEGER)");
        $db->execute(Query::insert("users")->values(["name" => "Edin", "active" => true]));

        $rows = $db
            ->select("users")
            ->columns("id", "name")
            ->where("active", true)
            ->all();

        $this->assertSame([["id" => 1, "name" => "Edin"]], $rows);
    }

    public function testDbSelectCanReturnEntities(): void
    {
        $db = new Db(new DatabaseConnection(SqliteDriver::memory()));
        $db->connection()->pdo()->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)");
        $db->execute(Query::insert("users")->values(["name" => "Edin"]));

        $user = $db
            ->select(DbHydratedUser::class)
            ->where("id", 1)
            ->first();

        $this->assertInstanceOf(DbHydratedUser::class, $user);
        $this->assertSame(1, $user->id);
        $this->assertSame("Edin", $user->name);
    }

    public function testDbSelectCanReturnScalarAndTotal(): void
    {
        $db = new Db(new DatabaseConnection(SqliteDriver::memory()));
        $db->connection()->pdo()->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)");
        $db->execute(Query::insert("users")->values(["name" => "Edin"]));
        $db->execute(Query::insert("users")->values(["name" => "Amar"]));

        $firstName = $db->select("users")->columns("name")->orderBy("id")->scalar();
        $total = $db->select("users")->columns("id", "name")->total();

        $this->assertSame("Edin", $firstName);
        $this->assertSame(2, $total);
    }

    public function testDbCanInsertEntityAndFillAutoIncrementPrimaryKey(): void
    {
        $db = new Db(new DatabaseConnection(SqliteDriver::memory()));
        $db->connection()->pdo()->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)");

        $user = new DbHydratedUser();
        $user->name = "Edin";

        $affected = $db->insert($user);

        $this->assertSame(1, $affected);
        $this->assertSame(1, $user->id);
        $this->assertSame("Edin", $db->select(DbHydratedUser::class)->where("id", 1)->first()->name);
    }

    public function testDbCanUpdateEntity(): void
    {
        $db = new Db(new DatabaseConnection(SqliteDriver::memory()));
        $db->connection()->pdo()->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)");
        $db->execute(Query::insert("users")->values(["name" => "Edin"]));

        $user = $db->select(DbHydratedUser::class)->where("id", 1)->first();
        $user->name = "Atom";

        $affected = $db->update($user);

        $this->assertSame(1, $affected);
        $this->assertSame("Atom", $db->select(DbHydratedUser::class)->where("id", 1)->first()->name);
    }

    public function testDbCanSaveNewAndExistingEntity(): void
    {
        $db = new Db(new DatabaseConnection(SqliteDriver::memory()));
        $db->connection()->pdo()->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)");

        $user = new DbHydratedUser();
        $user->name = "Edin";

        $db->save($user);
        $user->name = "Atom";
        $db->save($user);

        $this->assertSame(1, $user->id);
        $this->assertSame("Atom", $db->select(DbHydratedUser::class)->where("id", 1)->first()->name);
        $this->assertSame(1, $db->select(DbHydratedUser::class)->total());
    }

    public function testDbUsesColumnValueProvidersOnInsertAndUpdate(): void
    {
        $db = new Db(new DatabaseConnection(SqliteDriver::memory()));
        $db->connection()->pdo()->exec(
            "CREATE TABLE timestamped_posts (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, created_at TEXT, updated_at TEXT)"
        );

        $post = new DbTimestampedPost();
        $post->title = "First";

        $db->insert($post);
        $createdAt = $post->createdAt;
        $updatedAt = $post->updatedAt;

        $this->assertInstanceOf(DateTimeImmutable::class, $createdAt);
        $this->assertInstanceOf(DateTimeImmutable::class, $updatedAt);

        $post->title = "Second";
        $db->update($post);

        $this->assertSame($createdAt, $post->createdAt);
        $this->assertInstanceOf(DateTimeImmutable::class, $post->updatedAt);
    }

    public function testDbCanDeleteEntity(): void
    {
        $db = new Db(new DatabaseConnection(SqliteDriver::memory()));
        $db->connection()->pdo()->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)");
        $db->execute(Query::insert("users")->values(["name" => "Edin"]));

        $user = $db->select(DbHydratedUser::class)->where("id", 1)->first();

        $affected = $db->delete($user);

        $this->assertSame(1, $affected);
        $this->assertNull($db->select(DbHydratedUser::class)->where("id", 1)->first());
    }

    public function testModelBaseCanQueryFindSaveAndDelete(): void
    {
        $db = new Db(new DatabaseConnection(SqliteDriver::memory()));
        $db->connection()->pdo()->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)");
        DbModelUser::useDb($db);

        $user = new DbModelUser();
        $user->name = "Edin";

        $user->save();

        $this->assertSame(1, $user->id);
        $this->assertSame(1, DbModelUser::count());
        $this->assertSame("Edin", DbModelUser::find(1)?->name);
        $this->assertSame("Edin", DbModelUser::query()->where("id", 1)->first()?->name);

        $user->name = "Atom";
        $user->save();

        $this->assertSame("Atom", DbModelUser::find(1)?->name);

        $user->delete();

        $this->assertNull(DbModelUser::find(1));
    }

    public function testDbSelectCanLoadBelongsToRelation(): void
    {
        $db = new Db(new DatabaseConnection(SqliteDriver::memory()));
        $db->connection()->pdo()->exec("CREATE TABLE categories (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)");
        $db->connection()->pdo()->exec("CREATE TABLE articles (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, category_id INTEGER)");

        $db->execute(Query::insert("categories")->values(["name" => "Tech"]));
        $db->execute(Query::insert("articles")->values(["title" => "Atom", "category_id" => 1]));

        $article = $db
            ->select(DbArticle::class)
            ->with("category")
            ->where("id", 1)
            ->first();

        $this->assertInstanceOf(DbArticle::class, $article);
        $this->assertInstanceOf(DbCategory::class, $article->category);
        $this->assertSame("Tech", $article->category->name);
    }

    public function testDbSelectLoadsBelongsToRelationForManyModelsInBatch(): void
    {
        $db = new Db(new DatabaseConnection(SqliteDriver::memory()));
        $db->connection()->pdo()->exec("CREATE TABLE categories (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)");
        $db->connection()->pdo()->exec("CREATE TABLE articles (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, category_id INTEGER)");

        $db->execute(Query::insert("categories")->values(["name" => "Tech"]));
        $db->execute(Query::insert("categories")->values(["name" => "Sports"]));
        $db->execute(Query::insert("articles")->values(["title" => "Atom", "category_id" => 1]));
        $db->execute(Query::insert("articles")->values(["title" => "BiH", "category_id" => 2]));

        $articles = $db
            ->select(DbArticle::class)
            ->with("category")
            ->orderBy("id")
            ->all();

        $this->assertCount(2, $articles);
        $this->assertSame("Tech", $articles[0]->category->name);
        $this->assertSame("Sports", $articles[1]->category->name);
    }

    public function testDbSelectCanLoadHasOneRelation(): void
    {
        $db = new Db(new DatabaseConnection(SqliteDriver::memory()));
        $db->connection()->pdo()->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)");
        $db->connection()->pdo()->exec("CREATE TABLE profiles (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, bio TEXT)");

        $db->execute(Query::insert("users")->values(["name" => "Edin"]));
        $db->execute(Query::insert("profiles")->values(["user_id" => 1, "bio" => "Builder"]));

        $user = $db
            ->select(DbUserWithProfile::class)
            ->with("profile")
            ->where("id", 1)
            ->first();

        $this->assertInstanceOf(DbUserWithProfile::class, $user);
        $this->assertInstanceOf(DbProfile::class, $user->profile);
        $this->assertSame("Builder", $user->profile->bio);
    }

    public function testDbSelectCanLoadHasManyRelation(): void
    {
        $db = new Db(new DatabaseConnection(SqliteDriver::memory()));
        $db->connection()->pdo()->exec("CREATE TABLE articles (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, category_id INTEGER)");
        $db->connection()->pdo()->exec("CREATE TABLE comments (id INTEGER PRIMARY KEY AUTOINCREMENT, article_id INTEGER, body TEXT)");

        $db->execute(Query::insert("articles")->values(["title" => "Atom", "category_id" => 1]));
        $db->execute(Query::insert("comments")->values(["article_id" => 1, "body" => "First"]));
        $db->execute(Query::insert("comments")->values(["article_id" => 1, "body" => "Second"]));

        $article = $db
            ->select(DbArticleWithComments::class)
            ->with("comments")
            ->where("id", 1)
            ->first();

        $this->assertInstanceOf(DbArticleWithComments::class, $article);
        $this->assertCount(2, $article->comments);
        $this->assertSame("First", $article->comments[0]->body);
        $this->assertSame("Second", $article->comments[1]->body);
    }

    public function testDatabaseServicesRegistersDriverConnectionAndDb(): void
    {
        $driver = SqliteDriver::memory();
        $injector = ServiceProviderRegistry::create()
            ->add(new DatabaseServices($driver))
            ->injector();

        $db = $injector->get(Db::class);
        $connection = $injector->get(DatabaseConnection::class);

        $this->assertSame($driver, $injector->get(DatabaseDriver::class));
        $this->assertSame($connection, $db->connection());
    }
}

#[Table("users")]
final class DbHydratedUser
{
    #[PrimaryKey("id")]
    public int $id;

    #[Column("name")]
    public string $name;
}

#[Table("users")]
final class DbModelUser extends Model
{
    #[PrimaryKey("id")]
    public int $id;

    #[Column("name")]
    public string $name;
}

#[Table("categories")]
final class DbCategory
{
    #[PrimaryKey("id")]
    public int $id;

    #[Column("name")]
    public string $name;
}

#[Table("articles")]
final class DbArticle
{
    #[PrimaryKey("id")]
    public int $id;

    #[Column("title")]
    public string $title;

    #[Column("category_id")]
    public int $categoryId;

    #[BelongsTo(DbCategory::class, foreignKey: "category_id", ownerKey: "id")]
    public ?DbCategory $category = null;
}

#[Table("users")]
final class DbUserWithProfile
{
    #[PrimaryKey("id")]
    public int $id;

    #[Column("name")]
    public string $name;

    #[HasOne(DbProfile::class, foreignKey: "user_id", localKey: "id")]
    public ?DbProfile $profile = null;
}

#[Table("profiles")]
final class DbProfile
{
    #[PrimaryKey("id")]
    public int $id;

    #[Column("user_id")]
    public int $userId;

    #[Column("bio")]
    public string $bio;
}

#[Table("articles")]
final class DbArticleWithComments
{
    #[PrimaryKey("id")]
    public int $id;

    #[Column("title")]
    public string $title;

    #[Column("category_id")]
    public int $categoryId;

    #[HasMany(DbComment::class, foreignKey: "article_id", localKey: "id")]
    public array $comments = [];
}

#[Table("comments")]
final class DbComment
{
    #[PrimaryKey("id")]
    public int $id;

    #[Column("article_id")]
    public int $articleId;

    #[Column("body")]
    public string $body;
}

#[Table("timestamped_posts")]
final class DbTimestampedPost
{
    #[PrimaryKey("id")]
    public int $id;

    #[Column("title")]
    public string $title;

    #[Column("created_at", onInsert: NowProvider::class)]
    public DateTimeImmutable $createdAt;

    #[Column("updated_at", onInsert: NowProvider::class, onUpdate: NowProvider::class)]
    public DateTimeImmutable $updatedAt;
}
