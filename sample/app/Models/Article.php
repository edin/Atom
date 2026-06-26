<?php

declare(strict_types=1);

namespace App\Models;

use Atom\Database\Model;
use Atom\Database\Orm\Attributes\BelongsTo;
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

    #[Column("category_id")]
    public int $categoryId;

    #[Column("title")]
    public string $title;

    #[Column("summary")]
    public string $summary;

    #[Column("body")]
    public string $body;

    #[Column("is_published")]
    public bool $isPublished;

    #[Column("created_at", onInsert: NowProvider::class)]
    public DateTimeImmutable $createdAt;

    #[Column("updated_at", onInsert: NowProvider::class, onUpdate: NowProvider::class)]
    public DateTimeImmutable $updatedAt;

    #[BelongsTo(Category::class, foreignKey: "category_id")]
    public ?Category $category = null;
}
