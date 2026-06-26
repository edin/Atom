<?php

declare(strict_types=1);

namespace Atom\Di;

use Atom\Di\Attributes\Inject;
use Atom\Di\Exception\CircularDependencyException;
use Atom\Di\Exception\DependencyResolutionException;
use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

final class Injector
{
    /** @var array<string, Provider> */
    private array $providers = [];
    /** @var array<string, mixed> */
    private array $singletons = [];
    /** @var TypeFactory[] */
    private array $typeFactories = [];

    /**
     * @param iterable<Provider>|Bindings $providers
     */
    public function __construct(iterable|Bindings $providers = [], private ?self $parent = null)
    {
        if ($providers instanceof Bindings) {
            $this->typeFactories = $providers->typeFactories();
            $providers = $providers->providers();
        }

        foreach ($providers as $provider) {
            $this->providers[$provider->token] = $provider;
        }
    }

    /**
     * @param iterable<Provider>|Bindings $providers
     */
    public static function create(iterable|Bindings $providers = []): self
    {
        return new self($providers);
    }

    /**
     * @param iterable<Provider>|Bindings $providers
     */
    public function createChild(iterable|Bindings $providers = []): self
    {
        return new self($providers, $this);
    }

    public function has(string $token): bool
    {
        return isset($this->providers[$token]) ||
            $this->parent?->has($token) === true ||
            class_exists($token);
    }

    public function get(string $token, ?InjectionContext $context = null): mixed
    {
        $context ??= new InjectionContext();
        $provider = $this->findProvider($token);

        if ($provider === null) {
            if (!class_exists($token)) {
                throw new DependencyResolutionException($this->formatMessage(
                    "No provider found for token '{$token}'.",
                    $context,
                    $token
                ));
            }

            $typeFactory = $this->findTypeFactory($token);
            if ($typeFactory !== null) {
                return $this->resolveToken($token, $context, fn(): mixed => $typeFactory->create($token, $this, $context));
            }

            $provider = Provider::type($token);
        }

        $owner = $this->findProviderOwner($provider->token) ?? $this;

        if ($provider->lifetime === ProviderLifetime::Singleton && array_key_exists($provider->token, $owner->singletons)) {
            return $owner->singletons[$provider->token];
        }

        if ($provider->lifetime === ProviderLifetime::Scoped && $context->has($provider->token)) {
            return $context->get($provider->token);
        }

        $instance = $this->resolveToken(
            $provider->token,
            $context,
            fn(): mixed => $owner->resolveProvider($provider, $context)
        );

        if ($provider->lifetime === ProviderLifetime::Singleton) {
            $owner->singletons[$provider->token] = $instance;
        }

        if ($provider->lifetime === ProviderLifetime::Scoped) {
            $context->set($provider->token, $instance);
        }

        return $instance;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function instantiate(string $className, array $parameters = [], ?InjectionContext $context = null): object
    {
        $context ??= new InjectionContext();
        $reflection = new ReflectionClass($className);

        if (!$reflection->isInstantiable()) {
            throw new DependencyResolutionException($this->formatMessage(
                "Class '{$className}' is not instantiable.",
                $context,
                $className
            ));
        }

        $constructor = $reflection->getConstructor();
        $arguments = $constructor === null ? [] : $this->resolveParameters($constructor, $parameters, $context);

        return $reflection->newInstanceArgs($arguments);
    }

    public function addTypeFactory(TypeFactory $typeFactory): self
    {
        $this->typeFactories[] = $typeFactory;
        return $this;
    }

    /**
     * @param callable|array{0: object|string, 1: string} $callback
     * @param array<string, mixed> $parameters
     */
    public function invoke(callable|array $callback, array $parameters = [], ?InjectionContext $context = null): mixed
    {
        $context ??= new InjectionContext();
        $reflection = $this->reflectCallable($callback);
        $arguments = $this->resolveParameters($reflection, $parameters, $context);

        if ($reflection instanceof ReflectionMethod) {
            $target = is_array($callback) ? $callback[0] : null;
            return $reflection->invokeArgs(is_object($target) ? $target : null, $arguments);
        }

        return $reflection->invokeArgs($arguments);
    }

    private function resolveProvider(Provider $provider, InjectionContext $context): mixed
    {
        return match ($provider->kind) {
            ProviderKind::Type => $this->instantiate($provider->value, context: $context),
            ProviderKind::Value => $provider->value,
            ProviderKind::Factory => ($provider->value)($this, $context),
            ProviderKind::Existing => $this->get($provider->value, $context),
        };
    }

    private function resolveToken(string $token, InjectionContext $context, callable $resolve): mixed
    {
        if ($context->isResolving($token)) {
            throw new CircularDependencyException(
                "Circular dependency detected: " . implode(" -> ", $context->resolvingPath($token))
            );
        }

        $context->enter($token);

        try {
            return $resolve();
        } catch (DependencyResolutionException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new DependencyResolutionException($this->formatMessage(
                "Failed to resolve token '{$token}': {$exception->getMessage()}",
                $context
            ), $exception);
        } finally {
            $context->leave($token);
        }
    }

    private function findProvider(string $token): ?Provider
    {
        return $this->providers[$token] ?? $this->parent?->findProvider($token);
    }

    private function findProviderOwner(string $token): ?self
    {
        if (isset($this->providers[$token])) {
            return $this;
        }

        return $this->parent?->findProviderOwner($token);
    }

    private function findTypeFactory(string $className): ?TypeFactory
    {
        $type = new TypeInfo($className);

        foreach ($this->typeFactories as $typeFactory) {
            if ($typeFactory->matches($type)) {
                return $typeFactory;
            }
        }

        return $this->parent?->findTypeFactory($className);
    }

    /**
     * @param array<string, mixed> $parameters
     * @return array<int, mixed>
     */
    private function resolveParameters(
        ReflectionFunctionAbstract $function,
        array $parameters,
        InjectionContext $context
    ): array {
        $arguments = [];

        foreach ($function->getParameters() as $parameter) {
            try {
                $arguments[$parameter->getPosition()] = $this->resolveParameter($parameter, $parameters, $context);
            } catch (CircularDependencyException $exception) {
                throw $exception;
            } catch (DependencyResolutionException $exception) {
                throw new DependencyResolutionException(
                    $this->formatParameterMessage($function, $parameter, $exception->getMessage(), $context),
                    $exception
                );
            }
        }

        return $arguments;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function resolveParameter(ReflectionParameter $parameter, array $parameters, InjectionContext $context): mixed
    {
        if (array_key_exists($parameter->getName(), $parameters)) {
            return $parameters[$parameter->getName()];
        }

        $inject = $parameter->getAttributes(Inject::class)[0] ?? null;
        if ($inject !== null) {
            return $this->get($inject->newInstance()->token, $context);
        }

        $type = $parameter->getType();
        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            try {
                return $this->get($type->getName(), $context);
            } catch (DependencyResolutionException $exception) {
                if ($type->allowsNull()) {
                    return null;
                }

                throw $exception;
            }
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new DependencyResolutionException("Unable to resolve parameter '{$parameter->getName()}'.");
    }

    private function reflectCallable(callable|array $callback): ReflectionFunctionAbstract
    {
        if (is_array($callback)) {
            return new ReflectionMethod($callback[0], $callback[1]);
        }

        if ($callback instanceof Closure) {
            return new ReflectionFunction($callback);
        }

        if (is_string($callback) && str_contains($callback, "::")) {
            return new ReflectionMethod($callback);
        }

        return new ReflectionFunction($callback);
    }

    private function formatMessage(string $message, InjectionContext $context, ?string $token = null): string
    {
        $path = $context->resolvingPath($token);
        if ($path === []) {
            return $message;
        }

        return $message . " Resolution path: " . implode(" -> ", $path) . ".";
    }

    private function formatParameterMessage(
        ReflectionFunctionAbstract $function,
        ReflectionParameter $parameter,
        string $message,
        InjectionContext $context
    ): string {
        $owner = $function instanceof ReflectionMethod
            ? $function->getDeclaringClass()->getName() . "::" . $function->getName()
            : $function->getName();

        return $this->formatMessage(
            "Unable to resolve parameter '{$parameter->getName()}' for {$owner}: {$message}",
            $context
        );
    }
}
