<?php

declare(strict_types=1);

namespace Atom\Di;

use InvalidArgumentException;

final class ServiceProviderRegistry
{
    /** @var array<int, class-string<ServiceProviderInterface>|ServiceProviderInterface> */
    private array $providers = [];
    /** @var array<int, ServiceProviderInterface> */
    private array $resolved = [];

    /**
     * @param iterable<class-string<ServiceProviderInterface>|ServiceProviderInterface> $providers
     */
    public function __construct(iterable $providers = [])
    {
        foreach ($providers as $provider) {
            $this->add($provider);
        }
    }

    public static function create(): self
    {
        return new self();
    }

    /**
     * @param class-string<ServiceProviderInterface>|ServiceProviderInterface $provider
     */
    public function add(string|ServiceProviderInterface $provider): self
    {
        $this->providers[] = $provider;
        return $this;
    }

    public function register(Bindings $bindings): Bindings
    {
        foreach (array_keys($this->providers) as $index) {
            $this->resolve($index)->register($bindings);
        }

        return $bindings;
    }

    /**
     * @return ServiceProviderInterface[]
     */
    public function providers(): array
    {
        foreach (array_keys($this->providers) as $index) {
            $this->resolve($index);
        }

        return array_values($this->resolved);
    }

    public function bindings(): Bindings
    {
        return $this->register(Bindings::create());
    }

    public function injector(): Injector
    {
        return Injector::create($this->bindings());
    }

    private function resolve(int $index): ServiceProviderInterface
    {
        if (isset($this->resolved[$index])) {
            return $this->resolved[$index];
        }

        $provider = $this->providers[$index];

        if ($provider instanceof ServiceProviderInterface) {
            return $this->resolved[$index] = $provider;
        }

        $instance = new $provider();
        if (!$instance instanceof ServiceProviderInterface) {
            throw new InvalidArgumentException("Service provider '{$provider}' must implement ServiceProviderInterface.");
        }

        return $this->resolved[$index] = $instance;
    }
}
