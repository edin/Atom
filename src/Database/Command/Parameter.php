<?php

namespace Atom\Database\Command;

use PDO;

final class Parameter
{
    public const Input = 1;
    public const Output = 2;

    public const TypeString = PDO::PARAM_STR;
    public const TypeInteger = PDO::PARAM_INT;
    public const TypeBoolean = PDO::PARAM_BOOL;

    public $name;
    public $value;
    public $type;
    public $direction;

    public function __construct(string $name, $value, int $direction, int $type)
    {
        $this->name = $name;
        $this->value = $value;
        $this->type = $type;
        $this->direction = $direction;
    }
}