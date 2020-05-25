<?php

declare(strict_types=1);

namespace Atom\Database\Mapping;

use Atom\Hydrator\AbstractHydrator;

final class MappingHydrator extends AbstractHydrator
{
    private Mapping $mapping;

    public function __construct(string $typeName, Mapping $mapping)
    {
        parent::__construct($typeName);
        $this->mapping = $mapping;
    }

    public function hydrate(array $data)
    {
        $instance = $this->reflection->newInstanceArgs([]);

        foreach ($this->mapping->getFieldMapping() as $fieldMapping) {
            $fieldName = $fieldMapping->getFieldName();
            if (isset($data[$fieldName])) {
                $value = $data[$fieldName];
                $fieldMapping->setPropertyValue($this->reflection, $instance, $value);
            }
        }

        return $instance;
    }
}
