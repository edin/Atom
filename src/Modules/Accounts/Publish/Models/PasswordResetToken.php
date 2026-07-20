<?php

declare(strict_types=1);

namespace App\Models;

use Atom\Database\Model;
use Atom\Database\Orm\Attributes\Column;
use Atom\Database\Orm\Attributes\PrimaryKey;
use Atom\Database\Orm\Attributes\Table;
use Atom\Database\Orm\Provider\NowProvider;
use DateTimeImmutable;

#[Table("password_reset_tokens")]
final class PasswordResetToken extends Model
{
    #[PrimaryKey("id")]
    public int $id;

    #[Column("login")]
    public string $login;

    #[Column("token_hash")]
    public string $tokenHash;

    #[Column("expires_at")]
    public DateTimeImmutable $expiresAt;

    #[Column("created_at", onInsert: NowProvider::class)]
    public DateTimeImmutable $createdAt;
}
