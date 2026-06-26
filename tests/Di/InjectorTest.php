<?php

declare(strict_types=1);

namespace Atom\Tests\Di;

use Atom\Di\Attributes\Inject;
use Atom\Di\Bindings;
use Atom\Di\Exception\CircularDependencyException;
use Atom\Di\Exception\DependencyResolutionException;
use Atom\Di\InjectionContext;
use Atom\Di\Injector;
use Atom\Di\Provider;
use Atom\Di\ProviderLifetime;
use Atom\Di\TypeFactory;
use Atom\Di\TypeInfo;
use PHPUnit\Framework\TestCase;

final class InjectorTest extends TestCase
{
    public function testResolvesClassProviderWithConstructorDependencies(): void
    {
        $injector = Injector::create([
            Provider::type(DiDatabaseInterface::class, DiDatabase::class),
            Provider::type(DiRepository::class),
        ]);

        $repository = $injector->get(DiRepository::class);

        $this->assertInstanceOf(DiRepository::class, $repository);
        $this->assertInstanceOf(DiDatabase::class, $repository->database);
    }

    public function testFluentBindingsResolveTypeValueFactoryAndExistingProviders(): void
    {
        $bindings = Bindings::create();
        $bindings->bind(DiDatabaseInterface::class)->to(DiDatabase::class);
        $bindings->type(DiRepository::class);
        $bindings->value("prefix", "atom");
        $bindings->factory("name", fn(Injector $injector, InjectionContext $context): string =>
            $injector->get("prefix", $context) . "-framework");
        $bindings->existing("alias", "name");

        $injector = Injector::create($bindings);

        $this->assertInstanceOf(DiRepository::class, $injector->get(DiRepository::class));
        $this->assertSame("atom-framework", $injector->get("alias"));
    }

    public function testFluentBindingsConfigureLifetimes(): void
    {
        $bindings = Bindings::create();
        $bindings->bind(DiRequestState::class)->toSelf()->scoped();
        $injector = Injector::create($bindings);
        $context = new InjectionContext();

        $first = $injector->get(DiRequestState::class, $context);
        $second = $injector->get(DiRequestState::class, $context);

        $this->assertSame($first, $second);
    }

    public function testBindingsCanRegisterTypeFactories(): void
    {
        $bindings = Bindings::create()
            ->value(DiRequest::class, new DiRequest(["name" => "Atom"]))
            ->addTypeFactory(TypeFactory::match(
                fn(TypeInfo $type): bool => $type->hasAttribute(DiDto::class),
                function (string $className, Injector $injector, InjectionContext $context): object {
                    $request = $injector->get(DiRequest::class, $context);
                    return new $className($request->body["name"] ?? "");
                }
            ));

        $dto = Injector::create($bindings)->get(DiCreateUserDto::class);

        $this->assertSame("Atom", $dto->name);
    }

    public function testResolvesValueProviderByInjectAttribute(): void
    {
        $injector = Injector::create([
            Provider::value("config", ["name" => "Atom"]),
        ]);

        $service = $injector->instantiate(DiConfiguredService::class);

        $this->assertSame("Atom", $service->config["name"]);
    }

    public function testChildInjectorOverridesParentProvider(): void
    {
        $parent = Injector::create([
            Provider::value("name", "parent"),
        ]);

        $child = $parent->createChild([
            Provider::value("name", "child"),
        ]);

        $this->assertSame("parent", $parent->get("name"));
        $this->assertSame("child", $child->get("name"));
    }

    public function testScopedProviderIsReusedInsideSameContext(): void
    {
        $injector = Injector::create([
            Provider::type(DiRequestState::class)->scoped(),
            Provider::type(DiController::class),
        ]);
        $context = new InjectionContext();

        $controller = $injector->get(DiController::class, $context);
        $methodState = $injector->invoke([$controller, "handle"], context: $context);

        $this->assertSame($controller->state, $methodState);
    }

    public function testContextInstanceCanBeResolvedWithoutProvider(): void
    {
        $injector = Injector::create();
        $context = new InjectionContext();
        $request = new DiRequest(["name" => "Context"]);
        $context->set(DiRequest::class, $request);

        $this->assertSame($request, $injector->get(DiRequest::class, $context));
        $this->assertSame($request, $injector->invoke(
            fn(DiRequest $request): DiRequest => $request,
            context: $context
        ));
    }

    public function testScopedProviderCreatesNewInstanceForDifferentContext(): void
    {
        $injector = Injector::create([
            Provider::type(DiRequestState::class)->scoped(),
        ]);

        $first = $injector->get(DiRequestState::class, new InjectionContext());
        $second = $injector->get(DiRequestState::class, new InjectionContext());

        $this->assertNotSame($first, $second);
    }

    public function testSingletonProviderIsSharedAcrossContexts(): void
    {
        $injector = Injector::create([
            Provider::type(DiRequestState::class, lifetime: ProviderLifetime::Singleton),
        ]);

        $first = $injector->get(DiRequestState::class, new InjectionContext());
        $second = $injector->get(DiRequestState::class, new InjectionContext());

        $this->assertSame($first, $second);
    }

    public function testFactoryProviderReceivesInjectorAndContext(): void
    {
        $injector = Injector::create([
            Provider::value("prefix", "atom"),
            Provider::factory("name", function (Injector $injector, InjectionContext $context): string {
                return $injector->get("prefix", $context) . "-framework";
            }),
        ]);

        $this->assertSame("atom-framework", $injector->get("name"));
    }

    public function testExistingProviderAliasesAnotherToken(): void
    {
        $injector = Injector::create([
            Provider::value("primary", "value"),
            Provider::existing("alias", "primary"),
        ]);

        $this->assertSame("value", $injector->get("alias"));
    }

    public function testTypeFactoryCreatesMatchingUnregisteredType(): void
    {
        $injector = Injector::create([
            Provider::value(DiRequest::class, new DiRequest(["name" => "Atom"])),
        ]);
        $injector->addTypeFactory(TypeFactory::match(
            fn(TypeInfo $type): bool => $type->hasAttribute(DiDto::class),
            function (string $className, Injector $injector, InjectionContext $context): object {
                $request = $injector->get(DiRequest::class, $context);
                return new $className($request->body["name"] ?? "");
            }
        ));

        $dto = $injector->get(DiCreateUserDto::class);

        $this->assertInstanceOf(DiCreateUserDto::class, $dto);
        $this->assertSame("Atom", $dto->name);
    }

    public function testExplicitProviderWinsOverTypeFactory(): void
    {
        $injector = Injector::create([
            Provider::value(DiCreateUserDto::class, new DiCreateUserDto("Explicit")),
            Provider::value(DiRequest::class, new DiRequest(["name" => "Factory"])),
        ]);
        $injector->addTypeFactory(TypeFactory::match(
            fn(TypeInfo $type): bool => $type->hasAttribute(DiDto::class),
            function (string $className, Injector $injector, InjectionContext $context): object {
                $request = $injector->get(DiRequest::class, $context);
                return new $className($request->body["name"] ?? "");
            }
        ));

        $dto = $injector->get(DiCreateUserDto::class);

        $this->assertSame("Explicit", $dto->name);
    }

    public function testTypeFactoriesAreInheritedByChildInjectors(): void
    {
        $parent = Injector::create();
        $parent->addTypeFactory(TypeFactory::match(
            fn(TypeInfo $type): bool => $type->hasAttribute(DiDto::class),
            function (string $className, Injector $injector, InjectionContext $context): object {
                $request = $injector->get(DiRequest::class, $context);
                return new $className($request->body["name"] ?? "");
            }
        ));
        $child = $parent->createChild([
            Provider::value(DiRequest::class, new DiRequest(["name" => "Child"])),
        ]);

        $dto = $child->get(DiCreateUserDto::class);

        $this->assertSame("Child", $dto->name);
    }

    public function testUnregisteredNormalClassStillAutowiresWithoutTypeFactory(): void
    {
        $injector = Injector::create([
            Provider::type(DiDatabaseInterface::class, DiDatabase::class),
        ]);

        $repository = $injector->get(DiRepository::class);

        $this->assertInstanceOf(DiRepository::class, $repository);
    }

    public function testDetectsCircularDependencies(): void
    {
        $injector = Injector::create();

        $this->expectException(CircularDependencyException::class);
        $this->expectExceptionMessage(DiCircularA::class . " -> " . DiCircularB::class . " -> " . DiCircularA::class);

        $injector->get(DiCircularA::class);
    }

    public function testMissingProviderMessageIncludesParameterAndResolutionPath(): void
    {
        $injector = Injector::create();

        try {
            $injector->get(DiRepository::class);
            $this->fail("Expected dependency resolution to fail.");
        } catch (DependencyResolutionException $exception) {
            $this->assertStringContainsString("DiRepository::__construct", $exception->getMessage());
            $this->assertStringContainsString("database", $exception->getMessage());
            $this->assertStringContainsString(DiDatabaseInterface::class, $exception->getMessage());
            $this->assertStringContainsString("Resolution path", $exception->getMessage());
        }
    }

    public function testNullableTypedDependencyResolvesToNullWhenProviderIsMissing(): void
    {
        $injector = Injector::create();

        $service = $injector->get(DiOptionalService::class);

        $this->assertNull($service->database);
    }
}

interface DiDatabaseInterface
{
}

final class DiDatabase implements DiDatabaseInterface
{
}

final readonly class DiRepository
{
    public function __construct(public DiDatabaseInterface $database)
    {
    }
}

final readonly class DiOptionalService
{
    public function __construct(public ?DiDatabaseInterface $database)
    {
    }
}

final readonly class DiCircularA
{
    public function __construct(public DiCircularB $dependency)
    {
    }
}

final readonly class DiCircularB
{
    public function __construct(public DiCircularA $dependency)
    {
    }
}

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class DiDto
{
}

final readonly class DiRequest
{
    public function __construct(public array $body)
    {
    }
}

#[DiDto]
final readonly class DiCreateUserDto
{
    public function __construct(public string $name)
    {
    }
}

final readonly class DiConfiguredService
{
    public function __construct(
        #[Inject("config")]
        public array $config
    ) {
    }
}

final class DiRequestState
{
}

final readonly class DiController
{
    public function __construct(public DiRequestState $state)
    {
    }

    public function handle(DiRequestState $state): DiRequestState
    {
        return $state;
    }
}
