<?php

declare(strict_types=1);

namespace Atom\Queue;

use Atom\Console\ConsoleCommandProviderInterface;
use Atom\Console\ConsoleCommandSources;
use Atom\Database\DatabaseConnection;
use Atom\Di\Bindings;
use Atom\Di\Injector;
use Atom\Di\ServiceProviderInterface;
use Atom\Support\Paths;

final readonly class QueueServices implements ServiceProviderInterface, ConsoleCommandProviderInterface
{
    public function register(Bindings $bindings): void
    {
        $bindings->bind(JobRegistry::class)->toSelf()->singleton();
        $bindings->bind(JobExecutor::class)
            ->toFactory(static fn(Injector $injector): JobExecutor => new JobExecutor(
                $injector->get(JobRegistry::class),
                $injector
            ))
            ->singleton();

        $bindings->bind(QueueInterface::class)
            ->toFactory(static function (Injector $injector): QueueInterface {
                $options = $injector->get(QueueOptions::class);

                return match (strtolower($options->driver)) {
                    "sync" => new ArrayQueue(),
                    "file" => new FileQueue(
                        $injector->get(Paths::class)->resolve($options->directory)
                    ),
                    "database" => new DatabaseQueue($injector->get(DatabaseConnection::class)),
                    default => throw new QueueException("Unsupported queue driver '{$options->driver}'."),
                };
            })
            ->singleton();

        $bindings->bind(JobDispatcherInterface::class)
            ->toFactory(static function (Injector $injector): JobDispatcherInterface {
                $options = $injector->get(QueueOptions::class);
                if (strtolower($options->driver) === "sync") {
                    return new SyncJobDispatcher($injector->get(JobExecutor::class), $options);
                }

                return new QueueJobDispatcher($injector->get(QueueInterface::class), $options);
            })
            ->singleton();

        $bindings->bind(QueueWorker::class)->toSelf()->singleton();
    }

    public function consoleCommands(ConsoleCommandSources $commands): void
    {
        $commands->add(__DIR__ . "/Commands", __NAMESPACE__ . "\\Commands");
    }
}
