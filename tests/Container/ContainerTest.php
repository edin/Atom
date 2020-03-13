<?php

namespace Atom\Tests\Container;

use PHPUnit\Framework\TestCase;
use Atom\Container\Container;
use Atom\Tests\Container\Types\Database;
use Atom\Tests\Container\Types\IDatabase;
use Atom\Tests\Container\Types\IRepository;
use Atom\Tests\Container\Types\Repository;

final class ContainerTest extends TestCase
{
    public function testResolveValue(): void
    {
        $container = new Container;
        $container->bind("Hello")->toInstance("World");
        $result = $container->resolve("Hello");

        $this->assertEquals($result, "World");
    }

    public function testResolveInstance(): void
    {
        $repository = new Repository(new Database());

        $container = new Container;
        $container->bind(IRepository::class)->toInstance($repository);
        $result = $container->resolve(IRepository::class);

        $this->assertEquals($result, $repository);
        $this->assertInstanceOf(Repository::class, $result);
    }

    public function testResolveClassToSelf(): void
    {
        $container = new Container;
        $container->bind(IDatabase::class)->to(Database::class);
        $container->bind(Repository::class)->toSelf();

        $result = $container->resolve(Repository::class);

        $this->assertInstanceOf(Repository::class, $result);
        $this->assertInstanceOf(Database::class, $result->database);
    }

    public function testResolveInterfaceToClassWithParameter(): void
    {
        $container = new Container;
        $container->bind(IDatabase::class)->to(Database::class);
        $container->bind(IRepository::class)->to(Repository::class);

        $result = $container->resolve(Repository::class);

        $this->assertInstanceOf(Repository::class, $result);
        $this->assertInstanceOf(Database::class, $result->database);
    }

    public function testResolveInterfaceToClassWithoutParameters(): void
    {
        $container = new Container;
        $container->bind(IDatabase::class)->to(Database::class);

        $result = $container->resolve(IDatabase::class);

        $this->assertInstanceOf(Database::class, $result);
    }

    public function testResolveInterfaceToClassWithSharedParameter(): void
    {
        $container = new Container;
        $container->bind(IDatabase::class)->to(Database::class)->asShared();
        $container->bind(IRepository::class)->to(Repository::class);

        $result1 = $container->resolve(Repository::class);
        $result2 = $container->resolve(Repository::class);


        $this->assertInstanceOf(Database::class, $result1->database);
        $this->assertInstanceOf(Database::class, $result2->database);

        $this->assertEquals($result1->database, $result2->database);
    }

    public function testResolveByAlias(): void
    {
        $container = new Container;
        $container->alias("Repo", IRepository::class);
        $container->bind(IDatabase::class)->to(Database::class)->asShared();
        $container->bind(IRepository::class)->to(Repository::class);

        $result = $container->resolve("Repo");

        $this->assertInstanceOf(Repository::class, $result);
        $this->assertInstanceOf(Database::class, $result->database);
    }

    public function testResolveClassThatIsNotRegistered(): void
    {
        $container = new Container;
        $container->bind(IDatabase::class)->to(Database::class)->asShared();

        $result = $container->resolve(Repository::class);


        $this->assertInstanceOf(Repository::class, $result);
        $this->assertInstanceOf(Database::class, $result->database);
    }

    public function testRegisterMultipleComponentsOfSameType(): void
    {
        $container = new Container;
        $container->alias("X", "A");
        $container->bind("A")->to(Database::class)->asShared();
        $container->bind("B")->to(Database::class)->asShared();

        $result1 = $container->resolve("A");
        $result2= $container->resolve("B");

        $this->assertInstanceOf(Database::class, $result1);
        $this->assertInstanceOf(Database::class, $result2);

        $this->assertNotSame($result1, $result2);
    }
}
