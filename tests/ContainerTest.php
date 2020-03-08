<?php

namespace Atom\Tests;

use PHPUnit\Framework\TestCase;
use Atom\Container\Container;

interface IDatabase
{
}

interface IRepository
{
}

class MySqlDatabase implements IDatabase
{
}

class Repository implements IRepository
{
    public $database;

    public function __construct(IDatabase $database)
    {
        $this->database = $database;
    }
}

class ProxyRepository implements IRepository
{
    public $repository;

    public function __construct(IRepository $repository)
    {
        $this->repository = $repository;
    }
}


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
        $repository = new Repository(new MySqlDatabase());

        $container = new Container;
        $container->bind(IRepository::class)->toInstance($repository);
        $result = $container->resolve(IRepository::class);

        $this->assertEquals($result, $repository);
        $this->assertInstanceOf(Repository::class, $result);
    }

    public function testResolveClassToSelf(): void
    {
        $container = new Container;
        $container->bind(IDatabase::class)->to(MySqlDatabase::class);
        $container->bind(Repository::class)->toSelf();

        $result = $container->resolve(Repository::class);

        $this->assertInstanceOf(Repository::class, $result);
        $this->assertInstanceOf(MySqlDatabase::class, $result->database);
    }

    public function testResolveInterfaceToClassWithParameter(): void
    {
        $container = new Container;
        $container->bind(IDatabase::class)->to(MySqlDatabase::class);
        $container->bind(IRepository::class)->to(Repository::class);

        $result = $container->resolve(Repository::class);

        $this->assertInstanceOf(Repository::class, $result);
        $this->assertInstanceOf(MySqlDatabase::class, $result->database);
    }

    public function testResolveInterfaceToClassWithoutParameters(): void
    {
        $container = new Container;
        $container->bind(IDatabase::class)->to(MySqlDatabase::class);

        $result = $container->resolve(IDatabase::class);

        $this->assertInstanceOf(MySqlDatabase::class, $result);
    }

    public function testResolveInterfaceToClassWithSharedParameter(): void
    {
        $container = new Container;
        $container->bind(IDatabase::class)->to(MySqlDatabase::class)->asShared();
        $container->bind(IRepository::class)->to(Repository::class);

        $result1 = $container->resolve(Repository::class);
        $result2 = $container->resolve(Repository::class);


        $this->assertInstanceOf(MySqlDatabase::class, $result1->database);
        $this->assertInstanceOf(MySqlDatabase::class, $result2->database);

        $this->assertEquals($result1->database, $result2->database);
    }

    public function testResolveByAlias(): void
    {
        $container = new Container;
        $container->alias("Repo", IRepository::class);
        $container->bind(IDatabase::class)->to(MySqlDatabase::class)->asShared();
        $container->bind(IRepository::class)->to(Repository::class);

        $result = $container->resolve("Repo");

        $this->assertInstanceOf(Repository::class, $result);
        $this->assertInstanceOf(MySqlDatabase::class, $result->database);
    }

    public function testResolveClassThatIsNotRegistered(): void
    {
        $container = new Container;
        $container->bind(IDatabase::class)->to(MySqlDatabase::class)->asShared();

        $result = $container->resolve(Repository::class);


        $this->assertInstanceOf(Repository::class, $result);
        $this->assertInstanceOf(MySqlDatabase::class, $result->database);
    }

    public function testRegisterMultipleComponentsOfSameType(): void
    {
        $container = new Container;
        $container->alias("X", "A");
        $container->bind("A")->to(MySqlDatabase::class)->asShared();
        $container->bind("B")->to(MySqlDatabase::class)->asShared();

        $result1 = $container->resolve("A");
        $result2= $container->resolve("B");

        $this->assertInstanceOf(MySqlDatabase::class, $result1);
        $this->assertInstanceOf(MySqlDatabase::class, $result2);

        $this->assertNotSame($result1, $result2);
    }
}
