<?php

declare(strict_types=1);

namespace Atom\Console;

use Atom\Di\Bindings;
use Atom\Di\Injector;
use Atom\Di\ServiceProviderInterface;
use Atom\Di\ServiceProviderRegistry;

final class ConsoleServices implements ServiceProviderInterface, ConsoleCommandProviderInterface
{
    public function register(Bindings $bindings): void
    {
        $bindings->bind(ConsoleCommandSources::class)
            ->toFactory(function (Injector $injector): ConsoleCommandSources {
                $sources = new ConsoleCommandSources();
                $providers = $injector->get(ServiceProviderRegistry::class)->providers();

                foreach ($providers as $provider) {
                    if ($provider instanceof ConsoleCommandProviderInterface) {
                        $provider->consoleCommands($sources);
                    }
                }

                return $sources;
            })
            ->singleton();

        $bindings->bind(ConsoleApplication::class)
            ->toFactory(function (Injector $injector): ConsoleApplication {
                $console = new ConsoleApplication($injector);
                $console->discoverFrom($injector->get(ConsoleCommandSources::class));

                return $console;
            })
            ->singleton();
    }

    public function consoleCommands(ConsoleCommandSources $commands): void
    {
        $commands->add(__DIR__ . "/Commands", __NAMESPACE__ . "\\Commands");
    }
}
