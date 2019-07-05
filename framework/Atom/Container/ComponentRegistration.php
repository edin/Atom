<?php

namespace Atom\Container;

use Atom\Container\Resolver\AliasResolver;
use Atom\Container\Resolver\InstanceResolver;
use Atom\Container\Resolver\FactoryResolver;
use Atom\Container\Resolver\ClassResolver;

final class ComponentRegistration
{
    public const CLASS_NAME = 1;
    public const FACTORY_METHOD = 2;
    public const INSTANCE = 3;

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

    public function withConstructorArguments(array $constructorArguments): self
    {
        $this->constructorArguments = $constructorArguments;
        return $this;
    }

    public function withName(string $name): self
    {
        $this->name = $name;
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

    public function getResolvers(): array
    {
        $resolvers = [];

        if ($this->name) {
            $resolvers[$this->name] = new AliasResolver($this);
        }

        switch ($this->type) {
            case self::INSTANCE: {
                $resolvers[$this->sourceType] = new InstanceResolver($this);
                break;
            }
            case self::FACTORY_METHOD: {
                $resolvers[$this->sourceType] = new FactoryResolver($this);
                break;
            }
            case self::CLASS_NAME: {
                $resolvers[$this->sourceType] = new ClassResolver($this);
                break;
            }
            default: {
                throw new \Exception("Invalid component registration");
            }
        }

        return $resolvers;
    }
}
