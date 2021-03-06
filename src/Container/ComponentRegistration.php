<?php

declare(strict_types=1);

namespace Atom\Container;

use Atom\Container\Resolver\InstanceResolver;
use Atom\Container\Resolver\FactoryResolver;
use Atom\Container\Resolver\ClassResolver;
use Atom\Container\Resolver\IDependencyResolver;
use Atom\Container\Resolver\TypeFactoryResolver;

final class ComponentRegistration
{
    public const CLASS_NAME = 1;
    public const FACTORY_METHOD = 2;
    public const INSTANCE = 3;
    public const TYPE_FACTORY = 4;

    public $type;
    public $name;
    public $factory;
    public $sourceType;
    public $targetType;
    public $constructorArguments = [];
    public $properties = [];
    public $instance = null;
    public $isShared = false;
    private $container;

    public function __construct(string $sourceType, $container)
    {
        $this->container = $container;
        $this->sourceType = $sourceType;
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function getRegistrationTypeName(): string
    {
        switch ($this->type) {
            case self::CLASS_NAME:
                return "Class";
            case self::FACTORY_METHOD:
                return "Factory";
            case self::INSTANCE:
                return "Instance";
            case self::TYPE_FACTORY:
                return "TypeFactory";
            default:
                return "";
        }
    }

    public function to(string $targetType): self
    {
        $this->type = self::CLASS_NAME;
        $this->targetType = $targetType;
        return $this;
    }

    public function toSelf(): self
    {
        $this->type = self::CLASS_NAME;
        $this->targetType = $this->sourceType;
        return $this;
    }

    public function toInstance($instance): self
    {
        $this->type = self::INSTANCE;
        $this->instance = $instance;
        $this->isShared = true;
        return $this;
    }

    public function toFactory(callable $factory): self
    {
        $this->type = self::FACTORY_METHOD;
        $this->factory = $factory;
        return $this;
    }

    public function toTypeFactory(object $factory): self
    {
        $this->type = self::TYPE_FACTORY;
        $this->factory = $factory;
        return $this;
    }

    public function withConstructorArguments(array $constructorArguments): self
    {
        $this->constructorArguments = $constructorArguments;
        return $this;
    }

    public function withName(string $name): self
    {
        $this->name = $name;
        $this->container->alias($name, $this->sourceType);
        return $this;
    }

    public function asShared(): self
    {
        $this->isShared = true;
        return $this;
    }

    public function withProperties(array $properties): self
    {
        $this->properties = $properties;
        return $this;
    }

    public function getResolver(): IDependencyResolver
    {
        switch ($this->type) {
            case self::INSTANCE: {
                    return new InstanceResolver($this);
                }
            case self::FACTORY_METHOD: {
                    return new FactoryResolver($this);
                }
            case self::CLASS_NAME: {
                    return  new ClassResolver($this);
                }
            case self::TYPE_FACTORY: {
                    return new TypeFactoryResolver($this);
                }
        }
        throw new \RuntimeException("Invalid component registration");
    }
}
