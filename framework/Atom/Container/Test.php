<?php

include "../../../vendor/autoload.php";

use Atom\Container\Container;
use Atom\Container\Instance;

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
    public function __construct(IRepository $repository, string $cool)
    {
        $this->repository = $repository;
        $this->cool = $cool;
    }
}

$container = new Container();

$container
    ->bind(IRepository::class)->to(CachedRepository::class)
    ->withConstructorArguments([
        "repository" => Instance::of(Repository::class),
        "cool" => "Hello World"
    ])
    ->withProperties([
        "Property1" => Instance::of(Repository::class),
        "Property2" => Instance::of(Repository::class),
        "Property3" => Instance::of(Repository::class),
    ])
    ->asShared()
    ;


// $container->bind(IRepository::class)->withName("Cool")->toFactory(function () {
//     $x = new CachedRepository($repo = new Repository);
//     $x->Property1 = $repo;
//     $x->Property2 = $repo;
//     $x->Property3 = $repo;
//     return $x;
// });

$result = $container->resolve(IRepository::class);

print_r($result);



function benchmark(int $n, callable $function)
{
    $time = microtime(true);
    for ($i = 0; $i < $n; $i++) {
        call_user_func($function);
    }
    $time = (int)((microtime(true) - $time) * 1000);
    echo "Time: $time ms \n";
}

benchmark(100000, function () use ($container) {
    return $container->resolve(IRepository::class);
});


benchmark(100000, function () {
    return new Repository();
});
