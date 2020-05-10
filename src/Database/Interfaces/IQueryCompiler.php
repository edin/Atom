<?php

namespace Atom\Database\Interfaces;

use Atom\Database\Query\Query;
use Atom\Database\Query\Command;

interface IQueryCompiler
{
    public function quoteTableName(string $name): string;
    public function quoteColumnName(string $name): string;
    public function quoteValue($value): string;
    public function compileQuery(Query $query): Command;
}
