<?php

declare(strict_types=1);

namespace Atom\Database\Mapping;

use Closure;
use ReflectionClass;

final class Mapping
{
    private array $mapping = [];
    private array $relations = [];
    private ?string $table = null;
    private string $entityClass;
    private string $repositoryClass;

    public static function create($callable)
    {
        $mapping = new Mapping();
        $callable($mapping);
        return $mapping;
    }

    public function __get($name)
    {
        return $this->property($name);
    }

    public function setEntityClass(string $entityClass)
    {
        $this->entityClass = $entityClass;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function setRepositoryClass(string $repositoryClass)
    {
        $this->repositoryClass = $repositoryClass;
    }

    public function getRepositoryClass(): string
    {
        return $this->repositoryClass;
    }

    public function property(string $name): FieldMapping
    {
        if (!isset($this->mapping[$name])) {
            return $this->mapping[$name] = new FieldMapping($name);
        }
        return $this->mapping[$name];
    }

    public function table(string $name)
    {
        $this->table = $name;
    }

    public function getTableName(): string
    {
        return $this->table;
    }

    public function hasSinglePrimaryKey(): bool
    {
        return count($this->getPrimaryKeys()) === 1;
    }

    public function getPrimaryKey(): ?FieldMapping
    {
        $keys = $this->getPrimaryKeys();
        if (count($keys) === 1) {
            return $keys[0];
        }
        return null;
    }

    public function getPrimaryKeyValueOrNull(object $entity)
    {
        $keys = $this->getPrimaryKeys();
        if (count($keys) === 1) {
            $primaryKey = $keys[0];
            $reflection = new ReflectionClass($entity);
            return $primaryKey->getPropertyValue($reflection, $entity);
        }
        return null;
    }
    public function setPrimaryKeyValueOrNull(object $entity, $value)
    {
        $keys = $this->getPrimaryKeys();
        if (count($keys) === 1) {
            $primaryKey = $keys[0];
            $reflection = new ReflectionClass($entity);
            $primaryKey->setPropertyValue($reflection, $entity, $value);
        }
    }

    /**
     * @return FieldMapping[]
     */
    public function getPrimaryKeys(): array
    {
        return array_values(
            array_filter($this->mapping, function ($it) {
                return $it->isPrimaryKey();
            })
        );
    }

    /**
     * @return FieldMapping[]
     */
    public function getFieldMapping(): array
    {
        return $this->mapping;
    }

    /**
     * @return FieldMapping[]
     */
    public function filter(Closure $filter): array
    {
        return array_filter($this->mapping, $filter);
    }
}
