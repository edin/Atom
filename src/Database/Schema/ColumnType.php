<?php

declare(strict_types=1);

namespace Atom\Database\Schema;

enum ColumnType: string
{
    case String = "string";
    case Text = "text";
    case Integer = "integer";
    case BigInteger = "bigInteger";
    case Float = "float";
    case Decimal = "decimal";
    case Boolean = "boolean";
    case Date = "date";
    case DateTime = "dateTime";
    case Timestamp = "timestamp";
    case Json = "json";
    case Binary = "binary";
    case Uuid = "uuid";
}
