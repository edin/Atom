<?php

declare(strict_types=1);

namespace Atom\Hydrator;

use Atom\Hydrator\Attributes\Raw;
use Atom\Hydrator\Attributes\SourceAttributeInterface;
use ReflectionAttribute;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;

final readonly class HydrationTarget
{
    public string $name;
    public ?string $typeName;
    public bool $isBuiltin;
    public bool $allowsNull;
    public bool $hasDefaultValue;
    public mixed $defaultValue;
    public ?string $source;
    public string $sourceName;
    public bool $raw;
    /** @var ValueTransformerInterface[] */
    public array $transformers;

    private function __construct(
        public ReflectionProperty|ReflectionParameter $reflection,
        public ?ReflectionProperty $property
    ) {
        if ($property !== null) {
            $property->setAccessible(true);
        }

        $this->name = $reflection->getName();
        $type = $reflection->getType();
        $this->typeName = $type instanceof ReflectionNamedType ? $type->getName() : null;
        $this->isBuiltin = $type instanceof ReflectionNamedType && $type->isBuiltin();
        $this->allowsNull = $type?->allowsNull() ?? true;
        $this->hasDefaultValue = $this->resolveHasDefaultValue($reflection);
        $this->defaultValue = $this->hasDefaultValue ? $this->resolveDefaultValue($reflection) : null;

        $sourceAttribute = $this->sourceAttribute($reflection);
        $this->source = $sourceAttribute?->source();
        $this->sourceName = $sourceAttribute?->name() ?? $this->name;
        $this->raw = $reflection->getAttributes(Raw::class) !== [];
        $this->transformers = $this->transformers($reflection);
    }

    public static function fromProperty(ReflectionProperty $property): self
    {
        return new self($property, $property);
    }

    public static function fromParameter(ReflectionParameter $parameter): self
    {
        return new self($parameter, null);
    }

    public function setValue(object $instance, mixed $value): void
    {
        if ($this->property === null) {
            throw new \LogicException("Hydration target '{$this->name}' is not a property.");
        }

        $this->property->setValue($instance, $value);
    }

    private function resolveHasDefaultValue(ReflectionProperty|ReflectionParameter $reflection): bool
    {
        return $reflection instanceof ReflectionProperty
            ? $reflection->hasDefaultValue()
            : $reflection->isDefaultValueAvailable();
    }

    private function resolveDefaultValue(ReflectionProperty|ReflectionParameter $reflection): mixed
    {
        return $reflection instanceof ReflectionProperty
            ? $reflection->getDefaultValue()
            : $reflection->getDefaultValue();
    }

    private function sourceAttribute(ReflectionProperty|ReflectionParameter $reflection): ?SourceAttributeInterface
    {
        foreach ($reflection->getAttributes(SourceAttributeInterface::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            return $attribute->newInstance();
        }

        return null;
    }

    /**
     * @return ValueTransformerInterface[]
     */
    private function transformers(ReflectionProperty|ReflectionParameter $reflection): array
    {
        $transformers = [];
        foreach ($reflection->getAttributes(ValueTransformerInterface::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $transformers[] = $attribute->newInstance();
        }

        return $transformers;
    }
}
