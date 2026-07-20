<?php

declare(strict_types=1);

namespace App\Models;

use Atom\Database\Model;
use Atom\Database\Orm\Attributes\Column;
use Atom\Database\Orm\Attributes\PrimaryKey;
use Atom\Database\Orm\Attributes\Table;
use Atom\Database\Orm\Provider\NowProvider;
use Atom\Identity\IdentityInterface;
use DateTimeImmutable;

#[Table("users")]
final class User extends Model implements IdentityInterface
{
    #[PrimaryKey("id")]
    public int $id;

    #[Column("email")]
    public string $email;

    #[Column("name")]
    public string $name = "";

    #[Column("password_hash")]
    public string $passwordHash;

    #[Column("created_at", onInsert: NowProvider::class)]
    public DateTimeImmutable $createdAt;

    #[Column("updated_at", onInsert: NowProvider::class, onUpdate: NowProvider::class)]
    public DateTimeImmutable $updatedAt;

    public function identifier(): int
    {
        return $this->id;
    }
}
