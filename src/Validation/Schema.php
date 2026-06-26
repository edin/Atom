<?php

declare(strict_types=1);

namespace Atom\Validation;

use Closure;

class Schema
{
    /** @var array<string, Field> */
    private array $fields = [];

    public static function make(?Closure $builder = null): self
    {
        $schema = new self();

        if ($builder !== null) {
            $builder($schema);
        }

        return $schema;
    }

    public function field(string $name): Field
    {
        return $this->fields[$name] ??= new Field($this, $name);
    }

    public function validate(array|object $target): ValidationResult
    {
        $data = $this->dataFrom($target);
        $result = ValidationResult::valid();

        foreach ($this->fields as $field) {
            $value = $this->value($target, $field->name);
            $context = new ValidationContext($field->name, $target, $data);

            foreach ($field->rules() as $rule) {
                $error = $rule->validate($value, $context);

                if ($error !== null) {
                    $result->add($error->forField($field->name));
                }
            }
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function dataFrom(array|object $target): array
    {
        if (is_array($target)) {
            return $target;
        }

        return get_object_vars($target);
    }

    private function value(array|object $target, string $field): mixed
    {
        if (is_array($target)) {
            return $target[$field] ?? null;
        }

        return $target->{$field} ?? null;
    }
}

