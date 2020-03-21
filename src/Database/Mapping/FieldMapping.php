<?php

class FieldMapping
{
    public const TypeSmallInt = "smallint";
    public const TypeTinyInt = "tinyint";
    public const TypeInt = "int";
    public const TypeFloat = "float";
    public const TypeDecimal = "decimal";
    public const TypeString = "string";
    public const TypeText = "text";
    public const TypeGuid = "guid";
    public const TypeBinary = "binary";
    public const TypeDate = "date";
    public const TypeDateTime = "datetime";
    public const TypeTime = "time";
    public const TypeJson = "json";

    public $primaryKey = false;
    public $propertyName;
    public $fieldName;
    public $type;
    public $size;
    public $precision;
    public $nullable = false;
    public $converter = null;
    private $converterInstance = null;
    public $valueProvider;

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
        $this->type = self::TypeInt;
        return $this;
    }

    public function smallint(): self
    {
        $this->type = self::TypeSmallInt;
        return $this;
    }

    public function tinyint(): self
    {
        $this->type = self::TypeTinyInt;
        return $this;
    }

    public function float(): self
    {
        $this->type = self::TypeFloat;
        return $this;
    }

    public function decimal(int $size=18, int $precision=6): self
    {
        $this->type = self::TypeDecimal;
        $this->size = $size;
        $this->precision = $precision;
        return $this;
    }

    public function nullable(): self
    {
        $this->nullable = true;
        return $this;
    }

    public function string(?int $size=null): self
    {
        $this->type = self::TypeString;
        $this->size = $size;
        return $this;
    }

    public function text(): self
    {
        $this->type = self::TypeText;
        return $this;
    }

    public function date(): self
    {
        $this->type = self::TypeDate;
        return $this;
    }

    public function time(): self
    {
        $this->type = self::TypeTime;
        return $this;
    }

    public function dateTime(): self
    {
        $this->type = self::TypeDateTime;
        return $this;
    }

    public function binary(): self
    {
        $this->type = self::TypeBinary;
        return $this;
    }

    public function guid(): self
    {
        $this->type = self::TypeGuid;
        return $this;
    }

    public function json(): self
    {
        $this->type = self::TypeJson;
        return $this;
    }

    public function getConverter(): ?ITypeConverter
    {
        if ($this->converterInstance === null && $this->converter !== null) {
            $this->converterInstance = new $this->converter;
        }
        return $this->converterInstance;
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

    public function withConverter(string $typeName): self
    {
        //TODO: Check if $typeName is instance of ITypeConverter
        $this->converter = $typeName;
        return $this;
    }

    public function withValueProvider(string $valueProvider): self
    {
        //TODO: Check if $typeName is instance of IValueProvder
        $this->valueProvider = $valueProvider;
        return $this;
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
}
