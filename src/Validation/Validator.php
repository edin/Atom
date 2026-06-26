<?php

declare(strict_types=1);

namespace Atom\Validation;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;

final class Validator
{
    /** @var array<class-string, array<string, ValidationRuleInterface[]>> */
    private static array $attributeRules = [];

    private function __construct(private readonly Schema|string|null $source = null)
    {
    }

    /**
     * @param class-string|Schema|null $source
     */
    public static function for(Schema|string|null $source = null): self
    {
        return new self($source);
    }

    public function validate(array|object $target): ValidationResult
    {
        if ($this->source instanceof Schema) {
            return $this->source->validate($target);
        }

        $className = is_string($this->source)
            ? $this->source
            : (is_object($target) ? $target::class : null);

        if ($className === null) {
            throw new \InvalidArgumentException("A class name is required when validating arrays with attributes.");
        }

        return $this->validateAttributes($target, $className);
    }

    /**
     * @param class-string $className
     */
    private function validateAttributes(array|object $target, string $className): ValidationResult
    {
        $schema = new Schema();

        foreach ($this->rulesFor($className) as $field => $rules) {
            $builder = $schema->field($field);

            foreach ($rules as $rule) {
                $builder->rule($rule);
            }
        }

        return $schema->validate($target);
    }

    /**
     * @param class-string $className
     * @return array<string, ValidationRuleInterface[]>
     */
    private function rulesFor(string $className): array
    {
        return self::$attributeRules[$className] ??= $this->buildRules($className);
    }

    /**
     * @param class-string $className
     * @return array<string, ValidationRuleInterface[]>
     */
    private function buildRules(string $className): array
    {
        $reflection = new ReflectionClass($className);
        $fields = [];

        foreach ($reflection->getProperties() as $property) {
            $rules = $this->rulesFromProperty($property);

            if ($rules !== []) {
                $fields[$property->getName()] = $rules;
            }
        }

        return $fields;
    }

    /**
     * @return ValidationRuleInterface[]
     */
    private function rulesFromProperty(ReflectionProperty $property): array
    {
        $rules = [];

        foreach ($property->getAttributes(ValidationRuleInterface::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $rule = $attribute->newInstance();
            if ($rule instanceof ValidationRuleInterface) {
                $rules[] = $rule;
            }
        }

        return $rules;
    }
}

