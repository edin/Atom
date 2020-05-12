<?php

declare(strict_types=1);

namespace Atom\Database;

use Atom\Database\Mapping\Mapping;
use Atom\Database\Query\DeleteQuery;
use Atom\Database\Query\InsertQuery;
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
        $query = Query::insert();
        //TODO: Build insert query here

        return $query;
        // $table = $this->quoteTableName($this->mapping->getTableName());
        // $fields = $this->mapping->getMapping();

        // $insertFields = array_filter($fields, function ($f) {
        //     return $f->includeInInsert && !$f->primaryKey;
        // });

        // $commandParams = [];

        // foreach ($insertFields as $propertyMapping) {
        //     $fieldName = $this->quoteColumnName($propertyMapping->propertyName);
        //     $paramName = $this->getParameterName($propertyMapping->propertyName);
        //     $fieldList[] = $fieldName;
        //     $valueList[] = $paramName;
        //     $commandParams[$paramName] = $propertyMapping;
        // }

        // $fieldListStr  = implode(", ", $fieldList);
        // $valuesListStr = implode(", ", $valueList);

        // $sql = "INSERT INTO {$table} ($fieldListStr) VAlUES ($valuesListStr)";
        // return new Command($sql, $commandParams);
    }

    public function getUpdateQuery(): UpdateQuery
    {
        $query = Query::update();


        return $query;

        // $table = $this->quoteTableName($this->mapping->getTableName());
        // $fields = $this->mapping->getMapping();

        // $updateFields = array_filter($fields, function ($f) {
        //     return $f->includedInUpdate && !$f->primaryKey;
        // });

        // $whereFields = array_filter($fields, function ($f) {
        //     return $f->primaryKey;
        // });

        // $commandParams = [];

        // foreach ($updateFields as $propertyMapping) {
        //     $field = $this->quoteColumnName($propertyMapping->propertyName);
        //     $parameter = $this->getParameterName($propertyMapping->propertyName);
        //     $updateList[] = "$field = $parameter";
        //     $commandParams[$parameter] = $field;
        // }

        // foreach ($whereFields as $propertyMapping) {
        //     $field = $this->quoteColumnName($propertyMapping->propertyName);
        //     $parameter = $this->getParameterName($propertyMapping->propertyName);
        //     $whereList[] = "$field = $parameter";
        //     $commandParams[$parameter] = $field;
        // }

        // $updateListStr  = implode(", ", $updateList);
        // $whereListStr = implode(" AND ", $whereList);

        // $sql = "UPDATE {$table} SET ($updateListStr) WHERE ($whereListStr) LIMIT 1";
        // return new Command($sql, $commandParams);
    }

    public function getSelectByPkQuery(): SelectQuery
    {
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
        $query = Query::delete();

        return $query;
        // $commandParams = [];
        // $table = $this->quoteTableName($this->mapping->getTableName());
        // $fields = $this->mapping->getMapping();

        // $whereFields = array_filter($fields, function ($f) {
        //     return $f->primaryKey;
        // });

        // foreach ($whereFields as $propertyMapping) {
        //     $field = $this->quoteColumnName($propertyMapping->propertyName);
        //     $parameter = $this->getParameterName($propertyMapping->propertyName);
        //     $whereList[] = "$field = $parameter";
        //     $commandParams[$parameter] = $propertyMapping;
        // }

        // $whereStr  = implode(" AND ", $whereList);

        // $sql = "DELETE FROM {$table} WHERE {$whereStr} LIMIT 1";
        // return new Command($sql, $commandParams);
    }
}
