<?php

namespace Atom\Database\Query;

final class Operator
{
    private $operator;
    private $value;
    private $isNegation = false;

    public const Less = "<";
    public const LessOrEqual = "<=";
    public const Equal = "=";
    public const GreaterOrEqual = ">=";
    public const Greater = ">";

    public function __construct(string $operator, $value)
    {
        $this->operator = $operator;
        $this->value = $value;
    }

    public static function fromValue($value): self
    {
        if (is_array($value)) {
            return static::in($value);
        }
        return static::equal($value);
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getValue()
    {
        return $this->value;
    }

    public static function less($value): self
    {
        return new static("<", $value);
    }

    public static function lessOrEqual($value): self
    {
        return new static("<=", $value);
    }

    public static function equal($value): self
    {
        return new static("=", $value);
    }

    public static function greaterOrEqual($value): self
    {
        return new static(">=", $value);
    }

    public static function greater($value): self
    {
        return new static(">", $value);
    }

    public static function in($value): self
    {
        return new static("IN", $value);
    }

    public static function like($value): self
    {
        return new static("LIKE", $value);
    }

    public static function or($value): self
    {
        return new static("OR", $value);
    }
}
