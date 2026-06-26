<?php

declare(strict_types=1);

namespace Atom\Tests\Database\Orm;

use Atom\Database\Orm\Attributes\Column;
use Atom\Database\Orm\Attributes\BelongsTo;
use Atom\Database\Orm\Attributes\HasMany;
use Atom\Database\Orm\Attributes\HasOne;
use Atom\Database\Orm\Attributes\PrimaryKey;
use Atom\Database\Orm\Attributes\Table;
use Atom\Database\Orm\EntityMetadataFactory;
use Atom\Database\Orm\RowHydrator;
use PHPUnit\Framework\TestCase;

final class RowHydratorTest extends TestCase
{
    public function testBuildsEntityMetadata(): void
    {
        $metadata = (new EntityMetadataFactory())->for(HydratedUser::class);

        $this->assertSame("users", $metadata->tableName);
        $this->assertSame("id", $metadata->primaryKey()?->columnName);
        $this->assertTrue($metadata->primaryKey()?->autoIncrement);
        $this->assertCount(3, $metadata->selectableColumns());
        $this->assertCount(2, $metadata->insertableColumns());
        $this->assertCount(2, $metadata->updatableColumns());
    }

    public function testBuildsRelationMetadata(): void
    {
        $metadata = (new EntityMetadataFactory())->for(HydratedArticle::class);
        $relation = $metadata->relation("category");

        $this->assertNotNull($relation);
        $this->assertSame(HydratedCategory::class, $relation->relatedClass);
        $this->assertSame("category_id", $relation->foreignKey);
        $this->assertSame("id", $relation->ownerKey);
    }

    public function testBuildsHasOneAndHasManyRelationMetadata(): void
    {
        $metadata = (new EntityMetadataFactory())->for(HydratedUserWithRelations::class);

        $profile = $metadata->relation("profile");
        $posts = $metadata->relation("posts");

        $this->assertNotNull($profile);
        $this->assertSame("hasOne", $profile->type);
        $this->assertSame("user_id", $profile->foreignKey);
        $this->assertSame("id", $profile->localKey);

        $this->assertNotNull($posts);
        $this->assertSame("hasMany", $posts->type);
        $this->assertSame("user_id", $posts->foreignKey);
        $this->assertSame("id", $posts->localKey);
    }

    public function testHydratesRowsIntoTypedEntity(): void
    {
        $user = (new RowHydrator())->hydrate(HydratedUser::class, [
            "id" => "10",
            "name" => "Edin",
            "active" => "1",
        ]);

        $this->assertInstanceOf(HydratedUser::class, $user);
        $this->assertSame(10, $user->id);
        $this->assertSame("Edin", $user->name);
        $this->assertTrue($user->active);
    }
}

#[Table("users")]
final class HydratedUser
{
    #[PrimaryKey("id")]
    public int $id;

    #[Column("name")]
    public string $name;

    #[Column("active")]
    public bool $active;
}

#[Table("categories")]
final class HydratedCategory
{
    #[PrimaryKey("id")]
    public int $id;

    #[Column("name")]
    public string $name;
}

#[Table("articles")]
final class HydratedArticle
{
    #[PrimaryKey("id")]
    public int $id;

    #[Column("category_id")]
    public int $categoryId;

    #[BelongsTo(HydratedCategory::class, foreignKey: "category_id")]
    public ?HydratedCategory $category = null;
}

#[Table("users")]
final class HydratedUserWithRelations
{
    #[PrimaryKey("id")]
    public int $id;

    #[HasOne(HydratedProfile::class, foreignKey: "user_id")]
    public ?HydratedProfile $profile = null;

    #[HasMany(HydratedPost::class, foreignKey: "user_id")]
    public array $posts = [];
}

#[Table("profiles")]
final class HydratedProfile
{
    #[PrimaryKey("id")]
    public int $id;

    #[Column("user_id")]
    public int $userId;
}

#[Table("posts")]
final class HydratedPost
{
    #[PrimaryKey("id")]
    public int $id;

    #[Column("user_id")]
    public int $userId;
}
