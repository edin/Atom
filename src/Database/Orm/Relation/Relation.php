<?php

declare(strict_types=1);

namespace Atom\Database\Orm\Relation;

use Atom\Database\Db;
use Atom\Database\Orm\EntityMetadataFactory;
use Atom\Database\Orm\RelationMetadata;

abstract class Relation
{
    public function __construct(
        protected readonly RelationMetadata $relation,
        protected readonly EntityMetadataFactory $metadataFactory
    ) {
    }

    /**
     * @param object[] $models
     */
    abstract public function load(Db $db, array $models): void;
}
