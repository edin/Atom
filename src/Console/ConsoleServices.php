<?php

declare(strict_types=1);

namespace Atom\Console;

use Atom\Console\Make\ApplicationFileCreator;
use Atom\Console\Make\MakeOptions;
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

        $bindings->bind(MakeOptions::class)
            ->toFactory(fn(): MakeOptions => new MakeOptions(getcwd() ?: ""))
            ->singleton();

        $bindings->bind(FileTemplateRenderer::class)
            ->toFactory(fn(Injector $injector): FileTemplateRenderer => new FileTemplateRenderer([
                $this->templateDirectory($injector->get(MakeOptions::class)),
                __DIR__ . "/../Templates",
            ]))
            ->singleton();

        $bindings->bind(ApplicationFileCreator::class)
            ->toSelf()
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

    private function templateDirectory(MakeOptions $options): string
    {
        $directory = str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $options->templateDirectory);
        if ($options->root === "" || preg_match('/^(?:[A-Za-z]:[\/\\\\]|[\/\\\\])/', $directory) === 1) {
            return $directory;
        }

        return rtrim($options->root, "/\\") . DIRECTORY_SEPARATOR . ltrim($directory, "/\\");
    }
}
