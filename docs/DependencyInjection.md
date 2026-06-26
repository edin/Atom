# Dependency Injection

[Atom Framework](Index.md)

Atom uses a small constructor-injection container under `Atom\Di`.

The main pieces are:

- `Bindings` for registering providers
- `Injector` for resolving and invoking services
- `InjectionContext` for scoped services within a request/action
- `ServiceProviderInterface` and `ServiceProviderRegistry` for application-level registration

## Basic Usage

```php
use Atom\Di\Bindings;
use Atom\Di\Injector;

$bindings = Bindings::create();

$bindings->bind(UserRepositoryInterface::class)
    ->to(DatabaseUserRepository::class);

$bindings->bind(UserService::class)
    ->toSelf();

$injector = Injector::create($bindings);

$service = $injector->get(UserService::class);
```

If a concrete class is not registered, the injector still tries to autowire it by resolving constructor dependencies.

## Bindings

Register a type:

```php
$bindings->bind(UserRepositoryInterface::class)
    ->to(DatabaseUserRepository::class);

$bindings->bind(UserService::class)
    ->toSelf();
```

Short form:

```php
$bindings->type(UserService::class);
$bindings->type(UserRepositoryInterface::class, DatabaseUserRepository::class);
```

Register a value:

```php
$bindings->value("config", [
    "name" => "Atom",
]);
```

Register a factory:

```php
use Atom\Di\InjectionContext;
use Atom\Di\Injector;

$bindings->factory("app.name", function (Injector $injector, InjectionContext $context): string {
    $config = $injector->get("config", $context);

    return $config["name"];
});
```

Register an alias to an existing token:

```php
$bindings->existing("primaryDatabase", DatabaseConnection::class);
```

## Lifetimes

Providers are transient by default.

```php
$bindings->bind(RequestState::class)
    ->toSelf()
    ->transient();
```

Singleton services are shared for the lifetime of the injector:

```php
$bindings->bind(DatabaseConnection::class)
    ->toSelf()
    ->singleton();
```

Scoped services are shared inside one `InjectionContext`:

```php
use Atom\Di\InjectionContext;

$bindings->bind(RequestState::class)
    ->toSelf()
    ->scoped();

$injector = Injector::create($bindings);
$context = new InjectionContext();

$first = $injector->get(RequestState::class, $context);
$second = $injector->get(RequestState::class, $context);

// $first === $second
```

A different context receives a different scoped instance.

## Constructor Injection

Constructor parameters are resolved by type:

```php
final readonly class UserController
{
    public function __construct(private UserRepositoryInterface $users)
    {
    }
}
```

Manual parameters can be supplied when instantiating a class:

```php
$controller = $injector->instantiate(UserController::class, [
    "id" => 10,
]);
```

## Invoke Methods and Closures

The injector can call closures and methods while resolving typed parameters:

```php
$result = $injector->invoke(
    [$controller, "show"],
    ["id" => 10],
    $context
);
```

Explicit parameters win by name. Missing class-typed parameters are resolved from the container.

## Named Tokens

Use `#[Inject]` when a parameter should come from a string token instead of a class name:

```php
use Atom\Di\Attributes\Inject;

final readonly class ConfiguredService
{
    public function __construct(
        #[Inject("config")]
        public array $config
    ) {
    }
}
```

## Type Factories

Type factories create unregistered classes that match a predicate. This is useful for framework-level patterns such as DTOs.

```php
use Atom\Di\InjectionContext;
use Atom\Di\Injector;
use Atom\Di\TypeFactory;
use Atom\Di\TypeInfo;

$bindings->addTypeFactory(TypeFactory::match(
    fn(TypeInfo $type): bool => $type->hasAttribute(Dto::class),
    function (string $className, Injector $injector, InjectionContext $context): object {
        $request = $injector->get(Request::class, $context);

        return new $className($request->post()->string("name"));
    }
));
```

Explicit providers always win over type factories.

## Child Injectors

Child injectors inherit parent providers and type factories, but can override tokens locally:

```php
$parent = Injector::create([
    Provider::value("name", "parent"),
]);

$child = $parent->createChild([
    Provider::value("name", "child"),
]);

$parent->get("name"); // parent
$child->get("name");  // child
```

## Service Providers

Service providers group bindings:

```php
use Atom\Di\Bindings;
use Atom\Di\ServiceProviderInterface;

final class BlogServices implements ServiceProviderInterface
{
    public function register(Bindings $bindings): void
    {
        $bindings->bind(PostRepository::class)
            ->toSelf()
            ->singleton();
    }
}
```

Register providers in the application:

```php
use Atom\Di\ServiceProviderRegistry;

final class Application extends \Atom\Application
{
    protected function services(ServiceProviderRegistry $providers): void
    {
        $providers->add(BlogServices::class);
    }
}
```

`ServiceProviderRegistry` accepts provider classes or provider instances.

## Errors

The injector throws `DependencyResolutionException` when it cannot resolve a dependency, and `CircularDependencyException` when it detects a cycle.

