<?php

namespace Atom\Database\Query\Compilers;

class MySqlCompiler extends AbstractCompiler
{
    public function quoteTableName(string $name): string
    {
        return "`$name`";
    }

    public function quoteColumnName(string $name): string
    {
        return "`$name`";
    }

    public function quoteValue($value): string
    {
        if (is_null($value)) {
            // Usually field = null should be writen as field is null so just returning NULL string 
            // may not work here
            throw new \RuntimeException("Can't quote null value");
        }

        if (is_int($value)) {
            return (string)$value;
        }

        if (is_float($value)) {
            return (string)$value;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value)) {
            $items = [];
            foreach($value as $it) {
                $items[] = $this->quoteValue($it);
            }
            return "(" . implode(", ", $items) . ")";
        }

        $value = (string)$value;
        $value = str_replace("'", "''", $value);
        return "'$value'";
    }
}
