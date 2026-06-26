<?php

declare(strict_types=1);

namespace Atom\Tests\Database\Seeder;

use Atom\Console\BufferedConsoleOutput;
use Atom\Console\ConsoleApplication;
use Atom\Console\ConsoleServices;
use Atom\Database\DatabaseConnection;
use Atom\Database\DatabaseServices;
use Atom\Database\Driver\SqliteDriver;
use Atom\Database\Seeder\SeederDiscovery;
use Atom\Database\Seeder\SeederOptions;
use Atom\Database\Seeder\SeederRunner;
use Atom\Di\Bindings;
use Atom\Di\Injector;
use Atom\Di\ServiceProviderRegistry;
use PHPUnit\Framework\TestCase;

final class SeederRunnerTest extends TestCase
{
    public function testDiscoversAndRunsSeedersWithInjectedServices(): void
    {
        $connection = new DatabaseConnection(SqliteDriver::memory());
        $connection->execute("CREATE TABLE seed_users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL)");
        $injector = Injector::create(Bindings::create()->value(DatabaseConnection::class, $connection));

        $runner = new SeederRunner($injector, new SeederDiscovery(), $this->options());

        $result = $runner->run();

        $this->assertSame(["S26_01_01_000000_seed_users"], $result->seeders);
        $this->assertSame("Edin", $connection->scalar("SELECT name FROM seed_users LIMIT 1"));

        $secondRun = $runner->run();

        $this->assertSame(1, (int) $connection->scalar("SELECT COUNT(*) FROM seed_users"));
        $this->assertSame(["S26_01_01_000000_seed_users"], $secondRun->seeders);
    }

    public function testSeederCommandIsDiscoveredFromDatabaseServices(): void
    {
        $directory = $this->tempDirectory();
        $providers = ServiceProviderRegistry::create()
            ->add(ConsoleServices::class)
            ->add(new DatabaseServices(
                SqliteDriver::memory(),
                seeders: new SeederOptions($directory)
            ));
        $bindings = $providers->bindings()
            ->value(ServiceProviderRegistry::class, $providers);
        $injector = Injector::create($bindings);
        $console = $injector->get(ConsoleApplication::class);

        $this->assertTrue($console->commands()->has("make:seeder"));
        $this->assertTrue($console->commands()->has("db:seed"));

        $output = new BufferedConsoleOutput();
        $code = $console->run(["atom", "make:seeder", "seed_users"], $output);

        $this->assertSame(0, $code);
        $this->assertStringContainsString("Created seeder:", $output->output());
        $this->assertCount(1, glob($directory . DIRECTORY_SEPARATOR . "S*_seed_users.php") ?: []);
    }

    public function testSeederCommandRunsSeeders(): void
    {
        $providers = ServiceProviderRegistry::create()
            ->add(ConsoleServices::class)
            ->add(new DatabaseServices(
                SqliteDriver::memory(),
                seeders: $this->options()
            ));
        $bindings = $providers->bindings()
            ->value(ServiceProviderRegistry::class, $providers);
        $injector = Injector::create($bindings);
        $connection = $injector->get(DatabaseConnection::class);
        $connection->execute("CREATE TABLE seed_users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL)");
        $console = $injector->get(ConsoleApplication::class);

        $output = new BufferedConsoleOutput();
        $code = $console->run(["atom", "db:seed"], $output);

        $this->assertSame(0, $code);
        $this->assertStringContainsString("Seeded: S26_01_01_000000_seed_users", $output->output());
        $this->assertSame("Edin", $connection->scalar("SELECT name FROM seed_users LIMIT 1"));
    }

    private function options(): SeederOptions
    {
        return new SeederOptions(__DIR__ . "/Fixtures");
    }

    private function tempDirectory(): string
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "atom_make_seeders_" . uniqid();
        mkdir($directory, 0777, true);

        return $directory;
    }
}
