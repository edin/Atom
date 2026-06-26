<?php

declare(strict_types=1);

namespace Atom\Database\Schema;

enum ColumnType: string
{
    case String = "string";
    case Text = "text";
    case Integer = "integer";
    case Boolean = "boolean";
    case DateTime = "dateTime";
    case Timestamp = "timestamp";
}

