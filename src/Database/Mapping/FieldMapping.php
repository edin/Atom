<?php

namespace Atom\Database\Mapping;

use Atom\Database\Interfaces\ITypeConverter;
use ReflectionClass;

final class FieldMapping
{
    public $primaryKey = false;
    public $propertyName;
    public $fieldName;
    public $type;
    public $size;
    public $precision;
    public $nullable = false;
    public $converter = null;
    public $valueProvider = null;
    private $converterInstance = null;
    private $valueProviderInstance = null;

    public $includeInSelect = true;
    public $includeInInsert = true;
    public $includeInUpdate = true;
    public $isIndexed = false;
    public $isUnique = false;

    public function __construct(string $propertyName)
    {
        $this->propertyName = $propertyName;
        $this->fieldName = $propertyName;
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
        $value = $property->getValue($instance);

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
        $this->ensureType($provider, ITypeConverter::class);
        $this->converter = $converter;
        return $this;
    }

    /**
     * @param  string|IValueProvider $provider
     */
    public function withValueProvider($provider): self
    {
        $this->ensureType($provider, IValueProvider::class);
        $this->valueProvider = $valueProvider;
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
