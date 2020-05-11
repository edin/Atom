<?php

declare(strict_types=1);

namespace Atom\Validation\Rules;

use Atom\Validation\IRule;
use Atom\Validation\RuleResult;

abstract class AbstractRule implements IRule
{
    protected $errorMessage;
    private $resultValue;

    public function setErrorMessage(string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    protected function hasValue($value): bool
    {
        return !empty($value);
    }

    public function getAttributes(): array
    {
        return [];
    }

    public function validate($value): RuleResult
    {
        $this->resultValue = $value;
        if ($this->hasValue($value)) {
            $isValid = $this->isValid($value);
            if (!$isValid) {
                $attributes = $this->getAttributes();
                $attributes['value'] = $value;
                return RuleResult::failure($this->resultValue, $this->getErrorMessage(), $attributes);
            }
        }
        return RuleResult::success($this->resultValue);
    }

    public function setResultValue($value): void
    {
        $this->resultValue = $value;
    }

    abstract public function isValid($value): bool;
}
