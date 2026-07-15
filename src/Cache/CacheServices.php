<?php

declare(strict_types=1);

namespace Atom\Cache;

use Atom\Di\Bindings;
use Atom\Di\InjectionContext;
use Atom\Di\Injector;
use Atom\Di\ServiceProviderInterface;
use Atom\Support\Paths;
use Atom\Console\ConsoleCommandProviderInterface;
use Atom\Console\ConsoleCommandSources;

final readonly class CacheServices implements ServiceProviderInterface, ConsoleCommandProviderInterface
{
    public function register(Bindings $bindings): void
    {
        $bindings->bind(CacheInterface::class)
            ->toFactory(static function (Injector $injector, InjectionContext $context): FileCache {
                $options = $injector->get(CacheOptions::class, $context);
                $paths = $injector->get(Paths::class, $context);

                return new FileCache(
                    $paths->resolve($options->directory),
                    $options->prefix,
                    $options->defaultTtl
                );
            })
            ->singleton();
    }

    public function consoleCommands(ConsoleCommandSources $commands): void
    {
        $commands->add(__DIR__ . "/Commands", __NAMESPACE__ . "\\Commands");
    }
}
