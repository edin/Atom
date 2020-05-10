<?php

namespace Atom\Database\Query;

final class Operator
{
    private $operator;
    private $value;
    private $maxValue;
    //private $isNegation = false;
    private $isValue = false;
    private $parameter;

    // public const Less = "<";
    // public const LessOrEqual = "<=";
    // public const Equal = "=";
    // public const GreaterOrEqual = ">=";
    // public const Greater = ">";
    // public const Like = "LIKE";
    // public const In = "IN";

    public function __construct(string $operator, $value, $maxValue = null)
    {
        $this->operator = $operator;
        $this->value = $value;
        $this->maxValue = $maxValue;
    }

    public static function fromValue($value): self
    {
        if ($value instanceof Operator) {
            return $value;
        }

        if (is_array($value)) {
            return static::in($value);
        }
        return static::equal($value);
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getExpression()
    {
        return $this->value;
    }

    public function getMinExpression()
    {
        return $this->value;
    }

    public function getMaxExpression()
    {
        return $this->maxValue;
    }

    public function setIsValue(bool $value): void
    {
        $this->isValue = $value;
    }

    public function getIsValue()
    {
        return $this->isValue;
    }

    public function asField(): self
    {
        $this->isValue = false;
        return $this;
    }

    public function getParameterName(): ?string
    {
        return $this->parameter;
    }

    public function setParameterName(string $name): void
    {
        $this->parameter = $name;
    }

    public function asParameter(string $name): self
    {
        $this->parameter = $name;
        return $this;
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

    public static function notEqual($value): self
    {
        return new static("<>", $value);
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

    public static function ilike($value): self
    {
        return new static("ILIKE", $value);
    }

    public static function or($value): self
    {
        return new static("OR", $value);
    }

    public static function between($min, $max): self
    {
        return new static("BETWEEN", $min, $max);
    }
}
