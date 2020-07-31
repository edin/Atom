<?php

declare(strict_types=1);

namespace Atom\Database\Mapping;

use Atom\Database\Interfaces\ITypeConverter;
use Atom\Database\Interfaces\IValueProvider;
use Error;
use InvalidArgumentException;
use ReflectionClass;

final class FieldMapping
{
    private bool $primaryKey = false;
    private string $propertyName;
    private ?string $fieldName = null;
    private string $type = "";
    private ?int $size = null;
    private ?int $precision = null;
    private bool $nullable = false;
    private string $label = "";

    /* string | ITypeConverter | null */
    private $converter = null;
    /* string | IValueProvider | null */
    private $valueProvider = null;
    private ?ITypeConverter $converterInstance = null;
    private ?IValueProvider $valueProviderInstance = null;

    private bool $includeInSelect = true;
    private bool $includeInInsert = true;
    private bool $includeInUpdate = true;
    private bool $isIndexed = false;
    private bool $isUnique = false;
    private bool $searchable = false;

    public function __construct(string $propertyName)
    {
        $this->propertyName = $propertyName;
    }

    public function isPrimaryKey(): bool
    {
        return $this->primaryKey;
    }

    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    public function getFieldName(): string
    {
        return $this->fieldName ?? $this->propertyName;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getPrecision(): ?int
    {
        return $this->precision;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function isIncludedInSelect(): bool
    {
        return $this->includeInSelect;
    }

    public function isIncluedInUpdate(): bool
    {
        return $this->includeInUpdate;
    }

    public function isIncludedInInsert(): bool
    {
        return $this->includeInInsert;
    }

    public function isIndexed(): bool
    {
        return $this->isIndexed;
    }

    public function isUnique(): bool
    {
        return $this->isUnique;
    }

    public function label(string $label): self {
        $this->label = $label;
        return $this;
    }

    public function getLabel(): string {
        return $this->label;
    }

    public function searchable(): self {
        $this->searchable = true;
        return $this;
    }

    public function isSearchable(): bool {
        return $this->searchable;
    }

    public function primaryKey(): self
    {
        $this->primaryKey = true;
        $this->includeInUpdate = false;
        return $this;
    }

    public function excludeInSelect(): self
    {
        $this->includeInSelect = false;
        return $this;
    }

    public function excludeInInsert(): self
    {
        $this->includeInInsert = false;
        return $this;
    }

    public function excludeInUpdate(): self
    {
        $this->includeInUpdate = false;
        return $this;
    }

    public function field(string $name): self
    {
        $this->fieldName = $name;
        return $this;
    }

    public function int(): self
    {
        $this->type = DatabaseTypes::TypeInt;
        return $this;
    }

    public function smallint(): self
    {
        $this->type = DatabaseTypes::TypeSmallInt;
        return $this;
    }

    public function tinyint(): self
    {
        $this->type = DatabaseTypes::TypeTinyInt;
        return $this;
    }

    public function float(): self
    {
        $this->type = DatabaseTypes::TypeFloat;
        return $this;
    }

    public function decimal(int $size = 18, int $precision = 6): self
    {
        $this->type = DatabaseTypes::TypeDecimal;
        $this->size = $size;
        $this->precision = $precision;
        return $this;
    }

    public function nullable(): self
    {
        $this->nullable = true;
        return $this;
    }

    public function string(?int $size = null): self
    {
        $this->type = DatabaseTypes::TypeString;
        $this->size = $size;
        return $this;
    }

    public function text(): self
    {
        $this->type = DatabaseTypes::TypeText;
        return $this;
    }

    public function date(): self
    {
        $this->type = DatabaseTypes::TypeDate;
        return $this;
    }

    public function time(): self
    {
        $this->type = DatabaseTypes::TypeTime;
        return $this;
    }

    public function dateTime(): self
    {
        $this->type = DatabaseTypes::TypeDateTime;
        return $this;
    }

    public function binary(): self
    {
        $this->type = DatabaseTypes::TypeBinary;
        return $this;
    }

    public function guid(): self
    {
        $this->type = DatabaseTypes::TypeGuid;
        return $this;
    }

    public function json(): self
    {
        $this->type = DatabaseTypes::TypeJson;
        return $this;
    }

    private function getOrCreateType($type)
    {
        if (is_string($type)) {
            return new $type;
        }
        return $type;
    }

    public function getConverter(): ?ITypeConverter
    {
        if ($this->converterInstance === null && $this->converter !== null) {
            $this->converterInstance = $this->getOrCreateType($this->converter);
        }
        return $this->converterInstance;
    }

    public function getValueProvider(): ?IValueProvider
    {
        if ($this->valueProviderInstance === null && $this->valueProvider !== null) {
            $this->valueProviderInstance = $this->getOrCreateType($this->valueProvider);
        }
        return $this->valueProviderInstance;
    }

    public function getPropertyValue(ReflectionClass $classType, $instance)
    {
        $property = $classType->getProperty($this->propertyName);
        $property->setAccessible(true);

        $valueProvider = $this->getValueProvider();
        if ($valueProvider !== null) {
            $property->setValue($instance, $valueProvider->getValue());
        }

        try {
            $value = $property->getValue($instance);
        } catch (Error $err) {
            $value = null;
        }

        $converter = $this->getConverter();
        if ($converter !== null) {
            $value = $converter->convertBack($value);
        }
        return $value;
    }

    public function setPropertyValue(ReflectionClass $classType, $instance, $value)
    {
        $property = $classType->getProperty($this->propertyName);
        $property->setAccessible(true);
        $converter = $this->getConverter();
        if ($converter !== null) {
            $value = $converter->convertTo($value);
        }
        $property->setValue($instance, $value);
        return $value;
    }

    public function getValueFrom(array $data)
    {
        $fieldName = $this->fieldName;
        $value = $data[$fieldName] ?? null;
        $converter = $this->getConverter();
        if ($converter) {
            $value = $converter->convertTo($value);
        }
        return $value;
    }

    public function indexed(): self
    {
        $this->isIndexed = true;
        return $this;
    }

    public function unique(): self
    {
        $this->isUnique = true;
        return $this;
    }

    /**
     * @param  string|ITypeConverter $converter
     */
    public function withConverter($converter): self
    {
        $this->ensureType($converter, ITypeConverter::class);
        $this->converter = $converter;
        return $this;
    }

    /**
     * @param  string|IValueProvider $provider
     */
    public function withValueProvider($provider): self
    {
        $this->ensureType($provider, IValueProvider::class);
        $this->valueProvider = $provider;
        return $this;
    }

    private function ensureType($typeOrInstance, $type): void
    {
        $reflection = new ReflectionClass($typeOrInstance);
        if (!$reflection->implementsInterface($type)) {
            throw new InvalidArgumentException("Type does not implement $type interface.");
        }
    }
}
