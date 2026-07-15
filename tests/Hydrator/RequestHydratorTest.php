<?php

declare(strict_types=1);

namespace Atom\Tests\Hydrator;

use Atom\Http\Request;
use Atom\Http\UploadedFile;
use Atom\Hydrator\Attributes\DateFormat;
use Atom\Hydrator\Attributes\Dto;
use Atom\Hydrator\Attributes\FromBody;
use Atom\Hydrator\Attributes\FromFile;
use Atom\Hydrator\Attributes\FromHeader;
use Atom\Hydrator\Attributes\FromQuery;
use Atom\Hydrator\Attributes\FromRoute;
use Atom\Hydrator\Attributes\Raw;
use Atom\Hydrator\Exception\HydrationException;
use Atom\Hydrator\HydrationContext;
use Atom\Hydrator\HydrationTarget;
use Atom\Hydrator\RequestHydrator;
use PHPUnit\Framework\TestCase;

final class RequestHydratorTest extends TestCase
{
    public function testHydratesDtoPropertiesWithImplicitCoercion(): void
    {
        $hydrator = new RequestHydrator();
        $context = new HydrationContext(
            body: [
                "name" => "  Atom  ",
                "age" => "42",
                "enabled" => "yes",
                "score" => "10.5",
            ],
            query: ["invite" => ""]
        );

        $dto = $hydrator->hydrate(CreateUserDto::class, $context);

        $this->assertInstanceOf(CreateUserDto::class, $dto);
        $this->assertSame("Atom", $dto->name);
        $this->assertSame(42, $dto->age);
        $this->assertTrue($dto->enabled);
        $this->assertSame(10.5, $dto->score);
        $this->assertNull($dto->inviteCode);
    }

    public function testHydratesFromRouteHeaderAndFileSources(): void
    {
        $request = new Request(
            "POST",
            "/users/10",
            headers: ["X-Trace" => "abc"],
            files: [
                "avatar" => [
                    "name" => "avatar.png",
                    "tmp_name" => "/tmp/avatar",
                    "size" => 123,
                    "error" => UPLOAD_ERR_OK,
                    "type" => "image/png",
                ],
            ]
        );
        $context = HydrationContext::fromRequest($request, ["id" => "10"]);

        $dto = (new RequestHydrator())->hydrate(SourceDto::class, $context);

        $this->assertSame(10, $dto->id);
        $this->assertSame("abc", $dto->trace);
        $this->assertInstanceOf(UploadedFile::class, $dto->avatar);
        $this->assertSame("avatar.png", $dto->avatar->name);
    }

    public function testRawStringIsNotTrimmed(): void
    {
        $dto = (new RequestHydrator())->hydrate(RawDto::class, new HydrationContext(
            body: ["password" => "  secret  "]
        ));

        $this->assertSame("  secret  ", $dto->password);
    }

    public function testDateFormatTransformsBeforeDateCoercion(): void
    {
        $dto = (new RequestHydrator())->hydrate(DateDto::class, new HydrationContext(
            body: ["birthDate" => "24.06.2026"]
        ));

        $this->assertInstanceOf(\DateTimeImmutable::class, $dto->birthDate);
        $this->assertSame("2026-06-24", $dto->birthDate->format("Y-m-d"));
    }

    public function testRequiredMissingPropertyThrowsHydrationException(): void
    {
        $this->expectException(HydrationException::class);
        $this->expectExceptionMessage(CreateUserDto::class . "::name");

        (new RequestHydrator())->hydrate(CreateUserDto::class, new HydrationContext());
    }

    public function testInvalidScalarThrowsHydrationException(): void
    {
        $this->expectException(HydrationException::class);
        $this->expectExceptionMessage("expected int");

        (new RequestHydrator())->hydrate(CreateUserDto::class, new HydrationContext(
            body: ["name" => "Atom", "age" => "not-int"]
        ));
    }

    public function testHydrationTargetCanRepresentParameters(): void
    {
        $method = new \ReflectionMethod(ParameterTargetExample::class, "handle");
        $target = HydrationTarget::fromParameter($method->getParameters()[0]);

        $this->assertSame("id", $target->name);
        $this->assertSame("int", $target->typeName);
        $this->assertTrue($target->isBuiltin);
        $this->assertFalse($target->allowsNull);
        $this->assertSame("route", $target->source);
        $this->assertSame("userId", $target->sourceName);
    }

    public function testHydratesReadonlyConstructorDtoFromMultipleSources(): void
    {
        $request = new Request("POST", "/users", headers: ["X-Trace" => "trace-1"]);
        $dto = (new RequestHydrator())->hydrate(ReadonlyUserDto::class, new HydrationContext(
            body: ["full_name" => "  Atom  ", "enabled" => "yes"],
            query: ["page" => "3"],
            request: $request
        ));

        $this->assertSame("Atom", $dto->name);
        $this->assertSame(3, $dto->page);
        $this->assertTrue($dto->enabled);
        $this->assertSame("trace-1", $dto->trace);
    }

    public function testConstructorDefaultsApplyOnlyWhenInputIsMissing(): void
    {
        $hydrator = new RequestHydrator();
        $missing = $hydrator->hydrate(NullableDefaultDto::class, new HydrationContext());
        $explicitNull = $hydrator->hydrate(NullableDefaultDto::class, new HydrationContext(
            body: ["nickname" => null]
        ));

        $this->assertSame("anonymous", $missing->nickname);
        $this->assertNull($explicitNull->nickname);
    }

    public function testHydratesBackedAndUnitEnums(): void
    {
        $dto = (new RequestHydrator())->hydrate(EnumDto::class, new HydrationContext(body: [
            "role" => "admin",
            "priority" => "2",
            "state" => "Active",
        ]));

        $this->assertSame(HydratorRole::Admin, $dto->role);
        $this->assertSame(HydratorPriority::High, $dto->priority);
        $this->assertSame(HydratorState::Active, $dto->state);
    }

    public function testInvalidEnumValueThrowsHydrationException(): void
    {
        $this->expectException(HydrationException::class);
        $this->expectExceptionMessage(HydratorRole::class);

        (new RequestHydrator())->hydrate(EnumDto::class, new HydrationContext(body: [
            "role" => "owner",
            "priority" => "2",
            "state" => "Active",
        ]));
    }

    public function testHydratesNestedConstructorDtoAndRemainingWritableProperties(): void
    {
        $dto = (new RequestHydrator())->hydrate(ConstructorWithPropertyDto::class, new HydrationContext(body: [
            "id" => "42",
            "address" => ["city" => "  Sarajevo  ", "postalCode" => "71000"],
            "note" => "  ready  ",
        ]));

        $this->assertSame(42, $dto->id);
        $this->assertSame("Sarajevo", $dto->address->city);
        $this->assertSame(71000, $dto->address->postalCode);
        $this->assertSame("ready", $dto->note);
    }

    public function testConstructorParameterTransformerRunsBeforeCoercion(): void
    {
        $dto = (new RequestHydrator())->hydrate(ConstructorDateDto::class, new HydrationContext(
            body: ["created" => "15.07.2026"]
        ));

        $this->assertSame("2026-07-15", $dto->created->format("Y-m-d"));
    }

    public function testMissingRequiredConstructorParameterThrowsHydrationException(): void
    {
        $this->expectException(HydrationException::class);
        $this->expectExceptionMessage(ReadonlyUserDto::class . "::name");

        (new RequestHydrator())->hydrate(ReadonlyUserDto::class, new HydrationContext());
    }

}

#[Dto]
final class CreateUserDto
{
    #[FromBody]
    public string $name;

    #[FromBody]
    public int $age;

    #[FromBody]
    public bool $enabled = false;

    #[FromBody]
    public float $score = 0;

    #[FromQuery("invite")]
    public ?string $inviteCode = null;
}

#[Dto]
final class SourceDto
{
    #[FromRoute]
    public int $id;

    #[FromHeader("X-Trace")]
    public string $trace;

    #[FromFile]
    public UploadedFile $avatar;
}

#[Dto]
final class RawDto
{
    #[FromBody]
    #[Raw]
    public string $password;
}

#[Dto]
final class DateDto
{
    #[FromBody]
    #[DateFormat("d.m.Y")]
    public \DateTimeImmutable $birthDate;
}

final class ParameterTargetExample
{
    public function handle(#[FromRoute("userId")] int $id): void
    {
    }
}

#[Dto]
final readonly class ReadonlyUserDto
{
    public function __construct(
        #[FromBody("full_name")]
        public string $name,
        #[FromQuery]
        public int $page = 1,
        #[FromBody]
        public bool $enabled = false,
        #[FromHeader("X-Trace")]
        public ?string $trace = null
    ) {
    }
}

#[Dto]
final readonly class NullableDefaultDto
{
    public function __construct(public ?string $nickname = "anonymous")
    {
    }
}

enum HydratorRole: string
{
    case Member = "member";
    case Admin = "admin";
}

enum HydratorPriority: int
{
    case Normal = 1;
    case High = 2;
}

enum HydratorState
{
    case Active;
    case Disabled;
}

#[Dto]
final readonly class EnumDto
{
    public function __construct(
        public HydratorRole $role,
        public HydratorPriority $priority,
        public HydratorState $state
    ) {
    }
}

#[Dto]
final readonly class NestedAddressDto
{
    public function __construct(public string $city, public int $postalCode)
    {
    }
}

#[Dto]
final class ConstructorWithPropertyDto
{
    public string $note = "";

    public function __construct(public readonly int $id, public readonly NestedAddressDto $address)
    {
    }
}

#[Dto]
final readonly class ConstructorDateDto
{
    public function __construct(
        #[DateFormat("d.m.Y")]
        public \DateTimeImmutable $created
    ) {
    }
}
