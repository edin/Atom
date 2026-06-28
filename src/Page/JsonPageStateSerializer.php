<?php

declare(strict_types=1);

namespace Atom\Page;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;

final readonly class JsonPageStateSerializer implements PageStateSerializer
{
    public function serialize(Page $page): string
    {
        $state = [];

        foreach ($this->properties($page) as $property) {
            if (!$property->isInitialized($page)) {
                continue;
            }

            $state[$property->getName()] = $property->getValue($page);
        }

        if ($state === []) {
            return "";
        }

        $json = json_encode($state, JSON_UNESCAPED_SLASHES);

        return $json === false ? "" : $this->encode($json);
    }

    public function deserialize(Page $page, ?string $state): void
    {
        if ($state === null || trim($state) === "") {
            return;
        }

        $json = $this->decode($state);
        $values = json_decode($json, true);

        if (!is_array($values)) {
            return;
        }

        foreach ($this->properties($page) as $property) {
            $name = $property->getName();
            if (array_key_exists($name, $values)) {
                $property->setValue($page, $values[$name]);
            }
        }
    }

    /**
     * @return ReflectionProperty[]
     */
    private function properties(Page $page): array
    {
        $reflection = new ReflectionClass($page);
        $properties = [];

        foreach ($reflection->getProperties() as $property) {
            if ($property->isStatic() || $property->getAttributes(State::class, ReflectionAttribute::IS_INSTANCEOF) === []) {
                continue;
            }

            $property->setAccessible(true);
            $properties[] = $property;
        }

        return $properties;
    }

    private function encode(string $json): string
    {
        return rtrim(strtr(base64_encode($json), "+/", "-_"), "=");
    }

    private function decode(string $state): string
    {
        $state = strtr($state, "-_", "+/");
        $padding = strlen($state) % 4;

        if ($padding > 0) {
            $state .= str_repeat("=", 4 - $padding);
        }

        return base64_decode($state, true) ?: "";
    }
}
