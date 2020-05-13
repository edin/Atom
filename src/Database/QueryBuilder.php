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
            $parameter = new Parameter($field->getProperyName(), null, null, Parameter::Input);
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

    public function getSelectByPkQuery(): SelectQuery
    {
        $fields = $this->mapping->filter(function (FieldMapping $field) {
            return $field->isIncludedInSelect();
        });

        $query = Query::select();

        return $query;

        // $table = $this->quoteTableName($this->mapping->getTableName());
        // $fields = $this->mapping->getMapping();
        // $commandParams = [];

        // $selectFields = array_filter($fields, function ($f) {
        //     return $f->includeInSelect || $f->primaryKey;
        // });

        // $whereFields = array_filter($fields, function ($f) {
        //     return $f->primaryKey;
        // });

        // foreach ($selectFields as $field) {
        //     $fieldList[] = $this->quoteColumnName($field->propertyName);
        // }

        // foreach ($whereFields as $propertyMapping) {
        //     $field = $this->quoteColumnName($propertyMapping->propertyName);
        //     $parameter = $this->getParameterName($propertyMapping->propertyName);
        //     $whereList[] = "$field = $parameter";
        //     $commandParams[$parameter] = $propertyMapping;
        // }

        // $fieldListStr  = implode(", ", $fieldList);
        // $whereStr  = implode(" AND ", $whereList);

        // $sql = "SELECT $fieldListStr FROM {$table} WHERE {$whereStr} LIMIT 1";
        // return new Command($sql, $commandParams);
    }

    public function getSelectQuery(): SelectQuery
    {
        $fields = $this->mapping->filter(function (FieldMapping $field) {
            return $field->isIncludedInSelect();
        });

        $query = Query::select();

        return $query;

        // $table = $this->quoteTableName($this->mapping->getTableName());
        // $fields = $this->mapping->getMapping();

        // $selectFields = array_filter($fields, function ($f) {
        //     return $f->includeInSelect || $f->primaryKey;
        // });

        // foreach ($selectFields as $propertyMapping) {
        //     $fieldList[] = $this->quoteColumnName($propertyMapping->propertyName);
        // }

        // $fieldListStr  = implode(", ", $fieldList);

        // $sql = "SELECT $fieldListStr FROM {$table} LIMIT 1000";
        // return new Command($sql, []);
    }

    public function getDeleteQuery(): DeleteQuery
    {
        $query = Query::delete()->from($this->mapping->getTableName());

        foreach ($this->mapping->getPrimaryKeys() as $field) {
            $parameter = new Parameter($field->getPropertyName(), null, null, Parameter::Input);
            $query->where($field->getFieldName(), $parameter);
        }

        return $query;
    }
}
