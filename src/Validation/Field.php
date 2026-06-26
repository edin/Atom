<?php

declare(strict_types=1);

namespace Atom\Validation;

use Atom\Validation\Rules\Between;
use Atom\Validation\Rules\Email;
use Atom\Validation\Rules\In;
use Atom\Validation\Rules\Length;
use Atom\Validation\Rules\Max;
use Atom\Validation\Rules\MaxLength;
use Atom\Validation\Rules\Min;
use Atom\Validation\Rules\MinLength;
use Atom\Validation\Rules\Numeric;
use Atom\Validation\Rules\Pattern;
use Atom\Validation\Rules\Required;
use Atom\Validation\Rules\Url;

final class Field
{
    /** @var ValidationRuleInterface[] */
    private array $rules = [];

    public function __construct(private readonly Schema $schema, public readonly string $name)
    {
    }

    public function rule(ValidationRuleInterface $rule): self
    {
        $this->rules[] = $rule;
        return $this;
    }

    public function field(string $name): self
    {
        return $this->schema->field($name);
    }

    public function required(?string $message = null): self
    {
        return $this->rule(new Required($message));
    }

    public function email(?string $message = null): self
    {
        return $this->rule(new Email($message));
    }

    public function url(?string $message = null): self
    {
        return $this->rule(new Url($message));
    }

    public function numeric(?string $message = null): self
    {
        return $this->rule(new Numeric($message));
    }

    public function min(float $value, ?string $message = null): self
    {
        return $this->rule(new Min($value, $message));
    }

    public function max(float $value, ?string $message = null): self
    {
        return $this->rule(new Max($value, $message));
    }

    public function between(float $min, float $max, ?string $message = null): self
    {
        return $this->rule(new Between($min, $max, $message));
    }

    public function minLength(int $value, ?string $message = null): self
    {
        return $this->rule(new MinLength($value, $message));
    }

    public function maxLength(int $value, ?string $message = null): self
    {
        return $this->rule(new MaxLength($value, $message));
    }

    public function length(int $min, int $max, ?string $message = null): self
    {
        return $this->rule(new Length($min, $max, $message));
    }

    public function pattern(string $pattern, ?string $message = null): self
    {
        return $this->rule(new Pattern($pattern, $message));
    }

    /**
     * @param mixed[] $values
     */
    public function in(array $values, ?string $message = null): self
    {
        return $this->rule(new In($values, $message));
    }

    public function schema(): Schema
    {
        return $this->schema;
    }

    /**
     * @return ValidationRuleInterface[]
     */
    public function rules(): array
    {
        return $this->rules;
    }
}

