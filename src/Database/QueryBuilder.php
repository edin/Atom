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

    public function getSelectByPrimaryKey(object $entity): SelectQuery
    {
        $query = $this->getSelectQuery();

        foreach ($this->mapping->getPrimaryKeys() as $field) {
            $propertyName = $field->getPropertyName();
            $parameter = new Parameter($propertyName, null, null, Parameter::Input);
            $query->where($field->getFieldName(), $parameter);
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

        foreach ($this->mapping->getPrimaryKeys() as $field) {
            $propertyName = $field->getPropertyName();

            $parameter = new Parameter($propertyName, null, null, Parameter::Input);
            $query->where($field->getFieldName(), $parameter);
        }

        return $query;
    }
}
