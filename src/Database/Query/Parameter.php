<?php

declare(strict_types=1);

namespace Atom\Database\Query;

use PDO;

final class Parameter
{
    public const Input  = 1;
    public const Output = 2;

    public const TypeString  = PDO::PARAM_STR;
    public const TypeInteger = PDO::PARAM_INT;
    public const TypeBoolean = PDO::PARAM_BOOL;

    private string $name;
    private $value;
    private ?int $type;
    private int  $direction;

    public function __construct(string $name, $value, ?int $type, int $direction)
    {
        $this->name = $name;
        $this->value = $value;
        $this->type = $type;
        $this->direction = $direction;
    }

    public static function getParameterType($value): int
    {
        if (\is_integer($value)) {
            return self::TypeInteger;
        } elseif (\is_bool($value)) {
            return self::TypeBoolean;
        }
        return self::TypeString;
    }

    public static function from(string $name, $value): self
    {
        return new Parameter($name, $value, self::Input, self::getParameterType($value));
    }

    public function getType(): int
    {
        if ($this->type) {
            return $this->type;
        }
        return self::getParameterType($this->value);
    }

    public function isInput(): bool
    {
        return $this->direction == self::Input;
    }

    public function getDirection(): int
    {
        return $this->direction;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value): void
    {
        $this->value = $value;
    }
}
