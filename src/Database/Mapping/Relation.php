<?php

declare(strict_types=1);

namespace Atom\Database\Mapping;

use Atom\Database\Query\Criteria;

final class Relation
{
    public const HasOne = 1;
    public const HasMany = 2;
    public const BelongsTo = 3;

    private int $type;
    private string $modelClass;
    private string $via;
    private string $sourceKey;
    private string $foreignKey;
    private callable $criteria;

    public function withCriteria(callable $criteria) {
        $this->criteria = $criteria;
    }

    public function hasMany(string $modelClass, string $foreignKey): self {
        $this->type = self::HasMany;
        $this->modelClass = $modelClass;
        $this->foreignKey = $foreignKey;
        return $this;
    }

    public function via(string $table, string $foreignKey, string $relatedForeignKey): self {
        $this->via = $table;
        $this->foreignKey = $foreignKey;
        $this->relatedForeignKey = $relatedForeignKey;
        return $this;
    }

    public function hasOne(string $modelClass, string $foreignKey): self {
        $this->type = self::HasOne;
        $this->modelClass = $modelClass;
        $this->foreignKey = $foreignKey;
        return $this;
    }

    public function belongsTo(string $modelClass, string $foreignKey): self {
        $this->type = self::BelongsTo;
        $this->modelClass = $modelClass;
        $this->foreignKey = $foreignKey;
        return $this;
    }

    public static function create(): self {
        return new static();
    }
}

// class User {};
// class Role {};

// Relation::create(User::class)
//     ->hasMany(Role::class, "role_id")
//     ->via("user_roles", "user_id", "role_id")
//     ->withCriteria(function(Criteria $criteria) {
//         $criteria->where("status", 1);
//     });
