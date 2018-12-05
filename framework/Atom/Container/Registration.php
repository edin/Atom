<?php

namespace Atom\Container;

final class Registration
{
    public const CLASS_NAME = 1;
    public const FACTORY_METHOD = 2;
    public const INSTANCE = 3;

    public $type;
    public $factory;
    public $name;
    public $className;
    public $instance;
    public $isShared;
    public $namespaceOf;
    public $typeOf;

    public static function fromClassName(string $className, bool $isShared=true): Registration
    {
        $result = new Registration();
        $result->type = Registration::CLASS_NAME;
        $result->className = $className;
        $result->isShared = $isShared;
        return $result;
    }

    public static function fromFactory(callable $factory, bool $isShared=true): Registration
    {
        $result = new Registration();
        $result->type = Registration::FACTORY_METHOD;
        $result->factory = $factory;
        $result->isShared = $isShared;
        return $result;
    }

    public static function fromInstance($instance): Registration
    {
        $result = new Registration();
        $result->type = Registration::INSTANCE;
        $result->instance = $instance;
        $result->isShared = true;
        return $result;
    }

    public static function create($definition): Registration
    {
        if (empty($definition)) {
            throw new \Exception("Component definition can't be null or empty, it must be class name, factory method or instance.");
        }

        if ($definition instanceof Registration) {
            return $definition;
        }

        if (is_string($definition)) {
            return Registration::fromClassName($definition);
        }

        if ($definition instanceof \Closure) {
            return Registration::fromFactory($definition);
        }

        return Registration::fromInstance($definition);
    }
}