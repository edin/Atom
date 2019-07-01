<?php

namespace Atom\Database;

final class Operator {
    private $operator;
    private $value;

    public function __construct(string $operator, $value) {
        $this->operator = $operator;
        $this->value = $value;
    }

    public function getOperator(): string {
        return $this->operator;
    }

    public function getValue() {
        return $this->value;
    }

    public static function less($value): self {
        return new static("<", $value);
    }

    public static function lessOrEqual($value): self {
        return new static("<=", $value);
    }

    public static function equal($value): self {
        return new static("=", $value);
    }

    public static function greaterOrEqual($value): self {
        return new static(">=", $value);
    }

    public static function greater($value): self {
        return new static(">", $value);
    }

    public static function in($value): self {
        return new static("IN", $value);
    }

    public static function or($value): self {
        return new static("OR", $value);
    }
}