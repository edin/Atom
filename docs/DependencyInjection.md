# Dependency Injection Container

[Atom Framework](Index.md)

## Component registration

Component registration class synopsis

```php
final class ComponentRegistration {
    public function to(string $targetType): self;
    public function toSelf(): self;
    public function toInstance($instance): self;
    public function toFactory(callable $factory): self;
    public function toTypeFactory(object $factory): self;
    public function withConstructorArguments(array $constructorArguments): self;
    public function withName(string $name): self;
    public function asShared(): self;
    public function withProperties(array $properties): self;
    public function getResolver(): IDependencyResolver;
}
```

Container class synopsis

```php
final class Container {
    public function getDependencyResolver(): DependencyResolver;
    public function getDefinition($name): ComponentRegistration;
    public function getRegistry();
    public function createType(string $targetType): object;
    public function getResolver($typeName): IDependencyResolver;
    public function alias(string $alias, string $target);
    public function bind(string $typeName): ComponentRegistration;
    public function setInstance(string $name, $value);
    public function resolveType(string $typeName);
    public function resolve(string $typeName, $params = []);
    public function resolveWithProperties(string $typeName, $params = []);
    public function resolveMethodParameters(
        ?\ReflectionFunctionAbstract $method, 
        array $params = [], 
        ?ResolutionContext $context = null
    ): array
    public function resolveInContext(
        ResolutionContext $context, 
        string $typeName, 
        array $params = []
    );
    public function has($name): bool;
    public function get($name);
    public function set($name, $definition);
}
```

Dependency resolvers 

```php
interface IDependencyResolver
{
    public function resolve(ResolutionContext $context, array $params = []);
    public function getDependencies(): array;
    public function resolveType(): ?string;
    public function getRegistration(): ComponentRegistration;
}

final class ClassResolver implements IDependencyResolver {}
final class InstanceResolver implements IDependencyResolver {}
final class TypeFactoryResolver implements IDependencyResolver {}
final class FactoryResolver implements IDependencyResolver {}
```

Delayed object construction.

```php
final class ClassTypeFactory {}
```

## Examples

* Registering and resolving type

```php
$container = new Container();

//Bind IRepository interface to Repository class
$container->bind(IUserRepository::class)->to(UserRepository::class);

//Resolve instance
$repository = $conainer->resolve(IRepository::class);
```


* Registering instance

```php
$container->bind(IRepository::class)
          ->toInstance(new Repository());
```

* Registering factory

```php
$container->bind(IRepository::class)
          ->toFactory(function() {
              return new Repository();
          });
```

* Registering shared class 

```php
$container->bind(UserRepository::class)
          ->toSelf()
          ->asShared()
          ->withName("userRepository");
```

* Providing properties and constructor arguments

```php
$container->bind(Connection::class)
          ->toSelf()
          ->asShared()
          ->withProperties([
              'property' => 'value'
          ])
          ->withConstructorArguments([
              'host' => '',
              'database' => '',
              'username' => '',
              'password' => ''
          ]);
```

* Providing component reference using Instance::of

```php
$container->bind(SomeType::class)
          ->toSelf()
          ->withProperties([
              'property' => Instance::of('componentName')
          ]);
```