<?php

declare(strict_types=1);

namespace Atom\Database;

use Atom\Database\Mapping\FieldMapping;
use Atom\Database\Mapping\Mapping;
use Atom\Database\Query\DeleteQuery;
use Atom\Database\Query\InsertQuery;
use Atom\Database\Query\Parameter;
use Atom\Database\Query\Query;
use Atom\Database\Query\SelectQuery;
use Atom\Database\Query\UpdateQuery;
use ReflectionClass;
use RuntimeException;

class QueryBuilder
{
    private Mapping $mapping;

    public function __construct(Mapping $mapping)
    {
        $this->mapping = $mapping;
    }

    public function getMapping(): Mapping
    {
        return $this->mapping;
    }

    public function getInsertQuery(): InsertQuery
    {
        $fields = $this->mapping->filter(function (FieldMapping $field) {
            return $field->isIncludedInInsert() && !$field->isPrimaryKey();
        });

        $query = Query::insert()->into($this->mapping->getTableName());

        foreach ($fields as $field) {
            $parameter = new Parameter($field->getPropertyName(), null, null, Parameter::Input);
            $query->setValue($field->getFieldName(), $parameter);
        }

        return $query;
    }

    public function getUpdateQuery(): UpdateQuery
    {
        $fields = $this->mapping->filter(function (FieldMapping $field) {
            return $field->isIncluedInUpdate() && !$field->isPrimaryKey();
        });

        $query = Query::update()->table($this->mapping->getTableName());

        foreach ($fields as $field) {
            $parameter = new Parameter($field->getPropertyName(), null, null, Parameter::Input);
            $query->setValue($field->getFieldName(), $parameter);
        }

        foreach ($this->mapping->getPrimaryKeys() as $field) {
            $parameter = new Parameter($field->getPropertyName(), null, null, Parameter::Input);
            $query->where($field->getFieldName(), $parameter);
        }

        $query->limit(1);

        return $query;
    }

    private function ensureSinglePrimaryKey($primaryKeys)
    {
        if (count($primaryKeys) == 0) {
            throw new RuntimeException("Primary key is not defined on entity {$this->entityType}.");
        } elseif (count($primaryKeys) > 1) {
            throw new RuntimeException("Multiple primary keys are not supported.");
        }
    }

    public function getSelectByPrimaryKey($id): SelectQuery
    {
        $query = $this->getSelectQuery();
        $primaryKeys = $this->mapping->getPrimaryKeys();
        $this->ensureSinglePrimaryKey($primaryKeys);

        foreach ($primaryKeys as $field) {
            $parameter = new Parameter($field->getFieldName(), $id, null, Parameter::Input);
            $query->where($field->getFieldName(), $parameter);
        }
        $query->limit(1);
        return $query;
    }

    public function getSelectByPrimaryKeys(array $keys): SelectQuery
    {
        $query = $this->getSelectQuery();

        foreach ($this->mapping->getPrimaryKeys() as $field) {
            $propertyName = $field->getPropertyName();
            $fieldName = $field->getFieldName();
            $value = $keys[$propertyName] ?? null;
            $parameter = new Parameter($fieldName, $value, null, Parameter::Input);
            $query->where($fieldName, $parameter);
        }

        $query->limit(1);
        return $query;
    }

    public function getSelectQuery(): SelectQuery
    {
        $fields = $this->mapping->filter(function (FieldMapping $field) {
            return $field->isIncludedInSelect();
        });

        $columns = [];

        foreach ($fields as $field) {
            $columns[] = $field->getFieldName();
        }

        $query = Query::select($this->mapping->getTableName());
        $query->columns($columns);

        return $query;
    }

    public function getDeleteQuery(object $entity): DeleteQuery
    {
        $query = Query::delete()->from($this->mapping->getTableName());
        $reflection = new ReflectionClass($entity);

        foreach ($this->mapping->getPrimaryKeys() as $field) {
            $propertyName = $field->getPropertyName();
            $fieldName = $field->getFieldName();
            $property = $reflection->getProperty($propertyName);
            $property->setAccessible(true);
            $value  = $property->getValue($entity);
            $parameter = new Parameter($fieldName, $value, null, Parameter::Input);
            $query->where($fieldName, $parameter);
        }

        return $query;
    }

    public function getDeleteQueryByPk($key): DeleteQuery
    {
        $query = Query::delete()->from($this->mapping->getTableName());
        $primaryKeys = $this->mapping->getPrimaryKeys();
        $this->ensureSinglePrimaryKey($primaryKeys);

        foreach ($primaryKeys as $field) {
            $fieldName = $field->getFieldName();
            $parameter = new Parameter($fieldName, $key, null, Parameter::Input);
            $query->where($fieldName, $parameter);
        }

        return $query;
    }
}
