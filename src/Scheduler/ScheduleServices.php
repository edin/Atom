<?php

declare(strict_types=1);

namespace Atom\Scheduler;

use Atom\Console\ConsoleCommandProviderInterface;
use Atom\Console\ConsoleCommandSources;
use Atom\Di\Bindings;
use Atom\Di\ServiceProviderInterface;

final readonly class ScheduleServices implements ServiceProviderInterface, ConsoleCommandProviderInterface
{
    public function register(Bindings $bindings): void
    {
        $bindings->bind(Schedule::class)->toSelf()->singleton();
        $bindings->bind(ClockInterface::class)->to(SystemClock::class)->singleton();
        $bindings->bind(ScheduleRunner::class)->toSelf()->singleton();
    }

    public function consoleCommands(ConsoleCommandSources $commands): void
    {
        $commands->add(__DIR__ . "/Commands", __NAMESPACE__ . "\\Commands");
    }
}
