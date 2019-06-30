<?php

include "Container.php";
include "CyclicDependencyException.php";
include "Instance.php";
include "ComponentRegistration.php";
include "ResolutionScope.php";

use Atom\Container\{Container, Instance};

interface IRepository
{
}

class Repository implements IRepository
{
    public function __construct()
    {
        $this->value = mt_rand(1, 10000);
    }
}

class CachedRepository implements IRepository
{
    public function __construct(IRepository $repository)
    {
        $this->repository = $repository;
    }
}

$container = new Container();

$container
    ->bind(IRepository::class)->to(CachedRepository::class)
    ->withConstructorArguments([
        "repository" => Instance::of(Repository::class)
    ])
    ->withProperties([
        "Property1" => Instance::of(Repository::class),
        "Property2" => Instance::of(Repository::class),
        "Property3" => Instance::of(Repository::class),
    ]);

$container->bind(IRepository::class)->toFactory(function () {
    $x = new CachedRepository($repo = new Repository);
    $x->Property1 = $repo;
    $x->Property2 = $repo;
    $x->Property3 = $repo;
    return $x;
});

function benchmark(int $n, callable $function) {
    $time = microtime(true);
    for ($i = 0; $i < $n; $i++) {
        call_user_func($function);
    }
    $time = (int)((microtime(true) - $time) * 1000);
    echo "Time: $time ms \n";
}

benchmark(10000, function () use ($container) {
    $x = new CachedRepository($repo = new Repository);
    $x->Connection1 = $repo;
    $x->Connection2 = $repo;
    $x->Connection3 = $repo;
    return $x;
});

benchmark(10000, function () use ($container) {
    return $container->resolve(IRepository::class);
});

// $instance = $container->resolve(IRepository::class);
// print_r($instance);
