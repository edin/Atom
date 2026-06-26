<?php

declare(strict_types=1);

namespace Atom\Database;

use Atom\Console\ConsoleCommandProviderInterface;
use Atom\Console\ConsoleCommandSources;
use Atom\Database\Schema\Reset\DatabaseResetterInterface;
use Atom\Database\Orm\EntityMetadataFactory;
use Atom\Database\Orm\RowHydrator;
use Atom\Database\Lock\DatabaseLockManagerInterface;
use Atom\Database\Migration\DatabaseMigrationRepository;
use Atom\Database\Migration\MigrationCreator;
use Atom\Database\Migration\MigrationDiscovery;
use Atom\Database\Migration\MigrationOptions;
use Atom\Database\Migration\MigrationRepositoryInterface;
use Atom\Database\Migration\Migrator;
use Atom\Di\Bindings;
use Atom\Di\Injector;
use Atom\Di\ServiceProviderInterface;
use Atom\Database\Seeder\SeederDiscovery;
use Atom\Database\Seeder\SeederCreator;
use Atom\Database\Seeder\SeederOptions;
use Atom\Database\Seeder\SeederRunner;

final readonly class DatabaseServices implements ServiceProviderInterface, ConsoleCommandProviderInterface
{
    public function __construct(
        private DatabaseDriver $driver,
        private MigrationOptions $migrations = new MigrationOptions(),
        private SeederOptions $seeders = new SeederOptions()
    ) {
    }

    public function register(Bindings $bindings): void
    {
        $bindings->value(DatabaseDriver::class, $this->driver);

        $bindings->bind(DatabaseConnection::class)
            ->toSelf()
            ->singleton();

        $bindings->bind(EntityMetadataFactory::class)
            ->toSelf()
            ->singleton();

        $bindings->bind(RowHydrator::class)
            ->toSelf()
            ->singleton();

        $bindings->bind(Db::class)
            ->toSelf()
            ->singleton();

        $bindings->bind(DatabaseResetterInterface::class)
            ->toFactory(fn(Injector $injector): DatabaseResetterInterface => $injector->get(DatabaseDriver::class)->resetter())
            ->singleton();

        $bindings->bind(MigrationOptions::class)
            ->toValue($this->migrations);

        $bindings->bind(MigrationRepositoryInterface::class)
            ->to(DatabaseMigrationRepository::class)
            ->singleton();

        $bindings->bind(DatabaseLockManagerInterface::class)
            ->toFactory(fn(Injector $injector): DatabaseLockManagerInterface => $injector
                ->get(DatabaseDriver::class)
                ->lockManager($injector->get(DatabaseConnection::class)))
            ->singleton();

        $bindings->bind(MigrationDiscovery::class)
            ->toSelf()
            ->singleton();

        $bindings->bind(MigrationCreator::class)
            ->toSelf()
            ->singleton();

        $bindings->bind(Migrator::class)
            ->toSelf()
            ->singleton();

        $bindings->bind(SeederOptions::class)
            ->toValue($this->seeders);

        $bindings->bind(SeederDiscovery::class)
            ->toSelf()
            ->singleton();

        $bindings->bind(SeederCreator::class)
            ->toSelf()
            ->singleton();

        $bindings->bind(SeederRunner::class)
            ->toFactory(fn(Injector $injector): SeederRunner => new SeederRunner(
                $injector,
                $injector->get(SeederDiscovery::class),
                $injector->get(SeederOptions::class)
            ))
            ->singleton();
    }

    public function consoleCommands(ConsoleCommandSources $commands): void
    {
        $commands->add(__DIR__ . "/Migration/Commands", __NAMESPACE__ . "\\Migration\\Commands");
        $commands->add(__DIR__ . "/Seeder/Commands", __NAMESPACE__ . "\\Seeder\\Commands");
    }
}
